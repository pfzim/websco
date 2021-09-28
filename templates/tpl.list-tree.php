<?php if(!defined('Z_PROTECTED')) exit;

function print_folders_tree($location, &$current_folder_guid, $folders)
{
	if($folders)
	{
		echo '<ul>';

		foreach($folders as &$folder)
		{
			?><li><a<?php if($folder['guid'] === $current_folder_guid) { echo ' class="active"'; } ?> href="/websco/<?php eh($location); ?>/<?php eh($folder['guid']); ?>"><?php eh($folder['name']); ?></a><?php
			print_folders_tree($location, $current_folder_guid, $folder['childs']);

			echo '</li>';
		}

		echo '</ul>';
	}
}
