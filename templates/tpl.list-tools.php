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
  curl --silent --cookie <?php eh('"zl='.$core->UserAuth->get_login().';zh='.$core->UserAuth->get_token().'"'); ?>" --output /dev/null "http://localhost<?php eh('/websco/sync'); ?>"
</pre>

<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
