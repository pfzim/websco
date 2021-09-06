<?php
	/**
		\file
		\brief Get folders list from server
	*/

	if(!defined('Z_PROTECTED')) exit;

	function runbooks_get_folders()
	{
		$folders = array();
		$skip = 0;
		$total = 0;
		
		do
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, ORCHESTRATOR_URL.'/Folders?$inlinecount=allpages&$top=50&$skip='.$skip);
			curl_setopt($ch, CURLOPT_POST, false);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			curl_setopt($ch, CURLOPT_USERPWD, LDAP_USER.':'.LDAP_PASSWD);
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
