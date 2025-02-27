<?php
	if(!defined('Z_PROTECTED')) exit;

	// Available variables:
	//   $core
	//   $post_data

	$result = NULL;
	$action = @$post_data['action'];

	#$core->db->put(rpv('INSERT INTO @runbooks_jobs (`date`, `pid`, `guid`, `uid`, `flags`) VALUES (NOW(), #, !, #, 0)', $runbook['id'], $runbook['guid'], $core->UserAuth->get_id()));
	log_db('Run: '.$runbook['name'], '', 0);

	include($custom_script_dir.'tpl.password_generator.php');

	/*
		Execute SQL query to add this script to WebSCO:

			INSERT INTO w_runbooks (`folder_id`, `guid`,               `name`,               `description`,              `flags`)
			VALUES                 (1,           'password_generator', 'Password generator', 'Online password generator', 0x0004);

		Change folder ID!
		
	*/
