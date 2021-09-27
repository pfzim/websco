<?php

function show_folder(&$core, $params)
{
	$id = intval($params[1]);

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);

	log_db('Hide folder', 'id='.$id, 0);

	if(!$id || !$core->db->put(rpv("UPDATE `@runbooks_folders` SET `flags` = (`flags` & ~0x0002) WHERE `id` = # LIMIT 1", $id)))
	{
		echo '{"code": 1, "message": "Failed hide"}';
		exit;
	}

	echo '{"code": 0, "id": '.$id.', "message": "'.LL('FolderWasShown').' (ID: '.$id.')"}';
}
