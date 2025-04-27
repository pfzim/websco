<?php

function folder_delete(&$core, $params, $post_data)
{
	$id = intval(@$post_data['id']);

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);

	if(!$id || $core->db->select_ex($childs, rpv("SELECT COUNT(*) AS `childs_count` FROM `@runbooks_folders` AS f WHERE f.`pid` = # AND (f.`flags` & {%RBF_DELETED}) = 0", $id)))
	{
		echo '{"code": 1, "message": "Folder have ' . intval($childs[0][0]) . ' childs. Remove childs folders before"}';
		exit;
	}

	if($core->db->select_ex($childs, rpv("SELECT COUNT(*) AS `childs_count` FROM `@runbooks` AS r WHERE r.`folder_id` = # AND (r.`flags` & {%RBF_DELETED}) = 0", $id)))
	{
		echo '{"code": 1, "message": "Folder have ' . intval($childs[0][0]) . ' runbooks. Move runbooks to another folder before remove this folder"}';
		exit;
	}

	if(!$core->db->put(rpv("UPDATE `@runbooks_folders` SET `flags` = (`flags` | {%RBF_DELETED}) WHERE `id` = # AND `flags` & {%RBF_TYPE_CUSTOM} LIMIT 1", $id)))
	{
		echo '{"code": 1, "message": "Failed delete folder"}';
		exit;
	}

	log_db('Deleted folder', '{id='.$id.'}', 0);

	echo '{"code": 0, "id": '.$id.', "message": "'.LL('FolderWasDeleted').' (ID: '.$id.')"}';
}
