<?php

function runbook_get(&$core, $params, $post_data)
{
	$id = @$params[1];
	$job_id = @$params[2];
	
	$runbook = $core->Runbooks->get_runbook_by_id($id);

	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	$result_json = $core->Runbooks->get_runbook_form($id, $job_id);

	echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
