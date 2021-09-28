<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.list-tree.php'); ?>

<script type="text/javascript">
	g_pid = <?php eh($id); ?>;
</script>
<div>
	<div class="tree-menu">
		<?php print_folders_tree_id('permissions', $current_folder['id'], $folders_tree) ?>
	</div>
	<div class="content-box">
		<h3><?php L('AccessRightsManagement') ?>: <span id="section_name"><?php eh($current_folder['name']);?></span></h3>
		<span id="add_new_permission" class="command" onclick="f_new_permission(0);"><?php L('AddPermission') ?></span>
		<?php if($current_folder['id'] != 0) { if($current_folder['flags'] & 0x0002) { ?>
			<span id="show_hide" class="command" onclick="f_show_hide('/websco/show_folder/<?php eh($current_folder['id']); ?>');"><?php L('ShowFolder') ?></span>
		<?php } else { ?>
			<span id="show_hide" class="command" onclick="f_show_hide('/websco/hide_folder/<?php eh($current_folder['id']); ?>');"><?php L('HideFolder') ?></span>
		<?php } } ?>
		<table id="table" class="main-table" width="100%">
			<thead>
			<tr>
				<th width="5%">ID</th>
				<th width="55%">DN</th>
				<th width="20%"><?php L('Access') ?></th>
				<th width="20%"><?php L('Operations') ?></th>
			</tr>
			</thead>
			<tbody id="table-data">
			<?php
				$i = 0;
				foreach($permissions as &$row)
				{
					$i++;
					$group_name = &$row['dn'];
					if(preg_match('/^..=([^,]+),/i', $group_name, $matches))
					{
						$group_name = &$matches[1];
					}
					?>
						<tr id="<?php eh("row".$row['id']); ?>" data-id=<?php eh($row['id']);?>>
							<td><?php eh($row['id']); ?></td>
							<td><?php eh($group_name); ?></td>
							<td class="mono"><?php eh($core->UserAuth->permissions_to_string($row['allow_bits'])); ?></td>
							<td>
								<span class="command" onclick="f_show_form('<?php eh('/websco/get_permission/'.$row['id']); ?>');"><?php L('Edit') ?></span>
								<span class="command" onclick="f_delete_perm(event);"><?php L('Delete') ?></span>
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
