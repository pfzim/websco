<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

		<h3 align="center">Jobs: <?php eh($runbook['name']); ?></h3>

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


<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.job-info.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
