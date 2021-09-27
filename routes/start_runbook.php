<?php

function start_runbook(&$core, $params)
{
	$runbook = $core->Runbooks->get_runbook($_POST['guid']);
	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	$result_json = array(
		'code' => 0,
		'message' => '',
		'errors' => array()
	);

	$params = array(
	);

	$runbook_params = $core->Runbooks->get_runbook_params($runbook['guid']);

	//log_file(json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

	foreach($runbook_params as &$param)
	{
		$value = '';

		if($param['type'] == 'flags')
		{
			$flags = 0;
			if(isset($_POST['param'][$param['guid']]))
			{
				foreach($_POST['param'][$param['guid']] as $bit => $bit_value)
				{
					if(intval($bit_value))
					{
						$flags |= 0x01 << intval($bit);
					}
				}
			}

			if($param['required'] && ($flags == 0))
			{
				$result_json['code'] = 1;
				$result_json['errors'][] = array('name' => 'param['.$param['guid'].'][0]', 'msg' => LL('FlagMustBeSelected'));
			}
			else
			{
				$params[$param['guid']] = strval($flags);
			}

			//log_file('Value: '.strval($flags));
			continue;
		}
		elseif(isset($_POST['param'][$param['guid']]))
		{
			$value = trim($_POST['param'][$param['guid']]);
		}

		if($param['required'] && $value == '')
		{
			$result_json['code'] = 1;
			$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('ThisFieldRequired'));
			continue;
		}
		elseif($param['type'] == 'date')
		{
			list($nd, $nm, $ny) = explode('.', $value, 3);

			if(!datecheck($nd, $nm, $ny))
			{
				$result_json['code'] = 1;
				$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('IncorrectDate').' DD.MM.YYYY');
				continue;
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
			if(!preg_match('/^\d+$/i', $value))
			{
				$result_json['code'] = 1;
				$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('OnlyNumbers'));
				continue;
			}
		}

		$params[$param['guid']] = $value;
	}

	if($result_json['code'])
	{
		$result_json['message'] = LL('NotAllFilled');
		echo json_encode($result_json);
		exit;
	}

	//echo '{"code": 0, "guid": "0062978a-518a-4ba9-9361-4eb88ea3e0b0", "message": "Debug placeholder save_uform. Remove this line later'.$runbook['guid'].json_encode($runbook_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'"}'; exit;

	log_db('Run: '.$runbook['name'], json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0);

	$job_guid = $core->Runbooks->start_runbook($runbook['guid'], $params);

	if($job_guid !== FALSE)
	{
		if($core->db->put(rpv('INSERT INTO @runbooks_jobs (`date`, `pid`, `guid`, `uid`, `flags`) VALUES (NOW(), #, !, #, 0)', $runbook['id'], $job_guid, $core->UserAuth->get_id())))
		{
			$job_id = $core->db->last_id();

			foreach($params as $key => $value)
			{
				$core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $key, $value));
			}
		}

		log_db('Job created: '.$runbook['name'], $job_guid, 0);
		echo '{"code": 0, "guid": "'.json_escape($job_guid).'", "message": "'.LL('CreatedJob').' ID: '.json_escape($job_guid).'"}';
	}
	else
	{
		echo '{"code": 1, "message": "Failed: Runbook not started"}';
	}
}
