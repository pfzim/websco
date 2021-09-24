<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

<h3 align="center"><?php L('Tools') ?></h3>

<a href="<?php eh($self.'?action=sync'); ?>" onclick="return f_async(this);"><?php L('SyncRunbooks') ?></a><br />
<a href="<?php eh($self.'?action=sync_jobs'); ?>" onclick="return f_async(this);"><?php L('SyncJobs') ?></a><br />

<p>
	<?php L('CurrentUserToken') ?>: <b><?php eh($core->UserAuth->get_token()); ?></b><br />
	<?php L('TokenNote') ?>
</p>
<p><?php L('UsageExample') ?>:</p>
<pre>
  curl --silent --cookie <?php eh('"zl='.$core->UserAuth->get_login().';zh='.$core->UserAuth->get_token().'"'); ?>" --output /dev/null "http://localhost<?php eh($_SERVER['PHP_SELF'].'?action=sync'); ?>"
</pre>

<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
