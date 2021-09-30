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


	session_name('ZID');
	session_start();
	error_reporting(E_ALL);
	define('Z_PROTECTED', 'YES');

	//$self = $_SERVER['PHP_SELF'];

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
		echo '{"code": 1, "message": "'.LL('AccessDeniedToSection').' '.$section_id.' '.LL('forUser').' '.$core->UserAuth->get_login().'!"}';
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

	$path = '';
	if((php_sapi_name() == 'cli') && ($argc > 1) && !empty($argv[1]))
	{
		$path = $argv[1];
	}
	elseif(isset($_GET['path']))
	{
		$path = $_GET['path'];
	}

	$core = new Core(TRUE);
	$core->load_ex('db', 'MySQLDB');
	
	define('RB_ACCESS_LIST', 1);
	define('RB_ACCESS_EXECUTE', 2);
	$core->UserAuth->set_bits_representation('lx');

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
		$core->Router->add_route('list_folder', 'list_folder');					// default route
		//$core->Router->add_route('list_folders2', 'list_folders2');					// default route
		$core->Router->add_route('delete_permission', 'delete_permission', TRUE);
		$core->Router->add_route('expand', 'expand', TRUE);
		$core->Router->add_route('get_job', 'get_job', TRUE);
		$core->Router->add_route('get_permission', 'get_permission', TRUE);
		$core->Router->add_route('new_permission', 'new_permission', TRUE);
		$core->Router->add_route('get_permissions', 'get_permissions');
		$core->Router->add_route('get_runbook', 'get_runbook', TRUE);
		$core->Router->add_route('hide_folder', 'hide_folder', TRUE);
		$core->Router->add_route('list_jobs', 'list_jobs');
		$core->Router->add_route('list_tools', 'list_tools');
		$core->Router->add_route('logoff', 'logoff');
		$core->Router->add_route('permissions', 'permissions');
		$core->Router->add_route('save_permission', 'save_permission', TRUE);
		$core->Router->add_route('show_folder', 'show_folder', TRUE);
		$core->Router->add_route('start_runbook', 'start_runbook', TRUE);
		$core->Router->add_route('sync', 'sync', TRUE);
		$core->Router->add_route('sync_jobs', 'sync_jobs', TRUE);
	}

	$core->Router->process($path);
