<?php if(!defined("Z_PROTECTED")) exit; ?>

		<div id="uform-container" class="modal-container" style="display: none">
			<span class="close white" onclick="this.parentNode.style.display='none'">&times;</span>
			<div class="modal-content">
				<span class="close" onclick="this.parentNode.parentNode.style.display='none'">&times;</span>
				<h3 id="uform-title" class='form-header'></h3>
				<div id="uform-description" class='form-description'></div>
				<div id="uform-url" class='form-url'><a id="uform-url-href" href="#" target="_blank"><?php L('Instruction') ?></a></div>
				<form id="uform-fields" class="form-fields">
				</form>
			</div>
		</div>
