<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.search-form.php'); ?>

<div>
	<div class="content-box">
		<h3><?php L('SearchResult') ?>: <?php eh($search); ?></h3>

		<?php if($runbooks) { ?>
		<table id="table" class="main-table">
			<thead>
				<tr>
					<th width="5%">№</th>
					<th width="40%"><?php L('RunbookName') ?></th>
					<th width="40%"><?php L('FolderName') ?></th>
					<th width="15%"><?php L('Operations') ?></th>
				</tr>
			</thead>
			<tbody id="table-data">
				<?php $i = $offset; foreach($runbooks as &$row) { ?>
					<?php if($core->UserAuth->check_permission($row['folder_id'], RB_ACCESS_EXECUTE)) { $i++; ?>
						<tr>
							<td><?php eh($i); ?>.</td>
							<td><a href="<?php ln('runbook_get/'.$row['guid']); ?>" onclick="return f_show_form(this.href);"><?php eh($row['name']); ?></a></td>
							<td><a href="<?php ln('runbooks/'.$row['folder_id']); ?>"><?php eh($row['folder_name']); ?></a></td>
							<td>
								<a href="<?php ln('jobs/'.$row['id']); ?>"><?php L('ViewJobs') ?></a>
							</td>
						</tr>
					<?php } ?>
				<?php } ?>
			</tbody>
		</table>
		<?php } else { ?>
			<p><?php L('NothingFound') ?></p>
		<?php } ?>

		<a class="page-number<?php if($offset == 0) eh(' boldtext'); ?>" href="<?php ln('runbooks_search/0'); ?>" onclick="return f_search(this.href, '<?php eh($search) ?>');">1</a>
		<?php 
			$min = max(100, $offset - 1000);
			$max = min($offset + 1000, $total - ($total % 100));

			if($min > 100) { echo '&nbsp;...&nbsp;'; }

			for($i = $min; $i <= $max; $i += 100)
			{
			?>
				<a class="page-number<?php if($offset == $i) eh(' boldtext'); ?>" href="<?php ln('runbooks_search/'.$i); ?>" onclick="return f_search(this.href, '<?php eh($search) ?>');"><?php eh($i/100 + 1); ?></a>
			<?php
			}

			$max = $total - ($total % 100);
			if($i < $max)
			{
			?>
				&nbsp;...&nbsp;<a class="page-number<?php if($offset == $max) eh(' boldtext'); ?>" href="<?php ln('runbooks_search/'.$max); ?>" onclick="return f_search(this.href, '<?php eh($search) ?>');"><?php eh($max/100 + 1); ?></a>
			<?php
			}
		?>
	</div>
</div>


<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
