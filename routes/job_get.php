<?php

function job_get(&$core, $params, $post_data)
{
	$id = @$params[1];

	$runbook = $core->Runbooks->get_runbook_by_job_id($id);

	assert_permission_ajax($job['folder_id'], RB_ACCESS_EXECUTE);

	$job = $core->Runbooks->get_job($id);

	if(!$job)
	{
		$core->error('Job ID '.$id.' not found!');
		return FALSE;
	}

	echo json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
