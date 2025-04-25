<?php

function folder_get(&$core, $params, $post_data)
{
	$id = intval(@$params[1]);
	$pid = intval(@$params[2]);
	$name = '';

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);

	if($id)
	{
		if(!$core->db->select_assoc_ex($folder, rpv("SELECT f.`id`, f.`pid`, f.`name`, f.`flags` FROM @runbooks_folders AS f WHERE f.`id` = # AND (f.`flags` & ({%RBF_DELETED} | {%RBF_TYPE_CUSTOM})) = {%RBF_TYPE_CUSTOM} LIMIT 1", $id)))
		{
			echo '{"code": 1, "message": "Failed get folder"}';
			return;
		}

		$id = intval($folder[0]['id']);
		$pid = intval($folder[0]['pid']);
		$name = $folder[0]['name'];
	}

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
		'title' => LL('EditFolder'),
		'action' => 'folder_save',
		'fields' => array(
			array(
				'type' => 'hidden',
				'name' => 'id',
				'value' => $id
			),
			array(
				'type' => 'string',
				'name' => 'name',
				'title' => LL('Name').'*',
				'value' => $name,
				'placeholder' => 'New folder name'
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

	echo json_encode($result_json);
}
