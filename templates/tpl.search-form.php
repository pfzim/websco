<?php if(!defined('Z_PROTECTED')) exit; ?>

		<form id="search_form" action="<?php ln('runbooks_search'); ?>" method="get" onsubmit="return f_search(this);">
			<?php L('FindRunbook') ?>: <input type="text" id="search" class="form-field" placeholder="<?php L('RunbookName') ?>..." value="<?php if(isset($search)) eh($search); ?>">
			<input class="button-other" type="submit" value="<?php L('Search') ?>" /><br />
		</form>
