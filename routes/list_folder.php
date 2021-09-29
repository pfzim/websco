<?php

function list_folder(&$core, $params)
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

	if($core->db->select_assoc_ex($folder, rpv('SELECT f.`id`, f.`pid`, f.`guid`, f.`name` FROM @runbooks_folders AS f WHERE f.`guid` = !', $pid)))
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
	
	$core->db->select_assoc_ex($runbooks, rpv('SELECT r.`guid`, r.`name` FROM @runbooks AS r WHERE r.`folder_id` = {s0} AND (r.`flags` & (0x0001)) = 0 ORDER BY r.`name`', $current_folder['id']));

	include(TEMPLATES_DIR.'tpl.list-folder.php');
}