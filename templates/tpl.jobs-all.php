<?php include(TEMPLATES_DIR.'tpl.header.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.search-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.tree-list.php'); ?>

<div>
	<div class="tree-menu">
		<?php print_folders_tree_id('runbooks', 0, $folders_tree, $core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE)) ?>
	</div>
	<div class="content-box">
		<h3><?php L('JobsForRunbook') ?>: All</h3>

		<form id="search_form" action="<?php ln('jobs_all/0'); ?>" method="get" onsubmit="return f_search(this);">
			<?php L('FindJobs') ?>: <input type="text" name="search" class="form-field" placeholder="<?php L('LookingValue') ?>..." value="<?php if(isset($search_job)) eh($search_job); ?>">
			<input class="button-other" type="submit" value="<?php L('Search') ?>" /><br />
		</form>

		<table id="table" class="main-table">
			<thead>
				<tr>
					<th width="2%">№</th>
					<th width="10%"><?php L('Date') ?></th>
					<th width="30%"><?php L('Name') ?></th>
					<th width="20%">GUID</th>
					<th width="15%"><?php L('WhoRun') ?></th>
					<th width="20%"><?php L('Operations') ?></th>
				</tr>
			</thead>
			<tbody id="table-data">
				<?php $i = $offset; foreach($jobs as &$row) { $i++; ?>
					<tr>
						<td><?php eh($i); ?>.</td>
						<td><?php eh($row['run_date']); ?></td>
						<td><?php eh($row['runbook_name']); ?></td>
						<td>
							<?php if((intval($row['runbook_flags']) & RBF_TYPE_CUSTOM) == 0) { ?>
								<a href="<?php ln('job_get/'.$row['guid']); ?>" onclick="return f_get_job('<?php eh($row['guid']); ?>');" onmouseenter="si(event, <?php eh($row['id']); ?>)" onmouseleave="document.getElementById('popup').style.display='none'" onmousemove="mi(event);"><?php eh($row['guid']); ?></a>
							<?php } else { ?>
								<a href="<?php ln('job_custom_get/'.$row['id']); ?>" onclick="return f_get_custom_job('<?php eh($row['id']); ?>');" onmouseenter="si(event, <?php eh($row['id']); ?>)" onmouseleave="document.getElementById('popup').style.display='none'" onmousemove="mi(event);"><?php eh($row['guid'].'_'.$row['id']); ?></a>
							<?php } ?>
						</td>
						<td><?php eh($row['login']); ?></td>
						<td><?php if((intval($row['flags']) & RBF_TYPE_CUSTOM) == 0) { ?><a href="<?php ln('runbook_get/'.$row['runbook_guid'].'/'.$row['id']); ?>" onclick="return f_show_form(this.href);"><?php L('Restart') ?></a><?php } ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>

		<a class="page-number<?php if($offset == 0) eh(' boldtext'); ?>" href="<?php ln('jobs_all/0/'.urlencode($search_job)); ?>">1</a>
		<?php 
			$min = max(100, $offset - 1000);
			$max = min($offset + 1000, $total - ($total % 100));

			if($min > 100) { echo '&nbsp;...&nbsp;'; }

			for($i = $min; $i <= $max; $i += 100)
			{
			?>
				<a class="page-number<?php if($offset == $i) eh(' boldtext'); ?>" href="<?php ln('jobs_all/'.$i.'/'.urlencode($search_job)); ?>"><?php eh($i/100 + 1); ?></a>
			<?php
			}

			$max = $total - ($total % 100);
			if($i < $max)
			{
			?>
				&nbsp;...&nbsp;<a class="page-number<?php if($offset == $max) eh(' boldtext'); ?>" href="<?php ln('jobs_all/'.$max.'/'.urlencode($search_job)); ?>"><?php eh($max/100 + 1); ?></a>
			<?php
			}
		?>
	</div>
</div>

<div id="popup" class="tooltip-user" style="display: none;">
	<table id="popup_table">
		<tbody id="popup_table_data">
		</tbody>
	</table>
</div>


<?php include(TEMPLATES_DIR.'tpl.universal-form.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.job-info.php'); ?>
<?php include(TEMPLATES_DIR.'tpl.footer.php'); ?>
