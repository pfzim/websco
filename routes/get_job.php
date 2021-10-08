<?php

function get_job(&$core, $params, $post_data)
{
	$guid = @$params[1];

	echo json_encode($core->Runbooks->get_job($guid), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
