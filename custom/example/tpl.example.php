<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

<h2>Example custom script</h2>

<form action="" method="post" onsubmit="gi('loading').style.display = 'block'">
	<input type="hidden" name="action" value="my_custom_example_action"/>
	Remote computer: <input class="form-field" type="text" name="server_name" value="<?php eh(@$post_data['server_name']) ?>" placeholder="srv-ps-01.contoso.com"/><br />
	Your name: <input class="form-field" type="text" name="example_input_value" value="<?php eh(@$post_data['example_input_value']) ?>" placeholder="John"/><br />
	<br />
	Login (optional): <input class="form-field" type="text" name="example_input_login" value="<?php eh(@$post_data['example_input_login']) ?>" placeholder="domain\username"/><br />
	Password (optional): <input class="form-field" type="password" name="example_input_password" value="<?php eh(@$post_data['example_input_password']) ?>"/><br />
	Authentication: <select class="form-field" name="example_input_auth">
		<option value="Default" selected="selected">Default</option>
		<option value="Basic">Basic</option>
		<option value="Credssp">Credssp</option>
		<option value="Digest">Digest</option>
		<option value="Kerberos">Kerberos</option>
		<option value="Negotiate">Negotiate</option>
		<option value="NegotiateWithImplicitCredential">NegotiateWithImplicitCredential</option>
	</select><br />
	Configuration Name (optional): <input class="form-field" type="text" name="example_input_conf" value="<?php eh(@$post_data['example_input_conf']) ?>"/><br />
	<br /><input class="button-other" type="submit" value="Try connect" style="width: 250px"/>
</form>

<?php if($result) { ?>

	<?php if(intval(@$result['errors']) || intval(@$result['warnings']) || !empty(@$result['message'])) { ?>
		<div class="form-error" style="display: block;">
			Errors: <b><?php eh($result['errors']) ?></b><br />
			Warnings: <b><?php eh($result['warnings']) ?></b><br />
			<br />
			Message: <pre><?php eh($result['message']) ?></pre><br />
		</div>
	<?php } ?>

	<?php if(isset($result['data'])) { ?>
	
		<h3>Result from PowerShell:</h3>

		<pre><?php eh($result['data']['answer']) ?></pre>
			
	<?php  } ?>
<?php } ?>

<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
