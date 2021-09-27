<?php

function list_folders(&$core, $params)
{
	if(!empty($params[1]))
	{
		$pid = $params[1];
	}
	else
	{
		$pid = '00000000-0000-0000-0000-000000000000';
	}

	$current_folder_name = LL('RootLevel');
	$parent_folder_id = '';

	if($core->db->select_assoc_ex($current_folder, rpv('SELECT f.`id`, f.`pid`, f.`name` FROM @runbooks_folders AS f WHERE f.`guid` = !', $pid)))
	{
		if(!empty($current_folder[0]['name']))
		{
			$current_folder_name = $current_folder[0]['name'];
			$parent_folder_id = $current_folder[0]['pid'];
		}
	}
	
	$filter_folders = 'AND (f.`flags` & (0x0001 | 0x0002)) = 0';
	$filter_runbooks = 'AND (r.`flags` & (0x0001 | 0x0002)) = 0';

	if($core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE))
	{
		$filter_folders = '';
		$filter_runbooks = '';
	}

	$core->db->select_assoc_ex($folders, rpv('SELECT f.`guid`, f.`name` FROM @runbooks_folders AS f WHERE f.`pid` = {s0} {r1} ORDER BY f.`name`', $pid, $filter_folders));
	$core->db->select_assoc_ex($runbooks, rpv('SELECT r.`guid`, r.`name` FROM @runbooks AS r WHERE r.`folder_id` = {s0} {r1} ORDER BY r.`name`', $current_folder[0]['id'], $filter_runbooks));

	include(TEMPLATES_DIR.'tpl.list-folders.php');
}
