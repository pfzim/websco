<?php

function job_activity_get(&$core, $params, $post_data)
{
	$job_id = @$params[1];
	$activity_instance_guid = @$params[2];

	echo json_encode($core->Runbooks->get_activity($job_id, $activity_instance_guid), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
