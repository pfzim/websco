<?php
/*
    WebSCO - web console for Microsoft System Center Orchestrator
    Copyright (C) 2025 Dmitry V. Zimin

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
	header('Location: install.php');
	exit;
}

require_once(ROOT_DIR.'inc.config.php');


	if(!isset($_GET['action']) || ($_GET['action'] != 'upgrade'))
	{
		header("Content-Type: text/html; charset=utf-8");
?><!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title>Upgrade WebSCO</title>
	</head>
	<body>
	Start <a href="?action=upgrade">upgrade</a>
	</body>
</html>
<?php
		exit;
	}

	header("Content-Type: text/plain; charset=utf-8");

	require_once(ROOT_DIR.'inc.utils.php');
	require_once(ROOT_DIR.'modules'.DIRECTORY_SEPARATOR.'Core.php');

	$core = new Core(TRUE);
	$core->load_ex('db', 'MySQLDB');
	$core->load('UserAuth');

	echo "Upgrading...\n";

	function db_upgrade($core, $version, $message, $query)
	{
		if($version > intval($core->Config->get_global('db_version', 0)))
		{
			echo PHP_EOL . $message . PHP_EOL;
			if(!$core->db->put($query))
			{
				throw 'Error upgrade to version '. $version . '. ERROR['.__LINE__.']: '.$core->db->get_last_error();
			}
			echo 'Setting db_version = '. $version . PHP_EOL;
			if(!$core->db->put(rpv("UPDATE @config SET `value` = {d0} WHERE `name` = 'db_version' LIMIT 1", $version)))
			{
				throw 'Error set DB version '. $version . '. ERROR['.__LINE__.']: '.$core->db->get_last_error();
			}
			echo 'Upgrade to version '. $version .' complete!' . PHP_EOL;
		}
	}

	define('RBF_DELETED', 0x0001);
	define('RBF_HIDED', 0x0002);
	define('RBF_TYPE_CUSTOM', 0x0004);
	define('RBF_TYPE_SCO', 0x0008);
	define('RBF_TYPE_ANSIBLE', 0x0010);

	db_upgrade($core, 3, 'Set flag RBF_TYPE_SCO', 'UPDATE @runbooks SET `flags` = (`flags` | {%RBF_TYPE_SCO}) WHERE (`flags` & {%RBF_TYPE_CUSTOM}) = 0');
