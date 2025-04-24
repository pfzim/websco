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
	This class is intended for accessing the Microsoft System Center
	Orchestrator web service to get a list of ranbooks and launch them.
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

	public function start_playbook($id, $params)
	{
		$parameters = array();
		
		if(!empty($params))
		{
			foreach($params as &$param)
			{
				$parameters[$param['guid']] = $param['value'];
			}
		}

        $result = $this->awx_api_request('POST', '/api/v2/job_templates/' . $id . '/launch/', ['extra_vars' => !empty($parameters) ? json_encode($parameters, JSON_NUMERIC_CHECK) : '{}']);

        return $result['job'] ? $result['job'] : false;
	}

	/**
	 Stop job.

		\param [in] $guid   - job ID

		\return - TRUE | FALSE
	*/

	public function job_cancel($job_id)
	{
		return ($this->awx_api_request('POST', '/api/v2/jobs/' . $job_id . '/cancel/', NULL, TRUE) !== FALSE);
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

	public function retrieve_survey_params($id)
	{
		$params = array();

		$result = $this->awx_api_request('GET', '/api/v2/job_templates/' . $id . '/survey_spec/');
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
        $url = '/api/v2/job_templates/';

        do {
            $result = $this->awx_api_request('GET', $url);
            
            foreach ($result['results'] as $template) {
                $playbooks[] = [
                    'id' => (string) $template['id'],
                    'name' => $template['name'],
                    'description' => $template['description'] ?? '',
                    'params' => $template['survey_enabled'] ? $this->retrieve_survey_params(intval($template['id'])) : NULL,
                    'type' => 'job_template'
                ];
            }
            
            $url = $result['next'] ? $result['next'] : null;
        } while($url);

        return $playbooks;
	}
	
	public function sync()
	{
		log_file('Starting sync...');
		log_file('Retrieve playbooks list...');
		$playbooks = $this->retrieve_playbooks();

		$total = 0;

		$folder_id = 0;
		if(!$this->core->db->select_ex($res, rpv("SELECT f.`id` FROM @runbooks_folders AS f WHERE f.`name` = 'Ansible' AND f.`pid` = 0 LIMIT 1")))
		{
			//throw 'ERROR: Create under root level folder with name \'Ansible\' before start sync!';
			if($this->core->db->put(rpv("
					INSERT INTO @runbooks_folders (`guid`, `pid`, `name`, `flags`)
					VALUES (!, #, !, #)
				",
				0,
				0,
				'Ansible',
				RBF_TYPE_ANSIBLE
			)))
			{
				$folder_id = $this->core->db->last_id();
			}
		}
		else
		{
			$folder_id = $res[0][0];
		}

		$this->core->db->put(rpv("UPDATE @runbooks SET `flags` = (`flags` | {%RBF_DELETED}) WHERE (`flags` & {%RBF_TYPE_ANSIBLE})"));
		
		foreach($playbooks as &$playbook)
		{
			$runbook_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT r.`id` FROM @runbooks AS r WHERE r.`guid` = ! LIMIT 1", $playbook['id'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks (`guid`, `folder_id`, `name`, `description`, `wiki_url`, `flags`)
						VALUES (!, #, !, !, !, #)
					",
					intval($playbook['id']),
					$folder_id,
					$playbook['name'],
					$playbook['description'],
					$playbook['wiki_url'],
					RBF_TYPE_ANSIBLE
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
							`id` = !
						LIMIT 1
					",
					$folder_id,
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

	public function get_playbook($id)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks AS r LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE r.`id` = # LIMIT 1", $id)))
		{
			$this->core->error('Runbook '.$id.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_playbook_by_job_id($id)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks_jobs AS j LEFT JOIN @runbooks AS r ON r.`id` = j.`pid` LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE j.`id` = # LIMIT 1", $id)))
		{
			$this->core->error('Job '.$id.' not found!');
			return FALSE;
		}

		return $runbook[0];
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

		$result = $this->awx_api_request('GET', '/api/v2/jobs/' . $job['guid'] . '/');

		$modified_date = DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, $result['modified'], NULL);
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
			'status' => $result['status'],
			'modified_date' => $modified_date,
			'sid' => $result['launched_by']['id'],
			'sid_name' => $result['launched_by']['name'],
			'instances' => array()
		);

		$result = $this->awx_api_request('GET', '/api/v2/jobs/' . $job['guid'] . '/stdout/?format=txt', NULL, TRUE);
		// $instances = $this->retrieve_job_instances($job['guid']);

		if($result !== FALSE)
		{
			$job_info['output'] = $result;
		}

		return $job_info;
	}

	private function flags_to_type($flags)
	{
		switch($flags & (RBF_FIELD_TYPE_STRING | RBF_FIELD_TYPE_NUMBER | RBF_FIELD_TYPE_LIST | RBF_FIELD_TYPE_PASSWORD | RBF_FIELD_TYPE_FLAGS))
		{
			case RBF_FIELD_TYPE_NUMBER: return 'number';
			case RBF_FIELD_TYPE_LIST: return 'list';
			case RBF_FIELD_TYPE_FLAGS: return 'flags';
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
				'type' => $type,
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

			if(($type == 'list') || ($type == 'flags') || ($type == 'upload'))
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
}

/*
  Добавить колонки в таблицу runbook_params - choises, для pid не использовать GUID
  Избавится от использования GUID в ссылках
  Добавить флаги RBF_TYPE_SCORCH, RBF_TYPE_ANSIBLE
  Подумать над папками для Ansible
*/
