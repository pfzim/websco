<?php if(!defined('Z_PROTECTED')) exit; ?>
		<div id="message-box" class="modal-container" style="display: none">
			<span class="close" onclick="this.parentNode.style.display='none'">&times;</span>
			<div class="modal-content">
				<h3 id="message-text"></h3>
				<br />
				<center><button class="button-accept" type="button" onclick="this.parentNode.parentNode.parentNode.style.display='none'">OK</button></center>
			</div>
		</div>
		<div id="loading" class="modal-container" style="display: none">
			<div class="modal-content">
				<h3><?php eh($lang["footerLoading"]) ?></h3>
			</div>
		</div>
	</body>
</html>
