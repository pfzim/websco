<?php

function jobs_sync(&$core, $params, $post_data)
{
	$guid = @$params[1];

	$runbook_guid = '';
	if(!empty($guid))
	{
		$runbook_guid = $guid;
	}

	if(empty($runbook_guid))
	{
		assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// non-priveleged users cannot sync all jobs at once
	}

	log_db('Sync jobs started', $runbook_guid, 0);
	
	$total = $core->Runbooks->sync_jobs($runbook_guid);

	echo '{"code": 0, "message": "'.json_escape('Jobs loaded: '.$total).'"}';
}
