<?php

function logoff(&$core, $params, $post_data)
{
	$core->UserAuth->logoff();
	include(TEMPLATES_DIR.'tpl.login.php');
}
