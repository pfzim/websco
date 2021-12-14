<?php
	if(!defined('Z_PROTECTED')) exit;

	// Available variables:
	//   $core
	//   $post_data

	$result = NULL;
	$action = @$post_data['action'];
	
	switch($action)
	{
		case 'my_custom_example_action':
			{
				$result_raw = shell_exec('pwsh -File "'.$custom_script_dir.'example.ps1" -ComputerName "'.addslashes(@$post_data['server_name']).'" -Name "'.addslashes(@$post_data['example_input_value']).'"');
				$result = json_decode($result_raw, true);

				include($custom_script_dir.'tpl.example.php');
			}
			break;
		default:
			{
				include($custom_script_dir.'tpl.example.php');
			}
	}

	/*
		Execute SQL query to add this script to WebSCO:

			INSERT INTO w_runbooks (`folder_id`, `guid`,    `name`,                     `description`,          `flags`)
			VALUES                 (1,           'example', 'My custom example script', 'This is just a script', 0x0004);

		Change folder ID!
		
	*/
