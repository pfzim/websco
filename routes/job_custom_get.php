<?php

function job_custom_get(&$core, $params, $post_data)
{
	$id = @$params[1];
	
	$job = $core->Runbooks->get_custom_job($id);

	assert_permission_ajax($job['folder_id'], RB_ACCESS_EXECUTE);

	echo json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
