<?php

function save_permission(&$core, $params)
{
	$result_json = array(
		'code' => 0,
		'message' => '',
		'errors' => array()
	);

	$v_id = intval(@$_POST['id']);
	$v_pid = intval(@$_POST['pid']);
	$v_dn = trim(@$_POST['dn']);
	$v_allow = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
	$v_apply_to_childs = intval(@$_POST['apply_to_childs'][0]) || intval(@$_POST['apply_to_childs'][1]);
	$v_replace_childs = intval(@$_POST['apply_to_childs'][1]);

	if(isset($_POST['allow_bits']))
	{
		foreach($_POST['allow_bits'] as $bit => $bit_value)
		{
			if(intval($bit_value))
			{
				set_permission_bit($v_allow, intval($bit) + 1);
			}
		}
	}

	assert_permission_ajax(0, RB_ACCESS_EXECUTE);	// level 0 having access mean admin

	if(empty($v_dn))
	{
		$result_json['code'] = 1;
		$result_json['errors'][] = array('name' => 'dn', 'msg' => 'Fill DN!');
	}

	if($result_json['code'])
	{
		$result_json['message'] = LL('NotAllFilled');
		echo json_encode($result_json);
		exit;
	}

	if(!$v_id)
	{
		if($core->db->put(rpv("INSERT INTO `@access` (`oid`, `dn`, `allow_bits`) VALUES (#, !, !)",
			$v_pid,
			$v_dn,
			$v_allow
		)))
		{
			$v_id = $core->db->last_id();

			log_db('Added permission', 'id='.$v_id.';oid='.$v_pid.';dn='.$v_dn.';perms='.$core->UserAuth->permissions_to_string($v_allow), 0);

			$result_json['id'] = $v_id;
			$result_json['pid'] = $v_pid;
			$result_json['message'] = LL('Added').' (ID '.$v_id.')';
		}
		else
		{
			$result_json['code'] = 1;
			$result_json['message'] = 'ERROR: '.$core->get_last_error();
		}
	}
	else
	{
		if($core->db->put(rpv("UPDATE `@access` SET `dn` = !, `allow_bits` = ! WHERE `id` = # AND `oid` = # LIMIT 1",
			$v_dn,
			$v_allow,
			$v_id,
			$v_pid
		)))
		{
			log_db('Updated permission', 'id='.$v_id.';oid='.$v_pid.';dn='.$v_dn.';perms='.$core->UserAuth->permissions_to_string($v_allow), 0);

			$result_json['id'] = $v_id;
			$result_json['pid'] = $v_pid;
			$result_json['message'] = LL('Updated').' (ID '.$v_id.')';
		}
		else
		{
			$result_json['code'] = 1;
			$result_json['message'] = 'ERROR: '.$core->get_last_error();
		}
	}

	if($result_json['code'])
	{
		echo json_encode($result_json);
		exit;
	}

	if($v_apply_to_childs)
	{
		function permissions_apply_to_childs($parent_guid, $v_dn, $v_allow, $replace)
		{
			global $core;
			$childs = 0;

			//log_file('Apply to childs of ID: '.$parent_guid);
			if($core->db->select_assoc_ex($folders, rpv('SELECT f.`id`, f.`guid` FROM `@runbooks_folders` AS f WHERE f.`pid` = !', $parent_guid)))
			{
				foreach($folders as &$folder)
				{
					//log_file('  Folder ID: '.$folder['id'].', GUID: '.$folder['guid'].', Name: '.$folder['name']);

					if($core->db->select_assoc_ex($permissions, rpv('SELECT a.`id`, a.`allow_bits` FROM `@access` AS a WHERE a.`oid` = # AND a.`dn` = !', $folder['id'], $v_dn)))
					{
						if($replace)
						{
							$bits = $v_allow;
						}
						else
						{
							$bits = $core->UserAuth->merge_permissions($v_allow, $permissions[0]['allow_bits']);
						}
						$core->db->put(rpv("UPDATE `@access` SET `allow_bits` = ! WHERE `id` = # AND `oid` = # LIMIT 1", $bits, $permissions[0]['id'], $folder['id']));
						//log_file('  UPDATE');
					}
					else
					{
						$core->db->put(rpv("INSERT INTO `@access` (`oid`, `dn`, `allow_bits`) VALUES (#, !, !)", $folder['id'], $v_dn, $v_allow));
						//log_file('  INSERT');
					}

					$childs += permissions_apply_to_childs($folder['guid'], $v_dn, $v_allow, $replace) + 1;
				}
			}

			return $childs;
		}

		if($core->db->select_assoc_ex($folders, rpv('SELECT f.`guid` FROM `@runbooks_folders` AS f WHERE f.`id` = #', $v_pid)))
		{
			$result_json['childs'] = permissions_apply_to_childs($folders[0]['guid'], $v_dn, $v_allow, $v_replace_childs);
		}
	}

	if(defined('USE_MEMCACHED') && USE_MEMCACHED)
	{
		$core->Mem->flush();
	}

	echo json_encode($result_json);
}