<?php

function jobs_all(&$core, $params, $post_data)
{
	$total = 0;
	$offset = 0;
	if(isset($params[1]))
	{
		$offset = $params[1];
	}

	$search_job = '';
	$where = '';
	if(!empty($params[2]))
	{
		$search_job = trim(urldecode($params[2]));
		if(!empty($search_job))
		{
			$where = rpv('
					WHERE (
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

	if(!$core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE))
	{
		$error_msg = LL('AccessDeniedToSection').' 0 '.LL('forUser').' '.$core->UserAuth->get_login().'!';
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
		{r0}
		',
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
			r.`name` AS runbook_name,
			r.`id` AS runbook_id,
			r.`guid` AS runbook_guid,
			r.`flags` AS runbook_flags,
			u.`login`
		FROM @runbooks_jobs AS j
		LEFT JOIN @runbooks AS r ON  r.`id` = j.`pid`
		LEFT JOIN @users AS u ON u.`id` = j.`uid`
		{r0}
		ORDER BY j.`date` DESC, j.`id` DESC
		LIMIT {d1},100
	',
		$where,
		$offset
	));

	if(!empty($search_job))
	{
		log_db('Search jobs: '.$search_job, '', 0);
	}

	include(TEMPLATES_DIR.'tpl.jobs-all.php');
}
