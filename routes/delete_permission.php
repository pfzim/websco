<?php

function delete_permission(&$core, $params)
{
	$id = @$params[1];

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);

	log_db('Delete permission', 'id='.$id, 0);

	if(!$id || !$core->db->put(rpv("DELETE FROM `@access` WHERE `id` = # LIMIT 1", $id)))
	{
		echo '{"code": 1, "message": "Failed delete"}';
		exit;
	}

	echo '{"code": 0, "id": '.$id.', "message": "Permission deleted"}';
}
