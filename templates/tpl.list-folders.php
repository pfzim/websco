<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

		<h3 align="center">Folder: <?php eh($current_folder_name); ?></h3>

		<table id="table" class="main-table">
			<thead>
				<tr>
					<th width="5%">№</th>
					<th width="70%">Name</th>
					<th width="25%">Operations</th>
				</tr>
			</thead>
			<tbody id="table-data">
				<?php $i = 0; if(!empty($parent_folder_id)) { $i++; ?>
				<tr>
					<td><?php eh($i); ?>.</td>
					<td><a href="<?php eh($self.'?action=list_folder&guid='.$parent_folder_id); ?>"><b>[Up level]</b></a></td>
				</tr>
				<?php } ?>
				<?php foreach($folders as &$row) { $i++; ?>
					<tr>
						<td><?php eh($i); ?>.</td>
						<td><a href="<?php eh($self.'?action=list_folder&guid='.$row['guid']); ?>"><b><?php eh($row['name']); ?></b></a></td>
					</tr>
				<?php } ?>
				<?php foreach($runbooks as &$row) { $i++; ?>
					<tr>
						<td><?php eh($i); ?>.</td>
						<td><a href="<?php eh($self.'?action=get_runbook&guid='.$row['guid']); ?>" onclick="return f_show_form(this.href);"><?php eh($row['name']); ?></a></td>
						<td>
							<a href="<?php eh($self.'?action=list_jobs&guid='.$row['guid']); ?>">View jobs</a>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>


		<br />

<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.job-info.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
