<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.list-tree.php'); ?>

<div>
	<div class="tree-menu">
		<?php print_folders_tree('list_folder', $current_folder_guid, $folders_tree) ?>
	</div>
	<div class="content-box">
		<h3><?php L('CurrentFolder') ?>: <?php eh($current_folder_name); ?></h3>

		<table id="table" class="main-table">
			<thead>
				<tr>
					<th width="5%">№</th>
					<th width="70%"><?php L('Name') ?></th>
					<th width="25%"><?php L('Operations') ?></th>
				</tr>
			</thead>
			<tbody id="table-data">
				<?php $i = 0; if(isset($runbooks)) foreach($runbooks as &$row) { $i++; ?>
					<tr>
						<td><?php eh($i); ?>.</td>
						<td><a href="<?php eh('/websco/get_runbook/'.$row['guid']); ?>" onclick="return f_show_form(this.href);"><?php eh($row['name']); ?></a></td>
						<td>
							<a href="<?php eh('/websco/list_jobs/'.$row['guid']); ?>"><?php L('ViewJobs') ?></a>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</div>
		<br />
		<br />

<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.job-info.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
