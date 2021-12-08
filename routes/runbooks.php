<?php

function runbooks(&$core, $params, $post_data)
{
	if(!empty($params[1]))
	{
		$pid = $params[1];
	}
	else
	{
		$pid = '00000000-0000-0000-0000-000000000000';
	}

	$current_folder = array(
		'name' => LL('RootLevel'),
		'id' => 0,
		'pid' => '00000000-0000-0000-0000-000000000000',
		'guid' => '00000000-0000-0000-0000-000000000000',
		'childs' => NULL
	);

	if($core->db->select_assoc_ex($folder, rpv('SELECT f.`id`, f.`pid`, f.`guid`, f.`name` FROM @runbooks_folders AS f WHERE f.`id` = !', $pid)))
	{
		if(!empty($folder[0]['name']))
		{
			$current_folder = array(
				'name' => $folder[0]['name'],
				'id' => $folder[0]['id'],
				'pid' => $folder[0]['pid'],
				'guid' => $folder[0]['guid'],
				'childs' => NULL
			);
		}
	}

	$folders_tree = $core->Runbooks->get_folders_tree(TRUE);
	
	$runbooks = NULL;
	
	if($core->UserAuth->check_permission($current_folder['id'], RB_ACCESS_EXECUTE))
	{
		$core->db->select_assoc_ex($runbooks, rpv('SELECT r.`id`, r.`guid`, r.`name`, r.`flags` FROM @runbooks AS r WHERE r.`folder_id` = {s0} AND (r.`flags` & ({%RBF_DELETED})) = 0 ORDER BY r.`name`', $current_folder['id']));
	}

	include(TEMPLATES_DIR.'tpl.runbooks.php');
}
