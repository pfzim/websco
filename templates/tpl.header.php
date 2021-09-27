<?php if(!defined('Z_PROTECTED')) exit; ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title>WebSCO - System Center Orchestrator web interface</title>
		<link type="text/css" href="/websco/templates/style.css" rel="stylesheet" />
		<link type="text/css" href="/websco/templates/pikaday.css" rel="stylesheet" />
		<script src="/websco/websco.js"></script>
		<script src="/websco/moment.js"></script>
		<script src="/websco/pikaday.js"></script>
	</head>
	<body>
		<ul class="menu-bar">
			<?php if($core->UserAuth->get_id()) { ?>
				<li><a href="<?php eh('/websco/list_folder'); ?>"><?php L('Runbooks') ?></a></li>
				<li><a href="<?php eh('/websco/list_tools'); ?>"><?php L('Tools') ?></a></li>
				<li><a href="<?php eh('/websco/permissions'); ?>"><?php L('Permissions') ?></a></li>
			<?php } ?>
			<ul style="float:right;list-style-type:none;">
				<?php if($core->UserAuth->get_id()) { ?>
				<li><a href="/websco/logoff"><?php L('Logout') ?></a></li>
				<?php } else { ?>
				<li><a href="/websco/login"><?php L('LoginBtn') ?></a></li>
				<?php } ?>
			</ul>
		</ul>
