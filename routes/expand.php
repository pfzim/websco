<?php

function expand(&$core, $params)
{
	if(!isset($params[1]))
	{
		echo '{"code": 1, "status": "id undefined"}';
		exit;
	}

	$guid = @$params[1];

	$list = '';

	if($core->db->select_ex($folders, rpv('SELECT f.`id`, f.`guid`, f.`name` FROM @runbooks_folders AS f WHERE f.`pid` = ! ORDER BY f.`name`', $guid)))
	{
		foreach($folders as &$row)
		{
			if(!empty($list))
			{
				$list .= ', ';
			}
			$list .= '{"id": '.$row[0].', "guid": "'.json_escape($row[1]).'" ,"name": "'.json_escape($row[2]).'"}';
		}
	}

	echo '{"code": 0, "list": ['.$list.']}';
}
