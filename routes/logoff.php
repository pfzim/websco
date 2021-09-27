<?php

function logoff(&$core, $params)
{
	$core->UserAuth->logoff();
	include(TEMPLATES_DIR.'tpl.login.php');
}
