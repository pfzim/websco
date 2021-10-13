<?php

function job_get(&$core, $params, $post_data)
{
	$guid = @$params[1];
	
	$job = $core->Runbooks->get_job($guid);

	assert_permission_ajax($job['folder_id'], RB_ACCESS_EXECUTE);

	echo json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
