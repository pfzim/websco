<?php
/**
 *  @file setting_user_save.php
 *  @brief При сохранении параметра оканчивающегося на _json производится
 *  попытка распарсить значение, чтобы исключить опечатки в формате.
 */

function language_change(&$core, $params, $post_data)
{
	$result_json = array(
		'code' => 0,
		'message' => '',
		'errors' => array()
	);

	$language = @$post_data['value'];

	$_SESSION[DB_PREFIX.'lang'] = $language;
	if($core->UserAuth->get_id())
	{
		$core->Config->set_user('language', $language);
	}

	$result_json['message'] = LL('SuccessfulUpdated');

	echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
