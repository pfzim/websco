<?php

function runbooks_sync(&$core, $params, $post_data)
{
	$type = intval(@$params[1]);
	assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// level 0 having Write access mean admin

	log_db('Sync started', '{type=' . $type . '}', 0);

	$total = $core->Runbooks->sync($type);

	echo '{"code": 0, "message": "'.json_escape('Runbooks loaded: '.$total).'"}';
}
