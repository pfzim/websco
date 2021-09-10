<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<script type="text/javascript">
	g_pid = <?php eh($id); ?>;
</script>
		<h3 align="center">Access rights management: <span id="section_name"><?php eh($current_folder['name']);?></span></h3>
<div>
	<div class="left-menu">
		<ul>
		<li<?php if($id == 0) { echo ' class="active"'; } ?>><a href="?action=permissions&amp;id=0">Top level</a></li>
		<li>
			<ul>
		<?php $i = 0; foreach($folders as &$row) { $i++; ?>
		<li<?php if($id == $row['id']) { echo ' class="active"'; } ?>><span onclick="f_expand(this, '<?php eh($row['guid']); ?>');">+</span><a href="?action=get_permissions&amp;id=<?php eh($row['id']); ?>" onclick="return f_get_perms(<?php eh($row['id']); ?>);"><?php eh($row['name']); ?></a></li>
		<?php } ?>
			</ul>
		</li>
		</ul>
	</div>
	<div class="content-box">
		<span class="command" onclick="f_edit(null, 'permission');">Add permission group</span>
		<table id="table" class="main-table" width="100%">
			<thead>
			<tr>
				<th width="5%">ID</th>
				<th width="55%">DN</th>
				<th width="20%">Access</th>
				<th width="20%">Operations</th>
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
								<span class="command" onclick="f_edit(<?php eh($row['id']);?>, 'permission');">Edit</span>
								<span class="command" onclick="f_delete_perm(event);">Delete</span>
							</td>
						</tr>
		<?php } ?>
			</tbody>
		</table>
	</div>
</div>
		<br />
		<br />
		<div id="permission-container" class="modal-container" style="display: none">
			<span class="close white" onclick="this.parentNode.style.display='none'">&times;</span>
			<div class="modal-content">
				<span class="close" onclick="this.parentNode.parentNode.style.display='none'">&times;</span>
				<form id="permission">
				<h3>Edit permissions</h3>
				<input name="id" type="hidden" value=""/>
				<input name="pid" type="hidden" value=""/>
				<div class="form-title"><label for="dn">DN*:</label></div>
				<input class="form-field" id="dn" name="dn" type="edit" value=""/>
				<div id="dn-error" class="form-error"></div>
				<div class="form-title">Allow rights:</div>
				<span><input id="allow_bit_1" name="allow_bit_1" type="checkbox" value="1"/><label for="allow_bit_1">Execute</label></span>
				<div id="allow_bit_1-error" class="form-error"></div>
				</form>
				<br />
				<div class="f-right">
					<button class="button-accept" type="button" onclick="f_save('permission');">Сохранить</button>
					&nbsp;
					<button class="button-decline" type="button" onclick="this.parentNode.parentNode.parentNode.style.display='none'">Отмена</button>
				</div>
			</div>
		</div>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
