<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

<h3 align="center"><?php eh('Tools') ?></h3>

<a href="<?php eh($self.'?action=sync'); ?>" onclick="return f_async(this);">Sync folders, runbooks and activities</a><br />
<a href="<?php eh($self.'?action=sync_jobs'); ?>" onclick="return f_async(this);">Sync jobs (too slow)</a><br />

<p>
	Current user token: <b><?php eh($core->UserAuth->get_token()); ?></b><br />
	The token will be reset if you logout.
</p>
<p>Example usage:</p>
<pre>
  curl --silent --cookie <?php eh('"zl='.$core->UserAuth->get_login().';zh='.$core->UserAuth->get_token().'"'); ?>" --output /dev/null "http://localhost<?php eh($_SERVER['PHP_SELF'].'?action=sync'); ?>"
</pre>

<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
