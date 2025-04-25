<?php

function folder_delete(&$core, $params, $post_data)
{
	$id = intval(@$post_data['id']);

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);

	if(!$id || $core->db->select_ex($childs, rpv("SELECT f.`id` FROM `@runbooks_folders` AS f WHERE f.`pid` = # AND (`flags` & {%RBF_DELETED}) = 0 LIMIT 1", $id)))
	{
		echo '{"code": 1, "message": "Remove childs before this folder"}';
		exit;
	}

	if(!$id || !$core->db->put(rpv("UPDATE `@runbooks_folders` SET `flags` = (`flags` | {%RBF_DELETED}) WHERE `id` = # AND `flags` & {%RBF_TYPE_CUSTOM} LIMIT 1", $id)))
	{
		echo '{"code": 1, "message": "Failed deleted"}';
		exit;
	}

	log_db('Deleted folder', '{id='.$id.'}', 0);

	echo '{"code": 0, "id": '.$id.', "message": "'.LL('FolderWasDeleted').' (ID: '.$id.')"}';
}
