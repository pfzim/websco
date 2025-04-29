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

define('DB_VERSION', 26);
define('Z_PROTECTED', 'YES');

error_reporting(E_ALL);

if(!defined('ROOT_DIR'))
{
	define('ROOT_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR);
	define('TEMPLATES_DIR', ROOT_DIR.'templates'.DIRECTORY_SEPARATOR);
	define('MODULES_DIR', ROOT_DIR.'modules'.DIRECTORY_SEPARATOR);
	define('ROUTES_DIR', ROOT_DIR.'routes'.DIRECTORY_SEPARATOR);
}

if(!file_exists(ROOT_DIR.'inc.config.php'))
{
	//header('Content-Type: text/plain; charset=utf-8');
	//echo 'Configuration file inc.config.php is not found!';
	header('Location: install.php');
	exit;
}

require_once(ROOT_DIR.'inc.config.php');

if (defined('USE_PRETTY_LINKS') && USE_PRETTY_LINKS
	&& (
		(defined('USE_PRETTY_LINKS_FORCE') && (USE_PRETTY_LINKS_FORCE))
		|| (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()))
	))
{
	define('PRETTY_LINKS_BASE_PATH', WEB_LINK_BASE_PATH);
	define('WEB_LINK_PREFIX', WEB_LINK_BASE_PATH);			// '/websco/'
	define('WEB_LINK_STATIC_PREFIX', WEB_LINK_BASE_PATH);
	define('WEB_LINK_EXTERNAL', WEB_URL);
}
else
{
	define('PRETTY_LINKS_BASE_PATH', '');
	define('WEB_LINK_PREFIX', basename(__FILE__).'?path=');
	define('WEB_LINK_STATIC_PREFIX', '');
	define('WEB_LINK_EXTERNAL', WEB_URL.WEB_LINK_PREFIX);
}

session_name('ZID');
session_start(
	array(
		'cookie_path' => defined('WEB_LINK_BASE_PATH') ? WEB_LINK_BASE_PATH : '/'
	)
);

if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
	$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
	$ip = @$_SERVER['REMOTE_ADDR'];
}

require_once(ROOT_DIR.'modules'.DIRECTORY_SEPARATOR.'Core.php');
require_once(ROOT_DIR.'languages'.DIRECTORY_SEPARATOR.APP_LANGUAGE.'.php');
require_once(ROOT_DIR.'inc.utils.php');

function assert_permission_ajax($section_id, $allow_bit)
{
	global $core;

	if(!$core->UserAuth->check_permission($section_id, $allow_bit))
	{
		echo '{"code": 1, "message": "'.LL('AccessDeniedToSection').' '.$section_id.' '.LL('forUser').' '.$core->UserAuth->get_login().'!"}';
		exit;
	}
}

function log_db($operation, $params, $flags)
{
	global $core;

	if(strlen($params) > 4096)
	{
		$params = substr($params, 0, 4093).'...';
	}

	$core->db->put(rpv('INSERT INTO @logs (`date`, `uid`, `operation`, `params`, `flags`) VALUES (NOW(), #, !, !, #)',
		$core->UserAuth->get_id(),
		$operation,
		$params,
		$flags
	));
}

function log_file($message)
{
	if(defined('LOG_FILE'))
	{
		error_log(date('c').'  '.$message."\n", 3, LOG_FILE);
	}
}

function LL($key)
{
	global $lang;

	if(empty($lang[$key]))
	{
		return '{'.$key.'}';
	}

	return $lang[$key];
}

function L($key)
{
	eh(LL($key));
}

function ln($path)
{
	eh(WEB_LINK_PREFIX.$path);
}

function ls($path)
{
	eh(WEB_LINK_STATIC_PREFIX.$path);
}

function exception_handler($exception)
{
	$error_msg = 'Exception: File: '.$exception->getFile().'['.$exception->getLine().']: '.$exception->getMessage().' Trace: '.$exception->getTraceAsString();
	include(TEMPLATES_DIR.'tpl.error.php');
	log_file($error_msg);
}

function exception_handler_ajax($exception)
{
	$error_msg = 'Exception: File: '.$exception->getFile().'['.$exception->getLine().']: '.$exception->getMessage().' Trace: '.$exception->getTraceAsString();
	echo '{"code": 1, "message": "'.json_escape($error_msg).'"}';
	log_file($error_msg);
}

	define('RBF_DELETED',				0x00000001);
	define('RBF_HIDED',					0x00000002);
	define('RBF_TYPE_CUSTOM',			0x00000004);
	define('RBF_TYPE_SCO',				0x00000008);
	define('RBF_TYPE_SCO2022',			0x00000010);
	define('RBF_TYPE_ANSIBLE',			0x00000020);
	define('RBF_TYPE_ANSIBLE_WF',		0x00000040);
	define('RBF_FIELD_TYPE_REQUIRED',	0x01000000);
	define('RBF_FIELD_TYPE_PASSWORD',	0x02000000);
	define('RBF_FIELD_TYPE_NUMBER',		0x04000000);
	define('RBF_FIELD_TYPE_LIST',		0x08000000);
	define('RBF_FIELD_TYPE_FLAGS',		0x10000000);
	define('RBF_FIELD_TYPE_STRING',		0x20000000);

	$core = new Core(TRUE);
	$core->load_ex('db', 'MySQLDB');

	if(intval($core->Config->get_global('db_version', 0)) != DB_VERSION)
	{
		header('Location: '.WEB_LINK_STATIC_PREFIX.'upgrade.php');
		exit;
	}

	define('RB_ACCESS_LIST', 1);
	define('RB_ACCESS_EXECUTE', 2);
	$core->UserAuth->set_bits_representation('lx');

	$path = '';
	$data = NULL;

	if((php_sapi_name() == 'cli') && ($argc > 1) && !empty($argv[1]))
	{
		$user = '';
		$password = '';
		$token = '';
		
		$i = 1;
		while($i < ($argc-1))
		{
			switch($argv[$i])
			{
				case '--user':
					{
						$user = $argv[$i+1];
					}
					break;
				case '--password':
					{
						$password = $argv[$i+1];
					}
					break;
				case '--token':
					{
						$token = $argv[$i+1];
					}
					break;
				case '--path':
					{
						$path = $argv[$i+1];
					}
					break;
				case '--data':
					{
						parse_str($argv[$i+1], $data);
					}
					break;
				default:
					echo 'Unknown argument: '.$argv[$i]."\n";
					exit(1);
			}
			
			$i += 2;
		}
		
		if(!empty($user))
		{
			if(!empty($token))
			{
				if(!$core->UserAuth->logon_by_token($user, $token))
				{
					echo 'Invalid username or token'."\n";
					exit(1);
				}
			}
			else
			{
				if(!$core->UserAuth->logon($user, $password))
				{
					echo 'Invalid username or password'."\n";
					exit(1);
				}
			}
		}
	}
	elseif(isset($_GET['path']))
	{
		$path = &$_GET['path'];
		$data = &$_POST;
	}

	$core->Router->set_exception_handler_regular('exception_handler');
	$core->Router->set_exception_handler_ajax('exception_handler_ajax');

	//$core->Router->add_route('info', 'info');

	if(!$core->UserAuth->get_id())
	{
		$core->Router->add_route('login', 'login');									// default route
		$core->Router->add_route('logon', 'logon');
	}
	else
	{
		$core->Router->add_route('runbooks', 'runbooks');							// default route
		$core->Router->add_route('runbooks_search', 'runbooks_search');

		$core->Router->add_route('runbooks_sync', 'runbooks_sync', TRUE);
		$core->Router->add_route('runbook_get', 'runbook_get', TRUE);
		$core->Router->add_route('runbook_start', 'runbook_start', TRUE);

		$core->Router->add_route('runbook_move', 'runbook_move', TRUE);
		$core->Router->add_route('runbook_move_form', 'runbook_move_form', TRUE);

		$core->Router->add_route('jobs', 'jobs');
		$core->Router->add_route('jobs_all', 'jobs_all');
		$core->Router->add_route('jobs_sync', 'jobs_sync', TRUE);
		$core->Router->add_route('jobs_sync_all', 'jobs_sync_all', TRUE);
		$core->Router->add_route('job_cancel', 'job_cancel', TRUE);
		$core->Router->add_route('job_get', 'job_get', TRUE);
		$core->Router->add_route('job_activity_get', 'job_activity_get', TRUE);
		$core->Router->add_route('job_params_get', 'job_params_get', TRUE);

		$core->Router->add_route('complete_account', 'complete_account', TRUE);
		$core->Router->add_route('complete_computer', 'complete_computer', TRUE);
		$core->Router->add_route('complete_mail', 'complete_mail', TRUE);
		$core->Router->add_route('complete_group', 'complete_group', TRUE);
		$core->Router->add_route('complete_group_sam', 'complete_group_sam', TRUE);

		$core->Router->add_route('setting_get', 'setting_get', TRUE);
		$core->Router->add_route('setting_save', 'setting_save', TRUE);

		$core->Router->add_route('tools', 'tools');

		$core->Router->add_route('folder_hide', 'folder_hide', TRUE);
		$core->Router->add_route('folder_show', 'folder_show', TRUE);
		$core->Router->add_route('folder_get', 'folder_get', TRUE);
		$core->Router->add_route('folder_save', 'folder_save', TRUE);
		$core->Router->add_route('folder_delete', 'folder_delete', TRUE);

		$core->Router->add_route('permissions', 'permissions');
		$core->Router->add_route('permissions_get', 'permissions_get', TRUE);
		//$core->Router->add_route('permissions_convert', 'permissions_convert', TRUE);
		$core->Router->add_route('permission_delete', 'permission_delete', TRUE);
		$core->Router->add_route('permission_get', 'permission_get', TRUE);
		$core->Router->add_route('permission_new', 'permission_new', TRUE);
		$core->Router->add_route('permission_save', 'permission_save', TRUE);

		$core->Router->add_route('users', 'users');
		$core->Router->add_route('user_get', 'user_get', TRUE);
		$core->Router->add_route('user_save', 'user_save', TRUE);
		$core->Router->add_route('user_activate', 'user_activate', TRUE);
		$core->Router->add_route('user_deactivate', 'user_deactivate', TRUE);
		$core->Router->add_route('user_delete', 'user_delete', TRUE);

		$core->Router->add_route('password_change_form', 'password_change_form', TRUE);
		$core->Router->add_route('password_change', 'password_change', TRUE);

		$core->Router->add_route('register_approve_form', 'register_approve_form');
		$core->Router->add_route('register_approve', 'register_approve');
		$core->Router->add_route('register_decline', 'register_decline');

		$core->Router->add_route('memcached_flush', 'memcached_flush', TRUE);

		$core->Router->add_route('custom', 'custom');
		$core->Router->add_route('job_custom_get', 'job_custom_get', TRUE);
	}

	$core->Router->add_route('logoff', 'logoff');
	
	$core->Router->add_route('password_reset_send_form', 'password_reset_send_form', TRUE);
	$core->Router->add_route('password_reset_send', 'password_reset_send', TRUE);
	$core->Router->add_route('password_reset_form', 'password_reset_form');
	$core->Router->add_route('password_reset', 'password_reset');

	$core->Router->add_route('register_form', 'register_form', TRUE);
	$core->Router->add_route('register', 'register', TRUE);

	$core->Router->process($path, $data);
