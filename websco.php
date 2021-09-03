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

	require_once(ROOT_DIR.'languages'.DIRECTORY_SEPARATOR.'ru.php');
	require_once(ROOT_DIR.'inc.db.php');
	require_once(ROOT_DIR.'inc.ldap.php');
	require_once(ROOT_DIR.'inc.user.php');
	require_once(ROOT_DIR.'inc.utils.php');

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

	$db = new MySQLDB(DB_RW_HOST, NULL, DB_USER, DB_PASSWD, DB_NAME, DB_CPAGE, TRUE);

	$ldap = new LDAP(LDAP_URI, LDAP_USER, LDAP_PASSWD, FALSE);
	$user = new UserAuth($db, $ldap);

	if(!$user->get_id())
	{
		header('Content-Type: text/html; charset=utf-8');
		switch($action)
		{
			case 'logon':
			{
				if(!$user->logon(@$_POST['login'], @$_POST['passwd']))
				{
					$error_msg = 'Неверное имя пользователя или пароль!';
					include(TEMPLATES_DIR.'tpl.login.php');
					exit;
				}

				if(!$user->is_member(LDAP_ADMIN_GROUP_DN))
				{
					$user->logoff();
					$error_msg = 'Access denied!';
					include(TEMPLATES_DIR.'tpl.login.php');
					exit;
				}

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

	if(!$user->get_id())
	{
		include(TEMPLATES_DIR.'tpl.login.php');
		exit;
	}

	switch($action)
	{
		case 'logoff':
		{
			$user->logoff();
			include(TEMPLATES_DIR.'tpl.login.php');
		}
		exit;

		case 'load_runbooks_to_db':
		{
			include(ROOT_DIR.'modules'.DIRECTORY_SEPARATOR.'runbooks-list.php');

			header('Content-Type: text/plain; charset=utf-8');
			$runbooks = runbooks_get_list();

			foreach($runbooks as &$runbook)
			{
				$folder_id = 0;
				if(!$db->select_ex($res, rpv("SELECT r.`id` FROM @runbooks_folders AS r WHERE r.`guid` = ! LIMIT 1", $runbook['folder_id'])))
				{
					if($db->put(rpv("
							INSERT INTO @runbooks_folders (`pid`, `guid`, `name`, `flags`)
							VALUES (#, !, !, #)
						",
						0,
						$runbook['folder_id'],
						$runbook['path'],
						0x0000
					)))
					{
						$folder_id = $db->last_id();
					}
				}
				else
				{
					$folder_id = $res[0][0];
				}

				$runbook_id = 0;
				if(!$db->select_ex($res, rpv("SELECT r.`id` FROM @runbooks AS r WHERE r.`guid` = ! LIMIT 1", $runbook['id'])))
				{
					if($db->put(rpv("
							INSERT INTO @runbooks (`pid`, `guid`, `name`, `description`, `flags`)
							VALUES (#, !, !, !, #)
						",
						$folder_id,
						$runbook['id'],
						$runbook['name'],
						$runbook['description'],
						0x0000
					)))
					{
						$runbook_id = $db->last_id();
					}
				}
				else
				{
					$db->put(rpv("
							UPDATE
								@runbooks
							SET
								`pid` = #,
								`name` = !,
								`description` = !
							WHERE
								`id` = #
							LIMIT 1
						",
						$folder_id,
						$runbook['name'],
						$runbook['description'],
						$res[0][0]
					));

					$runbook_id = $res[0][0];
				}

				if($runbook_id)
				{
					$db->put(rpv("DELETE FROM @runbooks_params WHERE `pid` = #", $runbook_id));

					foreach($runbook['params'] as &$params)
					{
						$db->put(rpv("
								INSERT INTO @runbooks_params (`pid`, `guid`, `name`, `flags`)
								VALUES (#, !, !, #)
							",
							$runbook_id,
							$params['id'],
							$params['name'],
							0x0000
						));
					}
				}
			}
		}
		exit;

		case 'start_runbook':
		{
			include(MODULES_DIR.'runbooks-start.php');

			runbook_start($_GET['guid'], $_GET['param']);
		}
		exit;

		case 'list_runbooks':
		{
			if(!$db->select_assoc_ex($runbooks, rpv("SELECT r.`id`, r.`name` FROM @runbooks AS r")))
			{
				exit;
			}

			include(TEMPLATES_DIR.'tpl.list-runbooks.php');
		}
		exit;

		case 'show_runbook':
		{
			if(!$db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`name` FROM @runbooks AS r WHERE r.`id` = # LIMIT 1", $id)))
			{
				exit;
			}
			
			$runbook = $runbook[0];

			$db->select_assoc_ex($runbook_params, rpv("SELECT p.`guid`, p.`name` FROM @runbooks_params AS p WHERE p.`pid` = # ORDER BY p.`name`", $id));

			include(TEMPLATES_DIR.'tpl.show-runbook.php');
		}
		exit;
	}

	header('Content-Type: text/html; charset=utf-8');

	include(TEMPLATES_DIR.'tpl.main.php');
