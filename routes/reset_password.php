<?php

function reset_password(&$core, $params, $post_data)
{
	$new_password = @$post_data['new_password'];
	$new_password2 = @$post_data['new_password2'];
	$user_id = intval(@$post_data['uid']);
	$reset_token = @$post_data['reset_token'];

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
