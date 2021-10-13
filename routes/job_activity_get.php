<?php

function job_activity_get(&$core, $params, $post_data)
{
	$guid = @$params[1];

	echo json_encode($core->Runbooks->get_activity($guid), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
