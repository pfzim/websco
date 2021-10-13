<?php

function users_list(&$core, $params, $post_data)
{
	if(!$core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE))
	{
		$error_msg = LL('AccessDeniedToSection').' 0 '.LL('forUser').' '.$core->UserAuth->get_login().'!';
		include(TEMPLATES_DIR.'tpl.message.php');
		exit;
	}

	$core->db->select_assoc_ex($users, rpv('SELECT u.`id`, u.`login`, u.`mail`, u.`flags` FROM @users AS u WHERE (u.`flags` & (0x0002)) = 0x0000 ORDER BY u.`login`'));

	include(TEMPLATES_DIR.'tpl.users-list.php');
}
