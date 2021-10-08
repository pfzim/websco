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

	header('Location: /websco/');
}
