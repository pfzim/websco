<?php

function runbook_move_form(&$core, $params, $post_data)
{
	$id = @$params[1];
	
	$runbook = $core->Runbooks->get_runbook_by_id($id);

	assert_permission_ajax($runbook['folder_id'], RB_ACCESS_EXECUTE);

	if(($runbook['flags'] & (RBF_TYPE_ANSIBLE | RBF_TYPE_CUSTOM)) == 0)
	{
		$core->error('ERROR: Runbook with ID ' . $runbook['id'] . ' cannot be moved!');
		return NULL;
	}

	$pid = $runbook['folder_id'];
	$name = $runbook['name'];

	if($core->db->select_assoc_ex($result, rpv("
			SELECT
				f.`id`,
				f.`pid`,
				f.`name`
			FROM
				@runbooks_folders AS f
			WHERE
				(f.`flags` & ({%RBF_DELETED} | {%RBF_TYPE_CUSTOM})) = {%RBF_TYPE_CUSTOM}
			ORDER BY
				f.`pid` DESC,
				f.`name`
		"
	)))
	{
		$find_id = 0;
		$tree = array();
		$full_tree = TRUE;

		foreach($result as &$row)
		{
			$current_id = intval($row['id']);
			$parent_id = intval($row['pid']);
			
			$selected = FALSE;

			if($current_id == $pid)
			{
				$selected = TRUE;
				$find_id = $parent_id;
			}
	
			$tree[$parent_id][] = array(
				'id' => $current_id,
				'name' => $row['name'],
				'selected' => $selected,
				'closed' => !$full_tree
			);
		}

		if(!$full_tree)
		{
			while($find_id)
			{
				foreach($tree as $node_id => $node)
				{
					foreach($node as $key => $value)
					{
						if($value['id'] == $find_id)
						{
							$tree[$node_id][$key]['closed'] = FALSE;
							$find_id = $node_id;
							break;
						}
					}
				}
			}
		}
	}
	
	function build_tree($tree, $id, $this_id)
	{
		$result = array();

		foreach($tree[$id] as &$value)
		{
			if($value['id'] == $this_id)
			{
				continue;
			}

			if(isset($tree[$value['id']]))
			{
				$value['childs'] = $value['closed'] ? array() : build_tree($tree, $value['id'], $this_id);
			}
			$result[] = &$value;
		}
		
		return $result;
	}

	$result_json = array(
		'code' => 0,
		'message' => '',
		'title' => LL('MoveRunbook'),
		'action' => 'runbook_move',
		'fields' => array(
			array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => $id
			),
			array(
				'type' => 'readonly',
				'name' => 'name',
				'title' => LL('Name'),
				'value' => $name,
			),
			array(
				'type' => 'tree',
				'name' => 'pid',
				'title' => LL('ChooseParentFolder'),
				'value' => $pid,
				'tree' => array(
					array(
						'id' => 0,
						'name' => LL('RootLevel'),
						'selected' => ($pid == 0),
						'closed' => FALSE,
						'childs' => build_tree($tree, 0, $id)
					)
				)
			)
		)
	);

	echo json_encode($result_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
