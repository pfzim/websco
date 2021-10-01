<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.list-tree.php'); ?>

<div>
	<div class="content-box">
		<h3><?php L('UsersManagement') ?></h3>
		<span id="add_new_user" class="command" onclick="f_show_form('/websco/get_user/0');"><?php L('AddUser') ?></span>

		<table id="table" class="main-table" width="100%">
			<thead>
			<tr>
				<th width="5%">ID</th>
				<th width="20%"><?php L('Login') ?></th>
				<th width="20%"><?php L('Mail') ?></th>
				<th width="20%"><?php L('Operations') ?></th>
			</tr>
			</thead>
			<tbody id="table-data">
			<?php $i = 0; if($users) foreach($users as &$row) { ?>
				<tr id="<?php eh("row".$row['id']); ?>" data-id=<?php eh($row['id']);?>>
					<td><?php eh($row['id']); ?></td>
					<td><?php eh($row['login']); ?></td>
					<td><?php eh($row['mail']); ?></td>
					<td>
						<span class="command" onclick="f_show_form('<?php eh('/websco/get_user/'.$row['id']); ?>');"><?php L('Edit') ?></span>
						<span class="command" onclick="f_delete_user(event);"><?php L('Delete') ?></span>
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
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
