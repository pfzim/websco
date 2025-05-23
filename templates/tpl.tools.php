<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

<h3><?php L('Tools') ?></h3>

<?php if(defined('AWX_URL')) { ?>
	<a href="<?php ln('runbooks_sync/' . RBF_TYPE_ANSIBLE); ?>" onclick="return f_async(this);"><?php L('SyncPlaybooks') ?></a><br />
	<br />
<?php } ?>
<?php if(defined('ORCHESTRATOR_URL')) { ?>
	<a href="<?php ln('runbooks_sync/' . RBF_TYPE_SCO); ?>" onclick="return f_async(this);"><?php L('SyncRunbooks') ?></a><br />
<?php } ?>
<?php if(defined('ORCHESTRATOR2022_URL')) { ?>
	<a href="<?php ln('runbooks_sync/' . RBF_TYPE_SCO2022); ?>" onclick="return f_async(this);"><?php L('SyncRunbooks2022') ?></a><br />
<?php } ?>
<br />
<?php if(defined('AWX_URL')) { ?>
	<a href="<?php ln('jobs_sync_all/' . RBF_TYPE_ANSIBLE); ?>" onclick="return f_confirm_async(this);"><?php L('SyncJobsAnsible') ?></a><br />
<?php } ?>
<?php if(defined('ORCHESTRATOR_URL')) { ?>
	<a href="<?php ln('jobs_sync_all/' . RBF_TYPE_SCO); ?>" onclick="return f_confirm_async(this);"><?php L('SyncJobs') ?></a><br />
<?php } ?>
<?php if(defined('ORCHESTRATOR2022_URL')) { ?>
	<a href="<?php ln('jobs_sync_all/' . RBF_TYPE_SCO2022); ?>" onclick="return f_confirm_async(this);"><?php L('SyncJobs2022') ?></a><br />
<?php } ?>

<p>
	<?php L('CurrentUserToken') ?>: <b><?php eh($core->UserAuth->get_token()); ?></b><br />
	<?php L('TokenNote') ?>
</p>
<p><?php L('UsageExample') ?>:</p>
<pre>
  curl --silent --cookie <?php eh('"zl='.$core->UserAuth->get_login().';zh='.$core->UserAuth->get_token().'"'); ?> --output /dev/null "http://localhost/<?php ln('runbooks_sync'); ?>"
  php -f <?php eh($_SERVER['SCRIPT_FILENAME']); ?> -- --user <?php eh($core->UserAuth->get_login()); ?> --token <?php eh($core->UserAuth->get_token()); ?> --path runbooks_sync
  php -f <?php eh($_SERVER['SCRIPT_FILENAME']); ?> -- --user <?php eh($core->UserAuth->get_login()); ?> --token <?php eh($core->UserAuth->get_token()); ?> --path jobs_sync
  php -f <?php eh($_SERVER['SCRIPT_FILENAME']); ?> -- --user <?php eh($core->UserAuth->get_login()); ?> --token <?php eh($core->UserAuth->get_token()); ?> --path jobs_sync/&lt;runbook guid&gt;
  php -f <?php eh($_SERVER['SCRIPT_FILENAME']); ?> -- --user <?php eh($core->UserAuth->get_login()); ?> --token <?php eh($core->UserAuth->get_token()); ?> --path runbook_start --data 'guid=00000000-0000-0000-0000-000000000000&amp;param[00000000-0000-0000-0000-000000000000]=value'
  php -f <?php eh($_SERVER['SCRIPT_FILENAME']); ?> -- --user <?php eh($core->UserAuth->get_login()); ?> --password &lt;password&gt; --path runbooks_sync
</pre>

<br />
<?php if(defined('USE_MEMCACHED') && USE_MEMCACHED) { ?>
<a href="<?php ln('memcached_flush'); ?>" onclick="return f_async(this);"><?php L('FlushMemcached') ?></a><br />
<?php } ?>


<?php if(!$core->UserAuth->is_ldap_user() && $core->UserAuth->get_id()) { ?>
<a href="<?php ln('password_change_form'); ?>" onclick="return f_show_form(this.href);"><?php L('ChangePassword') ?></a><br />
<?php } ?>

<!-- -- >
<h3><?php L('Settings') ?></h3>

<table id="table" class="main-table">
	<thead>
		<tr>
			<th width="1%">UID</th>
			<th width="20%"><?php L('Name') ?></th>
			<th width="40%"><?php L('Value') ?></th>
			<th width="39%"><?php L('Description') ?></th>
		</tr>
	</thead>
	<tbody id="table-data">
		<?php foreach($config as &$row) { ?>
			<tr>
				<td><?php eh($row['uid']); ?></td>
				<td><span class="command" onclick="f_show_form('<?php ln('setting_get/'.$row['uid'].'/'.$row['name']) ?>');"><?php eh($row['name']); ?></span></td>
				<td><pre><?php eh($row['value']); ?></pre></td>
				<td><pre><?php eh($row['description']); ?></pre></td>
			</tr>
		<?php } ?>
	</tbody>
</table>
<!-- -->

<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
