<?php

function runbook_get(&$core, $params, $post_data)
{
	$guid = @$params[1];
	$job_id = @$params[2];
	
	$runbook = $core->Runbooks->get_runbook($guid);
	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	$result_json = array(
		'code' => 0,
		'message' => '',
		'title' => $runbook['name'],
		'description' => $runbook['description'],
		'action' => 'runbook_start',
		'fields' => array(
			/*
			array(
				'type' => 'hidden',
				'name' => 'action',
				'value' => 'start_runbook'
			),
			*/
			array(
				'type' => 'hidden',
				'name' => 'guid',
				'value' => $runbook['guid']
			)
		)
	);

	$params = $core->Runbooks->get_runbook_params($runbook['guid']);

	$job_params = NULL;

	if(!empty($job_id))
	{
		if(!$core->db->select_assoc_ex($job_params, rpv('SELECT jp.`guid`, jp.`value` FROM @runbooks_jobs_params AS jp WHERE jp.`pid` = #', $job_id)))
		{
			if($core->db->select_assoc_ex($job, rpv('SELECT j.`guid` FROM @runbooks_jobs AS j WHERE j.`id` = #', $job_id)))
			{
				$job_params = $core->Runbooks->retrieve_job_first_instance_input_params($job[0]['guid']);

				foreach($job_params as $param)
				{
					$core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $param['guid'], $param['value']));
				}
			}
		}
	}

	foreach($params as &$param)
	{
		 $field = array(
			'type' => $param['type'],
			'name' => 'param['.$param['guid'].']',
			'title' => $param['name'],
			'value' => ''
		);

		if(($param['type'] == 'list') || ($param['type'] == 'flags'))
		{
			$field['list'] = $param['list'];
		}
		elseif(($param['type'] == 'samaccountname'))
		{
			$field['autocomplete'] = 'complete_account';
		}
		elseif(($param['type'] == 'computer'))
		{
			$field['autocomplete'] = 'complete_computer';
		}
		elseif(($param['type'] == 'mail'))
		{
			$field['autocomplete'] = 'complete_mail';
		}
		elseif(($param['type'] == 'upload'))
		{
			$field['name'] = 'param_'.$param['guid'];
			$field['maxsize'] = 2000;
		}
		elseif(($param['type'] == 'who'))
		{
			continue;
		}

		if($job_params)
		{
			foreach($job_params as &$row)
			{
				if($row['guid'] == $param['guid'])
				{
					$field['value'] = $row['value'];
					break;
				}
			}
		}

		$result_json['fields'][] = $field;
	}

	$servers = $core->Runbooks->get_servers();

	$servers_list = array();
	foreach($servers as $server)
	{
		$servers_list[] = $server['name'];
	}

	$result_json['fields'][] = array(
		'type' => 'spoiler',
		'title' => LL('AdvancedSettings'),
		'fields' => array(
			array(
				'type' => 'flags',
				'name' => 'servers',
				'title' => LL('SelectRunbookServers'),
				'value' => '',
				'list' => $servers_list,
				'values' => $servers_list
			)
		)
	);

	echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
