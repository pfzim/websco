<?php

function permissions(&$core, $params)
{
	$id = @$params[1];

	if(!$core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE))
	{
		$error_msg = LL('AccessDeniedToSection').' 0 '.LL('forUser').' '.$core->UserAuth->get_login().'!';
		include(TEMPLATES_DIR.'tpl.message.php');
		exit;
	}

	if(empty($id) || intval($id) == 0)
	{
		//$core->db->select_assoc_ex($folder, rpv('SELECT f.`id`, f.`guid`, f.`pid`, f.`name` FROM `@runbooks_folders` AS f WHERE f.`id` = # ORDER BY f.`name`', $id));
		$current_folder = array(
			'name' => LL('RootLevel'),
			'id' => 0,
			'pid' => '00000000-0000-0000-0000-000000000000',
			'guid' => ''
		);
	}
	else
	{
		$core->db->select_assoc_ex($folder, rpv('SELECT f.`id`, f.`guid`, f.`pid`, f.`name` FROM `@runbooks_folders` AS f WHERE f.`id` = # ORDER BY f.`name`', $id));
		$current_folder = &$folder[0];
	}

	$core->db->select_assoc_ex($folders, rpv('SELECT f.`id`, f.`guid`, f.`name` FROM `@runbooks_folders` AS f WHERE f.`pid` = ! ORDER BY f.`name`', $current_folder['pid']));
	$core->db->select_assoc_ex($permissions, rpv('SELECT a.`id`, a.`oid`, a.`dn`, a.`allow_bits` FROM `@access` AS a WHERE a.`oid` = # ORDER BY a.`dn`', $current_folder['id']));

	include(TEMPLATES_DIR.'tpl.admin-permissions.php');
}
