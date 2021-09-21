<?php
/*
    WebSCO - web console for Microsoft System Center Orchestrator
    Copyright (C) 2021 Dmitry V. Zimin

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(!defined('ROOT_DIR'))
{
	define('ROOT_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR);
	define('TEMPLATES_DIR', ROOT_DIR.'templates'.DIRECTORY_SEPARATOR);
	define('MODULES_DIR', ROOT_DIR.'modules'.DIRECTORY_SEPARATOR);
}

if(!file_exists(ROOT_DIR.'inc.config.php'))
{
	header('Content-Type: text/plain; charset=utf-8');
	echo 'Configuration file inc.config.php is not found!';
	exit;
}

require_once(ROOT_DIR.'inc.config.php');


	session_name('ZID');
	session_start();
	error_reporting(E_ALL);
	define('Z_PROTECTED', 'YES');

	$self = $_SERVER['PHP_SELF'];

	if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = @$_SERVER['REMOTE_ADDR'];
	}

	require_once(ROOT_DIR.'modules'.DIRECTORY_SEPARATOR.'Core.php');
	require_once(ROOT_DIR.'languages'.DIRECTORY_SEPARATOR.'ru.php');
	require_once(ROOT_DIR.'inc.utils.php');

function assert_permission_ajax($section_id, $allow_bit)
{
	global $core;

	if(!$core->UserAuth->check_permission($section_id, $allow_bit))
	{
		echo '{"code": 1, "message": "Access denied to section '.$section_id.' for user '.$core->UserAuth->get_login().'!"}';
		exit;
	}
}

function log_db($operation, $params, $flags)
{
	global $core;

	$core->db->put(rpv('INSERT INTO @logs (`date`, `uid`, `operation`, `params`, `flags`) VALUES (NOW(), #, !, !, #)',
		$core->UserAuth->get_id(),
		$operation,
		$params,
		$flags
	));
}

function log_file($message)
{
	error_log(date('c').'  '.$message."\n", 3, '/var/log/websco/websco.log');
}

	$action = '';
	if((php_sapi_name() == 'cli') && ($argc > 1) && !empty($argv[1]))
	{
		$action = $argv[1];
	}
	elseif(isset($_GET['action']))
	{
		$action = $_GET['action'];
	}

	$id = 0;
	if(isset($_GET['id']))
	{
		$id = $_GET['id'];
	}

	if($action == 'message')
	{
		switch($id)
		{
			default:
				$error_msg = 'Unknown error';
				break;
		}

		include(TEMPLATES_DIR.'tpl.message.php');
		exit;
	}

	$core = new Core(TRUE);
	$core->load_ex('db', 'MySQLDB');
	$core->load('Mem');
	$core->load('LDAP');
	$core->load('UserAuth');
	$core->load('Runbooks');

	define('RB_ACCESS_EXECUTE', 1);
	$core->UserAuth->set_bits_representation('x');

	if(!$core->UserAuth->get_id())
	{
		header('Content-Type: text/html; charset=utf-8');
		switch($action)
		{
			case 'logon':
			{
				if(!$core->UserAuth->logon(@$_POST['login'], @$_POST['passwd']))
				{
					$error_msg = 'Invalid user name or password!';
					include(TEMPLATES_DIR.'tpl.login.php');
					exit;
				}

				/*
				if(!$core->UserAuth->is_member(LDAP_ADMIN_GROUP_DN))
				{
					$core->UserAuth->logoff();
					$error_msg = 'Access denied!';
					include(TEMPLATES_DIR.'tpl.login.php');
					exit;
				}
				*/

				header('Location: '.$self);
			}
			exit;

			case 'login':  // show login form
			{
				include(TEMPLATES_DIR.'tpl.login.php');
			}
			exit;
		}
	}

	if(!$core->UserAuth->get_id())
	{
		include(TEMPLATES_DIR.'tpl.login.php');
		exit;
	}

	switch($action)
	{
		case 'logoff':
		{
			$core->UserAuth->logoff();
			include(TEMPLATES_DIR.'tpl.login.php');
		}
		exit;

		case 'get_permission':
		{
			header("Content-Type: text/plain; charset=utf-8");

			assert_permission_ajax(0, RB_ACCESS_EXECUTE);

			if(!$core->db->select_assoc_ex($permission, rpv("SELECT m.`id`, m.`oid`, m.`dn`, m.`allow_bits` FROM `@access` AS m WHERE m.`id` = # LIMIT 1", $id)))
			{
				echo '{"code": 1, "message": "Failed get permissions"}';
				exit;
			}

			$result_json = array(
				'code' => 0,
				'message' => '',
				'title' => 'Edit permissions',
				'action' => 'save_permission',
				'fields' => array(
					array(
						'type' => 'hidden',
						'name' => 'id',
						'value' => $permission[0]['id']
					),
					array(
						'type' => 'hidden',
						'name' => 'pid',
						'value' => $permission[0]['oid']
					),
					array(
						'type' => 'string',
						'name' => 'dn',
						'title' => 'DN*',
						'value' => $permission[0]['dn'],
						'list' => array('Execute')
					),
					array(
						'type' => 'flags',
						'name' => 'allow_bits',
						'title' => 'Allow rights',
						'value' => ord($permission[0]['allow_bits'][0]) | (ord($permission[0]['allow_bits'][1]) << 8) | (ord($permission[0]['allow_bits'][2]) << 16) | (ord($permission[0]['allow_bits'][3]) << 24),
						'list' => array('Execute')
					),
					array(
						'type' => 'flags',
						'name' => 'apply_to_childs',
						'title' => 'Apply to childs',
						'value' => 0,
						'list' => array('Apply to childs', 'Replace childs')
					),
				)
			);

			echo json_encode($result_json);
		}
		exit;

		case 'save_permission':
		{
			header("Content-Type: text/plain; charset=utf-8");

			$result_json = array(
				'code' => 0,
				'message' => '',
				'errors' => array()
			);

			$v_id = intval(@$_POST['id']);
			$v_pid = intval(@$_POST['pid']);
			$v_dn = trim(@$_POST['dn']);
			$v_allow = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
			$v_apply_to_childs = intval(@$_POST['apply_to_childs'][0]);
			$v_replace_childs = intval(@$_POST['apply_to_childs'][1]);

			if(isset($_POST['allow_bits']))
			{
				foreach($_POST['allow_bits'] as $bit => $bit_value)
				{
					if(intval($bit_value))
					{
						set_permission_bit($v_allow, intval($bit) + 1);
					}
				}
			}

			assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// level 0 having access mean admin

			if(empty($v_dn))
			{
				$result_json['code'] = 1;
				$result_json['errors'][] = array('name' => 'dn', 'msg' => 'Fill DN!');
			}

			if($result_json['code'])
			{
				$result_json['message'] = 'Not all required field filled!';
				echo json_encode($result_json);
				exit;
			}

			if(!$v_id)
			{
				if($core->db->put(rpv("INSERT INTO `@access` (`oid`, `dn`, `allow_bits`) VALUES (#, !, !)",
					$v_pid,
					$v_dn,
					$v_allow
				)))
				{
					$v_id = $core->db->last_id();

					log_db('Added permission', 'id='.$v_id.';oid='.$v_pid.';dn='.$v_dn.';perms='.$core->UserAuth->permissions_to_string($v_allow), 0);

					$result_json['id'] = $v_id;
					$result_json['pid'] = $v_pid;
					$result_json['message'] = 'Added (ID '.$v_id.')';
				}
				else
				{
					$result_json['code'] = 1;
					$result_json['message'] = 'ERROR: '.$core->get_last_error();
				}
			}
			else
			{
				if($core->db->put(rpv("UPDATE `@access` SET `dn` = !, `allow_bits` = ! WHERE `id` = # AND `oid` = # LIMIT 1",
					$v_dn,
					$v_allow,
					$v_id,
					$v_pid
				)))
				{
					log_db('Updated permission', 'id='.$v_id.';oid='.$v_pid.';dn='.$v_dn.';perms='.$core->UserAuth->permissions_to_string($v_allow), 0);

					$result_json['id'] = $v_id;
					$result_json['pid'] = $v_pid;
					$result_json['message'] = 'Updated (ID '.$v_id.')';
				}
				else
				{
					$result_json['code'] = 1;
					$result_json['message'] = 'ERROR: '.$core->get_last_error();
				}
			}

			if($result_json['code'])
			{
				echo json_encode($result_json);
				exit;
			}

			if($v_apply_to_childs)
			{
				function permissions_apply_to_childs($parent_guid, $v_dn, $v_allow, $replace)
				{
					global $core;
					$childs = 0;

					//log_file('Apply to childs of ID: '.$parent_guid);
					if($core->db->select_assoc_ex($folders, rpv('SELECT f.`id`, f.`guid` FROM `@runbooks_folders` AS f WHERE f.`pid` = !', $parent_guid)))
					{
						foreach($folders as &$folder)
						{
							//log_file('  Folder ID: '.$folder['id'].', GUID: '.$folder['guid'].', Name: '.$folder['name']);

							if($core->db->select_assoc_ex($permissions, rpv('SELECT a.`id`, a.`allow_bits` FROM `@access` AS a WHERE a.`oid` = # AND a.`dn` = !', $folder['id'], $v_dn)))
							{
								if($replace)
								{
									$bits = $v_allow;
								}
								else
								{
									$bits = $core->UserAuth->merge_permissions($v_allow, $permissions[0]['allow_bits']);
								}
								$core->db->put(rpv("UPDATE `@access` SET `allow_bits` = ! WHERE `id` = # AND `oid` = # LIMIT 1", $bits, $permissions[0]['id'], $folder['id']));
								//log_file('  UPDATE');
							}
							else
							{
								$core->db->put(rpv("INSERT INTO `@access` (`oid`, `dn`, `allow_bits`) VALUES (#, !, !)", $folder['id'], $v_dn, $v_allow));
								//log_file('  INSERT');
							}

							$childs += permissions_apply_to_childs($folder['guid'], $v_dn, $v_allow, $replace) + 1;
						}
					}

					return $childs;
				}

				if($core->db->select_assoc_ex($folders, rpv('SELECT f.`guid` FROM `@runbooks_folders` AS f WHERE f.`id` = #', $v_pid)))
				{
					$result_json['childs'] = permissions_apply_to_childs($folders[0]['guid'], $v_dn, $v_allow, $v_replace_childs);
				}
			}

			echo json_encode($result_json);
		}
		exit;

		case 'permissions':
		{
			header("Content-Type: text/html; charset=utf-8");

			if(!$core->UserAuth->check_permission(0, RB_ACCESS_EXECUTE))
			{
				$error_msg = "Access denied to section 0 for user ".$core->UserAuth->get_login()."!";
				include(TEMPLATES_DIR.'tpl.message.php');
				exit;
			}

			if(empty($_GET['id']) || intval($_GET['id']) == 0)
			{
				//$core->db->select_assoc_ex($folder, rpv('SELECT f.`id`, f.`guid`, f.`pid`, f.`name` FROM `@runbooks_folders` AS f WHERE f.`id` = # ORDER BY f.`name`', $id));
				$current_folder = array(
					'name' => 'Top level',
					'id' => 0,
					'pid' => '00000000-0000-0000-0000-000000000000',
					'guid' => ''
				);
			}
			else
			{
				$core->db->select_assoc_ex($folder, rpv('SELECT f.`id`, f.`guid`, f.`pid`, f.`name` FROM `@runbooks_folders` AS f WHERE f.`id` = # ORDER BY f.`name`', $id));
				$current_folder = &$folder[0];
			}

			$core->db->select_assoc_ex($folders, rpv('SELECT f.`id`, f.`guid`, f.`name` FROM `@runbooks_folders` AS f WHERE f.`pid` = ! ORDER BY f.`name`', $current_folder['pid']));
			$core->db->select_assoc_ex($permissions, rpv('SELECT a.`id`, a.`oid`, a.`dn`, a.`allow_bits` FROM `@access` AS a WHERE a.`oid` = # ORDER BY a.`dn`', $current_folder['id']));

			include(TEMPLATES_DIR.'tpl.admin-permissions.php');
		}
		exit;

		case 'get_permissions':
		{
			header("Content-Type: text/plain; charset=utf-8");

			assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// level 0 having access mean admin

			if(empty($_GET['id']) || intval($_GET['id']) == 0)
			{
				$current_section = array(
					'name' => 'Top level',
					'id' => 0,
					'flags' => 0
				);
			}
			else
			{
				$core->db->select_assoc_ex($folder, rpv('SELECT f.`id`, f.`name`, f.`flags` FROM `@runbooks_folders` AS f WHERE f.`id` = # ORDER BY f.`name`', $_GET['id']));
				$current_section = &$folder[0];
			}

			$core->db->select_assoc_ex($permissions, rpv('SELECT a.`id`, a.`dn`, a.`allow_bits` FROM `@access` AS a WHERE a.`oid` = # ORDER BY a.`dn`', $current_section['id']));

			$result_json = array(
				'code' => 0,
				'name' => $current_section['name'],
				'id' => $current_section['id'],
				'flags' => $current_section['flags'],
				'permissions' => array()
			);

			foreach($permissions as &$row)
			{
				$group_name = &$row['dn'];
				if(preg_match('/^..=([^,]+),/i', $group_name, $matches))
				{
					$group_name = &$matches[1];
				}

				$result_json['permissions'][] = array(
					'id' => &$row['id'],
					'group' => $group_name,
					'perms' => $core->UserAuth->permissions_to_string($row['allow_bits'])
				);
			}

			echo json_encode($result_json);
		}
		exit;

		case 'delete_permission':
		{
			assert_permission_ajax(0, RB_ACCESS_EXECUTE);

			log_db('Delete permission', 'id='.$id, 0);

			if(!$core->db->put(rpv("DELETE FROM `@access` WHERE `id` = # LIMIT 1", $id)))
			{
				echo '{"code": 1, "message": "Failed delete"}';
				exit;
			}

			echo '{"code": 0, "id": '.$id.', "message": "Permission deleted"}';
		}
		exit;

		case 'expand':
		{
			if(!isset($_GET['guid']))
			{
				echo '{"code": 1, "status": "id undefined"}';
				exit;
			}

			$list = '';

			if($core->db->select_ex($folders, rpv('SELECT f.`id`, f.`guid`, f.`name` FROM @runbooks_folders AS f WHERE f.`pid` = ! ORDER BY f.`name`', $_GET['guid'])))
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
		exit;

		case 'sync':
		{
			header('Content-Type: text/plain; charset=utf-8');

			assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// level 0 having Write access mean admin

			$total = $core->Runbooks->sync();

			echo '{"code": 0, "message": "'.json_escape('Runbooks loaded: '.$total).'"}';
		}
		exit;

		case 'sync_jobs':
		{
			header('Content-Type: text/plain; charset=utf-8');

			$runbook_guid = '';
			if(!empty($_GET['guid']))
			{
				$runbook_guid = $_GET['guid'];
			}

			if(empty($runbook_guid))
			{
				assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// non-priveleged users cannot sync all jobs at once
			}

			$total = $core->Runbooks->sync_jobs($runbook_guid);

			echo '{"code": 0, "message": "'.json_escape('Jobs loaded: '.$total).'"}';
		}
		exit;

		case 'start_runbook':
		{
			header("Content-Type: text/plain; charset=utf-8");

			$runbook = $core->Runbooks->get_runbook($_POST['guid']);
			assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

			$result_json = array(
				'code' => 0,
				'message' => '',
				'errors' => array()
			);

			$params = array(
			);

			$runbook_params = $core->Runbooks->get_runbook_params($runbook['guid']);

			//log_file(json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

			foreach($runbook_params as &$param)
			{
				$value = '';

				if($param['type'] == 'flags')
				{
					$flags = 0;
					if(isset($_POST['param'][$param['guid']]))
					{
						foreach($_POST['param'][$param['guid']] as $bit => $bit_value)
						{
							if(intval($bit_value))
							{
								$flags |= 0x01 << intval($bit);
							}
						}
					}

					if($param['required'] && ($flags == 0))
					{
						$result_json['code'] = 1;
						$result_json['errors'][] = array('name' => 'param['.$param['guid'].'][0]', 'msg' => 'At least one flag must be selected');
					}
					else
					{
						$params[$param['guid']] = strval($flags);
					}

					//log_file('Value: '.strval($flags));
					continue;
				}
				elseif(isset($_POST['param'][$param['guid']]))
				{
					$value = trim($_POST['param'][$param['guid']]);
				}

				if($param['required'] && $value == '')
				{
					$result_json['code'] = 1;
					$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => 'This field is required');
					continue;
				}
				elseif($param['type'] == 'date')
				{
					list($nd, $nm, $ny) = explode('.', $value, 3);

					if(!datecheck($nd, $nm, $ny))
					{
						$result_json['code'] = 1;
						$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => 'Incorrect date DD.MM.YYYY');
						continue;
					}
				}
				elseif($param['type'] == 'list')
				{
					if(!in_array($value, $param['list']))
					{
						$result_json['code'] = 1;
						$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => 'Value not from list ('.implode(', ', $param['list']).')');
						continue;
					}
				}
				elseif($param['type'] == 'integer')
				{
					if(!preg_match('/^\d+$/i', $value))
					{
						$result_json['code'] = 1;
						$result_json['errors'][] = array('name' => 'param['.$param['guid'].']', 'msg' => 'Only numbers accepted');
						continue;
					}
				}

				$params[$param['guid']] = $value;
			}

			if($result_json['code'])
			{
				$result_json['message'] = 'Not all required fields are filled in correctly!';
				echo json_encode($result_json);
				exit;
			}

			//echo '{"code": 0, "guid": "0062978a-518a-4ba9-9361-4eb88ea3e0b0", "message": "Debug placeholder save_uform. Remove this line later'.$runbook['guid'].json_encode($runbook_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'"}'; exit;

			log_db('Run: '.$runbook['name'], json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0);

			$job_guid = $core->Runbooks->start_runbook($runbook['guid'], $params);

			if($job_guid !== FALSE)
			{
				if($core->db->put(rpv('INSERT INTO @runbooks_jobs (`date`, `pid`, `guid`, `uid`, `flags`) VALUES (NOW(), #, !, #, 0)', $runbook['id'], $job_guid, $core->UserAuth->get_id())))
				{
					$job_id = $core->db->last_id();

					foreach($params as $key => $value)
					{
						$core->db->put(rpv('INSERT INTO @runbooks_jobs_params (`pid`, `guid`, `value`) VALUES (#, !, !)', $job_id, $key, $value));
					}
				}

				log_db('Job created: '.$runbook['name'], $job_guid, 0);
				echo '{"code": 0, "guid": "'.json_escape($job_guid).'", "message": "Created job ID: '.json_escape($job_guid).'"}';
			}
			else
			{
				echo '{"code": 1, "message": "Failed: Runbook not started"}';
			}
		}
		exit;

		case 'get_job':
		{
			header("Content-Type: text/plain; charset=utf-8");

			echo json_encode($core->Runbooks->get_job($_GET['guid']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}
		exit;

		case 'list_jobs':
		{
			header("Content-Type: text/html; charset=utf-8");

			$runbook = $core->Runbooks->get_runbook($_GET['guid']);

			if(!$core->UserAuth->check_permission($runbook['folder_id'], RB_ACCESS_EXECUTE))
			{
				$error_msg = "Access denied to section ".$runbook['folder_id']." for user ".$core->UserAuth->get_login()."!";
				include(TEMPLATES_DIR.'tpl.message.php');
				exit;
			}

			$offset = 0;
			$total = 0;

			if(isset($_GET['offset']))
			{
				$offset = $_GET['offset'];
			}

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
		exit;

		case 'get_runbook':
		{
			$runbook = $core->Runbooks->get_runbook($_GET['guid']);
			assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

			$result_json = array(
				'code' => 0,
				'message' => '',
				'title' => $runbook['name'],
				'description' => $runbook['description'],
				'action' => 'start_runbook',
				'fields' => array(
					/*
					array(
						'type' => 'hidden',
						'name' => 'action',
						'value' => 'start_runbook'
					),
					*/
					array(
						'type' => 'hidden',
						'name' => 'guid',
						'value' => $runbook['guid']
					)
				)
			);

			$params = $core->Runbooks->get_runbook_params($runbook['guid']);

			$job_params = NULL;

			if(!empty($_GET['job_id']))
			{
				$core->db->select_assoc_ex($job_params, rpv('SELECT jp.`guid`, jp.`value` FROM @runbooks_jobs_params AS jp WHERE jp.`pid` = #', $_GET['job_id']));
			}

			foreach($params as &$param)
			{
				 $field = array(
					'type' => $param['type'],
					'name' => 'param['.$param['guid'].']',
					'title' => $param['name'],
					'value' => ''
				);

				if(($param['type'] == 'list') || ($param['type'] == 'flags'))
				{
					$field['list'] = $param['list'];
				}

				foreach($job_params as &$row)
				{
					if($row['guid'] == $param['guid'])
					{
						$field['value'] = $row['value'];
						break;
					}
				}

				$result_json['fields'][] = $field;
			}

			echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}
		exit;

		case 'hide_folder':
		{
			assert_permission_ajax(0, RB_ACCESS_EXECUTE);

			log_db('Hide folder', 'id='.$id, 0);

			if(!$core->db->put(rpv("UPDATE `@runbooks_folders` SET `flags` = (`flags` | 0x0002) WHERE `id` = # LIMIT 1", $id)))
			{
				echo '{"code": 1, "message": "Failed hide"}';
				exit;
			}

			echo '{"code": 0, "id": '.$id.', "message": "The folder was hidden (ID: '.$id.')"}';
		}
		exit;

		case 'show_folder':
		{
			assert_permission_ajax(0, RB_ACCESS_EXECUTE);

			log_db('Hide folder', 'id='.$id, 0);

			if(!$core->db->put(rpv("UPDATE `@runbooks_folders` SET `flags` = (`flags` & ~0x0002) WHERE `id` = # LIMIT 1", $id)))
			{
				echo '{"code": 1, "message": "Failed hide"}';
				exit;
			}

			echo '{"code": 0, "id": '.$id.', "message": "The folder was shown (ID: '.$id.')"}';
		}
		exit;

		case 'list_folder':
		{
			if(empty($_GET['guid']))
			{
				$pid = '00000000-0000-0000-0000-000000000000';
			}
			else
			{
				$pid = $_GET['guid'];
			}

			$current_folder_name = 'Root';
			$parent_folder_id = '';

			if($core->db->select_assoc_ex($current_folder, rpv('SELECT f.`id`, f.`pid`, f.`name` FROM @runbooks_folders AS f WHERE f.`guid` = !', $pid)))
			{
				if(!empty($current_folder[0]['name']))
				{
					$current_folder_name = $current_folder[0]['name'];
					$parent_folder_id = $current_folder[0]['pid'];
				}
			}

			$core->db->select_assoc_ex($folders, rpv('SELECT f.`guid`, f.`name` FROM @runbooks_folders AS f WHERE (f.`flags` & (0x0001 | 0x0002)) = 0 AND f.`pid` = ! ORDER BY f.`name`', $pid));
			$core->db->select_assoc_ex($runbooks, rpv('SELECT r.`guid`, r.`name` FROM @runbooks AS r WHERE (r.`flags` & (0x0001 | 0x0002)) = 0 AND r.`folder_id` = ! ORDER BY r.`name`', $current_folder[0]['id']));

			include(TEMPLATES_DIR.'tpl.list-folders.php');
		}
		exit;

		case 'list_tools':
		{
			include(TEMPLATES_DIR.'tpl.list-tools.php');
		}
		exit;
	}

	header('Content-Type: text/html; charset=utf-8');

	include(TEMPLATES_DIR.'tpl.main.php');
