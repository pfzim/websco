<?php

function form_ask_mail_for_reset(&$core, $params)
{
	$result_json = array(
		'code' => 0,
		'message' => '',
		'title' => LL('ResetPassword'),
		'action' => 'reset_send_mail',
		'fields' => array(
			array(
				'type' => 'string',
				'name' => 'mail',
				'title' => LL('Mail').'*',
				'value' => ''
			)
		)
	);

	echo json_encode($result_json);
}
