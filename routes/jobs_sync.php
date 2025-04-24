<?php

function jobs_sync(&$core, $params, $post_data)
{
	$id = @$params[1];

	$runbook_guid = '';
	if(!$id)
	{
		assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// non-priveleged users cannot sync all jobs at once
	}
	else
	{
		$runbook = $core->Runbooks->get_runbook_by_id($id);

		if((intval($runbook['flags']) & RBF_TYPE_SCO) == 0)
		{
			echo '{"code": 1, "message": "'.json_escape('ERROR: Runbook with ID '.$id.' is a custom type!').'"}';
			return;
		}

		assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);
		$runbook_guid = $runbook['guid'];
	}

	log_db('Sync jobs started', $runbook_guid, 0);

	$total = $core->Runbooks->sync_jobs($runbook_guid);

	echo '{"code": 0, "message": "'.json_escape('Jobs loaded: '.$total).'"}';
}
