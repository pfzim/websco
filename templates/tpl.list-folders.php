<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

		<h3 align="center">Folder: <?php eh($current_folder_name); ?></h3>

		<table id="table" class="main-table">
			<thead>
				<tr>
					<th width="5%">№</th>
					<th width="60%">Name</th>
				</tr>
			</thead>
			<tbody id="table-data">
				<?php if(!empty($parent_folder_id)) { $i++; ?>
				<tr>
					<td>0.</td>
					<td><a href="<?php eh($self.'?action=list_folder&guid='.$parent_folder_id); ?>"><b>[Up level]</b></a></td>
				</tr>
				<?php } ?>
				<?php $i = 0; foreach($folders as &$row) { $i++; ?>
					<tr>
						<td><?php eh($i); ?>.</td>
						<td><a href="<?php eh($self.'?action=list_folder&guid='.$row['guid']); ?>"><b><?php eh($row['name']); ?></b></a></td>
					</tr>
				<?php } ?>
				<?php foreach($runbooks as &$row) { $i++; ?>
					<tr>
						<td><?php eh($i); ?>.</td>
						<td><a href="#" onclick="f_show_runbook('<?php eh($row['guid']); ?>', 'runbook');"><?php eh($row['name']); ?></a></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>


		<br />

		<div id="runbook-container" class="modal-container" style="display: none">
		</div>

<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
