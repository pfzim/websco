<?php

function runbooks_search(&$core, $params, $post_data)
{
	$runbooks = NULL;

	$total = 0;
	$offset = 0;
	if(!empty($params[1]))
	{
		$offset = intval($params[1]);
	}

	$search = '';
	$where = '';
	if(!empty($params[2]))
	{
		$search = trim(urldecode($params[2]));
		if(!empty($search))
		{
			$where = rpv('
					AND (
						r.`name` LIKE \'%{r0}%\'
						OR f.`name` LIKE \'%{r0}%\'
						OR r.`description` LIKE \'%{r0}%\'
					)
				',
				sql_escape($search)
			);
		}
	}

	if($core->db->select_ex($runbooks_total, rpv('
		SELECT
			COUNT(*)
		FROM @runbooks AS r
		LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id`
		WHERE
			(r.`flags` & {%RBF_DELETED}) = 0
			{r0}
		',
		$where
	)))
	{
		$total = intval($runbooks_total[0][0]);
	}

	$core->db->select_assoc_ex($runbooks, rpv('
		SELECT
			r.`id`,
			r.`folder_id`,
			f.`name` AS folder_name,
			r.`guid`,
			r.`name`,
			r.`flags`
		FROM @runbooks AS r
		LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id`
		WHERE
			(r.`flags` & {%RBF_DELETED}) = 0
			{r0}
		ORDER BY
			r.`name`,
			f.`name`
		LIMIT {d1},100
		',
		$where,
		$offset
	));

	//log_db('Search: '.$search, '', 0);

	include(TEMPLATES_DIR.'tpl.runbooks-search.php');
}
