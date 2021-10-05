<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

<h3><?php L('Tools') ?></h3>

<a href="<?php eh('/websco/sync'); ?>" onclick="return f_async(this);"><?php L('SyncRunbooks') ?></a><br />
<a href="<?php eh('/websco/sync_jobs'); ?>" onclick="return f_async(this);"><?php L('SyncJobs') ?></a><br />

<p>
	<?php L('CurrentUserToken') ?>: <b><?php eh($core->UserAuth->get_token()); ?></b><br />
	<?php L('TokenNote') ?>
</p>
<p><?php L('UsageExample') ?>:</p>
<pre>
  curl --silent --cookie <?php eh('"zl='.$core->UserAuth->get_login().';zh='.$core->UserAuth->get_token().'"'); ?> --output /dev/null "http://localhost<?php eh('/websco/sync'); ?>"
  php -f <?php eh($_SERVER['SCRIPT_FILENAME']); ?> -- --user <?php eh($core->UserAuth->get_login()); ?> --token <?php eh($core->UserAuth->get_token()); ?> --path sync
  php -f <?php eh($_SERVER['SCRIPT_FILENAME']); ?> -- --user <?php eh($core->UserAuth->get_login()); ?> --token <?php eh($core->UserAuth->get_token()); ?> --path sync_jobs
  php -f <?php eh($_SERVER['SCRIPT_FILENAME']); ?> -- --user <?php eh($core->UserAuth->get_login()); ?> --token <?php eh($core->UserAuth->get_token()); ?> --path sync_jobs/&lt;runbook guid&gt;
  php -f <?php eh($_SERVER['SCRIPT_FILENAME']); ?> -- --user <?php eh($core->UserAuth->get_login()); ?> --password &lt;password&gt; --path sync
</pre>

<br />
<?php if(defined('USE_MEMCACHED') && USE_MEMCACHED) { ?>
<a href="<?php eh('/websco/flush_memcached'); ?>" onclick="return f_async(this);"><?php L('FlushMemcached') ?></a><br />
<?php } ?>


<?php if(!$core->UserAuth->is_ldap_user() && $core->UserAuth->get_id()) { ?>
<a href="#" onclick="f_show_form('/websco/password_form');"><?php L('ChangePassword') ?></a><br />
<?php } ?>

<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
