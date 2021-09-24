<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
			<div class="login-block">
				<h1><?php L('LoginHdr') ?></h1>
				<form action="<?php eh("$self?action=logon"); ?>" method="post">
					<?php L('UserName') ?>
					<input name="login" type="text" autofocus="autofocus" placeholder="domain\user_name"/><br />
					<?php L('Password') ?>
					<input name="passwd" type="password" /><br />
					<?php if(!empty($error_msg)) { ?>
					<p><?php eh($error_msg); ?></p>
					<?php } ?>
					<input type="submit" value="<?php L('LoginBtn') ?>" /><br />
				</form>
			</div>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
