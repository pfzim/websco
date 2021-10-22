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
	if(!empty($post_data['search']))
	{
		$search = $post_data['search'];
		$where = rpv('
				AND (
					r.`name` LIKE \'%{r0}%\'
					OR f.`name` LIKE \'%{r0}%\'
					OR r.`description` LIKE \'%{r0}%\'
				)
			',
			sql_escape(trim($search)
		));
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
			r.`name`
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

	include(TEMPLATES_DIR.'tpl.runbooks-search.php');
}
