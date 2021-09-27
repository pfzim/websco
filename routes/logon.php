<?php

function logon(&$core, $params)
{
	if(!$core->UserAuth->logon(@$_POST['login'], @$_POST['passwd']))
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
