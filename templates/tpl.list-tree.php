<?php if(!defined('Z_PROTECTED')) exit;

function print_folders_tree($location, &$current_folder_guid, $folders)
{
	if($folders)
	{
		echo '<ul>';

		foreach($folders as &$folder)
		{
			?><li><a<?php if($folder['guid'] === $current_folder_guid) { echo ' class="active"'; } else if($folder['flags'] & 0x0002) { echo ' class="disabled"'; } ?> href="/websco/<?php eh($location); ?>/<?php eh($folder['guid']); ?>"><?php eh($folder['name']); ?></a><?php
			print_folders_tree($location, $current_folder_guid, $folder['childs']);

			echo '</li>';
		}

		echo '</ul>';
	}
}

function print_folders_tree_id($location, &$current_folder_id, $folders)
{
	if($folders)
	{
		echo '<ul>';

		foreach($folders as &$folder)
		{
			?><li><a<?php if($folder['id'] === $current_folder_id) { echo ' class="active"'; } else if($folder['flags'] & 0x0002) { echo ' class="disabled"'; } ?> href="/websco/<?php eh($location); ?>/<?php eh($folder['id']); ?>"><?php eh($folder['name']); ?></a><?php
			print_folders_tree_id($location, $current_folder_id, $folder['childs']);

			echo '</li>';
		}

		echo '</ul>';
	}
}
