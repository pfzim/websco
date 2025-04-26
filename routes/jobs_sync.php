<?php

function jobs_sync(&$core, $params, $post_data)
{
	$id = @$params[1];

	$runbook = $core->Runbooks->get_runbook_by_id($id);

	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	log_db('Sync jobs started', $id, 0);

	$total = $core->Runbooks->sync_jobs($id);

	echo '{"code": 0, "message": "'.json_escape('Jobs loaded: '.$total).'"}';
}
