<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.tree-list.php'); ?>

<div>
	<div class="tree-menu">
		<?php print_folders_tree_id('permissions', $current_folder['id'], $folders_tree, TRUE) ?>
	</div>
	<div class="content-box">
		<h3><?php L('AccessRightsManagement') ?>: <span id="section_name"><?php eh($current_folder['name']);?></span></h3>
		<span id="add_new_permission" class="command" onclick="f_show_form('<?php ln('permission_new/'.$current_folder['id']) ?>');"><?php L('AddPermission') ?></span>
		<?php if($current_folder['id'] != 0) { if($current_folder['flags'] & RBF_HIDED) { ?>
			<span id="show_hide" class="command" onclick="f_show_hide('<?php ln('folder_show') ?>', <?php eh($current_folder['id']) ?>);"><?php L('ShowFolder') ?></span>
		<?php } else { ?>
			<span id="show_hide" class="command" onclick="f_show_hide('<?php ln('folder_hide') ?>', <?php eh($current_folder['id']) ?>);"><?php L('HideFolder') ?></span>
		<?php } } ?>
		<?php if(($current_folder['flags'] & RBF_TYPE_CUSTOM) || $current_folder['id'] == 0) { ?>
			<span class="command" onclick="f_show_form('<?php ln('folder_get/0/'.$current_folder['id']) ?>');"><?php L('CreateFolder') ?></span>
		<?php } ?>
		<?php if($current_folder['flags'] & RBF_TYPE_CUSTOM) { ?>
			<span class="command" onclick="f_show_form('<?php ln('folder_get/'.$current_folder['id'].'/0') ?>');"><?php L('EditFolder') ?></span>
			<span class="command" onclick="f_delete_folder('<?php ln('folder_delete') ?>', <?php eh($current_folder['id']) ?>);"><?php L('DeleteFolder') ?></span>
		<?php } ?>
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
					
					if($core->LDAP->search($result, '(&(objectCategory=group)(distinguishedName='.ldap_escape($row['dn'], '', LDAP_ESCAPE_FILTER).'))', array('distinguishedName')) == 1)
					{
						$icon_title = 'Group exists in AD';
						$icon_path = 'templates/check_mark.png';
					}
					else
					{
						$icon_title = 'Group not found in AD';
						$icon_path = 'templates/cross_mark.png';
					}
					?>
						<tr id="<?php eh("row".$row['id']); ?>" data-id=<?php eh($row['id']);?>>
							<td><?php eh($row['id']); ?></td>
							<td><img src="<?php ls($icon_path) ?>" title="<?php eh($icon_title); ?>" width="13"/> <?php eh($group_name); ?></td>
							<td class="mono"><?php eh($core->UserAuth->permissions_to_string($row['allow_bits'])); ?></td>
							<td>
								<span class="command" onclick="f_show_form('<?php ln('permission_get/'.$row['id']) ?>');"><?php L('Edit') ?></span>
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
