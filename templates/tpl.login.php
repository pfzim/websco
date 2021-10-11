<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
			<div class="login-block">
				<h1><?php L('LoginHdr') ?></h1>
				<form action="<?php eh('/websco/logon'); ?>" method="post">
					<input name="return" type="hidden" value="<?php eh($return_url); ?>"/><br />
					<?php L('UserName') ?>
					<input name="login" type="text" autofocus="autofocus" placeholder="domain\user_name"/><br />
					<?php L('Password') ?>
					<input name="passwd" type="password" /><br />
					<?php if(!empty($error_msg)) { ?>
					<p><?php eh($error_msg); ?></p>
					<?php } ?>
					<input type="submit" value="<?php L('LoginBtn') ?>" /><br />
				</form>
				<a href="#" onclick="f_show_form('/websco/form_register');"><?php L('Register') ?></a> &VerticalSeparator; <a href="#" onclick="f_show_form('/websco/form_ask_mail_for_reset');"><?php L('ResetPasswordBtn') ?></a><br />
			</div>
<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
