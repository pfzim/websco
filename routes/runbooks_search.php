<?php

function runbooks_search(&$core, $params, $post_data)
{
	$runbooks = NULL;

	if(!empty($post_data['search']))
	{
		$search = $post_data['search'];
		$offset = 0;
		if(!empty($params[1]))
		{
			$offset = intval($params[1]);
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
				AND (
					r.`name` LIKE \'%{r0}%\'
					OR f.`name` LIKE \'%{r0}%\'
					OR r.`description` LIKE \'%{r0}%\'
				)
			ORDER BY
				r.`name`,
				f.`name`
			LIMIT {d1},100
			',
			sql_escape(trim($search)),
			$offset
		));
	}

	include(TEMPLATES_DIR.'tpl.runbooks-search.php');
}
