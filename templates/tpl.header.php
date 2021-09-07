<?php if(!defined('Z_PROTECTED')) exit; ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title>WebSCO - System Center Orchestartor web interface</title>
		<link type="text/css" href="templates/style.css" rel="stylesheet" />
		<link type="text/css" href="templates/pikaday.css" rel="stylesheet" />
		<script src="websco.js"></script>
		<script src="moment.js"></script>
		<script src="pikaday.js"></script>
	</head>
	<body>
		<ul class="menu-bar">
			<li><a href="<?php eh($self); ?>"><?php eh($lang["headerHome"]) ?></a></li>
			<?php if($core->UserAuth->get_id()) { ?>
				<li><a href="<?php eh($self.'?action=list_folder'); ?>">Runbooks</a></li>
				<li><a href="<?php eh($self.'?action=sync'); ?>">Sync</a></li>
				<li><a href="<?php eh($self.'?action=permissions'); ?>">Permissions</a></li>
			<?php } ?>
			<ul style="float:right;list-style-type:none;">
				<?php if($core->UserAuth->get_id()) { ?>
				<li><a href="?action=logoff"><?php eh($lang["headerLogOut"]) ?></a></li>
				<?php } else { ?>
				<li><a href="?action=login"><?php eh($lang["headerLogIn"]) ?></a></li>
				<?php } ?>
			</ul>
		</ul>
