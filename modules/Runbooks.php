<?php
	/**
		\file
		\brief Get folders list from server
	*/

	if(!defined('Z_PROTECTED')) exit;

class Runbooks
{
	private $core;

	function __construct(&$core)
	{
		$this->core = &$core;

		$this->orchestrator_url = ORCHESTRATOR_URL;
		$this->orchestrator_user = LDAP_USER;
		$this->orchestrator_passwd = LDAP_PASSWD;
	}

	public function get_http_xml($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
		curl_setopt($ch, CURLOPT_USERPWD, $this->orchestrator_user.':'.$this->orchestrator_passwd);
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

	public function start_runbook($guid, $params)
	{
		$request = <<<'EOT'
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
    <content type="application/xml">
        <m:properties>
EOT;

		$request .= '<d:RunbookId m:type="Edm.Guid">'.$guid.'</d:RunbookId>';

		if(!empty($params))
		{
			$request .= '<d:Parameters><![CDATA[<Data>';
			foreach($params as $key => $value)
			{
				$request .= '<Parameter><ID>{'.$key.'}</ID><Value>'.str_replace('\'', '\'\'', $value).'</Value></Parameter>';
			}
			$request .= '</Data>]]></d:Parameters>';
		}
		$request .= <<<'EOT'
        </m:properties>
    </content>
</entry>
EOT;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->orchestrator_url.'/Jobs');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
		curl_setopt($ch, CURLOPT_USERPWD, $this->orchestrator_user.':'.$this->orchestrator_passwd);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);


		$output = curl_exec($ch);
		$result = curl_getinfo($ch);

		//echo $output;
		//log_file($output);

		if(intval($result['http_code']) != 201)
		{
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
	 Get job instances list.

		\param [in] $guid   - job ID

		\return - array of job instances ID and it statuses
	*/

	public function get_job_instances($guid)
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
					'guid' => (string) $properties->ActivityId,
					'name' => '',
					'sequence' => (string) $properties->SequenceNumber,
					'status' => (string) $properties->Status
				);

				if($this->core->db->select_ex($name, rpv('SELECT a.`name` FROM @runbooks_activities AS a WHERE a.`guid` = ! LIMIT 1', (string) $properties->ActivityId)))
				{
					$activity['name'] = $name[0][0];
				}

				$instance['activities'][] = $activity;
			}

			$instances[] = $instance;
		}
		return $instances;
	}

	public function get_folders()
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

	public function get_activities()
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

	public function get_runbooks()
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

				$runbook = array(
					'guid' => (string) $properties->Id,
					'name' => (string) $properties->Name,
					'description' => (string) $properties->Description,
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
		$this->core->db->put(rpv("UPDATE @runbooks SET `flags` = (`flags` | 0x0001)"));
		$this->core->db->put(rpv("UPDATE @runbooks_folders SET `flags` = (`flags` | 0x0001)"));
		$this->core->db->put(rpv("UPDATE @runbooks_activities SET `flags` = (`flags` | 0x0001)"));

		$total = 0;
		$folders = $this->get_folders();

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
							`flags` = (`flags` & ~0x0001)
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

		$activities = $this->get_activities();

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
							`flags` = (`flags` & ~0x0001)
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

		$runbooks = $this->get_runbooks();

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
					$folder_id = $core->db->last_id();
				}
			}
			else
			{
				$folder_id = $res[0][0];
			}

			$runbook_id = 0;
			if(!$this->core->db->select_ex($res, rpv("SELECT r.`guid` FROM @runbooks AS r WHERE r.`guid` = ! LIMIT 1", $runbook['guid'])))
			{
				if($this->core->db->put(rpv("
						INSERT INTO @runbooks (`guid`, `folder_id`, `name`, `description`, `flags`)
						VALUES (!, #, !, !, #)
					",
					$runbook['guid'],
					$folder_id,
					$runbook['name'],
					$runbook['description'],
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
							`flags` = (`flags` & ~0x0001)
						WHERE
							`guid` = !
						LIMIT 1
					",
					$folder_id,
					$runbook['name'],
					$runbook['description'],
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

	public function get_runbook($guid)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, r.`name`, r.`description` FROM @runbooks AS r WHERE r.`guid` = ! LIMIT 1", $guid)))
		{
			$this->core->error('Runbook '.$guid.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_runbook_by_id($id)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, r.`name`, r.`description` FROM @runbooks AS r WHERE r.`id` = ! LIMIT 1", $id)))
		{
			$this->core->error('Runbook '.$guid.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_job($guid)
	{
		if(!$this->core->db->select_assoc_ex($job, rpv('
			SELECT
				j.`id`,
				j.`guid`,
				r.`name`,
				r.`id` AS `runbook_id`,
				r.`guid` AS `runbook_guid`,
				r.`folder_id`,
				u.`login`
			FROM @runbooks_jobs AS j
			LEFT JOIN @runbooks AS r ON r.`id` = j.`pid`
			LEFT JOIN @users AS u ON u.`id` = j.`uid`
			WHERE j.`guid` = !
			LIMIT 1
		', $guid)))
		{
			$this->core->error('Job '.$guid.' not found!');
			return FALSE;
		}

		$job = &$job[0];

		$xml = $this->get_http_xml($this->orchestrator_url.'/Jobs(guid\''.$guid.'\')');

		$properties = $xml->content->children('m', TRUE)->properties->children('d', TRUE);

		$job_info = array(
			'id' => $job['id'],
			'guid' => $job['guid'],
			'name' => $job['name'],
			'runbook_id' => $job['runbook_id'],
			'runbook_guid' => $job['runbook_guid'],
			'folder_id' => $job['folder_id'],
			'user' => $job['login'],
			'status' => (string) $properties->Status,
			'instances' => array()
		);

		$instances = $this->get_job_instances($guid);

		if($instances !== FALSE)
		{
			$job_info['instances'] = &$instances;
		}

		return $job_info;
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
			if(preg_match('#[/_]([isdlafr]+)$#i', $row['name'], $matches))
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
						case 'f':
							$type = 'flags';
							break;
						case 'a':
							$type = 'samaccountname';
							break;
						case 'r':
							$required = TRUE;
							break;
					}

					$k++;
				}
			}

			$name = preg_replace('#\s*[/_][isdlafr]+$#i', '', $row['name']);

			if(preg_match('/\*\s*:?\s*$/i', $name))
			{
				$required = TRUE;
			}

			$name = preg_replace('/\s*:\s*$/i', '', $name);

			if((($type == 'list') || ($type == 'flags')) && preg_match('/\(([^\)]+)\)\s*\*?$/i', $name, $matches))
			{
				$name = preg_replace('/\s*\(([^\)]+)\)\s*(\*?)/i', '\2', $name);
				$list = preg_split('/\s*[,;]\s*/', $matches[1]);

				$form_fields[] = array(
					'type' => $type,
					'required' => $required,
					'name' => $name,
					'guid' => $row['guid'],
					'list' => $list
				);
			}
			else
			{
				$form_fields[] = array(
					'type' => $type,
					'required' => $required,
					'name' => $name,
					'guid' => $row['guid']
				);
			}
		}

		return $form_fields;
	}
}
