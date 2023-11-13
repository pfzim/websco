<?php

function custom(&$core, $params, $post_data)
{
	$runbook = $core->Runbooks->get_runbook_by_id(intval(@$params[1]));
	
	if(($runbook['flags'] & RBF_TYPE_CUSTOM) == 0)
	{
		$core->error('ERROR: Runbook with ID '.$filepath.' is not a custom type!');
		return NULL;
	}

	if(!$core->UserAuth->check_permission($runbook['folder_id'], RB_ACCESS_EXECUTE))
	{
		$error_msg = LL('AccessDeniedToSection').' '.$runbook['folder_id'].' '.LL('forUser').' '.$core->UserAuth->get_login().'!';
		include(TEMPLATES_DIR.'tpl.message.php');
		return;
	}
	
	$custom_script_dir = ROOT_DIR.'custom'.DIRECTORY_SEPARATOR.preg_replace('/[^a-z0-9_-]/i', '', $runbook['guid']).DIRECTORY_SEPARATOR;
	$custom_script_file = $custom_script_dir.'main.php';
	if(!file_exists($custom_script_file))
	{
		$core->error('ERROR: Script '.$custom_script_file.' not found!');
		return NULL;
	}

	require_once($custom_script_file);
}
