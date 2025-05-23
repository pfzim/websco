<?php

function jobs_sync_all(&$core, $params, $post_data)
{
	$type = @$params[1];

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// non-priveleged users cannot sync all jobs at once

	log_db('Sync all jobs started', $type, 0);

	set_time_limit(0);

	$total = $core->Runbooks->sync_jobs_all($type);

	echo '{"code": 0, "message": "'.json_escape('Jobs loaded: '.$total).'"}';
}
