<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

		<h3 align="center"><?php L('CurrentFolder') ?>: <?php eh($current_folder_name); ?></h3>

		<table id="table" class="main-table">
			<thead>
				<tr>
					<th width="5%">№</th>
					<th width="70%"><?php L('Name') ?></th>
					<th width="25%"><?php L('Operations') ?></th>
				</tr>
			</thead>
			<tbody id="table-data">
				<?php $i = 0; if(!empty($parent_folder_id)) { $i++; ?>
				<tr>
					<td><?php eh($i); ?>.</td>
					<td><a href="<?php eh('/websco/list_folder/'.$parent_folder_id); ?>"><b>[<?php L('UpLevel') ?>]</b></a></td>
				</tr>
				<?php } ?>
				<?php if(isset($folders)) foreach($folders as &$row) { $i++; ?>
					<tr>
						<td><?php eh($i); ?>.</td>
						<td><a href="<?php eh('/websco/list_folder/'.$row['guid']); ?>"><b><?php eh($row['name']); ?></b></a></td>
					</tr>
				<?php } ?>
				<?php if(isset($runbooks)) foreach($runbooks as &$row) { $i++; ?>
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


		<br />

<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.job-info.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
