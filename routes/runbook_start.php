<?php

function runbook_start(&$core, $params, $post_data)
{
	$runbook = $core->Runbooks->get_runbook_by_id($post_data['id']);

	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	log_db('Run: '.$runbook['name'], json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0);

	$job_id = $core->Runbooks->start_runbook($post_data, $result_json);

	if($job_id !== FALSE)
	{
		log_db('Job created: '.$runbook['name'], $job_id, 0);
		echo '{"code": 0, "id": '.intval($job_id).', "guid": "'.json_escape($job_id).'", "message": "'.LL('CreatedJob').' ID: '.json_escape($job_id).'"}';
	}
	else
	{
		echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}
}
