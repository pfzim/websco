<?php

function password_reset_form(&$core, $params, $post_data)
{
	$user_id = @$params[1];
	$reset_token = @$params[2];
	include(TEMPLATES_DIR.'tpl.password-reset-form.php');
}
