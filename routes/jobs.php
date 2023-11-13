<?php

function jobs(&$core, $params, $post_data)
{
	$id = @$params[1];

	$total = 0;
	$offset = 0;
	if(isset($params[2]))
	{
		$offset = $params[2];
	}
	
	$search_job = '';
	$where = '';
	if(!empty($params[3]))
	{
		$search_job = trim(urldecode($params[3]));
		if(!empty($search_job))
		{
			$where = rpv('
					AND (
						u.`login` LIKE \'%{r0}%\'
						OR j.`guid` LIKE \'%{r0}%\'
						OR (
							SELECT COUNT(*)
							FROM w_runbooks_jobs_params AS jp
							WHERE
								jp.`pid` = j.`id`
								AND jp.`value` LIKE \'%{r0}%\'
							LIMIT 1
						) > 0
					)
				',
				sql_escape($search_job)
			);
		}
	}

	$runbook = $core->Runbooks->get_runbook_by_id($id);

	if(!$core->UserAuth->check_permission($runbook['folder_id'], RB_ACCESS_EXECUTE))
	{
		$error_msg = LL('AccessDeniedToSection').' '.$runbook['folder_id'].' '.LL('forUser').' '.$core->UserAuth->get_login().'!';
		include(TEMPLATES_DIR.'tpl.message.php');
		exit;
	}

	$current_folder = array(
		'id' => $runbook['folder_id'],
		'guid' => $runbook['folder_guid']
	);

	$folders_tree = $core->Runbooks->get_folders_tree(TRUE);

	if($core->db->select_ex($jobs_total, rpv('
		SELECT
			COUNT(*)
		FROM @runbooks_jobs AS j
		LEFT JOIN @users AS u ON u.`id` = j.`uid`
		WHERE
			j.`pid` = {d0}
			{r1}
		',
		$runbook['id'],
		$where
	)))
	{
		$total = intval($jobs_total[0][0]);
	}

	$core->db->select_assoc_ex($jobs, rpv('
		SELECT
			j.`id`,
			DATE_FORMAT(j.`date`, \'%d.%m.%Y %H:%i:%s\') AS `run_date`,
			j.`guid`,
			u.`login`
		FROM @runbooks_jobs AS j
		LEFT JOIN @users AS u ON u.`id` = j.`uid`
		WHERE j.`pid` = {d0}
		{r1}
		ORDER BY j.`date` DESC, j.`id` DESC
		LIMIT {d2},100
	',
		$runbook['id'],
		$where,
		$offset
	));

	if(!empty($search_job))
	{
		log_db('Search jobs: '.$search_job, '', 0);
	}

	include(TEMPLATES_DIR.'tpl.jobs.php');
}
