<?php

function playbook_start(&$core, $params, $post_data)
{
	$playbook = $core->AnsibleAWX->get_playbook($post_data['id']);

	if(($playbook['flags'] & RBF_TYPE_ANSIBLE) == 0)
	{
		$core->error('ERROR: Playbook with ID ' . $playbook['id'] . ' is a custom type!');
		return NULL;
	}

	assert_permission_ajax($playbook['folder_id'], RB_ACCESS_EXECUTE);

	$result_json = array(
		'code' => 0,
		'message' => '',
		'errors' => array()
	);

	$params = array(
	);

	$playbook_params = $core->AnsibleAWX->get_playbook_params($playbook['id']);

	//log_file(json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

	foreach($playbook_params as &$param)
	{
		$value = '';
		//$params[$param['guid']] = $value;

		if($param['type'] == 'who')
		{
			$params[] = array(
				'guid' => $param['guid'],
				'name' => $param['name'],
				'value' => $core->UserAuth->get_login()
			);
			continue;
		}
		elseif($param['type'] == 'flags')
		{
			$value = array();
			if(isset($post_data['param'][$param['guid']]))
			{
				foreach($post_data['param'][$param['guid']] as $bit => $bit_value)
				{
					$value[] = $bit_value;
				}
			}

			if($param['required'] && (count($value) == 0))
			{
				$result_json['code'] = 1;
				$result_json['errors'][] = array('name' => 'param['.$param['guid'].'][0]', 'msg' => LL('FlagMustBeSelected'));
			}
			else
			{
				$params[] = array(
					'guid' => $param['guid'],
					'name' => $param['name'],
					'value' => $value
				);
			}

			//log_file('Value: '.strval($flags));
			continue;
		}
		elseif($param['type'] == 'upload')
		{
			if(isset($_FILES['param_'.$param['guid']]['tmp_name']) && file_exists($_FILES['param_'.$param['guid']]['tmp_name']))
			{
				if(filesize($_FILES['param_'.$param['guid']]['tmp_name']) > $param['max_size'])
				{
					$result_json['code'] = 1;
					$result_json['errors'][] = array('name' => 'param_'.$param['guid'], 'msg' => LL('FileTooLarge').' (max '.$param['max_size'].' bytes)');
					continue;
				}

				$value = base64_encode(file_get_contents($_FILES['param_'.$param['guid']]['tmp_name']));
			}
			elseif($param['required'])
			{
				$result_json['code'] = 1;
				$result_json['errors'][] = array('name' => 'param_'.$param['guid'], 'msg' => LL('ThisFieldRequired'));
				continue;
			}
		}
		elseif(isset($post_data['param'][$param['guid']]))
		{
			$value = trim($post_data['param'][$param['guid']]);
		}

		if($param['required'] && empty($value))
		{
			$result_json['code'] = 1;
			$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('ThisFieldRequired'));
			continue;
		}
		elseif($param['type'] == 'date')
		{
			if(!empty($value))
			{
				list($nd, $nm, $ny) = explode('.', $value, 3);

				if(!datecheck($nd, $nm, $ny))
				{
					$result_json['code'] = 1;
					$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('IncorrectDate').' DD.MM.YYYY');
					continue;
				}
			}
		}
		elseif($param['type'] == 'list')
		{
			if(!in_array($value, $param['list']))
			{
				$result_json['code'] = 1;
				$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('ValueNotFromList').' ('.implode(', ', $param['list']).')');
				continue;
			}
		}
		elseif($param['type'] == 'integer')
		{
			if(!empty($value) && !preg_match('/^\d+$/i', $value))
			{
				$result_json['code'] = 1;
				$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('OnlyNumbers'));
				continue;
			}

			$params[] = array(
				'guid' => $param['guid'],
				'name' => $param['name'],
				'value' => intval($value)
			);
			continue;
		}

		$params[] = array(
			'guid' => $param['guid'],
			'name' => $param['name'],
			'value' => $value
		);
	}

	if($result_json['code'])
	{
		$result_json['message'] = LL('NotAllFilled');
		echo json_encode($result_json);
		return;
	}

	// $servers_list = '';
	// if(!empty($post_data['servers']))
	// {
		// $delimeter = '';
		// foreach($post_data['servers'] as $value)
		// {
			// $servers_list .= $delimeter.$value;
			// $delimeter = ',';
		// }
	// }

	$servers_list = NULL;
	if(!empty($post_data['servers']))
	{
		$servers_list = $post_data['servers'];
	}

	//echo '{"code": 0, "guid": "0062978a-518a-4ba9-9361-4eb88ea3e0b0", "message": "Debug placeholder save_uform. Remove this line later'.$runbook['guid'].json_encode($runbook_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'"}'; exit;

	// echo json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	// echo json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	// return;

	log_db('Run: '.$playbook['name'], json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0);

	$job_guid = $core->AnsibleAWX->start_playbook($playbook['guid'], $params);

	if($job_guid !== FALSE)
	{
		if($core->db->put(rpv('INSERT INTO @runbooks_jobs (`date`, `pid`, `guid`, `uid`, `flags`) VALUES (NOW(), #, !, #, 0)', $playbook['id'], $job_guid, $core->UserAuth->get_id())))
		{
			$job_id = $core->db->last_id();

			foreach($params as &$param)
			{
				$value = $param['value'];
				if(strlen((string) $value) > 4096)
				{
					$value = substr((string) $value, 0, 4093).'...';
				}
				$core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $param['guid'], is_array($value) ? implode(', ', $value) : $value));
			}
		}

		log_db('Job created: '.$playbook['name'], (string) $job_guid, 0);
		echo '{"code": 0, "id": '.intval($job_id).', "guid": "'.json_escape($job_guid).'", "message": "'.LL('CreatedJob').' ID: '.json_escape($job_guid).'"}';
	}
	else
	{
		echo '{"code": 1, "message": "Failed: Playbook not started"}';
	}
}
