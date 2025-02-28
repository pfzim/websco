<?php if(!defined('Z_PROTECTED')) exit;

function print_folders_tree($location, $current_folder_guid, $folders, $is_admin = FALSE)
{
	if($folders)
	{
		echo '<ul>';

		foreach($folders as &$folder)
		{
			if((($folder['flags'] & 0x0002) == 0) || $is_admin)
			{
				?><li><a<?php if($folder['guid'] === $current_folder_guid) { echo ' class="active"'; } else if($folder['flags'] & 0x0002) { echo ' class="disabled"'; } ?> href="<?php ln($location.'/'.$folder['guid']) ?>"><?php eh($folder['name']); ?></a><?php
				print_folders_tree($location, $current_folder_guid, $folder['childs'], $is_admin);

				echo '</li>';
			}
		}

		echo '</ul>';
	}
}

function print_folders_tree_id($location, $current_folder_id, $folders, $is_admin = FALSE)
{
	if($folders)
	{
		echo '<ul>';

		foreach($folders as &$folder)
		{
			if((($folder['flags'] & 0x0002) == 0) || $is_admin)
			{
				?><li><a<?php if($folder['id'] === $current_folder_id) { echo ' class="active"'; } else if($folder['flags'] & 0x0002) { echo ' class="disabled"'; } ?> href="<?php ln($location.'/'.$folder['id']) ?>"><?php eh($folder['name']); ?></a><?php
				print_folders_tree_id($location, $current_folder_id, $folder['childs'], $is_admin);

				echo '</li>';
			}
		}

		echo '</ul>';
	}
}
