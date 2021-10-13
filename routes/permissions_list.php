<?php

function permissions_list(&$core, $params, $post_data)
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
		$current_folder = array(
			'name' => LL('RootLevel'),
			'id' => 0,
			'pid' => '00000000-0000-0000-0000-000000000000',
			'guid' => '00000000-0000-0000-0000-000000000000',
			'flags' => 0,
			'childs' => NULL
		);
	}
	else
	{
		$core->db->select_assoc_ex($folder, rpv('SELECT f.`id`, f.`guid`, f.`pid`, f.`name`, f.`flags` FROM `@runbooks_folders` AS f WHERE f.`id` = # ORDER BY f.`name`', $id));

		$current_folder = array(
			'name' => $folder[0]['name'],
			'id' => $folder[0]['id'],
			'pid' => $folder[0]['pid'],
			'guid' => $folder[0]['guid'],
			'flags' => $folder[0]['flags'],
			'childs' => NULL
		);
	}

	$folders_tree = $core->Runbooks->get_folders_tree(FALSE);

	$core->db->select_assoc_ex($permissions, rpv('SELECT a.`id`, a.`oid`, a.`dn`, a.`allow_bits` FROM `@access` AS a WHERE a.`oid` = # ORDER BY a.`dn`', $current_folder['id']));

	include(TEMPLATES_DIR.'tpl.permissions-list.php');
}
