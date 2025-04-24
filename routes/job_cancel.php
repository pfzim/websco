<?php

function job_cancel(&$core, $params, $post_data)
{
	if(!$core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, j.`guid` AS `job_guid`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks_jobs AS j LEFT JOIN @runbooks AS r ON r.`id` = j.`pid` LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE j.`id` = # LIMIT 1", $post_data['id'])))
	{
		$core->error('Job '.$post_data['id'].' not found!');
		return FALSE;
	}

	$guid = $runbook[0]['job_guid'];

	if(intval($runbook[0]['flags']) & RBF_TYPE_SCO)
	{
		$runbook = $core->Runbooks->get_runbook_by_job_guid($post_data['id']);

		assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

		if($core->Runbooks->job_cancel($guid))
		{
			log_db('Job canceled: '.$post_data['id'], $post_data['id'], 0);
			echo '{"code": 0, "id": "'.json_escape($post_data['id']).'", "message": "'.LL('JobCanceled').' ID: '.json_escape($post_data['id']).'"}';
		}
		else
		{
			echo '{"code": 1, "message": "Failed: Failed cancel job"}';
		}
	}
	else if(intval($runbook[0]['flags']) & RBF_TYPE_ANSIBLE)
	{
		$runbook = $core->AnsibleAWX->get_playbook_by_job_id($post_data['id']);

		assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

		if($core->AnsibleAWX->job_cancel($guid))
		{
			log_db('Job canceled: '.$post_data['id'], $post_data['id'], 0);
			echo '{"code": 0, "id": "'.json_escape($post_data['id']).'", "message": "'.LL('JobCanceled').' ID: '.json_escape($post_data['id']).'"}';
		}
		else
		{
			echo '{"code": 1, "message": "Failed: Failed cancel job"}';
		}
	}
	else
	{
		$core->error('Job '.$post_data['id'].' not found!');
		return FALSE;
	}
}
