<?php

function sync(&$core, $params)
{
	header('Content-Type: text/plain; charset=utf-8');

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// level 0 having Write access mean admin

	$total = $core->Runbooks->sync();

	echo '{"code": 0, "message": "'.json_escape('Runbooks loaded: '.$total).'"}';
}
