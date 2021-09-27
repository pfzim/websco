<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>

		<h3 align="center"><?php eh($runbook['name']); ?></h3>

		<form action="<?php eh('/websco/'); ?>" method="get">
			<input type="hidden" name="action" value="start_runbook" />
			<input type="hidden" name="guid" value="<?php eh($runbook['guid']); ?>" />
		<?php if(isset($runbook_params)) { ?>
			<table id="table" class="main-table">
				<thead>
					<tr>
						<th width="50%">Name</th>
						<th width="50%">Value</th>
					</tr>
				</thead>
				<tbody id="table-data">
					<?php $i = 0; foreach($runbook_params as &$row) { $i++; ?>
						<tr>
							<td><?php eh($row['name']); ?></td>
							<td><input type="text" name="param[<?php eh($row['guid']); ?>]" value=""/></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>

		<?php } ?>
			<div class="f-right">
				<button class="button-accept" type="submit" onclick="return f_save('document');">Запустить</button>
				&nbsp;
				<button class="button-decline" type="button" onclick="this.parentNode.parentNode.parentNode.parentNode.style.display='none'">Отмена</button>
			</div>
		</form>

		<br />

<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
