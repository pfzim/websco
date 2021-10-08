<?php

function list_jobs(&$core, $params, $post_data)
{
	$guid = @$params[1];

	if(isset($params[2]))
	{
		$offset = $params[2];
	}
	else
	{
		$offset = 0;
	}
	
	$runbook = $core->Runbooks->get_runbook($guid);

	if(!$core->UserAuth->check_permission($runbook['folder_id'], RB_ACCESS_EXECUTE))
	{
		$error_msg = LL('AccessDeniedToSection').' '.$runbook['folder_id'].' '.LL('forUser').' '.$core->UserAuth->get_login().'!';
		include(TEMPLATES_DIR.'tpl.message.php');
		exit;
	}

	$current_folder = array(
		'guid' => $runbook['folder_guid']
	);

	$folders_tree = $core->Runbooks->get_folders_tree(TRUE);

	$total = 0;

	if($core->db->select_ex($result, rpv("SELECT COUNT(*) FROM @runbooks_jobs AS j WHERE j.`pid` = #", $runbook['id'])))
	{
		$total = $result[0][0];
	}

	$core->db->select_assoc_ex($jobs, rpv('
		SELECT
			j.`id`,
			DATE_FORMAT(j.`date`, \'%d.%m.%Y %H:%i:%s\') AS `run_date`,
			j.`guid`,
			u.`login`
		FROM @runbooks_jobs AS j
		LEFT JOIN @users AS u ON u.`id` = j.`uid`
		WHERE j.`pid` = #
		ORDER BY j.`date` DESC, j.`id` DESC
		LIMIT #,100
	',
		$runbook['id'],
		$offset
	));

	include(TEMPLATES_DIR.'tpl.list-jobs.php');
}
