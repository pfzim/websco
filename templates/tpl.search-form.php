<?php if(!defined('Z_PROTECTED')) exit; ?>

		<form action="<?php ln('runbooks_search'); ?>" method="post">
			<?php L('FindRunbook') ?>: <input type="text" id="search" name="search" class="form-field" placeholder="Runbook name" value="<?php if(isset($search)) eh($search); ?>">
			<input type="submit" value="<?php L('Search') ?>" /><br />
		</form>
