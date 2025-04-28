<?php

function job_params_get(&$core, $params, $post_data)
{
	$job_id = @$params[1];
	
	$runbook = $core->Runbooks->get_runbook_by_job_id($job_id);

	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	$result_json = array(
		'code' => 0,
		'message' => '',
		'params' => NULL
	);

	if($runbook['flags'] & RBF_TYPE_CUSTOM)
	{
		if(!$core->db->select_assoc_ex($job_params, rpv('SELECT jp.`guid` AS `name`, jp.`value` FROM @runbooks_jobs_params AS jp WHERE jp.`pid` = # ORDER BY jp.`guid`', $job_id)))
		{
			$result_json['code'] = 1;
			$result_json['message'] = LL('NothingFound');
		}
		else
		{
			$result_json['params'] = $job_params;
		}
	}
	else if(!$core->db->select_assoc_ex($job_params, rpv('
		SELECT
			jp.`guid`, rp.`name`, jp.`value`
		FROM @runbooks_jobs_params AS jp
		LEFT JOIN @runbooks_jobs AS j
			ON j.`id` = jp.`pid`
		LEFT JOIN @runbooks_params AS rp
			ON rp.`pid` = j.`pid` AND rp.`guid` = jp.`guid`
		WHERE
			jp.`pid` = #
		ORDER BY
			rp.`name`
	', $job_id)))
	{
		$result_json['code'] = 1;
		$result_json['message'] = LL('NothingFound');
	}
	else
	{
		$result_json['params'] = $job_params;
	}

	echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
