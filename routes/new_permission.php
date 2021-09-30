<?php

function new_permission(&$core, $params)
{
	$id = @$params[1];

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);

	$result_json = array(
		'code' => 0,
		'message' => '',
		'title' => LL('AddPermissions'),
		'action' => 'save_permission',
		'fields' => array(
			array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => 0
			),
			array(
				'type' => 'hidden',
				'name' => 'pid',
				'value' => $id
			),
			array(
				'type' => 'string',
				'name' => 'dn',
				'title' => LL('GroupDN').'*',
				'value' => '',
				'placeholder' => 'CN=WebSCO access group,OU=Access groups,DC=domain,DC=local',
			),
			array(
				'type' => 'flags',
				'name' => 'allow_bits',
				'title' => LL('AllowRights'),
				'value' => 0,
				'list' => array(LL('List'), LL('Execute'))
			),
			array(
				'type' => 'flags',
				'name' => 'apply_to_childs',
				'title' => LL('ApplyToChilds'),
				'value' => 0,
				'list' => array(LL('ApplyToChilds'), LL('ReplaceChilds'))
			),
		)
	);

	echo json_encode($result_json);
}
