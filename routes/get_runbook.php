<?php

function get_runbook(&$core, $params)
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
		'action' => 'start_runbook',
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
				$job_params = $core->Runbooks->get_job_first_instance_input_params($job[0]['guid']);

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

	echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
