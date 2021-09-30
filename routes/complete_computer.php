<?php

function complete_computer(&$core, $params)
{
	$search = @$_POST['search'];

	$result_json = array(
		'code' => 0,
		'message' => '',
		'list' => array()
	);
	
	if(!empty($search) && strlen($search) >= 3)
	{
		if($core->LDAP->search($result, '(&(objectClass=computer)(sAMAccountName='.ldap_escape($search, null, LDAP_ESCAPE_FILTER).'*))', array('samaccountname')))
		{
			foreach($result as $row)
			{
				$result_json['list'][] = str_replace('$', '', $row['sAMAccountName'][0]);
			}
		}
	}

	echo json_encode($result_json);
}
