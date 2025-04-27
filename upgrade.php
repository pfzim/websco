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

define('RBF_DELETED',				0x00000001);
define('RBF_HIDED',					0x00000002);
define('RBF_TYPE_CUSTOM',			0x00000004);
define('RBF_TYPE_SCO',				0x00000008);
define('RBF_TYPE_SCO2022',			0x00000010);
define('RBF_TYPE_ANSIBLE',			0x00000020);
define('RBF_FIELD_TYPE_REQUIRED',	0x01000000);
define('RBF_FIELD_TYPE_PASSWORD',	0x02000000);
define('RBF_FIELD_TYPE_NUMBER',		0x04000000);
define('RBF_FIELD_TYPE_LIST',		0x08000000);
define('RBF_FIELD_TYPE_FLAGS',		0x10000000);
define('RBF_FIELD_TYPE_STRING',		0x20000000);

$runbook_type = (defined('ORCHESTRATOR_VERSION') && (ORCHESTRATOR_VERSION == 2022)) ? RBF_TYPE_SCO2022 : RBF_TYPE_SCO;

db_upgrade($core, 3, 'Add `description` column to @config', rpv('ALTER TABLE `@config` ADD COLUMN `description` VARCHAR(2048) DEFAULT NULL AFTER `value`'));
db_upgrade($core, 4, 'Set flag RBF_TYPE_SCO to @runbooks', rpv('UPDATE @runbooks SET `flags` = (`flags` | #) WHERE (`flags` & {%RBF_TYPE_CUSTOM}) = 0', $runbook_type));
db_upgrade($core, 5, 'Set flag RBF_TYPE_SCO to @runbooks_folders', rpv('UPDATE @runbooks_folders SET `flags` = (`flags` | #)', $runbook_type));
db_upgrade($core, 6, 'Set flag RBF_TYPE_SCO to @runbooks_servers', rpv('UPDATE @runbooks_servers SET `flags` = (`flags` | #)', $runbook_type));
db_upgrade($core, 7, 'Change PRIMARY KEY for table `@runbooks_params`', rpv('ALTER TABLE `@runbooks_params` DROP PRIMARY KEY, ADD PRIMARY KEY (`pid`, `guid`)'));
db_upgrade($core, 8, 'Change PRIMARY KEY for table `@runbooks_folders`', rpv('ALTER TABLE `@runbooks_folders` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`)'));
db_upgrade($core, 9, 'Change PRIMARY KEY for table `@runbooks`', rpv('ALTER TABLE `@runbooks` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`)'));
db_upgrade($core, 10, 'Change PRIMARY KEY for table `@runbooks_activities`', rpv('ALTER TABLE `@runbooks_activities` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`)'));
db_upgrade($core, 11, 'Change PRIMARY KEY for table `@runbooks_jobs`', rpv('ALTER TABLE `@runbooks_jobs` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`)'));
db_upgrade($core, 12, 'Change PRIMARY KEY for table `@runbooks_servers`', rpv('ALTER TABLE `@runbooks_servers` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`)'));
db_upgrade($core, 13, 'Update parent IDs in @runbooks_folders', rpv('UPDATE `@runbooks_folders` AS f LEFT JOIN `@runbooks_folders` AS parent ON f.`pid` = parent.`guid` SET f.`pid` = IFNULL(parent.`id`, 0), f.`name` = IF(f.`name` = \'\', \'(undefined folder name)\', f.`name`)'));
db_upgrade($core, 14, 'Change `pid` column type in @runbooks_folders', rpv('ALTER TABLE `@runbooks_folders` MODIFY COLUMN `pid` INT(10) UNSIGNED NOT NULL'));
db_upgrade($core, 15, 'Update parent IDs in @runbooks_params', rpv('UPDATE `@runbooks_params` AS rp JOIN `@runbooks` AS r ON rp.`pid` = r.`guid` AND r.flags & # SET rp.`pid` = r.`id`', $runbook_type));
db_upgrade($core, 16, 'Change `pid` column type in @runbooks_params', rpv('ALTER TABLE `@runbooks_params` MODIFY COLUMN `pid` INT(10) UNSIGNED NOT NULL'));
db_upgrade($core, 17, 'Add `extra_data_json` column to @runbooks_params', rpv('ALTER TABLE `@runbooks_params` ADD COLUMN `extra_data_json` VARCHAR(4096) NOT NULL DEFAULT \'\' AFTER `name`'));

if(defined('ORCHESTRATOR_VERSION') && (ORCHESTRATOR_VERSION == 2022) && !defined('ORCHESTRATOR2022_URL'))
{
	echo PHP_EOL . 'You must add new configuration parameters to inc.config.php:' . PHP_EOL;
	echo '  define(\'ORCHESTRATOR2022_URL\', \'https://scorch.example.org\');' . PHP_EOL;
	echo '  define(\'ORCHESTRATOR2022_USER\', \'scorch_user\');' . PHP_EOL;
	echo '  define(\'ORCHESTRATOR2022_PASSWD\', \'scorch_passwd\');' . PHP_EOL;
}

if(!defined('ORCHESTRATOR2022_URL') || !defined('ORCHESTRATOR_URL') || !defined('AWX_URL'))
{
	echo PHP_EOL . 'Not all parameters exist in config!!! Check and adjust your configuration to match example/inc.config.php.' . PHP_EOL;
	echo 'Check permissions on top folders!' . PHP_EOL;
}

echo PHP_EOL . 'Upgrade complete.' . PHP_EOL;
