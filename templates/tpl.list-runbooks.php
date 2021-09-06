<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

		<?php if(isset($runbooks)) { ?>
		<h3 align="center">Runbooks</h3>

			<table id="table" class="main-table">
				<thead>
					<tr>
						<th width="5%">№</th>
						<th width="60%">Name</th>
					</tr>
				</thead>
				<tbody id="table-data">
					<?php $i = 0; foreach($runbooks as &$row) { $i++; ?>
						<tr>
							<td><?php eh($i); ?></td>
							<td><a href="<?php eh($self.'?action=show_runbook&guid='.$row['guid']); ?>"><?php eh($row['name']); ?></a></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>

		<?php } ?>

		<br />

<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>

