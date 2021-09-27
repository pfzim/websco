<?php

function sync_jobs(&$core, $params)
{
	$guid = @$params[1];

	header('Content-Type: text/plain; charset=utf-8');

	$runbook_guid = '';
	if(!empty($guid))
	{
		$runbook_guid = $guid;
	}

	if(empty($runbook_guid))
	{
		assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// non-priveleged users cannot sync all jobs at once
	}

	$total = $core->Runbooks->sync_jobs($runbook_guid);

	echo '{"code": 0, "message": "'.json_escape('Jobs loaded: '.$total).'"}';
}
