<?php

function delete_user(&$core, $params)
{
	$user_id = intval(@$params[1]);

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);

	if(!$user_id)
	{
		echo '{"code": 1, "message": "Undefinded user ID"}';
		return;
	}

	log_db('Delete user', '{id='.$user_id.'}', 0);

	if(!$core->UserAuth->delete_user_ex($user_id))
	{
		echo '{"code": 1, "message": "Failed delete"}';
		return;
	}

	echo '{"code": 0, "id": '.$user_id.', "message": "'.LL('UserDeleted').'"}';
}
