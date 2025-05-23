<?php

function custom(&$core, $params, $post_data)
{
	$runbook = $core->Runbooks->get_runbook_by_id(intval(@$params[1]));
	
	if((intval($runbook['flags']) & RBF_TYPE_CUSTOM) == 0)
	{
		$core->error('ERROR: Runbook with ID '.intval(@$params[1]).' is not a custom type!');
		return NULL;
	}

	if(!$core->UserAuth->check_permission($runbook['folder_id'], RB_ACCESS_EXECUTE))
	{
		$error_msg = LL('AccessDeniedToSection').' '.$runbook['folder_id'].' '.LL('forUser').' '.$core->UserAuth->get_login().'!';
		include(TEMPLATES_DIR.'tpl.message.php');
		return;
	}

	$current_folder = array(
		'name' => LL('RootLevel'),
		'id' => 0,
		'pid' => '00000000-0000-0000-0000-000000000000',
		'guid' => '00000000-0000-0000-0000-000000000000',
		'childs' => NULL
	);

	if($core->db->select_assoc_ex($folder, rpv('SELECT f.`id`, f.`pid`, f.`guid`, f.`name` FROM @runbooks_folders AS f WHERE f.`id` = !', $runbook['folder_id'])))
	{
		if(!empty($folder[0]['name']))
		{
			$current_folder = array(
				'name' => $folder[0]['name'],
				'id' => $folder[0]['id'],
				'pid' => $folder[0]['pid'],
				'guid' => $folder[0]['guid'],
				'childs' => NULL
			);
		}
	}

	$folders_tree = $core->Runbooks->get_folders_tree(TRUE);

	$custom_script_dir = ROOT_DIR.'custom'.DIRECTORY_SEPARATOR.preg_replace('/[^a-z0-9_-]/i', '', $runbook['guid']).DIRECTORY_SEPARATOR;
	$custom_script_file = $custom_script_dir.'main.php';
	if(!file_exists($custom_script_file))
	{
		$core->error('ERROR: Script '.$custom_script_file.' not found!');
		return NULL;
	}

	require_once($custom_script_file);
}
