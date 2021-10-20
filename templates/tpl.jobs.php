<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.search-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.tree-list.php'); ?>

<div>
	<div class="tree-menu">
		<?php print_folders_tree_id('runbooks', $current_folder['id'], $folders_tree, $core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE)) ?>
	</div>
	<div class="content-box">
		<h3><?php L('JobsForRunbook') ?>: <?php eh($runbook['name']); ?> (<a href="<?php ln('jobs_sync/'.$runbook['guid']); ?>" onclick="return f_async(this);">Sync</a>)</h3>

		<table id="table" class="main-table">
			<thead>
				<tr>
					<th width="10%">№</th>
					<th width="20%"><?php L('Date') ?></th>
					<th width="30%">GUID</th>
					<th width="20%"><?php L('WhoRun') ?></th>
					<th width="20%"><?php L('Operations') ?></th>
				</tr>
			</thead>
			<tbody id="table-data">
				<?php $i = $offset; foreach($jobs as &$row) { $i++; ?>
					<tr>
						<td><?php eh($i); ?>.</td>
						<td><?php eh($row['run_date']); ?></td>
						<td><a href="<?php ln('job_get/'.$row['guid']); ?>" onclick="return f_get_job('<?php eh($row['guid']); ?>');"><?php eh($row['guid']); ?></a></td>
						<td><?php eh($row['login']); ?></td>
						<td><a href="<?php ln('runbook_get/'.$runbook['guid'].'/'.$row['id']); ?>" onclick="return f_show_form(this.href);"><?php L('Restart') ?></a></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>

		<a class="page-number<?php if($offset == 0) eh(' boldtext'); ?>" href="<?php ln('jobs/'.$runbook['id'].'/0'); ?>">1</a>
		<?php 
			$min = max(100, $offset - 1000);
			$max = min($offset + 1000, $total - ($total % 100));

			if($min > 100) { echo '&nbsp;...&nbsp;'; }

			for($i = $min; $i <= $max; $i += 100)
			{
			?>
				<a class="page-number<?php if($offset == $i) eh(' boldtext'); ?>" href="<?php ln('jobs/'.$runbook['id'].'/'.$i); ?>"><?php eh($i/100 + 1); ?></a>
			<?php
			}

			$max = $total - ($total % 100);
			if($i < $max)
			{
			?>
				&nbsp;...&nbsp;<a class="page-number<?php if($offset == $max) eh(' boldtext'); ?>" href="<?php ln('jobs/'.$runbook['id'].'/'.$max); ?>"><?php eh($max/100 + 1); ?></a>
			<?php
			}
		?>
	</div>
</div>


<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.job-info.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
