<?php
/**
 *  @file setting_save.php
 *  @brief При сохранении параметра оканчивающегося на _json производится
 *  попытка распарсить значение, чтобы исключить опечатки в формате.
 */
 
function setting_save(&$core, $params, $post_data)
{
	$result_json = array(
		'code' => 0,
		'message' => '',
		'errors' => array()
	);

	$user_id = intval(@$post_data['uid']);
	$setting_key = @$post_data['key'];
	$setting_value = @$post_data['value'];

	if(($user_id == 0) || ($user_id != $core->UserAuth->get_id()))
	{
		assert_permission_ajax(0, RB_ACCESS_EXECUTE);
	}

	if(!empty($setting_value) && preg_match('/_json$/i', $setting_key))
	{
		if(json_decode($setting_value, TRUE) === NULL)
		{
			$result_json['code'] = 1;
			$result_json['message'] = 'Failed parse JSON value. ERROR: '.json_last_error_msg();

			echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

			return;
		}
	}

	if($user_id)
	{
		$core->Config->set_user($setting_key, $setting_value);
	}
	else
	{
		$core->Config->set_global($setting_key, $setting_value);
	}

	log_db('Updated settings', '{uid='.$user_id.',key="'.$setting_key.',value="'.$setting_value.'"}', 0);
	$result_json['message'] = LL('SuccessfulUpdated');

	echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
