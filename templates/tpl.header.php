<?php if(!defined('Z_PROTECTED')) exit; ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title><?php eh('TITLE') ?></title>
		<link type="text/css" href="templates/style.css" rel="stylesheet" />
	</head>
	<body>
		<ul class="menu-bar">
			<li><a href="<?php eh("$self"); ?>"><?php eh($lang["headerHome"]) ?></a></li>
			<ul style="float:right;list-style-type:none;">
				<?php if($user->get_id()) { ?>
				<li><a href="?action=logoff"><?php eh($lang["headerLogOut"]) ?></a></li>
				<?php } else { ?>
				<li><a href="?action=login"><?php eh($lang["headerLogIn"]) ?></a></li>
				<?php } ?>
			</ul>
		</ul>
