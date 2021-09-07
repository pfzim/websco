<?php
/*
    WebSCO
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
	//require_once(ROOT_DIR.'inc.db.php');
	//require_once(ROOT_DIR.'inc.ldap.php');
	//require_once(ROOT_DIR.'inc.user.php');
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

	$action = '';
	if(isset($_GET['action']))
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
					$error_msg = 'Неверное имя пользователя или пароль!';
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

			//assert_permission_ajax(0, RB_ACCESS_EXECUTE);

			if(!$core->db->select_assoc_ex($permission, rpv("SELECT m.`id`, m.`oid`, m.`dn`, m.`allow_bits` FROM `@access` AS m WHERE m.`id` = # LIMIT 1", $id)))
			{
				echo '{"code": 1, "message": "Failed get permissions"}';
				exit;
			}

			$permission[0]['pid'] = &$permission[0]['oid'];

			for($i = 0; $i < 2; $i++)
			{
				$permission[0]['allow_bit_'.($i+1)] = ((ord($permission[0]['allow_bits'][(int) ($i / 8)]) >> ($i % 8)) & 0x01)?1:0;
			}

			$result_json = array(
				'code' => 0,
				'message' => '',
				'data' => $permission[0]
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

			if(intval(@$_POST['allow_bit_1']))
			{
				set_permission_bit($v_allow, RB_ACCESS_EXECUTE);
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
					$id = $core->db->last_id();
					echo '{"code": 0, "id": '.$id.', "message": "Added (ID '.$id.')"}';
					exit;
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
					echo '{"code": 0, "id": '.$id.',"message": "Updated (ID '.$id.')"}';
					exit;
				}
			}

			echo '{"code": 1, "id": '.$id.',"message": "Error: '.json_escape($core->get_last_error()).'"}';
		}
		exit;

		case 'permissions':
		{
			header("Content-Type: text/html; charset=utf-8");

			/*
			if(!$user_perm->check_permission(0, LPD_ACCESS_READ))
			{
				$error_msg = "Access denied to section 0 for user ".$uid."!";
				include('templ/tpl.message.php');
				exit;
			}
			*/

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
			$core->db->select_assoc_ex($permissions, rpv('SELECT a.`id`, a.`oid`, a.`dn`, a.`allow_bits` FROM `@access` AS a WHERE a.`oid` = # ORDER BY a.`dn`', $_GET['id']));

			include(TEMPLATES_DIR.'tpl.admin-permissions.php');
		}
		exit;

		case 'delete_permission':
		{
			assert_permission_ajax(0, RB_ACCESS_EXECUTE);

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

		case 'start_runbook':
		{
			header("Content-Type: text/plain; charset=utf-8");
			
			$runbook = $core->Runbooks->get_runbook($_GET['guid']);
			assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

			if($core->Runbooks->start_runbook($_GET['guid'], $_GET['param']))
			{
				echo '{"code": 0, "message": "OK"}';
			}
			else
			{
				echo '{"code": 1, "message": "Failed: Runbook not started"}';
			}
		}
		exit;

		case 'get_runbook':
		{
			$runbook = $core->Runbooks->get_runbook($_GET['guid']);
			assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

			$result_json = array(
				'code' => 0,
				'message' => '',
				'fields' => array_merge(
					array(
						array(
							'type' => 'header',
							'title' => $runbook['name']
						),
						array(
							'type' => 'hidden',
							'name' => 'action',
							'value' => 'start_runbook'
						),
						array(
							'type' => 'hidden',
							'name' => 'guid',
							'value' => $runbook['guid']
						)
					),
					$core->Runbooks->get_runbook_params($_GET['guid'])
				)
			);

			echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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

			$core->db->select_assoc_ex($folders, rpv('SELECT f.`guid`, f.`name` FROM @runbooks_folders AS f WHERE f.`pid` = ! ORDER BY f.`name`', $pid));
			$core->db->select_assoc_ex($runbooks, rpv('SELECT r.`guid`, r.`name` FROM @runbooks AS r WHERE r.`folder_id` = ! ORDER BY r.`name`', $current_folder[0]['id']));

			include(TEMPLATES_DIR.'tpl.list-folders.php');
		}
		exit;
	}

	header('Content-Type: text/html; charset=utf-8');

	include(TEMPLATES_DIR.'tpl.main.php');
