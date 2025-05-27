<?php
/*
    Runbooks2022 class - This class is intended for accessing the Microsoft
	System Center Orchestrator 2022 web service to get a list of runbooks and
	launch them.
    Copyright (C) 2024 Dmitry V. Zimin

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

class Orchestrator2022
{
	private $core;
	private $orchestrator_url;
	private $orchestrator_user;
	private $orchestrator_passwd;

	function __construct(&$core)
	{
		$this->core = &$core;

		$this->orchestrator_url = ORCHESTRATOR2022_URL;
		$this->orchestrator_user = ORCHESTRATOR2022_USER;
		$this->orchestrator_passwd = ORCHESTRATOR2022_PASSWD;
	}

	public function get_http_json($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, false);

		if(defined('USE_GSSAPI') && USE_GSSAPI)
		{
			curl_setopt($ch, CURLOPT_GSSAPI_DELEGATION, CURLGSSAPI_DELEGATION_FLAG);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_GSSNEGOTIATE);
			curl_setopt($ch, CURLOPT_USERPWD, ":");
		}
		else
		{
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			curl_setopt($ch, CURLOPT_USERPWD, $this->orchestrator_user.':'.$this->orchestrator_passwd);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json, text/plain, */*'));


		$output = curl_exec($ch);
		$result = curl_getinfo($ch);

		//echo $output;
		//log_file($url."\n".$output."\n\n\n");

		curl_close($ch);

		if(intval($result['http_code']) != 200)
		{
			$this->core->error('Unexpected HTTP '.$result['http_code'].' response code!');
			return FALSE;
		}

		$json = @json_decode($output, TRUE);
		if($json === NULL)
		{
			$this->core->error('JSON parse error!');
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

	public function start_runbook($guid, $params, $servers = NULL)
	{
		$parameters = array();

		if(!empty($params))
		{
			foreach($params as &$param)
			{
				$parameters[] = array(
					'Name' => $param['name'],
					'Value' => $param['value']
				);
			}
		}

		$request = json_encode(array(
			'RunbookId'			=> $guid,
			'RunbookServers' 	=> &$servers,
			'Parameters'		=> &$parameters
			//,'CreatedBy'			=> NULL
		), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		log_file($request);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->orchestrator_url.'/Jobs');
		curl_setopt($ch, CURLOPT_POST, true);

		if(defined('USE_GSSAPI') && USE_GSSAPI)
		{
			curl_setopt($ch, CURLOPT_GSSAPI_DELEGATION, CURLGSSAPI_DELEGATION_FLAG);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_GSSNEGOTIATE);
			curl_setopt($ch, CURLOPT_USERPWD, ":");
		}
		else
		{
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			curl_setopt($ch, CURLOPT_USERPWD, $this->orchestrator_user.':'.$this->orchestrator_passwd);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json, text/plain, */*'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);


		$output = curl_exec($ch);
		$result = curl_getinfo($ch);

		//echo $output;
		//log_file($output);

		if(intval($result['http_code']) != 201)
		{
			log_file('ERROR: GET '.$this->orchestrator_url.'/Jobs'."\n".$output."\n\n");
			/*
				<error xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
				  <code></code>
				  <message xml:lang="ru-RU">The requested operation requires Publish permissions on the Runbook</message>
				</error>
			*/
			return FALSE;
		}

		$json_data = @json_decode($output, TRUE);
		if($json_data === NULL)
		{
			return FALSE;
		}

		return $json_data['Id'];
	}

	/**
	 Stop job.

		\param [in] $guid   - job ID

		\return - TRUE | FALSE
	*/

	public function job_cancel($job_id, $job_guid)
	{
		$request = json_encode(array(
			'Id'			=> $job_guid
		), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		log_file($request);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->orchestrator_url.'/Jobs/'.$job_guid);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');

		if(defined('USE_GSSAPI') && USE_GSSAPI)
		{
			curl_setopt($ch, CURLOPT_GSSAPI_DELEGATION, CURLGSSAPI_DELEGATION_FLAG);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_GSSNEGOTIATE);
			curl_setopt($ch, CURLOPT_USERPWD, ":");
		}
		else
		{
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			curl_setopt($ch, CURLOPT_USERPWD, $this->orchestrator_user.':'.$this->orchestrator_passwd);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json, text/plain, */*'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);


		$output = curl_exec($ch);
		$result = curl_getinfo($ch);

		//echo $output;
		//log_file($output);

		if(intval($result['http_code']) != 200)
		{
			log_file('ERROR: GET '.$this->orchestrator_url.'/Jobs/'.$job_guid."\n".$output."\n\n");
			/*
				<error xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
				  <code></code>
				  <message xml:lang="ru-RU">The requested operation requires Publish permissions on the Runbook</message>
				</error>
			*/
			return FALSE;
		}

		$json_data = @json_decode($output, TRUE);
		if($json_data === NULL)
		{
			return FALSE;
		}

		return isset($json_data['Id']) ? $json_data['Id'] : FALSE;
	}

	public function parse_form_and_start_runbook($post_data, &$result_json)
	{
		$result_json = array(
			'code' => 0,
			'message' => '',
			'errors' => array()
		);

		$params = array();

		$runbook = $this->core->Runbooks->get_runbook_by_id($post_data['id']);
		$runbook_params = $this->get_runbook_params($post_data['id']);

		//log_file(json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

		foreach($runbook_params as &$param)
		{
			$value = '';
			//$params[$param['guid']] = $value;

			if($param['type'] == 'who')
			{
				$params[] = array(
					'guid' => $param['guid'],
					'name' => $param['name_original'],
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
						'name' => $param['name_original'],
						'value' => strval($flags)
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
			}

			$params[] = array(
				'guid' => $param['guid'],
				'name' => $param['name_original'],
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

		log_db('Run: '.$runbook['name'], json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0);

		$job_guid = $this->start_runbook($runbook['guid'], $params, $servers_list);

		if($job_guid === FALSE)
		{
			$result_json['code'] = 1;
			$result_json['message'] = 'API ERROR: Failed to start runbook!';
			return FALSE;
		}

		$job_id = FALSE;

		if($this->core->db->put(rpv('INSERT INTO @runbooks_jobs (`date`, `pid`, `guid`, `uid`, `flags`) VALUES (NOW(), #, !, #, 0)', $runbook['id'], $job_guid, $this->core->UserAuth->get_id())))
		{
			$job_id = $this->core->db->last_id();

			foreach($params as &$param)
			{
				$value = $param['value'];
				if(strlen($value) > 4096)
				{
					$value = substr($value, 0, 4093).'...';
				}
				$this->core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $param['guid'], $value));
			}
		}

		return $job_id;
	}

	public function parse_form_and_start_runbook($post_data, &$result_json)
	{
		$result_json = array(
			'code' => 0,
			'message' => '',
			'errors' => array()
		);

		$params = array();

		$runbook = $this->core->Runbooks->get_runbook_by_id($post_data['id']);
		$runbook_params = $this->get_runbook_params($post_data['id']);

		//log_file(json_encode($post_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

		foreach($runbook_params as &$param)
		{
			$value = '';
			//$params[$param['guid']] = $value;

			if($param['type'] == 'who')
			{
				$params[] = array(
					'guid' => $param['guid'],
					'name' => $param['name_original'],
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
						'name' => $param['name_original'],
						'value' => strval($flags)
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
			}

			$params[] = array(
				'guid' => $param['guid'],
				'name' => $param['name_original'],
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

		log_db('Run: '.$runbook['name'], json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0);

		$job_guid = $this->start_runbook($runbook['guid'], $params, $servers_list);

		if($job_guid !== FALSE)
		{
			if($this->core->db->put(rpv('INSERT INTO @runbooks_jobs (`date`, `pid`, `guid`, `uid`, `flags`) VALUES (NOW(), #, !, #, 0)', $runbook['id'], $job_guid, $this->core->UserAuth->get_id())))
			{
				$job_id = $this->core->db->last_id();

				foreach($params as &$param)
				{
					$value = $param['value'];
					if(strlen($value) > 4096)
					{
						$value = substr($value, 0, 4093).'...';
					}
					$this->core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $param['guid'], $value));
				}
			}
		}

		return $job_id;
	}

	/**
	 Get job instances list.

		\param [in] $guid   - job ID

		\return - array of job instances ID and it statuses
	*/

	public function retrieve_job_instances($guid)
	{
		$json_data = $this->get_http_json($this->orchestrator_url.'/RunbookInstances?$filter=JobID%20eq%20'.$guid);

		$instances = array();

		foreach($json_data['value'] as $properties)
		{
			$instance = array(
				'guid' => (string) $properties['Id'],
				'status' => (string) $properties['Status'],
				'params_in' => array(),
				'params_out' => array(),
				'activities' => array()
			);

			$sub_json_data = $this->get_http_json($this->orchestrator_url.'/RunbookInstanceParameters?$filter=RunbookInstanceId%20eq%20'.$instance['guid']);

			foreach($sub_json_data['value'] as $sub_properties)
			{
				$activity = array(
					'guid' => (string) $sub_properties['RunbookParameterId'],
					'name' => (string) $sub_properties['Name'],
					'value' => (string) $sub_properties['Value']
				);

				if(((string) $sub_properties['Direction']) == 'Out')
				{
					$instance['params_out'][] = $activity;
				}
				else
				{
					$instance['params_in'][] = $activity;
				}
			}

			$sub_json_data = $this->get_http_json($this->orchestrator_url.'/ActivityInstances/latestOf(runbookInstanceId='.$instance['guid'].')');

			foreach($sub_json_data['value'] as $sub_properties)
			{
				$activity = array(
					'instance_id' =>  (string) $sub_properties['Id'],
					'guid' => (string) $sub_properties['ActivityId'],
					'name' => '',
					'sequence' => (string) $sub_properties['SequenceNumber'],
					'status' => (string) $sub_properties['Status']
				);

				if($this->core->db->select_ex($name, rpv('SELECT a.`id`, a.`name` FROM @runbooks_activities AS a WHERE a.`guid` = ! AND (a.`flags` & ({%RBF_TYPE_SCO2022} | {%RBF_DELETED})) = {%RBF_TYPE_SCO2022} LIMIT 1', (string) $sub_properties['ActivityId'])))
				{
					$activity['id'] = $name[0][0];
					$activity['name'] = $name[0][1];
				}

				$instance['activities'][] = $activity;
			}

			usort($instance['params_in'], 'cmp_name');
			usort($instance['activities'], 'cmp_sequence');
			usort($instance['params_out'], 'cmp_name');

			if(!$this->core->db->select_assoc_ex($job, rpv('SELECT j.id, COUNT(jp.`guid`) AS `params_count` FROM @runbooks_jobs AS j LEFT JOIN @runbooks_jobs_params AS jp ON jp.`pid` = j.`id` WHERE j.`guid` = ! LIMIT 1', $guid)) || (intval($job[0]['params_count']) == 0))
			{
				foreach($instance['params_in'] as &$param)
				{
					$this->core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job[0]['id'], $param['guid'], $param['value']));
				}
			}

			$instances[] = $instance;
		}
		return $instances;
	}

	public function retrieve_job_first_instance_input_params($guid)
	{
		$json_data = $this->get_http_json($this->orchestrator_url.'/RunbookInstances?$top=1&$skip=0&$count=true&$filter=JobID%20eq%20'.$guid);

		$params_in = array();

		foreach($json_data['value'] as $properties)
		{
			$sub_json_data = $this->get_http_json($this->orchestrator_url.'/RunbookInstanceParameters?$filter=RunbookInstanceId%20eq%20'.((string) $properties['Id']));

			foreach($sub_json_data['value'] as $sub_properties)
			{
				if(((string) $sub_properties['Direction']) == 'In')
				{
					$params_in[] = array(
						'guid' => (string) $sub_properties['RunbookParameterId'],
						'value' => (string) $sub_properties['Value']
					);
				}
			}

			break;
		}

		return $params_in;
	}

	public function retrieve_activity_data($guid)
	{
		$json_data = $this->get_http_json($this->orchestrator_url.'/ActivityInstanceData?$filter=ActivityInstanceId%20eq%20'.$guid);

		$params = array();

		foreach($json_data['value'] as $properties)
		{
			$params[] = array(
				'name' => (string) $properties['Name'],
				'value' => (string) $properties['Value']
			);
		}

		return $params;
	}

	public function retrieve_folders()
	{
		$folders = array();

		$json_data = $this->get_http_json($this->orchestrator_url.'/folders');

		foreach($json_data['value'] as $properties)
		{
			$folder = array(
				'guid' => (string) $properties['Id'],
				'name' => (string) $properties['Name'],
				'pid' => (string) $properties['ParentId']
			);

			$folders[] = $folder;
		}

		//echo $output;
		//echo json_encode($runbooks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		return $folders;
	}

	public function retrieve_servers()
	{
		$servers = array();

		$json_data = $this->get_http_json($this->orchestrator_url.'/runbookservers');

		foreach($json_data['value'] as $properties)
		{
			$server = array(
				'guid' => (string) $properties['Id'],
				'name' => (string) $properties['Name']
			);

			$servers[] = $server;
		}

		//echo $output;
		//echo json_encode($runbooks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		return $servers;
	}

	public function retrieve_jobs($guid)
	{
		$jobs = array();
		$skip = 0;
		$total = 0;

		$job_filter = '';

		if(!empty($guid))
		{
			$job_filter = '&$filter=RunbookId%20eq%20'.$guid;
		}

		do
		{
			$json_data = $this->get_http_json($this->orchestrator_url.'/Jobs?$count=true&$top=50&$skip='.$skip.$job_filter);

			$total = intval($json_data['@odata.count']);

			foreach($json_data['value'] as $properties)
			{
				$job = array(
					'guid' => (string) $properties['Id'],
					'pid' => (string) $properties['RunbookId'],
					'date' => (string) $properties['CreationTime']
				);

				$jobs[] = $job;

				//break;
				$skip++;
			}
			//break;
		}
		while($skip < $total);

		//echo $output;
		//echo json_encode($runbooks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		return $jobs;
	}

	public function retrieve_activities()
	{
		$activities = array();
		$skip = 0;
		$total = 0;

		do
		{
			$json_data = $this->get_http_json($this->orchestrator_url.'/Activities?$count=true&$top=50&$skip='.$skip);

			$total = intval($json_data['@odata.count']);

			foreach($json_data['value'] as $properties)
			{
				/*
					<d:Id m:type="Edm.Guid">1423fe6f-7e0e-4e0a-bfd1-00af163cd522</d:Id>
					<d:RunbookId m:type="Edm.Guid">b2862173-3bf0-4787-8f76-a04294ab1f55</d:RunbookId>
					<d:Name>Запись результатов в базу</d:Name>
					<d:TypeName>Run .Net Script</d:TypeName>
					<d:Description m:null="true" />
					<d:CreationTime m:type="Edm.DateTime">2021-05-26T12:22:57.977</d:CreationTime>
					<d:CreatedBy>S-1-5-500</d:CreatedBy>
					<d:LastModifiedTime m:type="Edm.DateTime">2021-08-16T08:15:28</d:LastModifiedTime>
					<d:LastModifiedBy>S-1-5-21-3119835862-1306673144-2631644997-1160710</d:LastModifiedBy>
					<d:Enabled m:type="Edm.Boolean">true</d:Enabled>
					<d:PositionX m:type="Edm.Int32">-129</d:PositionX>
					<d:PositionY m:type="Edm.Int32">77</d:PositionY>
				*/

				$activities[] = array(
					'guid' => (string) $properties['Id'],
					'name' => (string) $properties['Name']
				);

				//break;
				$skip++;
			}
		}
		while($skip < $total);

		//echo $output;
		//echo json_encode($runbooks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		return $activities;
	}

	public function retrieve_runbooks()
	{
		$runbooks = array();

		$json_data = $this->get_http_json($this->orchestrator_url.'/Runbooks');

		foreach($json_data['value'] as $properties)
		{
			$description = (string) $properties['Description'];
			$wiki_url = '';

			if(preg_match('#\[wiki\](.*?)\[/wiki\]#i', $description, $matches))
			{
				$wiki_url = trim($matches[1]);
				$description = preg_replace('#\s*\[wiki\](.*?)\[/wiki\]#i', '', $description, 1);
			}

			$runbook = array(
				'guid' => (string) $properties['Id'],
				'name' => (string) $properties['Name'],
				'description' => $description,
				'wiki_url' => $wiki_url,
				'folder_id' => (string) $properties['FolderId'],
				'path' => (string) $properties['Path'],
				'params' => array()
			);

			$json_runbook_params = $this->get_http_json($this->orchestrator_url.'/RunbookParameters?$filter=RunbookId%20eq%20' . $runbook['guid']);

			if($json_runbook_params !== FALSE)
			{
				foreach($json_runbook_params['value'] as $params_entry)
				{
					if($params_entry['Direction'] == 'In')
					{
						$runbook['params'][] = array(
							'guid' =>  (string) $params_entry['Id'],
							'name' => (string) $params_entry['Name']
						);
					}
				}
			}

			$runbooks[] = $runbook;
		}

		//echo $output;
		//echo json_encode($runbooks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		return $runbooks;
	}

	public function sync()
	{
		log_file('Starting sync...');
		log_file('Retrieve servers list...');
		$servers = $this->retrieve_servers();
		log_file('Retrieve folders list...');
		$folders = $this->retrieve_folders();
		log_file('Retrieve activities list...');
		$activities = $this->retrieve_activities();
		log_file('Retrieve runbooks list...');
		$runbooks = $this->retrieve_runbooks();

		$this->core->db->put(rpv("UPDATE @runbooks SET `flags` = (`flags` | {%RBF_DELETED}) WHERE (`flags` & {%RBF_TYPE_SCO2022})"));
		$this->core->db->put(rpv("UPDATE @runbooks_folders SET `flags` = (`flags` | {%RBF_DELETED}) WHERE (`flags` & {%RBF_TYPE_SCO2022})"));
		$this->core->db->put(rpv("UPDATE @runbooks_activities SET `flags` = (`flags` | {%RBF_DELETED}) WHERE (`flags` & {%RBF_TYPE_SCO2022})"));
		$this->core->db->put(rpv("UPDATE @runbooks_servers SET `flags` = (`flags` | {%RBF_DELETED}) WHERE (`flags` & {%RBF_TYPE_SCO2022})"));

		$total = 0;

		//$servers = $this->retrieve_servers();

		foreach($servers as &$server)
		{
			//echo $folder['guid']."\r\n";
			$server_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT s.`guid` FROM @runbooks_servers AS s WHERE s.`guid` = ! AND (s.`flags` & {%RBF_TYPE_SCO2022}) LIMIT 1", $server['guid'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks_servers (`guid`, `name`, `flags`)
						VALUES (!, !, #)
					",
					$server['guid'],
					$server['name'],
					RBF_TYPE_SCO2022
				)))
				{
					$server_id = $this->core->db->last_id();
				}
			}
			else
			{
				$this->core->db->put(rpv("
						UPDATE
							@runbooks_servers
						SET
							`name` = !,
							`flags` = (`flags` & ~{%RBF_DELETED})
						WHERE
							`guid` = !
						LIMIT 1
					",
					$server['name'],
					$res[0][0]
				));

				$server_id = $res[0][0];
			}
		}

		unset($servers);

		//$folders = $this->retrieve_folders();

		foreach($folders as &$folder)
		{
			//echo $folder['guid']."\r\n";
			$folder_pid = 0;
			if($this->core->db->select_ex($res, rpv("SELECT f.`id` FROM @runbooks_folders AS f WHERE f.`guid` = ! AND (f.`flags` & ({%RBF_TYPE_SCO2022} | {%RBF_DELETED})) = {%RBF_TYPE_SCO2022} LIMIT 1", $folder['pid'])))
			{
				$folder_pid = $res[0][0];
			}

			$folder_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT f.`id` FROM @runbooks_folders AS f WHERE f.`guid` = ! AND (f.`flags` & {%RBF_TYPE_SCO2022}) LIMIT 1", $folder['guid'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks_folders (`guid`, `pid`, `name`, `flags`)
						VALUES (!, #, !, #)
					",
					$folder['guid'],
					$folder_pid,
					empty($folder['name']) ? (($folder['guid'] === '00000000-0000-0000-0000-000000000000') ? 'Orchestrator 2022' : '(undefined folder name)') : $folder['name'],
					RBF_TYPE_SCO2022
				)))
				{
					$folder_id = $this->core->db->last_id();
				}
			}
			else
			{
				$folder_id = $res[0][0];

				$this->core->db->put(rpv("
						UPDATE
							@runbooks_folders
						SET
							`pid` = #,
							`name` = !,
							`flags` = (`flags` & ~{%RBF_DELETED})
						WHERE
							`id` = #
						LIMIT 1
					",
					$folder_pid,
					empty($folder['name']) ? (($folder['guid'] === '00000000-0000-0000-0000-000000000000') ? 'Orchestrator 2022' : '(undefined folder name)') : $folder['name'],
					$folder_id
				));
			}
		}

		unset($folders);

		//$activities = $this->retrieve_activities();

		foreach($activities as &$activity)
		{
			$activity_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT a.`id`, a.`guid` FROM @runbooks_activities AS a WHERE a.`guid` = ! AND a.`flags` & {%RBF_TYPE_SCO2022} LIMIT 1", $activity['guid'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks_activities (`guid`, `name`, `flags`)
						VALUES (!, !, #)
					",
					$activity['guid'],
					$activity['name'],
					RBF_TYPE_SCO2022
				)))
				{
					$activity_id = $this->core->db->last_id();
				}
			}
			else
			{
				$this->core->db->put(rpv("
						UPDATE
							@runbooks_activities
						SET
							`name` = !,
							`flags` = (`flags` & ~{%RBF_DELETED})
						WHERE
							`id` = #
						LIMIT 1
					",
					$activity['name'],
					$res[0][0]
				));

				$activity_id = $res[0][0];
			}
		}

		unset($activities);

		//$runbooks = $this->retrieve_runbooks();

		foreach($runbooks as &$runbook)
		{
			$folder_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT f.`id` FROM @runbooks_folders AS f WHERE f.`guid` = ! AND (f.`flags` & ({%RBF_TYPE_SCO2022} | {%RBF_DELETED})) = {%RBF_TYPE_SCO2022} LIMIT 1", $runbook['folder_id'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks_folders (`guid`, `pid`, `name`, `flags`)
						VALUES (!, #, !, #)
					",
					$runbook['folder_id'],
					0,
					$runbook['path'],
					RBF_TYPE_SCO2022
				)))
				{
					$folder_id = $this->core->db->last_id();
				}
			}
			else
			{
				$folder_id = $res[0][0];
			}

			$runbook_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT r.`id` FROM @runbooks AS r WHERE (r.`flags` & {%RBF_TYPE_SCO2022}) AND r.`guid` = ! LIMIT 1", $runbook['guid'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks (`guid`, `folder_id`, `name`, `description`, `wiki_url`, `flags`)
						VALUES (!, #, !, !, !, #)
					",
					$runbook['guid'],
					$folder_id,
					$runbook['name'],
					$runbook['description'],
					$runbook['wiki_url'],
					RBF_TYPE_SCO2022
				)))
				{
					$runbook_id = $this->core->db->last_id();
				}
			}
			else
			{
				$runbook_id = $res[0][0];

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
					$folder_id,
					$runbook['name'],
					$runbook['description'],
					$runbook['wiki_url'],
					$runbook_id
				));
			}

			if($runbook_id)
			{
				$this->core->db->put(rpv("DELETE FROM @runbooks_params WHERE `pid` = #", $runbook_id));

				foreach($runbook['params'] as &$params)
				{
					$this->core->db->put(rpv("
							INSERT INTO @runbooks_params (`pid`, `guid`, `name`, `flags`)
							VALUES (#, !, !, #)
						",
						$runbook_id,
						$params['guid'],
						$params['name'],
						0x0000
					));
				}
			}

			$total++;
		}

		return $total;
	}

	public function sync_jobs_old($guid)
	{
		$total = 0;
		$jobs = $this->retrieve_jobs($guid);

		foreach($jobs as &$job)
		{
			if(!$this->core->db->select_ex($res, rpv("SELECT j.`id` FROM @runbooks_jobs AS j WHERE j.`guid` = ! LIMIT 1", $job['guid'])))
			{
				if($this->core->db->select_ex($rb, rpv("SELECT r.`id` FROM @runbooks AS r WHERE r.`guid` = ! LIMIT 1", $job['pid'])))
				{
					$job_date = DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, $job['date'], NULL);
					if($job_date === FALSE)
					{
						$job_date = '0000-00-00 00:00:00';
					}
					else
					{
						$job_date->setTimeZone(new DateTimeZone(date_default_timezone_get()));
						$job_date = $job_date->format('Y-m-d H:i:s');
					}

					$this->core->db->put(rpv("
							INSERT INTO @runbooks_jobs (`date`, `pid`, `guid`, `uid`, `flags`)
							VALUES (!, #, !, NULL, 0x0000)
						",
						$job_date,
						$rb[0][0],
						$job['guid']
					));

					$total++;
				}
			}
		}

		return $total;
	}

	public function sync_jobs($id)
	{
		$skip = 0;
		$total = 0;
		$jobs_added = 0;

		$job_filter = '';

		if($id)
		{
			$runbook = $this->core->Runbooks->get_runbook_by_id($id);
			$job_filter = '&$filter=RunbookId%20eq%20' . $runbook['guid'];
		}

		do
		{
			$json_data = $this->get_http_json($this->orchestrator_url.'/Jobs?$count=true&$top=50&$skip='.$skip.$job_filter);

			$total = intval($json_data['@odata.count']);

			foreach($json_data['value'] as $properties)
			{
				$job = array(
					'guid' => (string) $properties['Id'],
					'pid' => (string) $properties['RunbookId'],
					'date' => (string) $properties['CreationTime']
				);

				if($this->core->db->select_ex($rb, rpv("SELECT r.`id` FROM @runbooks AS r WHERE r.`guid` = ! AND r.`flags` & {%RBF_TYPE_SCO2022} LIMIT 1", $job['pid'])))
				{
					if(!$this->core->db->select_ex($res, rpv("SELECT j.`id` FROM @runbooks_jobs AS j WHERE j.`guid` = ! AND j.`pid` = # LIMIT 1", $job['guid'], $rb[0][0])))
					{
						$job_date = DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, $job['date'], NULL);
						if($job_date === FALSE)
						{
							$job_date = '0000-00-00 00:00:00';
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
							$job['guid']
						)))
						{
							// Load input params

							$job_id = $this->core->db->last_id();

							$job_params = $this->retrieve_job_first_instance_input_params($job['guid']);

							foreach($job_params as $param)
							{
								$this->core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $param['guid'], $param['value']));
							}

							$jobs_added++;
						}
					}
				}

				//break;
				$skip++;
			}
			//break;
		}
		while($skip < $total);

		return $jobs_added;
	}

	public function get_servers()
	{
		if(!$this->core->db->select_assoc_ex($servers, rpv("SELECT s.`id`, s.`name` FROM @runbooks_servers AS s WHERE (s.`flags` & {%RBF_TYPE_SCO2022})  ORDER BY s.`name`, s.`id`")))
		{
			return FALSE;
		}

		return $servers;
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
				AND (r.`flags` & {%RBF_TYPE_SCO2022})
			LIMIT 1
		', $id)))
		{
			$this->core->error('Job '.$id.' not found!');
			return FALSE;
		}

		$job = &$job[0];

		$json_data = $this->get_http_json($this->orchestrator_url.'/Jobs?$filter=Id%20eq%20'.$job['guid']);

		$properties = $json_data['value'][0];

		$sid = (string) $properties['CreatedBy'];
		$sid_name = '';
		if(defined('USE_LDAP') && USE_LDAP && !empty($sid))
		{
			if($this->core->LDAP->search($user, '(objectSid='.ldap_escape($sid, '', LDAP_ESCAPE_FILTER).')', array('samaccountname')))
			{
				$sid_name = $user[0]['sAMAccountName'][0];
			}
		}

		$modified_date = DateTime::createFromFormat(DateTime::RFC3339_EXTENDED, (string) $properties['LastModifiedTime'], NULL);
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
			'status' => (string) $properties['Status'],
			'modified_date' => $modified_date,
			'sid' => $sid,
			'sid_name' => $sid_name,
			'instances' => array()
		);

		$instances = $this->retrieve_job_instances($job['guid']);

		if($instances !== FALSE)
		{
			$job_info['instances'] = &$instances;
		}

		return $job_info;
	}

	public function get_activity($guid)
	{
		/*
		if(!$this->core->db->select_assoc_ex($activity, rpv('SELECT a.`id`, a.`guid`, a.`name` FROM @runbooks_activities AS a WHERE a.`guid` = ! AND (a.`flags` & 0x0001) = 0 LIMIT 1', $guid)))
		{
			$this->core->error('Activity '.$guid.' not found! Try to sync runbooks.');
			return FALSE;
		}

		$activity = &$activity[0];

		$activity_info = array(
			'id' => $activity['id'],
			'guid' => $activity['guid'],
			'name' => $activity['name'],
			'params' => array()
		);
		*/

		$activity_info = array(
			'guid' => $guid,
			'params' => array()
		);

		$params = $this->retrieve_activity_data($guid);

		if($params !== FALSE)
		{
			$activity_info['params'] = &$params;
		}

		return $activity_info;
	}

	public function get_runbook_params($id)
	{
		$this->core->db->select_assoc_ex($runbook_params, rpv("SELECT p.`guid`, p.`name` FROM @runbooks_params AS p WHERE p.`pid` = # ORDER BY p.`name`", $id));

		$form_fields = array();

		$i = 0;
		foreach($runbook_params as &$row)
		{
			$required = FALSE;
			$type = 'string';

			$i++;
			if(preg_match('#[/_]([isdtlacgmfrwu]+)$#i', $row['name'], $matches))
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
							if($type == 'time')
							{
								$type = 'datetime';
							}
							else
							{
								$type = 'date';
							}
							break;
						case 't':
							if($type == 'date')
							{
								$type = 'datetime';
							}
							else
							{
								$type = 'time';
							}
							break;
						case 'l':
							$type = 'list';
							break;
						case 'f':
							$type = 'flags';
							break;
						case 'a':
							$type = 'samaccountname';
							break;
						case 'c':
							$type = 'computer';
							break;
						case 'g':
							$type = 'group';
							break;
						case 'm':
							$type = 'mail';
							break;
						case 'u':
							$type = 'upload';
							break;
						case 'w':
							$type = 'who';
							break;
						case 'r':
							$required = TRUE;
							break;
					}

					$k++;
				}
			}

			$name = preg_replace('#\s*[/_][isdtlacgmfrwu]+$#i', '', $row['name']);

			if(preg_match('/\*\s*:?\s*$/i', $name))
			{
				$required = TRUE;
			}

			$name = preg_replace('/\s*:\s*$/i', '', $name);

			$form_field = array(
				'type' => $type,
				'required' => $required,
				'name' => $name,
				'name_original' => $row['name'],
				'guid' => $row['guid']
			);

			if($type == 'upload')
			{
				$form_field['accept'] = '';
				$form_field['max_size'] = 102400;
			}

			if((($type == 'list') || ($type == 'flags') || ($type == 'upload')) && preg_match('/\(([^\)]+)\)\s*\*?$/i', $name, $matches))
			{
				$form_field['name'] = preg_replace('/\s*\(([^\)]+)\)\s*(\*?)/i', '\2', $name);
				$list = preg_split('/\s*[,;]\s*/', $matches[1]);

				if($type == 'upload')
				{
					$form_field['accept'] = $list;
				}
				else
				{
					$form_field['list'] = $list;
				}
			}

			$form_fields[] = $form_field;
		}

		return $form_fields;
	}

	public function get_runbook_form($id, $job_id)
	{
		$runbook = $this->core->Runbooks->get_runbook_by_id($id);

		$result_json = array(
			'code' => 0,
			'message' => '',
			'guid' => $runbook['guid'],
			'title' => $runbook['name'],
			'description' => $runbook['description'],
			'wiki_url' => $runbook['wiki_url'],
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
					'value' => $runbook['id']
				)
			)
		);

		$params = $this->get_runbook_params($runbook['id']);

		$job_params = NULL;

		if(!empty($job_id))
		{
			if(!$this->core->db->select_assoc_ex($job_params, rpv('SELECT jp.`guid`, jp.`value` FROM @runbooks_jobs_params AS jp WHERE jp.`pid` = #', $job_id)))
			{
				if($this->core->db->select_assoc_ex($job, rpv('SELECT j.`guid` FROM @runbooks_jobs AS j WHERE j.`id` = #', $job_id)))
				{
					$job_params = $this->retrieve_job_first_instance_input_params($job[0]['guid']);

					foreach($job_params as $param)
					{
						$this->core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $param['guid'], $param['value']));
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
				'value' => ''
			);

			if(($param['type'] == 'list') || ($param['type'] == 'flags'))
			{
				$field['list'] = $param['list'];
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
						$field['value'] = $row['value'];
						break;
					}
				}
			}

			$result_json['fields'][] = $field;
		}

		$servers = $this->get_servers();

		$servers_list = array();
		foreach($servers as $server)
		{
			$servers_list[] = $server['name'];
		}

		$result_json['fields'][] = array(
			'type' => 'spoiler',
			'title' => LL('AdvancedSettings'),
			'fields' => array(
				array(
					'type' => 'flags',
					'name' => 'servers',
					'title' => LL('SelectRunbookServers'),
					'value' => '',
					'list' => $servers_list,
					'values' => $servers_list
				)
			)
		);

		return $result_json;
	}
}

function cmp_name($a, $b)
{
	return strcasecmp($a['name'], $b['name']);
}

function cmp_sequence($a, $b)
{
	$a = intval($a['sequence']);
	$b = intval($b['sequence']);

	if($a == $b)
	{
		return 0;
	}

	return ($a < $b) ? -1 : 1;
}
