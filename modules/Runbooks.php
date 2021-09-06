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

		return (intval($result['http_code']) == 201);
	}

	public function get_folders()
	{
		$folders = array();
		$skip = 0;
		$total = 0;
		
		do
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->orchestrator_url.'/Folders?$inlinecount=allpages&$top=50&$skip='.$skip);
			curl_setopt($ch, CURLOPT_POST, false);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			curl_setopt($ch, CURLOPT_USERPWD, $this->orchestrator_user.':'.$this->orchestrator_passwd);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));


			$output = curl_exec($ch);
			$result = curl_getinfo($ch);

			curl_close($ch);

			
			$xml = @simplexml_load_string($output);
			if($xml == FALSE)
			{
				break;
			}
			
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

	public function get_runbooks()
	{
		$runbooks = array();
		$skip = 0;
		$total = 0;
		
		do
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->orchestrator_url.'/Runbooks?$inlinecount=allpages&$top=50&$skip='.$skip);
			curl_setopt($ch, CURLOPT_POST, false);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			curl_setopt($ch, CURLOPT_USERPWD, $this->orchestrator_user.':'.$this->orchestrator_passwd);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));


			$output = curl_exec($ch);
			$result = curl_getinfo($ch);

			curl_close($ch);

			
			$xml = @simplexml_load_string($output);
			if($xml == FALSE)
			{
				break;
			}
			
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
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $entry->id.'/Parameters');
				curl_setopt($ch, CURLOPT_POST, false);
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
				curl_setopt($ch, CURLOPT_USERPWD, $this->orchestrator_user.':'.$this->orchestrator_passwd);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));

				$output = curl_exec($ch);
				$result = curl_getinfo($ch);
				curl_close($ch);

				//echo $output;
				
				$xml_runbook_params = @simplexml_load_string($output);
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
								`name` = !
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

			$runbooks = $this->get_runbooks();

			foreach($runbooks as &$runbook)
			{
				$folder_id = '00000000-0000-0000-0000-000000000000';
				if(!$this->core->db->select_ex($res, rpv("SELECT r.`guid` FROM @runbooks_folders AS r WHERE r.`guid` = ! LIMIT 1", $runbook['folder_id'])))
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
						$folder_id = $runbook['folder_id'];
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
							VALUES (!, !, !, !, #)
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
								`folder_id` = !,
								`name` = !,
								`description` = !
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
			}
		}
}
