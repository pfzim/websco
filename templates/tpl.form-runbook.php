<?php if(!defined("Z_PROTECTED")) exit; ?>

			<span class="close white" onclick="this.parentNode.style.display='none'">&times;</span>
			<div class="modal-content">
				<span class="close" onclick="this.parentNode.parentNode.style.display='none'">&times;</span>
				<form id="runbook">
					<h3><?php eh($runbook['name']); ?></h3>
				
					<input name="action" type="hidden" value="start_runbook" />
					<input name="guid" type="hidden" value="<?php eh($runbook['guid']); ?>" />
				
					<?php
						$i = 0;
						foreach($runbook_params as &$row)
						{
							$required = FALSE;
							$type = 'string';
							
							$i++;
							if(preg_match('/_([isdla]+)$/i', $row['name'], $matches))
							{
								$suffix = $matches[1];
								
								$k = 0;
								$len = strlen($suffix);
								while($k < $len)
								{
									switch($suffix[$k])
									{
										case 'i':
											$type = 'integer';
											break;
										case 'i':
											$type = 'string';
											break;
										case 'd':
											$type = 'date';
											break;
										case 'l':
											$type = 'list';
											break;
										case 'a':
											$type = 'samaccountname';
											break;
									}
									
									$k++;
								}
							}

							$name = preg_replace('/\s*_[isdla]+$/i', '', $row['name']);
							
							if(preg_match('/\*\s*:\s*?$/i', $row['name']))
							{
								$required = TRUE;
							}
							
							$name = preg_replace('/\s*:\s*$/i', '', $name);
							
							if(($type == 'list') && preg_match('/\(([^\)]+)\)\s*\*$/i', $name, $matches))
							{
								$name = preg_replace('/\s*\(([^\)]+)\)\s*(\*)/i', '\2', $name);
								$list = preg_split('/[,;]/', $matches[1]);
								?>
									<div class="form-title"><label for="param[<?php eh($row['guid']); ?>]">[list] <?php eh($name); ?>:</label></div>
									<select class="form-field" name="param[<?php eh($row['guid']); ?>]">
										<option value=""></option>
										<?php
											for($i = 0; $i < count($list); $i++)
											{
												?>
													<option value="<?php eh($list[$i]); ?>"><?php eh($list[$i]); ?></option>
												<?php
											}
								?>
									</select>
								<?php
							}
							elseif($type == 'date')
							{
								?>
									<div class="form-title"><label for="param[<?php eh($row['guid']); ?>]">[date] <?php eh($name); ?>:</label></div>
									<input class="form-field" id="g-<?php eh($row['guid']); ?>" name="param[<?php eh($row['guid']); ?>]" type="edit" value=""/>
									<div id="param[<?php eh($row['guid']); ?>]-error" class="form-error"></div>
									<script>
										var picker = new Pikaday({
											field: document.getElementById('g-<?php eh($row['guid']); ?>'),
											format: 'DD.MM.YYYY',
										});
									</script>
								<?php
							}
							else
							{
								?>
									<div class="form-title"><label for="param[<?php eh($row['guid']); ?>]">[string] <?php eh($name); ?>:</label></div>
									<input class="form-field" id="param[<?php eh($row['guid']); ?>]" name="param[<?php eh($row['guid']); ?>]" type="edit" value=""/>
									<div id="param[<?php eh($row['guid']); ?>]-error" class="form-error"></div>
								<?php
							}
						} 
					?>

					<div class="f-right">
						<button class="button-accept" type="submit" onclick="return f_start_runbook('runbook');">Запустить</button>
						&nbsp;
						<button class="button-decline" type="button" onclick="this.parentNode.parentNode.parentNode.parentNode.style.display='none'">Отмена</button>
					</div>
				</form>
			</div>
