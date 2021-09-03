<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
			<div class="login-block">
				<h1><?php eh($lang["loginLoginHead"]) ?></h1>
				<form action="<?php eh("$self?action=logon"); ?>" method="post">
					<?php eh($lang["loginLogin"]) ?>
					<input name="login" type="text" autofocus="autofocus" placeholder="domain\user_name"/><br />
					<?php eh($lang["loginPassword"]) ?>
					<input name="passwd" type="password" /><br />
					<?php if(!empty($error_msg)) { ?>
					<p><?php eh($error_msg); ?></p>
					<?php } ?>
					<input type="submit" value="<?php eh($lang["loginLoginBtn"]) ?>" /><br />
				</form>
			</div>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
