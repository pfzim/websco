<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.search-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.tree-list.php'); ?>

<div>
	<div class="tree-menu">
		<?php print_folders_tree_id('runbooks', $current_folder['id'], $folders_tree, $core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE)) ?>
	</div>
	<div class="content-box">
		<h3><?php eh($current_folder['name']); ?></h3>

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
						<?php if(intval($row['flags']) & RBF_TYPE_CUSTOM) { ?>
							<td><a href="<?php ln('custom/'.$row['id']); ?>" ><?php eh($row['name']); ?></a></td>
						<?php } else { ?>
							<td><a href="<?php ln('runbook_get/'.$row['id']); ?>" onclick="return f_show_form(this.href);"><?php eh($row['name']); ?></a></td>
						<?php } ?>
						<td>
							<a href="<?php ln('jobs/'.$row['id']); ?>"><?php L('ViewJobs') ?></a>
							<?php if(!empty($row['wiki_url'])) { ?> <a href="<?php eh($row['wiki_url']); ?>" target="_blank"><?php L('Instruction') ?></a> <?php } ?>
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
