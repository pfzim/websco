<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

		<h3 align="center">Jobs for runbook: <?php eh($runbook['name']); ?> (<a href="<?php eh($self.'?action=sync_jobs&guid='.$runbook['guid']); ?>" onclick="return f_async(this);">Sync</a>)</h3>

		<table id="table" class="main-table">
			<thead>
				<tr>
					<th width="10%">№</th>
					<th width="20%">Date</th>
					<th width="30%">GUID</th>
					<th width="20%">Who run</th>
					<th width="20%">Operations</th>
				</tr>
			</thead>
			<tbody id="table-data">
				<?php $i = 0; foreach($jobs as &$row) { $i++; ?>
					<tr>
						<td><?php eh($i); ?>.</td>
						<td><?php eh($row['run_date']); ?></td>
						<td><a href="<?php eh($self.'?action=get_job&guid='.$row['guid']); ?>" onclick="return f_get_job('<?php eh($row['guid']); ?>');"><?php eh($row['guid']); ?></a></td>
						<td><?php eh($row['login']); ?></td>
						<td><a href="<?php eh($self.'?action=get_runbook&guid='.$runbook['guid'].'&job_id='.$row['id']); ?>" onclick="return f_show_form(this.href);">Restart</a></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>

		<a class="page-number<?php if($offset == 0) eh(' boldtext'); ?>" href="<?php eh($self.'?action='.$action.'&guid='.$runbook['guid'].'&offset=0'); ?>">1</a>
		<?php 
			$min = max(100, $offset - 1000);
			$max = min($offset + 1000, $total - ($total % 100));

			if($min > 100) { echo '&nbsp;...&nbsp;'; }

			for($i = $min; $i <= $max; $i += 100)
			{
			?>
				<a class="page-number<?php if($offset == $i) eh(' boldtext'); ?>" href="<?php eh($self.'?action='.$action.'&guid='.$runbook['guid'].'&offset='.$i); ?>"><?php eh($i/100 + 1); ?></a>
			<?php
			}

			$max = $total - ($total % 100);
			if($i < $max)
			{
			?>
				&nbsp;...&nbsp;<a class="page-number<?php if($offset == $max) eh(' boldtext'); ?>" href="<?php eh($self.'?action='.$action.'&guid='.$runbook['guid'].'&offset='.$max); ?>"><?php eh($max/100 + 1); ?></a>
			<?php
			}
		?>


<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.job-info.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
