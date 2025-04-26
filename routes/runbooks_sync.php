<?php

function runbooks_sync(&$core, $params, $post_data)
{
	assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// level 0 having Write access mean admin

	log_db('Sync started', '', 0);

	//$total = $core->Orchestartor->sync();
	$total = $core->Orchestrator2022->sync();

	echo '{"code": 0, "message": "'.json_escape('Runbooks loaded: '.$total).'"}';
}
