<?php

function form_approve(&$core, $params, $post_data)
{
	if(!$core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE))
	{
		$error_msg = LL('AccessDeniedToSection').' 0 '.LL('forUser').' '.$core->UserAuth->get_login().'!';
		include(TEMPLATES_DIR.'tpl.message.php');
		exit;
	}

	$user_id = @$params[1];
	
	$user_info = $core->UserAuth->get_user_info_ex($user_id);

	include(TEMPLATES_DIR.'tpl.form-approve.php');
}
