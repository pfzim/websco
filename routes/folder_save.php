<?php

function folder_save(&$core, $params, $post_data)
{
	$id = intval(@$post_data['id']);
	$pid = intval(@$post_data['pid']);
	$name = @$post_data['name'];

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);

	if(empty($name))
	{
		echo '{"code": 1, "message": "Failed create folder"}';
		return;
	}

	if($pid && !$core->db->select_ex($res, rpv("SELECT f.`id` FROM @runbooks_folders AS f WHERE f.`id` = # AND (f.`flags` & ({%RBF_DELETED} | {%RBF_TYPE_CUSTOM})) = {%RBF_TYPE_CUSTOM} LIMIT 1", $pid)))
	{
		echo '{"code": 1, "message": "Failed create folder"}';
		return;
	}

	if(!$id)
	{
		if(!$core->db->put(rpv("
				INSERT INTO @runbooks_folders (`guid`, `pid`, `name`, `flags`)
				VALUES (!, #, !, #)
			",
			0,
			$pid,
			$name,
			RBF_TYPE_CUSTOM
		)))
		{
			echo '{"code": 1, "message": "Failed create folder"}';
			return;
		}

		$id = $core->db->last_id();
		
		log_db('Created folder', '{id='.$id.'}', 0);
		echo '{"code": 0, "id": '.$id.', "message": "'.LL('FolderWasCreated').' (ID: '.$id.')"}';
		return;
	}
	else
	{
		if(!$core->db->put(rpv("UPDATE @runbooks_folders SET `pid` = #, `name` = ! WHERE `id` = # AND (`flags` & {%RBF_TYPE_CUSTOM}) = {%RBF_TYPE_CUSTOM}",
			$pid,
			$name,
			$id
		)))
		{
			echo '{"code": 1, "message": "Failed update folder"}';
			return;
		}
		log_db('Updated folder', '{id='.$id.'}', 0);
	}

	echo '{"code": 0, "id": '.$id.', "message": "'.LL('FolderWasUpdated').' (ID: '.$id.')"}';
}
