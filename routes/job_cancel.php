<?php

function job_cancel(&$core, $params, $post_data)
{
	$runbook = $core->Runbooks->get_runbook_by_job_guid($post_data['id']);

	if($runbook['flags'] & RBF_TYPE_CUSTOM)
	{
		$core->error('ERROR: Runbook with ID '.$filepath.' is a custom type!');
		return NULL;
	}

	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	if($core->Runbooks->job_cancel($post_data['id']))
	{
		log_db('Job canceled: '.$post_data['id'], $post_data['id'], 0);
		echo '{"code": 0, "guid": "'.json_escape($job_guid).'", "message": "'.LL('JobCanceled').' ID: '.json_escape($post_data['id']).'"}';
	}
	else
	{
		echo '{"code": 1, "message": "Failed: Failed cancel job"}';
	}
}
