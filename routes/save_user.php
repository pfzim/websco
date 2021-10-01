<?php

function save_user(&$core, $params)
{
	$result_json = array(
		'code' => 0,
		'message' => '',
		'errors' => array()
	);

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);

	$user_id = intval(@$_POST['id']);
	$login = @$_POST['login'];
	$mail = @$_POST['mail'];

	if(empty($login))
	{
		$result_json['code'] = 1;
		$result_json['errors'][] = array('name' => 'login', 'msg' => LL('ThisFieldRequired'));
	}

	if(empty($mail))
	{
		$result_json['code'] = 1;
		$result_json['errors'][] = array('name' => 'mail', 'msg' => LL('ThisFieldRequired'));
	}

	if($result_json['code'])
	{
		$result_json['message'] = LL('NotAllFilled');
	}
	elseif($core->UserAuth->set_user_info_ex($user_id, $login, $mail))
	{
		log_db('Updated user', '{id='.$user_id.',login="'.$login.'"}', 0);
		$result_json['message'] = LL('SuccessfulUpdated');
	}
	else
	{
		$result_json['code'] = 1;
		$result_json['message'] = LL('UnknownError');
	}

	//log_file('Password changed: '.json_encode($result_json, JSON_UNESCAPED_UNICODE));
	echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
