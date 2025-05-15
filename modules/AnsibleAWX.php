<?php
/*
    AnsibleAWX class - This class is intended for accessing the AWX web
	service to get a list of playbooks and launch them.
    Copyright (C) 2025 Dmitry V. Zimin

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
	This class is intended for accessing the AWX API to get a list of runbooks
	and launch them.
*/

class AnsibleAWX
{
	private $core;
	private $awx_url;
	private $awx_user;
	private $awx_passwd;

	function __construct(&$core)
	{
		$this->core = &$core;

		$this->awx_url = AWX_URL;
		$this->awx_user = AWX_USER;
		$this->awx_passwd = AWX_PASSWD;
	}

    private function awx_api_request($method, $path, $data = NULL, $raw_output = FALSE)
	{
		// echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		// return false;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->awx_url . $path);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->awx_user.':'.$this->awx_passwd);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if($data !== NULL)
		{
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

		$output = curl_exec($ch);
		$result = curl_getinfo($ch);

		//echo $output;
		//log_file($url."\n".$output."\n\n\n");

		curl_close($ch);

		if(intval($result['http_code']) < 200 || intval($result['http_code']) >= 300)
		{
			$this->core->error('Unexpected HTTP '.$result['http_code'].' response code: '. $path . ' ' . $output);
			return FALSE;
		}

		if($raw_output)
		{
			return $output;
		}

		$json = @json_decode($output, TRUE);
		if($json === NULL)
		{
			$this->core->error('JSON parse error: '. $output);
			return FALSE;
		}

		return $json;
	}

	/**
	 Start runbook.

		\param [in] $guid   - runbook ID
		\param [in] $params - array of param GUID and value

		\return - created job ID
	*/

	public function start_playbook($guid, $params)
	{
		$parameters = array();

		if(!empty($params))
		{
			foreach($params as &$param)
			{
				$parameters[$param['guid']] = $param['value'];
			}
		}

        $result = $this->awx_api_request('POST', '/api/v2/job_templates/' . $guid . '/launch/', ['extra_vars' => !empty($parameters) ? json_encode($parameters, JSON_NUMERIC_CHECK) : '{}']);

        return $result['job'] ? $result['job'] : false;
	}

	public function start_workflow($guid, $params)
	{
		$parameters = array();

		if(!empty($params))
		{
			foreach($params as &$param)
			{
				$parameters[$param['guid']] = $param['value'];
			}
		}

        $result = $this->awx_api_request('POST', '/api/v2/workflow_job_templates/' . $guid . '/launch/', ['extra_vars' => !empty($parameters) ? json_encode($parameters, JSON_NUMERIC_CHECK) : '{}']);

        return $result['workflow_job'] ? $result['workflow_job'] : false;
	}

	/**
	 Stop job.

		\param [in] $guid   - job ID

		\return - TRUE | FALSE
	*/

	public function job_cancel($job_id, $job_guid)
	{
		$runbook = $this->core->Runbooks->get_runbook_by_job_id($job_id);
		if((intval($runbook['flags']) & (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF)) == (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF))
		{
			$url = '/api/v2/workflow_jobs/' . $job_guid . '/cancel/';
		}
		else
		{
			$url = '/api/v2/jobs/' . $job_guid . '/cancel/';
		}

		return ($this->awx_api_request('POST', $url, NULL, TRUE) !== FALSE);
	}

	public function parse_form_and_start_runbook($post_data, &$result_json)
	{
		$result_json = array(
			'code' => 0,
			'message' => '',
			'errors' => array()
		);

		$params = array();

		$playbook = $this->core->Runbooks->get_runbook_by_id($post_data['id']);
		$playbook_params = $this->get_playbook_params($post_data['id']);

		//log_file(json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

		foreach($playbook_params as &$param)
		{
			$value = '';
			//$params[$param['guid']] = $value;

			if($param['type'] == 'who')
			{
				$params[] = array(
					'guid' => $param['guid'],
					'name' => $param['name'],
					'value' => $this->core->UserAuth->get_login()
				);
				continue;
			}
			elseif($param['type'] == 'flags')
			{
				$flags = 0;
				if(isset($post_data['param'][$param['guid']]))
				{
					foreach($post_data['param'][$param['guid']] as $bit => $bit_value)
					{
						if(intval($bit_value))
						{
							$flags |= 0x01 << intval($bit);
						}
					}
				}

				if($param['required'] && (count($value) == 0))
				{
					$result_json['code'] = 1;
					$result_json['errors'][] = array('name' => 'param['.$param['guid'].'][0]', 'msg' => LL('FlagMustBeSelected'));
				}
				else
				{
					$params[] = array(
						'guid' => $param['guid'],
						'name' => $param['name'],
						'value' => strval($flags)
					);
				}

				//log_file('Value: '.strval($flags));
				continue;
			}
			elseif($param['type'] == 'multiselect')
			{
				$value = array();
				if(isset($post_data['param'][$param['guid']]))
				{
					foreach($post_data['param'][$param['guid']] as $bit => $bit_value)
					{
						$value[] = $bit_value;
					}
				}

				if($param['required'] && (count($value) == 0))
				{
					$result_json['code'] = 1;
					$result_json['errors'][] = array('name' => 'param['.$param['guid'].'][0]', 'msg' => LL('FlagMustBeSelected'));
				}
				else
				{
					$params[] = array(
						'guid' => $param['guid'],
						'name' => $param['name'],
						'value' => $value
					);
				}

				//log_file('Value: '.strval($flags));
				continue;
			}
			elseif($param['type'] == 'upload')
			{
				if(isset($_FILES['param_'.$param['guid']]['tmp_name']) && file_exists($_FILES['param_'.$param['guid']]['tmp_name']))
				{
					if(filesize($_FILES['param_'.$param['guid']]['tmp_name']) > $param['max_size'])
					{
						$result_json['code'] = 1;
						$result_json['errors'][] = array('name' => 'param_'.$param['guid'], 'msg' => LL('FileTooLarge').' (max '.$param['max_size'].' bytes)');
						continue;
					}

					$value = base64_encode(file_get_contents($_FILES['param_'.$param['guid']]['tmp_name']));
				}
				elseif($param['required'])
				{
					$result_json['code'] = 1;
					$result_json['errors'][] = array('name' => 'param_'.$param['guid'], 'msg' => LL('ThisFieldRequired'));
					continue;
				}
			}
			elseif(isset($post_data['param'][$param['guid']]))
			{
				$value = trim($post_data['param'][$param['guid']]);
			}

			if($param['required'] && empty($value))
			{
				$result_json['code'] = 1;
				$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('ThisFieldRequired'));
				continue;
			}
			elseif($param['type'] == 'date')
			{
				if(!empty($value))
				{
					list($nd, $nm, $ny) = explode('.', $value, 3);

					if(!datecheck($nd, $nm, $ny))
					{
						$result_json['code'] = 1;
						$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('IncorrectDate').' DD.MM.YYYY');
						continue;
					}
				}
			}
			elseif($param['type'] == 'list')
			{
				if(!in_array($value, $param['list']))
				{
					$result_json['code'] = 1;
					$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('ValueNotFromList').' ('.implode(', ', $param['list']).')');
					continue;
				}
			}
			elseif($param['type'] == 'integer')
			{
				if(!empty($value) && !preg_match('/^\d+$/i', $value))
				{
					$result_json['code'] = 1;
					$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => LL('OnlyNumbers'));
					continue;
				}

				$params[] = array(
					'guid' => $param['guid'],
					'name' => $param['name'],
					'value' => intval($value)
				);
				continue;
			}

			$params[] = array(
				'guid' => $param['guid'],
				'name' => $param['name'],
				'value' => $value
			);
		}

		if($result_json['code'])
		{
			$result_json['message'] = LL('NotAllFilled');
			return FALSE;
		}

		// $servers_list = '';
		// if(!empty($post_data['servers']))
		// {
			// $delimeter = '';
			// foreach($post_data['servers'] as $value)
			// {
				// $servers_list .= $delimeter.$value;
				// $delimeter = ',';
			// }
		// }

		$servers_list = NULL;
		if(!empty($post_data['servers']))
		{
			$servers_list = $post_data['servers'];
		}

		//echo '{"code": 0, "guid": "0062978a-518a-4ba9-9361-4eb88ea3e0b0", "message": "Debug placeholder save_uform. Remove this line later'.$runbook['guid'].json_encode($runbook_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'"}'; exit;

		// echo json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		// echo json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		// return;

		// log_db('Run: '.$playbook['name'], json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0);

		if((intval($playbook['flags']) & (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF)) == (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF))
		{
			$job_guid = $this->start_workflow($playbook['guid'], $params);
		}
		else
		{
			$job_guid = $this->start_playbook($playbook['guid'], $params);
		}

		if($job_guid !== FALSE)
		{
			if($this->core->db->put(rpv('INSERT INTO @runbooks_jobs (`date`, `pid`, `guid`, `uid`, `flags`) VALUES (NOW(), #, !, #, 0)', $playbook['id'], $job_guid, $this->core->UserAuth->get_id())))
			{
				$job_id = $this->core->db->last_id();

				foreach($params as &$param)
				{
					$value = $param['value'];
					if(strlen((string) $value) > 4096)
					{
						$value = substr((string) $value, 0, 4093).'...';
					}
					$this->core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $param['guid'], is_array($value) ? implode(', ', $value) : $value));
				}
			}
		}

		return $job_id;
	}

	private function parse_extra_vars($extra_vars)
	{
		if(empty($extra_vars)) {
			return [];
		}

		$parsed_data = json_decode($extra_vars, TRUE);
		if(json_last_error() !== JSON_ERROR_NONE)
		{
			$parsed_data = yaml_parse($extra_vars);
			if($yamlData === false) {
				return [];
			}
		}

		$params = array();

		foreach($parsed_data as $key => $value)
		{
			$params[] = [
				'name' => $key,
				'description' => '',
				'variable' => $key,
				'default' => $value,
				'flags' => RBF_FIELD_TYPE_STRING,
				'list' => NULL
			];
		}

		return $params;
	}

	private function param_type_to_flag($type)
	{
		switch($type)
		{
			case 'password': return RBF_FIELD_TYPE_PASSWORD;
			case 'integer': return RBF_FIELD_TYPE_NUMBER;
			case 'float': return RBF_FIELD_TYPE_NUMBER;
			case 'multiplechoice': return RBF_FIELD_TYPE_LIST;
			case 'multiselect': return RBF_FIELD_TYPE_FLAGS;
		}

		return RBF_FIELD_TYPE_STRING;
	}

	public function retrieve_survey_params($url)
	{
		$params = array();

		$result = $this->awx_api_request('GET', $url);
		if($result['spec'])
		{
            foreach ($result['spec'] as &$param) {
                $params[] = [
                    'name' => $param['question_name'],
                    'description' => $param['question_description'] ?? '',
                    'variable' => $param['variable'] ?? '',
                    'default' => $param['default'] ?? '',
                    'flags' => $this->param_type_to_flag($param['type']) | (intval($param['required']) ? RBF_FIELD_TYPE_REQUIRED : 0),
					'list' => empty($param['choices']) ? NULL : $param['choices']
                ];

            }
        }

        return $params;
	}

	public function retrieve_playbooks()
	{
		$playbooks = array();
        $urls = array(
			'job_template' => '/api/v2/job_templates/',
			'workflow_job_template' => '/api/v2/workflow_job_templates/'
		);

		foreach($urls as $type => $url)
		{
			do {
				$result = $this->awx_api_request('GET', $url);

				foreach($result['results'] as $template)
				{
					$params = (defined('AWX_DONT_PARSE_EXTRA_VARS') && AWX_DONT_PARSE_EXTRA_VARS) ? NULL : $this->parse_extra_vars($template['extra_vars']);

					if($template['survey_enabled'])
					{
						$survey_params = $this->retrieve_survey_params($template['related']['survey_spec']);

						$merged = array();
						foreach($params as $param)
						{
							$merged[$param['variable']] = $param;
						}

						foreach ($survey_params as $param)
						{
							$merged[$param['variable']] = $param;
						}

						$params = array_values($merged);
					}

					$playbooks[] = [
						'id' => (string) $template['id'],
						'name' => $template['name'],
						'description' => $template['description'] ?? '',
						'params' => $params,
						'type' => $type
					];
				}

				$url = $result['next'] ? $result['next'] : null;
			} while($url);
		}

        return $playbooks;
	}

	public function sync()
	{
		log_file('Starting sync...');
		log_file('Retrieve playbooks list...');
		$playbooks = $this->retrieve_playbooks();

		$total = 0;

		$folder_id = 0;
		if(!$this->core->db->select_ex($res, rpv("SELECT f.`id` FROM @runbooks_folders AS f WHERE f.`name` = 'Unassigned Ansible playbooks' AND (f.`flags` & ({%RBF_DELETED} | {%RBF_TYPE_CUSTOM})) = {%RBF_TYPE_CUSTOM} LIMIT 1")))
		{
			// Create folders:
			// - Ansible
			//   |- Unassigned Ansible playbooks

			//throw 'ERROR: Create under root level folder with name \'Ansible\' before start sync!';
			if(!$this->core->db->select_ex($res, rpv("SELECT f.`id` FROM @runbooks_folders AS f WHERE f.`name` = 'Ansible' AND (f.`flags` & ({%RBF_DELETED} | {%RBF_TYPE_CUSTOM})) = {%RBF_TYPE_CUSTOM} LIMIT 1")))
			{
				if(!$this->core->db->put(rpv("
						INSERT INTO @runbooks_folders (`guid`, `pid`, `name`, `flags`)
						VALUES (!, #, !, #)
					",
					0,
					0,
					'Ansible',
					RBF_TYPE_CUSTOM
				)))
				{
					throw 'ERROR: Create folder with name \'Unassigned Ansible playbooks\' before start sync!';
				}

				$folder_id = $this->core->db->last_id();
			}
			if(!$this->core->db->put(rpv("
					INSERT INTO @runbooks_folders (`guid`, `pid`, `name`, `flags`)
					VALUES (!, #, !, #)
				",
				0,
				$folder_id,
				'Unassigned Ansible playbooks',
				RBF_TYPE_CUSTOM | RBF_HIDED
			)))
			{
				throw 'ERROR: Create folder with name \'Unassigned Ansible playbooks\' before start sync!';
			}

			$folder_id = $this->core->db->last_id();
		}
		else
		{
			$folder_id = $res[0][0];
		}

		$this->core->db->put(rpv("UPDATE @runbooks SET `flags` = (`flags` | {%RBF_DELETED}) WHERE (`flags` & {%RBF_TYPE_ANSIBLE})"));

		foreach($playbooks as &$playbook)
		{
			$runbook_type = $playbook['type'] == 'job_template' ? RBF_TYPE_ANSIBLE : (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF);
			$runbook_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT r.`id`, r.`folder_id`, f.`flags` AS `folder_flags` FROM @runbooks AS r LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE r.`guid` = {s0} AND (r.`flags` & {d1}) = {d1} LIMIT 1", $playbook['id'], $runbook_type)))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks (`guid`, `folder_id`, `name`, `description`, `wiki_url`, `flags`)
						VALUES (#, #, !, !, !, #)
					",
					intval($playbook['id']),
					$folder_id,
					$playbook['name'],
					$playbook['description'],
					$playbook['wiki_url'],
					$runbook_type
				)))
				{
					$playbook_id = $this->core->db->last_id();
				}
			}
			else
			{
				$playbook_id = intval($res[0][0]);

				$this->core->db->put(rpv("
						UPDATE
							@runbooks
						SET
							`folder_id` = #,
							`name` = !,
							`description` = !,
							`wiki_url` = !,
							`flags` = (`flags` & ~{%RBF_DELETED})
						WHERE
							`id` = #
						LIMIT 1
					",
					(intval($res[0][2]) & RBF_DELETED) ? $folder_id : $res[0][1],
					$playbook['name'],
					$playbook['description'],
					$playbook['wiki_url'],
					$playbook_id
				));
			}

			if($playbook_id)
			{
				$this->core->db->put(rpv("DELETE FROM @runbooks_params WHERE `pid` = !", $playbook_id));

				foreach($playbook['params'] as &$params)
				{
					$extra_data_json = NULL;

					if(!empty($params['description']))
					{
						$extra_data_json['description'] = &$params['description'];
					}

					if(!empty($params['list']))
					{
						$extra_data_json['list'] = &$params['list'];
					}

					if(!empty($params['default']))
					{
						$extra_data_json['default'] = &$params['default'];
					}

					$this->core->db->put(rpv("
							INSERT INTO @runbooks_params (`pid`, `guid`, `name`, `extra_data_json`, `flags`)
							VALUES (!, !, !, !, #)
						",
						$playbook_id,
						$params['variable'],
						$params['name'],
						json_encode($extra_data_json, JSON_UNESCAPED_UNICODE),
						$params['flags']
					));
				}
			}

			$total++;
		}

		return $total;
	}

	public function sync_jobs($id)
	{
		$jobs_added = 0;

		$url = '/api/v2/jobs/';

		if($id)
		{
			$runbook = $this->core->Runbooks->get_runbook_by_id($id);
			if((intval($runbook['flags']) & (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF)) == (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF))
			{
				$urls = array(
					'workflow_job_template' => '/api/v2/workflow_job_templates/' . $runbook['guid'] . '/workflow_jobs/'
				);
			}
			else
			{
				$urls = array(
					'job_template' => '/api/v2/job_templates/' . $runbook['guid'] . '/jobs/'
				);
			}
		}
		else
		{
			$urls = array(
				'job_template' => '/api/v2/jobs/',
				'workflow_job_template' => '/api/v2/workflow_jobs/',
			);
		}

		foreach($urls as $type => $url)
		{
			$runbook_type = $type == 'job_template' ? RBF_TYPE_ANSIBLE : (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF);
			do {
				$result = $this->awx_api_request('GET', $url);

				foreach ($result['results'] as &$job) {
				if($this->core->db->select_ex($rb, rpv("SELECT r.`id` FROM @runbooks AS r WHERE r.`guid` = {s0} AND (r.`flags` & {d1}) = {d1} LIMIT 1", $job[$type], $runbook_type)))
					{
						if(!$this->core->db->select_ex($res, rpv("SELECT j.`id` FROM @runbooks_jobs AS j WHERE j.`guid` = ! AND j.`pid` = # LIMIT 1", $job['id'], $rb[0][0])))
						{
							$job_date = new DateTime($job['created']);
							if($job_date === FALSE)
							{
								$job_date = '0000-00-0000 00:00:00';
							}
							else
							{
								$job_date->setTimeZone(new DateTimeZone(date_default_timezone_get()));
								$job_date = $job_date->format('Y-m-d H:i:s');
							}

							if($this->core->db->put(rpv("
									INSERT INTO @runbooks_jobs (`date`, `pid`, `guid`, `uid`, `flags`)
									VALUES (!, #, !, NULL, 0x0000)
								",
								$job_date,
								$rb[0][0],
								$job['id']
							)))
							{
								// Load input params

								$job_id = $this->core->db->last_id();
								
								$extra_vars = json_decode($job['extra_vars'], TRUE);
								if($extra_vars !== FALSE)
								{
									foreach($extra_vars as $var => $value)
									{
										$this->core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $var, is_array($value) ? implode(', ', $value) : $value));
									}
								}

								$jobs_added++;
							}
						}
					}
				}

				$url = $result['next'] ? $result['next'] : null;
			} while($url);
		}

		return $jobs_added;
	}

	public function get_job($id)
	{
		if(!$this->core->db->select_assoc_ex($job, rpv('
			SELECT
				j.`id`,
				j.`guid`,
				DATE_FORMAT(j.`date`, \'%d.%m.%Y %H:%i:%s\') AS `run_date`,
				r.`name`,
				r.`id` AS `runbook_id`,
				r.`guid` AS `runbook_guid`,
				r.`folder_id`,
				r.`flags`,
				u.`login`
			FROM @runbooks_jobs AS j
			LEFT JOIN @runbooks AS r ON r.`id` = j.`pid`
			LEFT JOIN @users AS u ON u.`id` = j.`uid`
			WHERE
				j.`id` = #
				AND (r.`flags` & {%RBF_TYPE_ANSIBLE})
			LIMIT 1
		', $id)))
		{
			$this->core->error('Job '.$id.' not found!');
			return FALSE;
		}

		$job = &$job[0];
		
		if((intval($job['flags']) & (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF)) == (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF))
		{
			$url = '/api/v2/workflow_jobs/' . $job['guid'] . '/';
		}
		else
		{
			$url = '/api/v2/jobs/' . $job['guid'] . '/';
		}

		$job_data = $this->awx_api_request('GET', $url);

		$modified_date = new DateTime($job_data['finished']);
		if($modified_date === FALSE)
		{
			$modified_date = '00.00.0000 00:00:00';
		}
		else
		{
			$modified_date->setTimeZone(new DateTimeZone(date_default_timezone_get()));
			$modified_date = $modified_date->format('d.m.Y H:i:s');
		}

		$job_info = array(
			'id' => $job['id'],
			'guid' => $job['guid'],
			'name' => $job['name'],
			'run_date' => $job['run_date'],
			'runbook_id' => $job['runbook_id'],
			'runbook_guid' => $job['runbook_guid'],
			'folder_id' => $job['folder_id'],
			'user' => $job['login'],
			'status' => $job_data['status'],
			'modified_date' => $modified_date,
			'sid' => $job_data['launched_by']['id'],
			'sid_name' => $job_data['launched_by']['name'],
			'instances' => array()
		);

		// if($this->core->db->select_assoc_ex($job_params, rpv('SELECT jp.`guid`, jp.`value` FROM @runbooks_jobs_params AS jp WHERE jp.`pid` = #', $id)))
		// {
			// $job_info['input_params'] = array();
			// foreach($job_params as &$job_param)
			// {
				// $job_info['input_params'][] = array(
					// 'name' => $job_param['guid'],
					// 'value' => $job_param['value']
				// );
			// }
		// }

		if(isset($job_data['extra_vars']))
		{
			$extra_vars = json_decode($job_data['extra_vars'], TRUE);
			if($extra_vars !== FALSE)
			{
				if(!isset($job_info['input_params']))
				{
					$job_info['input_params'] = array();
				}

				foreach($extra_vars as $var => $value)
				{
					$job_info['input_params'][] = array(
						'name' => $var,
						'value' => is_array($value) ? implode(', ', $value) : $value
					);
				}
			}
		}

		if(isset($job_data['related']['stdout']))
		{
			$result = $this->awx_api_request('GET', $job_data['related']['stdout'] . '?format=ansi', NULL, TRUE);

			if($result !== FALSE && !empty($result))
			{
				$job_info['output'] = $this->ansi_to_html($result);
			}
		}

		if(isset($job_data['related']['workflow_nodes']))
		{
			$result = $this->awx_api_request('GET', $job_data['related']['workflow_nodes']);

			if(isset($result['results']))
			{
				$job_info['workflow_nodes'] = array();
				foreach($result['results'] as &$workflow_node)
				{
					$job_info['workflow_nodes'][] = array(
						'job_id' => $workflow_node['summary_fields']['job']['id'],
						'name' => $workflow_node['summary_fields']['unified_job_template']['name'],
						'status' => $workflow_node['summary_fields']['job']['status']
					);
				}
				usort($job_info['workflow_nodes'], 'cmp_job_id');
			}
		}

		return $job_info;
	}

	public function get_activity($guid)
	{
		$activity_info = array(
			'guid' => $guid,
			'params' => array()
		);

		$job_data = $this->awx_api_request('GET', '/api/v2/unified_jobs/?id=' . $guid . '');

		if(isset($job_data['results'][0]['extra_vars']))
		{
			$extra_vars = json_decode($job_data['results'][0]['extra_vars'], TRUE);
			if($extra_vars !== FALSE)
			{
				foreach($extra_vars as $var => $value)
				{
					$activity_info['params'][] = array(
						'name' => $var,
						'value' => is_array($value) ? implode(', ', $value) : $value
					);
				}
			}
		}

		if(isset($job_data['results'][0]['related']['stdout']))
		{
			$stdout = $this->awx_api_request('GET', $job_data['results'][0]['related']['stdout'] . '?format=ansi', NULL, TRUE);

			if($stdout !== FALSE && !empty($stdout))
			{
				$activity_info['output'] = $this->ansi_to_html($stdout);
			}
		}

		return $activity_info;
	}

	private function flags_to_type($flags)
	{
		switch($flags & (RBF_FIELD_TYPE_STRING | RBF_FIELD_TYPE_NUMBER | RBF_FIELD_TYPE_LIST | RBF_FIELD_TYPE_PASSWORD | RBF_FIELD_TYPE_FLAGS))
		{
			case RBF_FIELD_TYPE_NUMBER: return 'integer';
			case RBF_FIELD_TYPE_LIST: return 'list';
			case RBF_FIELD_TYPE_FLAGS: return 'multiselect';
		}
		return 'string';
	}

	public function get_playbook_params($id)
	{
		$this->core->db->select_assoc_ex($runbook_params, rpv("SELECT p.`guid`, p.`name`, p.`extra_data_json`, p.`flags` FROM @runbooks_params AS p WHERE p.`pid` = # ORDER BY p.`name`", $id));

		$form_fields = array();

		$i = 0;
		foreach($runbook_params as &$row)
		{
			$i++;

			$extra_data_json = array();
			if(!empty($row['extra_data_json']))
			{
				$extra_data_json = json_decode($row['extra_data_json'], TRUE);
			}

			$type = $this->flags_to_type(intval($row['flags']));

			$form_field = array(
				'type' => ($row['guid'] === 'who_websco') ? 'who' : $type,
				'required' => intval($row['flags']) & RBF_FIELD_TYPE_REQUIRED,
				'name' => $row['name'],
				'description' => $extra_data_json['description'] ?? '',
				'guid' => $row['guid'],
				'default' => $extra_data_json['default'] ?? ''
			);

			if($type == 'upload')
			{
				$form_field['accept'] = '';
				$form_field['max_size'] = 102400;
			}

			if(($type == 'list') || ($type == 'flags') || ($type == 'multiselect') || ($type == 'upload'))
			{
				if($type == 'upload')
				{
					$form_field['accept'] = $extra_data_json['list'] ?? '';
				}
				else
				{
					$form_field['list'] = $extra_data_json['list'] ?? '';
				}
			}

			$form_fields[] = $form_field;
		}

		return $form_fields;
	}

	public function get_runbook_form($id, $job_id)
	{
		$playbook = $this->core->Runbooks->get_runbook_by_id($id);

		$result_json = array(
			'code' => 0,
			'message' => '',
			'guid' => $playbook['guid'],
			'title' => $playbook['name'],
			'description' => $playbook['description'],
			'wiki_url' => $playbook['wiki_url'],
			'action' => 'runbook_start',
			'fields' => array(
				/*
				array(
					'type' => 'hidden',
					'name' => 'action',
					'value' => 'start_runbook'
				),
				*/
				array(
					'type' => 'hidden',
					'name' => 'id',
					'value' => $playbook['id']
				)
			)
		);

		$params = $this->get_playbook_params($playbook['id']);

		$job_params = NULL;

		if(!empty($job_id))
		{
			if(!$this->core->db->select_assoc_ex($job_params, rpv('SELECT jp.`guid`, jp.`value` FROM @runbooks_jobs_params AS jp WHERE jp.`pid` = #', $job_id)))
			{
				if($this->core->db->select_assoc_ex($job, rpv('SELECT j.`guid` FROM @runbooks_jobs AS j WHERE j.`id` = #', $job_id)))
				{
					// $job_params = $this->retrieve_job($job[0]['guid']);
					if((intval($playbook['flags']) & (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF)) == (RBF_TYPE_ANSIBLE | RBF_TYPE_ANSIBLE_WF))
					{
						$url = '/api/v2/workflow_jobs/' . $job[0]['guid'] . '/';
					}
					else
					{
						$url = '/api/v2/jobs/' . $job[0]['guid'] . '/';
					}
						
					$job = $this->awx_api_request('GET', $url);

					$extra_vars = json_decode($job['extra_vars'], TRUE);
					if($extra_vars !== FALSE)
					{
						$job_params = array();
						foreach($extra_vars as $var => $value)
						{
							$job_params[] = array(
								'guid' => $var,
								'value' => is_array($value) ? implode(', ', $value) : $value
							);
							$this->core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $var, is_array($value) ? implode(', ', $value) : $value));
						}
					}
				}
			}
		}

		foreach($params as &$param)
		{
			 $field = array(
				'type' => $param['type'],
				'name' => 'param['.$param['guid'].']',
				'title' => $param['name'],
				'description' => $param['description'],
				'value' => $job_params ? '' : $param['default']
			);

			if(($param['type'] == 'list') || ($param['type'] == 'flags'))
			{
				$field['list'] = $param['list'];
				$field['values'] = $param['list'];
			}
			elseif($param['type'] == 'multiselect')
			{
				$field['list'] = $param['list'];
				$field['values'] = $param['list'];
				if(!$job_params) $field['selected'] = (empty($param['default'])) ? [] : array_map('trim', explode("\n", $param['default']));
			}
			elseif(($param['type'] == 'samaccountname'))
			{
				$field['autocomplete'] = 'complete_account';
			}
			elseif(($param['type'] == 'computer'))
			{
				$field['autocomplete'] = 'complete_computer';
			}
			elseif(($param['type'] == 'group'))
			{
				$field['autocomplete'] = 'complete_group_sam';
			}
			elseif(($param['type'] == 'mail'))
			{
				$field['autocomplete'] = 'complete_mail';
			}
			elseif(($param['type'] == 'upload'))
			{
				$field['name'] = 'param_'.$param['guid'];
				$field['max_size'] = $param['max_size'];
				$field['accept'] = $param['accept'];
			}
			elseif(($param['type'] == 'who'))
			{
				continue;
			}

			if($job_params)
			{
				foreach($job_params as &$row)
				{
					if($row['guid'] == $param['guid'])
					{
						if($param['type'] == 'multiselect')
						{
							$field['selected'] = empty($row['value']) ? [] : array_map('trim', explode(',', $row['value']));;
						}
						else
						{
							$field['value'] = $row['value'];
						}
						break;
					}
				}
			}

			$result_json['fields'][] = $field;
		}

		return $result_json;
	}

	static function ansi_to_html($ansi_text) {
		$ansi_text = htmlspecialchars($ansi_text);

		// Replace ANSI colors with HTML styles
		$patterns = [
			// Reset styles
			'/\033\[0m/i' => '</span>',

			// Normal colors (replaced with darker shades)
			'/\033\[0;30m/i' => '<span style="color: #aaaaaa">',     // Dark gray (instead of black)
			'/\033\[0;31m/i' => '<span style="color: #ff6b6b">',     // Red
			'/\033\[0;32m/i' => '<span style="color: #5cdb5c">',     // Green
			'/\033\[0;33m/i' => '<span style="color: #f0e68c">',     // Yellow (closer to khaki)
			'/\033\[0;34m/i' => '<span style="color: #6b8cff">',     // Blue
			'/\033\[0;35m/i' => '<span style="color: #d98cff">',     // Magenta
			'/\033\[0;36m/i' => '<span style="color: #7fffd4">',     // Cyan (aquamarine)
			'/\033\[0;37m/i' => '<span style="color: #f8f8f8">',     // Light gray (almost white)

			// Bright colors (bold)
			'/\033\[1;30m/i' => '<span style="color: #777777">',     // Gray
			'/\033\[1;31m/i' => '<span style="color: #ff8787">',     // Bright red
			'/\033\[1;32m/i' => '<span style="color: #7be87b">',     // Bright green
			'/\033\[1;33m/i' => '<span style="color: #ffeb7b">',     // Bright yellow
			'/\033\[1;34m/i' => '<span style="color: #7b9cff">',     // Bright blue
			'/\033\[1;35m/i' => '<span style="color: #f07bff">',     // Bright magenta
			'/\033\[1;36m/i' => '<span style="color: #7bffff">',     // Bright cyan
			'/\033\[1;37m/i' => '<span style="color: #ffffff">',      // White

			// Additional styles
			'/\033\[1m/i'  => '<span style="font-weight: bold">',    // Bold
			'/\033\[3m/i'  => '<span style="font-style: italic">',   // Italic
			'/\033\[4m/i'  => '<span style="text-decoration: underline">', // Underline
		];

		// Apply replacements
		$html = preg_replace(array_keys($patterns), array_values($patterns), $ansi_text);

		// Clean up remaining ANSI codes (if any)
		$html = preg_replace('/\033\[[0-9;]*m/', '', $html);

		// Dark background + monospace font
		return '<pre style="background: #1e1e1e; color: #e0e0e0; padding: 5px; border-radius: 3px;">' . $html . '</pre>';
	}
}

function cmp_job_id($a, $b)
{
	$a = intval($a['job_id']);
	$b = intval($b['job_id']);

	if($a == $b)
	{
		return 0;
	}

	return ($a < $b) ? -1 : 1;
}
