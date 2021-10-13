<?php if(!defined('Z_PROTECTED')) exit; ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title><?php L('Title') ?></title>
		<link type="text/css" href="<?php ls('templates/style.css') ?>" rel="stylesheet" />
		<link type="text/css" href="<?php ls('templates/flatpickr.material_red.css') ?>" rel="stylesheet" />
		<script>
			g_link_prefix = '<?php global $g_link_prefix; echo $g_link_prefix; ?>';
			g_link_static_prefix = '<?php global $g_link_static_prefix; echo $g_link_static_prefix; ?>';
		</script>
		<script src="<?php ls('languages/'.APP_LANGUAGE.'.js') ?>"></script>
		<script src="<?php ls('websco.js') ?>"></script>
		<script src="<?php ls('flatpickr.min.js') ?>"></script>
<!--
		<link type="text/css" href="/websco/templates/pikaday.css" rel="stylesheet" />
		<script src="/websco/moment.js"></script>
		<script src="/websco/pikaday.js"></script>
-->
	</head>
	<body>
		<ul class="menu-bar">
			<?php if($core->UserAuth->get_id()) { ?>
				<li><a href="<?php ln('runbooks') ?>"><?php L('Runbooks') ?></a></li>
				<li><a href="<?php ln('tools') ?>"><?php L('Tools') ?></a></li>
				<li><a href="<?php ln('permissions') ?>"><?php L('Permissions') ?></a></li>
				<li><a href="<?php ln('users') ?>"><?php L('Users') ?></a></li>
			<?php } ?>
			<ul style="float:right;list-style-type:none;">
				<?php if($core->UserAuth->get_id()) { ?>
				<li><a href="<?php ln('logoff') ?>"><?php L('Logout') ?></a></li>
				<?php } else { ?>
				<li><a href="<?php ln('login') ?>"><?php L('LoginBtn') ?></a></li>
				<?php } ?>
			</ul>
		</ul>
