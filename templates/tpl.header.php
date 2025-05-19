<?php if(!defined('Z_PROTECTED')) exit; ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title><?php L('Title') ?></title>
        <link rel="icon" type="image/png" href="<?php ls('templates/favicon.png') ?>">
		<link type="text/css" href="<?php ls('templates/style.css') ?>" rel="stylesheet" />
		<link type="text/css" href="<?php ls('templates/flatpickr.material_red.css') ?>" rel="stylesheet" />
		<script>
			g_link_prefix = '<?php echo WEB_LINK_PREFIX; ?>';
			g_link_static_prefix = '<?php echo WEB_LINK_STATIC_PREFIX; ?>';
		</script>
		<script src="<?php global $g_app_language; ls('languages/'.$g_app_language.'.js'); ?>"></script>
		<script src="<?php ls('websco.js') ?>"></script>
		<script src="<?php ls('flatpickr.min.js') ?>"></script>
<!--
		<link type="text/css" href="/websco/templates/pikaday.css" rel="stylesheet" />
		<script src="/websco/moment.js"></script>
		<script src="/websco/pikaday.js"></script>
-->
		<script type="text/javascript">
			document.documentElement.setAttribute("data-theme-color", localStorage.getItem("theme-color") || 'light');
		</script>
	</head>
	<body>
		<ul class="menu-bar">
			<?php if($core->UserAuth->get_id()) { ?>
				<li><a href="<?php ln('runbooks' . (isset($current_folder['id']) ? '/' . $current_folder['id'] .'/' : '')) ?>"><?php L('Runbooks') ?></a></li>
				<li><a href="<?php ln('tools') ?>"><?php L('Tools') ?></a></li>
				<li><a href="<?php ln('permissions' . (isset($current_folder['id']) ? '/' . $current_folder['id'] .'/' : '')) ?>"><?php L('Permissions') ?></a></li>
				<li><a href="<?php ln('users') ?>"><?php L('Users') ?></a></li>
			<?php } ?>

			<li class="right-menu-container">
				<ul class="right-menu">
				
					<li class="menu-item">
						<a href="#" onclick="return false;"><?php L('Theme') ?></a>
						<ul class="submenu">
							<li><a href="#" onclick="f_theme_change('dark'); return false;">dark</a></li>
							<li><a href="#" onclick="f_theme_change('light'); return false;">light</a></li>
						</ul>
					</li>

					<li class="menu-item">
						<a href="#" onclick="return false;"><?php L('Language') ?></a>
						<ul class="submenu">
							<?php
								$languages = languages_list();
								foreach($languages as $language)
								{
									echo '<li><a href="#" onclick="f_language_change(\'' . htmlspecialchars($language) . '\'); return false;">' . htmlspecialchars(strtoupper($language)) . '</a></li>';
								}
							?>
						</ul>
					</li>

					<?php if($core->UserAuth->get_id()) { ?>
						<li><a href="<?php ln('logoff') ?>"><?php L('Logout') ?> (<?php eh($core->UserAuth->get_login()); ?>)</a></li>
					<?php } else { ?>
						<li><a href="<?php ln('login') ?>"><?php L('LoginBtn') ?></a></li>
					<?php } ?>
				</ul>
			</li>
		</ul>
