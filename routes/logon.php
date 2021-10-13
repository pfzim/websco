<?php

function logon(&$core, $params, $post_data)
{
	if(!$core->UserAuth->logon(@$post_data['login'], @$post_data['passwd']))
	{
		$error_msg = LL('InvalidUserPasswd');
		include(TEMPLATES_DIR.'tpl.login.php');
		exit;
	}

	/*
	if(!$core->UserAuth->is_member(LDAP_ADMIN_GROUP_DN))
	{
		$core->UserAuth->logoff();
		$error_msg = 'Access denied!';
		include(TEMPLATES_DIR.'tpl.login.php');
		exit;
	}
	*/
	
	global $g_link_prefix;

	if(!empty($post_data['return']))
	{
		header('Location: '.$post_data['return']);
	}
	else
	{
		header('Location: '.$g_link_prefix);
	}
}
