<?php if(!defined('Z_PROTECTED')) exit; ?>

		<div id="job" class="modal-container" style="display: none">
			<span class="close" onclick="this.parentNode.style.display='none'">&times;</span>
			<div class="modal-content">
				<h3 id="runbook_title"></h3>
				Job ID: <span id="job_guid"></span><br />				
				Who run: <span id="job_user"></span><br />
				Status: <span id="job_status"></span><br />				
				<br />
				
				<table id="job_table">
					<tbody id="job_table_data">
					</tbody>
				</table>

				</br />
				<button class="button-accept" type="button" onclick="this.parentNode.parentNode.style.display='none'">OK</button>
				<button id="job_update" class="button-accept" type="button" onclick="">Обновить</button>
			</div>
		</div>
