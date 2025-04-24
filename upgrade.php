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

function exception_handler($exception)
{
	$error_msg = 'Exception: File: '.$exception->getFile().'['.$exception->getLine().']: '.$exception->getMessage().' Trace: '.$exception->getTraceAsString();
	echo $error_msg;
}

set_exception_handler('exception_handler');


require_once(ROOT_DIR.'inc.config.php');


if(!isset($_POST['key']) || !defined('UPGRADE_ADMIN_KEY') || ($_POST['key'] !== UPGRADE_ADMIN_KEY))
{
	header("Content-Type: text/html; charset=utf-8");

?><!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<title>Upgrade</title>
	</head>
	<body>
	<form method="POST">
		Enter UPGRADE_ADMIN_KEY from inc.config.php to start upgrade:<br />
		<input type="text" name="key" value="" /><br />
		<?php if(isset($_POST['key'])) { ?>
		<p style="color: red">Key doesn't match</p>
		<?php } ?>
		<?php if(!defined('UPGRADE_ADMIN_KEY')) { ?>
		<p style="color: red">Add parameter to inc.config.php: define('UPGRADE_ADMIN_KEY', 'YOU_SECRET_KEY_HERE');</p>
		<?php } ?>
		<input type="submit" value="Start upgrade" />
	</form>
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
	if(!$core->db->select_ex($cfg, rpv('SELECT m.`value` FROM @config AS m WHERE m.`name` = \'db_version\' AND m.`uid` = 0')))
	{
		throw 'Error get DB version: '.$core->db->get_last_error();
	}

	if($version > intval($cfg[0][0]))
	{
		echo PHP_EOL . 'Upgrade to version '. $version . ': ' . $message . PHP_EOL;
		$core->db->start_transaction();
		if(!$core->db->put($query))
		{
			throw 'Error upgrade to version '. $version . '. ERROR['.__LINE__.']: '.$core->db->get_last_error();
		}
		echo 'Setting db_version = '. $version . PHP_EOL;
		if(!$core->db->put(rpv("UPDATE @config SET `value` = {d0} WHERE `name` = 'db_version' LIMIT 1", $version)))
		{
			throw 'Error set DB version ' . $version . '. ERROR['.__LINE__.']: '.$core->db->get_last_error();
		}
		$core->db->commit();
		echo 'Upgrade to version ' . $version . ' complete!' . PHP_EOL;
	}
}

define('RBF_DELETED', 0x0001);
define('RBF_HIDED', 0x0002);
define('RBF_TYPE_CUSTOM', 0x0004);
define('RBF_TYPE_SCO', 0x0008);
define('RBF_TYPE_ANSIBLE', 0x0010);

db_upgrade($core, 3, 'Set flag RBF_TYPE_SCO to runbooks', rpv('UPDATE @runbooks SET `flags` = (`flags` | {%RBF_TYPE_SCO}) WHERE (`flags` & {%RBF_TYPE_CUSTOM}) = 0'));
db_upgrade($core, 4, 'Set flag RBF_TYPE_SCO to folders', rpv('UPDATE @runbooks_folders SET `flags` = (`flags` | {%RBF_TYPE_SCO})'));
db_upgrade($core, 5, 'Update parent IDs', rpv('UPDATE `@runbooks_folders` AS f LEFT JOIN `@runbooks_folders` AS parent ON f.`pid` = parent.`guid` SET f.`pid` = IFNULL(parent.`id`, 0), f.`name` = IF(f.`name` = \'\', \'(undefined folder name)\', f.`name`)'));
db_upgrade($core, 6, 'Change `pid` column type', rpv('ALTER TABLE `@runbooks_folders` MODIFY COLUMN `pid` INT(10) UNSIGNED NOT NULL'));
db_upgrade($core, 7, 'Change PRIMARY KEY for table `@runbooks_params`', rpv('ALTER TABLE `@runbooks_params` DROP PRIMARY KEY, ADD PRIMARY KEY (`pid`, `guid`)'));
db_upgrade($core, 8, 'Update parent IDs', rpv('UPDATE `@runbooks_params` AS rp JOIN `@runbooks` AS r ON rp.`pid` = r.`guid` AND r.flags & {%RBF_TYPE_SCO} SET rp.`pid` = r.`id`'));
db_upgrade($core, 9, 'Change `pid` column type', rpv('ALTER TABLE `@runbooks_params` MODIFY COLUMN `pid` INT(10) UNSIGNED NOT NULL'));
db_upgrade($core, 10, 'Add `extra_data_json` column', rpv('ALTER TABLE `@runbooks_params` ADD COLUMN `extra_data_json` VARCHAR(4096) NOT NULL DEFAULT '' AFTER `name`'));

echo PHP_EOL . 'Upgrade complete.' . PHP_EOL;
