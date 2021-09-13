<?php if(!defined('Z_PROTECTED')) exit; ?>

		<div id="job" class="modal-container" style="display: none">
			<span class="close" onclick="this.parentNode.style.display='none'">&times;</span>
			<div class="modal-content">
				<span class="close" onclick="this.parentNode.parentNode.style.display='none'">&times;</span>
				<h3 id="runbook_title"></h3>
				Job ID: <span id="job_guid"></span><br />				
				Who run: <span id="job_user"></span><br />
				<br />
				Status: <span id="job_status"></span><br />				
				<br />
				
				<table id="job_table">
					<tbody id="job_table_data">
					</tbody>
				</table>

				</br />
				<div class="f-right">
					<button id="job_restart" class="button-other" type="button" onclick="">Restart...</button>
					&nbsp;<button class="button-accept" type="button" onclick="this.parentNode.parentNode.parentNode.style.display='none'">OK</button>
					&nbsp;<button id="job_update" class="button-accept" type="button" onclick="">Update</button>
				</div>
			</div>
		</div>
