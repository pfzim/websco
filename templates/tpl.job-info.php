<?php if(!defined('Z_PROTECTED')) exit; ?>

		<div id="job" class="modal-container" style="display: none">
			<span class="close white" onclick="this.parentNode.style.display='none'">&times;</span>
			<div class="modal-content">
				<span class="close" onclick="this.parentNode.parentNode.style.display='none'">&times;</span>
				<h3 id="runbook_title"></h3>
				<?php L('JobID') ?>: <span id="job_guid"></span><br />
				<?php L('JobSID') ?>: <span id="job_sid"></span><br />
				<?php L('WhoRun') ?>: <span id="job_user"></span><br />
				<br />
				<?php L('Status') ?>: <span id="job_status"></span><br />
				<br />

				<table id="job_table">
					<tbody id="job_table_data">
					</tbody>
				</table>

				<br />
				<div class="f-right">
					<button id="job_restart" class="button-other" type="button" onclick=""><?php L('Restart') ?>...</button>
					&nbsp;<button class="button-accept" type="button" onclick="this.parentNode.parentNode.parentNode.style.display='none'"><?php L('OK') ?></button>
					&nbsp;<button id="job_update" class="button-accept" type="button" onclick=""><?php L('Refresh') ?></button>
				</div>
			</div>
		</div>

		<div id="activity" class="modal-container" style="display: none">
			<span class="close white" onclick="this.parentNode.style.display='none'">&times;</span>
			<div class="modal-content">
				<span class="close" onclick="this.parentNode.parentNode.style.display='none'">&times;</span>
				<h3 id="activity_title"><?php L('ActivityData') ?></h3>

				<table id="activity_table">
					<tbody id="activity_table_data">
					</tbody>
				</table>

				<br />
				<div class="f-right">
					<button class="button-accept" type="button" onclick="this.parentNode.parentNode.parentNode.style.display='none'"><?php L('OK') ?></button>
				</div>
			</div>
		</div>
