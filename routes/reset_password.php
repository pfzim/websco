<?php

function reset_password(&$core, $params)
{
	$new_password = @$_POST['new_password'];
	$new_password2 = @$_POST['new_password2'];
	$user_id = intval(@$_POST['uid']);
	$reset_token = @$_POST['reset_token'];

	if(empty($reset_token))
	{
		$error_msg = LL('UserNotFound');
		include(TEMPLATES_DIR.'tpl.form-reset-password.php');
		return;
	}
	elseif(!$user_id)
	{
		$error_msg = LL('UserNotFound');
		include(TEMPLATES_DIR.'tpl.form-reset-password.php');
		return;
	}
	elseif(empty($new_password) || empty($new_password2))
	{
		$error_msg = LL('NotAllFilled');
		include(TEMPLATES_DIR.'tpl.form-reset-password.php');
		return;
	}

	if(strcmp($new_password, $new_password2) !== 0)
	{
		$error_msg = LL('PasswordsNotMatch');
		include(TEMPLATES_DIR.'tpl.form-reset-password.php');
		return;
	}

	if(!$core->UserAuth->reset_password($user_id, $reset_token, $new_password))
	{
		$error_msg = LL('UnknownError');
		include(TEMPLATES_DIR.'tpl.form-reset-password.php');
		return;
	}

	header('Location: '.WS_URL);
}
