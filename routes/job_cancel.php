<?php

function job_cancel(&$core, $params, $post_data)
{
	$runbook = $core->Runbooks->get_runbook_by_job_id($post_data['id']);
	
	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	if($core->Runbooks->job_cancel($post_data['id']))
	{
		log_db('Job canceled: '.$post_data['id'], $post_data['id'], 0);
		echo '{"code": 0, "id": "'.json_escape($post_data['id']).'", "message": "'.LL('JobCanceled').' ID: '.json_escape($post_data['id']).'"}';
	}
	else
	{
		echo '{"code": 1, "message": "Failed: Failed cancel job"}';
	}
}
