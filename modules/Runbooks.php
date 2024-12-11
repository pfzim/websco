<?php
/*
    Runbooks class - This class is intended for accessing the Microsoft
	                 System CenterOrchestrator web service to get a list
					 of ranbooks and launch them.
    Copyright (C) 2021 Dmitry V. Zimin

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

class Runbooks
{
	private $core;

	function __construct(&$core)
	{
		$this->core = &$core;

		$this->orchestrator_url = ORCHESTRATOR_URL;
		$this->orchestrator_user = ORCHESTRATOR_USER;
		$this->orchestrator_passwd = ORCHESTRATOR_PASSWD;
	}

	public function get_http_xml($url)
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
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));


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

		$xml = @simplexml_load_string($output);
		if($xml == FALSE)
		{
			$this->core->error('XML parse error!');
			return FALSE;
		}

		return $xml;
	}

	/**
	 Start runbook.

		\param [in] $guid   - runbook ID
		\param [in] $params - array of param GUID and value

		\return - created job ID
	*/

	public function start_runbook($guid, $params, $servers = NULL)
	{
		$request = <<<'EOT'
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
    <content type="application/xml">
        <m:properties>
EOT;

		$request .= '<d:RunbookId m:type="Edm.Guid">'.htmlspecialchars($guid).'</d:RunbookId>';

		if($servers)
		{
			$request .= '<d:RunbookServers>'.htmlspecialchars(implode(',', $servers)).'</d:RunbookServers>';
		}

		if(!empty($params))
		{
			$request .= '<d:Parameters><![CDATA[<Data>';
			foreach($params as &$param)
			{
				$request .= '<Parameter><ID>{'.htmlspecialchars($param['guid']).'}</ID><Value>'.htmlspecialchars(str_replace('\'', '\'\'', $param['value'])).'</Value></Parameter>';
			}
			$request .= '</Data>]]></d:Parameters>';
		}
		$request .= <<<'EOT'
        </m:properties>
    </content>
</entry>
EOT;

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
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);


		$output = curl_exec($ch);
		$result = curl_getinfo($ch);

		//echo $output;
		//log_file($output);

		if(intval($result['http_code']) != 201)
		{
			log_file('ERROR: Start runbook '.$this->orchestrator_url.'/Jobs'."\n".$output."\n\n");
			/*
				<error xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
				  <code></code>
				  <message xml:lang="ru-RU">The requested operation requires Publish permissions on the Runbook</message>
				</error>
			*/
			return FALSE;
		}

		$xml = @simplexml_load_string($output);
		if($xml == FALSE)
		{
			return FALSE;
		}

		return $xml->content->children('m', TRUE)->properties->children('d', TRUE)->Id;
	}

	/**
	 Stop job.

		\param [in] $guid   - job ID

		\return - TRUE | FALSE
	*/

	public function job_cancel($guid)
	{
		$request = <<<'EOT'
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
    <content type="application/xml">
        <m:properties>
EOT;

		$request .= '<d:Id m:type="Edm.Guid">'.htmlspecialchars($guid).'</d:Id>';
		//$request .= '<d:RunbookId m:type="Edm.Guid">'.htmlspecialchars($runbook_guid).'</d:RunbookId>';

		$request .= <<<'EOT'
			<d:Status>Canceled</d:Status>
        </m:properties>
    </content>
</entry>
EOT;

		log_file($request);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->orchestrator_url.'/Jobs(guid'.$guid.')');
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
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml', 'X-HTTP-Method: MERGE', 'If-Match: *'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

		$output = curl_exec($ch);
		$result = curl_getinfo($ch);

		//echo $output;
		//log_file($output);

		if(intval($result['http_code']) != 204)
		{
			log_file('ERROR: Stop job '.$this->orchestrator_url.'/Jobs(guid'.$guid.')'."\n".$output."\n\n");
			/*
				<error xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
				  <code></code>
				  <message xml:lang="ru-RU">The requested operation requires Publish permissions on the Runbook</message>
				</error>
			*/
			return FALSE;
		}

		return TRUE;
	}
	
	/**
	 Get job instances list.

		\param [in] $guid   - job ID

		\return - array of job instances ID and it statuses
	*/

	public function retrieve_job_instances($guid)
	{
		$xml = $this->get_http_xml($this->orchestrator_url.'/Jobs(guid\''.$guid.'\')/Instances');

		$instances = array();

		foreach($xml->entry as $entry)
		{
			$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

			$instance = array(
				'guid' => (string) $properties->Id,
				'status' => (string) $properties->Status,
				'params_in' => array(),
				'params_out' => array(),
				'activities' => array()
			);

			$sub_xml = $this->get_http_xml($this->orchestrator_url.'/RunbookInstances(guid\''.$instance['guid'].'\')/Parameters');

			foreach($sub_xml->entry as $entry)
			{
				$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

				$activity = array(
					'name' => (string) $properties->Name,
					'value' => (string) $properties->Value
				);

				if(((string) $properties->Direction) == 'Out')
				{
					$instance['params_out'][] = $activity;
				}
				else
				{
					$instance['params_in'][] = $activity;
				}
			}

			$sub_xml = $this->get_http_xml($this->orchestrator_url.'/RunbookInstances(guid\''.$instance['guid'].'\')/ActivityInstances');

			foreach($sub_xml->entry as $entry)
			{
				$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

				$activity = array(
					'id' =>  (string) $properties->Id,
					'guid' => (string) $properties->ActivityId,
					'name' => '',
					'sequence' => (string) $properties->SequenceNumber,
					'status' => (string) $properties->Status
				);

				if($this->core->db->select_ex($name, rpv('SELECT a.`name` FROM @runbooks_activities AS a WHERE a.`guid` = ! AND (a.`flags` & {%RBF_DELETED}) = 0 LIMIT 1', (string) $properties->ActivityId)))
				{
					$activity['name'] = $name[0][0];
				}

				$instance['activities'][] = $activity;
			}

			usort($instance['params_in'], 'cmp_name');
			usort($instance['activities'], 'cmp_sequence');
			usort($instance['params_out'], 'cmp_name');

			$instances[] = $instance;
		}
		return $instances;
	}

	public function retrieve_job_first_instance_input_params($guid)
	{
		$xml = $this->get_http_xml($this->orchestrator_url.'/Jobs(guid\''.$guid.'\')/Instances');

		$params_in = array();

		foreach($xml->entry as $entry)
		{
			$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

			$sub_xml = $this->get_http_xml($this->orchestrator_url.'/RunbookInstances(guid\''.((string) $properties->Id).'\')/Parameters');

			foreach($sub_xml->entry as $sub_entry)
			{
				$properties = $sub_entry->content->children('m', TRUE)->properties->children('d', TRUE);

				if(((string) $properties->Direction) == 'In')
				{
					$params_in[] = array(
						'guid' => (string) $properties->RunbookParameterId,
						'value' => (string) $properties->Value
					);
				}
			}

			break;
		}

		return $params_in;
	}

	public function retrieve_activity_data($guid)
	{
		$xml = $this->get_http_xml($this->orchestrator_url.'/ActivityInstances(guid\''.$guid.'\')/Data');

		$params = array();

		foreach($xml->entry as $entry)
		{
			$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

			$params[] = array(
				'name' => (string) $properties->Name,
				'value' => (string) $properties->Value
			);
		}

		return $params;
	}

	public function retrieve_folders()
	{
		$folders = array();
		$skip = 0;
		$total = 0;

		do
		{
			$xml = $this->get_http_xml($this->orchestrator_url.'/Folders?$inlinecount=allpages&$top=50&$skip='.$skip);

			$total = intval($xml->children('m', TRUE)->count);

			foreach($xml->entry as $entry)
			{
				$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

				$folder = array(
					'guid' => (string) $properties->Id,
					'name' => (string) $properties->Name,
					'pid' => (string) $properties->ParentId
				);

				$folders[] = $folder;

				//break;
				$skip++;
			}
		}
		while($skip < $total);

		//echo $output;
		//echo json_encode($runbooks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		return $folders;
	}

	public function retrieve_servers()
	{
		$servers = array();
		$skip = 0;
		$total = 0;

		do
		{
			$xml = $this->get_http_xml($this->orchestrator_url.'/RunbookServers?$inlinecount=allpages&$top=50&$skip='.$skip);

			$total = intval($xml->children('m', TRUE)->count);

			foreach($xml->entry as $entry)
			{
				$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

				$server = array(
					'guid' => (string) $properties->Id,
					'name' => (string) $properties->Name
				);

				$servers[] = $server;

				//break;
				$skip++;
			}
		}
		while($skip < $total);

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
			$job_filter = '/Runbooks(guid\''.$guid.'\')';
		}

		do
		{
			$xml = $this->get_http_xml($this->orchestrator_url.$job_filter.'/Jobs?$inlinecount=allpages&$top=50&$skip='.$skip);

			$total = intval($xml->children('m', TRUE)->count);

			foreach($xml->entry as $entry)
			{
				$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

				$job = array(
					'guid' => (string) $properties->Id,
					'pid' => (string) $properties->RunbookId,
					'date' => (string) $properties->CreationTime
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
			$xml = $this->get_http_xml($this->orchestrator_url.'/Activities?$inlinecount=allpages&$top=50&$skip='.$skip);

			$total = intval($xml->children('m', TRUE)->count);

			foreach($xml->entry as $entry)
			{
				$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

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
					'guid' => (string) $properties->Id,
					'name' => (string) $properties->Name
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
		$skip = 0;
		$total = 0;

		do
		{
			$xml = $this->get_http_xml($this->orchestrator_url.'/Runbooks?$inlinecount=allpages&$top=50&$skip='.$skip);

			$total = intval($xml->children('m', TRUE)->count);

			foreach($xml->entry as $entry)
			{
				$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);
				//echo "\n".'Runbook: '.$properties->Name.' ('.$properties->Id.')'."\n";
				
				$description = (string) $properties->Description;
				$wiki_url = '';
				
				if(preg_match('#\[wiki\](.*?)\[/wiki\]#i', $description, $matches))
				{
					$wiki_url = trim($matches[1]);
					$description = preg_replace('#\s*\[wiki\](.*?)\[/wiki\]#i', '', $description, 1);
				}

				$runbook = array(
					'guid' => (string) $properties->Id,
					'name' => (string) $properties->Name,
					'description' => $description,
					'wiki_url' => $wiki_url,
					'folder_id' => (string) $properties->FolderId,
					'path' => (string) $properties->Path,
					'params' => array()
				);

				$xml_runbook_params = $this->get_http_xml($this->orchestrator_url.'/Runbooks(guid\''.$properties->Id.'\')/Parameters');

				if($xml_runbook_params !== FALSE)
				{
					foreach($xml_runbook_params->entry as $params_entry)
					{
						$properties = $params_entry->content->children('m', TRUE)->properties->children('d', TRUE);
						if($properties->Direction == 'In')
						{
							$runbook['params'][] = array(
								'guid' =>  (string) $properties->Id,
								'name' => (string) $properties->Name
							);
						}
					}
				}

				$runbooks[] = $runbook;

				//break;
				$skip++;
			}
		}
		while($skip < $total);

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

		$this->core->db->put(rpv("UPDATE @runbooks SET `flags` = (`flags` | {%RBF_DELETED}) WHERE (`flags` & {%RBF_TYPE_CUSTOM}) = 0"));
		$this->core->db->put(rpv("UPDATE @runbooks_folders SET `flags` = (`flags` | {%RBF_DELETED})"));
		$this->core->db->put(rpv("UPDATE @runbooks_activities SET `flags` = (`flags` | {%RBF_DELETED})"));
		$this->core->db->put(rpv("UPDATE @runbooks_servers SET `flags` = (`flags` | {%RBF_DELETED})"));

		$total = 0;

		//$servers = $this->retrieve_servers();

		foreach($servers as &$server)
		{
			//echo $folder['guid']."\r\n";
			$server_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT f.`guid` FROM @runbooks_servers AS f WHERE f.`guid` = ! LIMIT 1", $server['guid'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks_servers (`guid`, `name`, `flags`)
						VALUES (!, !, #)
					",
					$server['guid'],
					$server['name'],
					0x0000
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
			$folder_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT f.`guid` FROM @runbooks_folders AS f WHERE f.`guid` = ! LIMIT 1", $folder['guid'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks_folders (`guid`, `pid`, `name`, `flags`)
						VALUES (!, !, !, #)
					",
					$folder['guid'],
					$folder['pid'],
					$folder['name'],
					0x0000
				)))
				{
					$folder_id = $this->core->db->last_id();
				}
			}
			else
			{
				$this->core->db->put(rpv("
						UPDATE
							@runbooks_folders
						SET
							`pid` = !,
							`name` = !,
							`flags` = (`flags` & ~{%RBF_DELETED})
						WHERE
							`guid` = !
						LIMIT 1
					",
					$folder['pid'],
					$folder['name'],
					$res[0][0]
				));

				$folder_id = $res[0][0];
			}
		}

		unset($folders);

		//$activities = $this->retrieve_activities();

		foreach($activities as &$activity)
		{
			$activity_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT a.`id`, a.`guid` FROM @runbooks_activities AS a WHERE a.`guid` = ! LIMIT 1", $activity['guid'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks_activities (`guid`, `name`, `flags`)
						VALUES (!, !, #)
					",
					$activity['guid'],
					$activity['name'],
					0x0000
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
			if(!$this->core->db->select_ex($res, rpv("SELECT f.`id` FROM @runbooks_folders AS f WHERE f.`guid` = ! LIMIT 1", $runbook['folder_id'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks_folders (`guid`, `pid`, `name`, `flags`)
						VALUES (!, !, !, #)
					",
					$runbook['folder_id'],
					'00000000-0000-0000-0000-000000000000',
					$runbook['path'],
					0x0000
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
			if(!$this->core->db->select_ex($res, rpv("SELECT r.`guid` FROM @runbooks AS r WHERE (`flags` & {%RBF_TYPE_CUSTOM}) = 0 AND r.`guid` = ! LIMIT 1", $runbook['guid'])))
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
					0x0000
				)))
				{
					$runbook_id = $runbook['guid'];
				}
			}
			else
			{
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
							`guid` = !
						LIMIT 1
					",
					$folder_id,
					$runbook['name'],
					$runbook['description'],
					$runbook['wiki_url'],
					$res[0][0]
				));

				$runbook_id = $res[0][0];
			}

			if($runbook_id)
			{
				$this->core->db->put(rpv("DELETE FROM @runbooks_params WHERE `pid` = !", $runbook_id));

				foreach($runbook['params'] as &$params)
				{
					$this->core->db->put(rpv("
							INSERT INTO @runbooks_params (`pid`, `guid`, `name`, `flags`)
							VALUES (!, !, !, #)
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
					$job_date = DateTime::createFromFormat('Y-m-d?H:i:s', preg_replace('#\..*$#', '', $job['date']), new DateTimeZone('UTC'));
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

	public function sync_jobs($guid)
	{
		$skip = 0;
		$total = 0;
		$jobs_added = 0;

		$job_filter = '';

		if(!empty($guid))
		{
			$job_filter = '/Runbooks(guid\''.$guid.'\')';
		}

		do
		{
			$xml = $this->get_http_xml($this->orchestrator_url.$job_filter.'/Jobs?$inlinecount=allpages&$top=50&$skip='.$skip);

			$total = intval($xml->children('m', TRUE)->count);

			foreach($xml->entry as $entry)
			{
				$properties = $entry->content->children('m', TRUE)->properties->children('d', TRUE);

				$job = array(
					'guid' => (string) $properties->Id,
					'pid' => (string) $properties->RunbookId,
					'date' => (string) $properties->CreationTime
				);

				if(!$this->core->db->select_ex($res, rpv("SELECT j.`id` FROM @runbooks_jobs AS j WHERE j.`guid` = ! LIMIT 1", $job['guid'])))
				{
					if($this->core->db->select_ex($rb, rpv("SELECT r.`id` FROM @runbooks AS r WHERE r.`guid` = ! LIMIT 1", $job['pid'])))
					{
						$job_date = DateTime::createFromFormat('Y-m-d?H:i:s', preg_replace('#\..*$#', '', $job['date']), new DateTimeZone('UTC'));
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

	public function get_runbook_by_id($id)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks AS r LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE r.`id` = # LIMIT 1", $id)))
		{
			$this->core->error('Runbook '.$id.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_runbook($guid)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks AS r LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE r.`guid` = ! LIMIT 1", $guid)))
		{
			$this->core->error('Runbook '.$guid.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_runbook_by_job_guid($guid)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks_jobs AS j LEFT JOIN @runbooks AS r ON r.`id` = j.`pid` LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE j.`guid` = ! LIMIT 1", $guid)))
		{
			$this->core->error('Job '.$guid.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_servers()
	{
		if(!$this->core->db->select_assoc_ex($servers, rpv("SELECT s.`id`, s.`name` FROM @runbooks_servers AS s ORDER BY s.`name`, s.`id`")))
		{
			return FALSE;
		}

		return $servers;
	}

	public function get_job($guid)
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
				j.`guid` = !
				AND (r.`flags` & {%RBF_TYPE_CUSTOM}) = 0
			LIMIT 1
		', $guid)))
		{
			$this->core->error('Job '.$guid.' not found! Try to sync jobs.');
			return FALSE;
		}

		$job = &$job[0];

		$xml = $this->get_http_xml($this->orchestrator_url.'/Jobs(guid\''.$guid.'\')');

		$properties = $xml->content->children('m', TRUE)->properties->children('d', TRUE);

		$sid = (string) $properties->CreatedBy;
		$sid_name = '';
		if(defined('USE_LDAP') && USE_LDAP && !empty($sid))
		{
			if($this->core->LDAP->search($user, '(objectSid='.ldap_escape($sid, null, LDAP_ESCAPE_FILTER).')', array('samaccountname')))
			{
				$sid_name = $user[0]['sAMAccountName'][0];
			}
		}

		$modified_date = DateTime::createFromFormat('Y-m-d?H:i:s', preg_replace('#\..*$#', '', (string) $properties->LastModifiedTime), new DateTimeZone('UTC'));
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
			'status' => (string) $properties->Status,
			'modified_date' => $modified_date,
			'sid' => $sid,
			'sid_name' => $sid_name,
			'instances' => array()
		);

		$instances = $this->retrieve_job_instances($guid);

		if($instances !== FALSE)
		{
			$job_info['instances'] = &$instances;
		}

		return $job_info;
	}

	public function get_custom_job($id)
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
				AND (r.`flags` & {%RBF_TYPE_CUSTOM})
			LIMIT 1
		', $id)))
		{
			$this->core->error('Job '.$id.' not found!');
			return FALSE;
		}

		$job = &$job[0];

		!$this->core->db->select_assoc_ex($job_params, rpv('
			SELECT
				jp.`guid`,
				jp.`value`
			FROM @runbooks_jobs_params AS jp
			WHERE jp.`pid` = #
		', $job['id']));

		$job_info = array(
			'id' => $job['id'],
			'guid' => $job['guid'],
			'name' => $job['name'],
			'run_date' => $job['run_date'],
			'runbook_id' => $job['runbook_id'],
			'runbook_guid' => $job['runbook_guid'],
			'status' => 'Completed',
			'folder_id' => $job['folder_id'],
			'user' => $job['login'],
			'params' => &$job_params
		);

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

	public function get_runbook_params($guid)
	{
		$this->core->db->select_assoc_ex($runbook_params, rpv("SELECT p.`guid`, p.`name` FROM @runbooks_params AS p WHERE p.`pid` = ! ORDER BY p.`name`", $guid));

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

	public function load_tree_childs($guid, $check_permissions)
	{
		$childs = NULL;

		if($this->core->db->select_assoc_ex($folders, rpv('SELECT f.`id`, f.`guid`, f.`name`, f.`flags` FROM @runbooks_folders AS f WHERE f.`pid` = {s0} AND (f.`flags` & {%RBF_DELETED}) = 0 ORDER BY f.`name`', $guid)))
		{
			$childs = array();

			foreach($folders as $folder)
			{
				if(!$check_permissions || $this->core->UserAuth->check_permission($folder['id'], RB_ACCESS_LIST))     // || ($folder['id'] == 0) - if top level always allow list
				{
					$childs[] = array(
						'name' => $folder['name'],
						'id' => $folder['id'],
						'guid' => $folder['guid'],
						'flags' => $folder['flags'],
						'childs' => $this->load_tree_childs($folder['guid'], $check_permissions)
					);
				}
			}
		}

		return $childs;
	}

	public function get_folders_tree($check_permissions)
	{
		return array(
			array(
				'name' => 'Root folder',
				'guid' => '00000000-0000-0000-0000-000000000000',
				'id' => 0,
				'flags' => 0,
				'childs' => $this->load_tree_childs('00000000-0000-0000-0000-000000000000', $check_permissions)
			)
		);
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
