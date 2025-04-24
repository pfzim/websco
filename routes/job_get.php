<?php

function job_get(&$core, $params, $post_data)
{
	$id = @$params[1];
	if(!$core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks_jobs AS j LEFT JOIN @runbooks AS r ON r.`id` = j.`pid` LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE j.`id` = # LIMIT 1", $id)))
	{
		$core->error('Job '.$id.' not found!');
		return FALSE;
	}

	if(intval($runbook[0]['flags']) & RBF_TYPE_SCO)
	{
		$job = $core->Runbooks->get_job($id);
	}
	else if(intval($runbook[0]['flags']) & RBF_TYPE_ANSIBLE)
	{
		$job = $core->AnsibleAWX->get_job($id);
	}
	else
	{
		$core->error('Job '.$id.' not found!');
		return FALSE;
	}

	assert_permission_ajax($job['folder_id'], RB_ACCESS_EXECUTE);

	echo json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
