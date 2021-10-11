<?php

function login(&$core, $params, $post_data)
{
	$return_url = $_SERVER['REQUEST_URI'];

	$core->UserAuth->logoff();

	include(TEMPLATES_DIR.'tpl.login.php');
}
