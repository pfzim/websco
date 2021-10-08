<?php

function form_reset_password(&$core, $params, $post_data)
{
	$user_id = @$params[1];
	$reset_token = @$params[2];
	include(TEMPLATES_DIR.'tpl.form-reset-password.php');
}
