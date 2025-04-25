<?php

function runbook_move(&$core, $params, $post_data)
{
	$id = $post_data['id'];
	$pid = $post_data['pid'];
	
	$runbook = $core->Runbooks->get_runbook_by_id($id);

	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	if(($runbook['flags'] & (RBF_TYPE_ANSIBLE | RBF_TYPE_CUSTOM)) == 0)
	{
		$core->error('ERROR: Runbook with ID ' . $runbook['id'] . ' cannot be moved!');
		return NULL;
	}

	if(!$core->db->put(rpv("
			UPDATE
				@runbooks
			SET
				`folder_id` = #
			WHERE
				`id` = #
			LIMIT 1
		",
		$pid,
		$id
	)))
	{
		echo '{"code": 1, "message": "Failed update folder"}';
		return;
	}

	log_db('Runbook moved', '{id='.$id.'}', 0);
	echo '{"code": 0, "id": '.$id.', "message": "'.LL('RunbookWasMoved').' (ID: '.$id.')"}';
}
