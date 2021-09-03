<?php
	/**
		\file
		\brief Start runbook
	*/

	if(!defined('Z_PROTECTED')) exit;

	function runbook_start($id, $params)
	{
		$request = <<<'EOT'
<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<entry xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
    <content type="application/xml">
        <m:properties>
EOT;

		$request .= '<d:RunbookId m:type="Edm.Guid">'.$id.'</d:RunbookId>';

		if(!empty($params))
		{
			$request .= '<d:Parameters><![CDATA[<Data>';
			foreach($params as $key => $value)
			{
				$request .= '<Parameter><ID>{'.$key.'}</ID><Value>'.$value.'</Value></Parameter>';
			}
			$request .= '</Data>]]></d:Parameters>';
		}
		$request .= <<<'EOT'
        </m:properties>
    </content>
</entry>
EOT;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, ORCHESTRATOR_URL);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
		curl_setopt($ch, CURLOPT_USERPWD, LDAP_USER.':'.LDAP_PASSWD);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/atom+xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);


		$output = curl_exec($ch);
		$result = curl_getinfo($ch);

		return (intval($result['http_code']) == 201);
		
		//echo $request;
		//echo "\r\n\r\n\r\n----------------------------------------------------------\r\n\r\n\r\n";
		/*
		echo $output;

		$xml = @simplexml_load_string($output);
		if($xml !== FALSE)
		{
			echo 'ID: '.$xml->entry->content->children('d', true)->Id;
		}
		*/
	}
