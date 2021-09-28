<?php

function list_jobs(&$core, $params)
{
	$guid = @$params[1];

	if(isset($params[2]))
	{
		$offset = $params[2];
	}
	else
	{
		$offset = 0;
	}
	
	$runbook = $core->Runbooks->get_runbook($guid);

	if(!$core->UserAuth->check_permission($runbook['folder_id'], RB_ACCESS_EXECUTE))
	{
		$error_msg = LL('AccessDeniedToSection').' '.$runbook['folder_id'].' '.LL('forUser').' '.$core->UserAuth->get_login().'!';
		include(TEMPLATES_DIR.'tpl.message.php');
		exit;
	}

	$current_folder_guid = $runbook['folder_guid'];

	function load_tree(&$core, $guid, &$filter_folders)
	{
		$childs = NULL;
		
		if($core->db->select_assoc_ex($folders, rpv('SELECT f.`guid`, f.`name` FROM @runbooks_folders AS f WHERE f.`pid` = {s0} {r1} ORDER BY f.`name`', $guid, $filter_folders)))
		{
			$childs = array();
			
			foreach($folders as $folder)
			{
				$childs[] = array(
					'name' => $folder['name'],
					'guid' => $folder['guid'],
					'childs' => load_tree($core, $folder['guid'], $filter_folders)
				);
			}
		}

		return $childs;
	}

	$folders_tree = array(
		array(
			'name' => LL('RootLevel'),
			'guid' => '00000000-0000-0000-0000-000000000000',
			'childs' => load_tree($core, '00000000-0000-0000-0000-000000000000', $filter_folders)
		)
	);

	$total = 0;

	if($core->db->select_ex($result, rpv("SELECT COUNT(*) FROM @runbooks_jobs AS j WHERE j.`pid` = #", $runbook['id'])))
	{
		$total = $result[0][0];
	}

	$core->db->select_assoc_ex($jobs, rpv('
		SELECT
			j.`id`,
			DATE_FORMAT(j.`date`, \'%d.%m.%Y %H:%i:%s\') AS `run_date`,
			j.`guid`,
			u.`login`
		FROM @runbooks_jobs AS j
		LEFT JOIN @users AS u ON u.`id` = j.`uid`
		WHERE j.`pid` = #
		ORDER BY j.`date` DESC, j.`id` DESC
		LIMIT #,100
	',
		$runbook['id'],
		$offset
	));

	include(TEMPLATES_DIR.'tpl.list-jobs.php');
}
