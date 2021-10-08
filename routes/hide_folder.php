<?php

function hide_folder(&$core, $params, $post_data)
{
	$id = intval(@$post_data['id']);

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);


	if(!$id || !$core->db->put(rpv("UPDATE `@runbooks_folders` SET `flags` = (`flags` | 0x0002) WHERE `id` = # LIMIT 1", $id)))
	{
		echo '{"code": 1, "message": "Failed hidden"}';
		exit;
	}

	log_db('Hidden folder', '{id='.$id.'}', 0);

	echo '{"code": 0, "id": '.$id.', "message": "'.LL('FolderWasHidden').' (ID: '.$id.')"}';
}
