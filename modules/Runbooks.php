<?php
/*
    RunbookBase class - This class is intended for accessing the Microsoft
	System Center Orchestrator 2022 web service to get a list of runbooks and
	launch them.
    Copyright (C) 2024 Dmitry V. Zimin

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
	This class is intended for accessing the Microsoft System Center
	Orchestrator web service to get a list of ranbooks and launch them.
*/

class Runbooks
{
	private $core;

	function __construct(&$core)
	{
		$this->core = &$core;
	}

	private function get_runbook_class_by_type($flags)
	{
		if($flags & RBF_TYPE_SCO2022)
		{
			return $this->core->Orchestrator2022;
		}

		if($flags & RBF_TYPE_SCO)
		{
			return $this->core->Orchestrator;
		}

		if($flags & RBF_TYPE_ANSIBLE)
		{
			return $this->core->AnsibleAWX;
		}

		$this->core->error('Runbook '.$id.' has unknown type!');
		return NULL;
	}

	private function get_runbook_class($id)
	{
		if(!$this->core->db->select_ex($runbook, rpv("SELECT r.`flags` FROM @runbooks AS r WHERE r.`id` = # LIMIT 1", $id)))
		{
			$this->core->error('Runbook '.$id.' not found!');
			return NULL;
		}

		return $this->get_runbook_class_by_type(intval($runbook[0][0]));
	}

	private function get_runbook_class_by_job_id($id)
	{
		if(!$this->core->db->select_ex($runbook, rpv("SELECT r.`flags` FROM @runbooks_jobs AS j LEFT JOIN @runbooks AS r ON r.`id` = j.`pid` WHERE j.`id` = # LIMIT 1", $id)))
		{
			$this->core->error('Runbook '.$id.' not found!');
			return NULL;
		}

		return $this->get_runbook_class_by_type(intval($runbook[0][0]));
	}

	/**
	 Start runbook.

		\param [in] $guid   - runbook ID
		\param [in] $params - array of param GUID and value

		\return - created job ID
	*/

	public function start_runbook($post_data, &$result_json)
	{
		return $this->get_runbook_class($post_data['id'])->parse_form_and_start_runbook($post_data, $result_json);
	}

	/**
	 Stop job.

		\param [in] $guid   - job ID

		\return - TRUE | FALSE
	*/

	public function job_cancel($id)
	{
		$runbook = $this->get_runbook_by_job_id($id);
		return $this->get_runbook_class_by_job_id($id)->job_cancel($runbook['job_guid']);
	}

	public function sync($flags)
	{
		return $this->get_runbook_class_by_type($flags)->sync();
	}

	public function sync_jobs_all($flags)
	{
		return $this->get_runbook_class_by_type($flags)->sync_jobs($id);
	}

	public function sync_jobs($id)
	{
		return $this->get_runbook_class($id)->sync_jobs($id);
	}

	public function get_runbook_by_id($id)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks AS r LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE r.`id` = # LIMIT 1", $id)))
		{
			$this->core->error('Runbook '.$id.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_runbook_by_job_id($id)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, j.`id` AS `job_id`, j.`guid` AS `job_guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks_jobs AS j LEFT JOIN @runbooks AS r ON r.`id` = j.`pid` LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE j.`id` = # LIMIT 1", $id)))
		{
			$this->core->error('Job '.$id.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_runbook($guid, $type_flag)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks AS r LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE r.`guid` = ! AND r.`flags` & # LIMIT 1", $guid, $type_flag)))
		{
			$this->core->error('Runbook '.$guid.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_runbook_by_job_guid($guid, $type_flag)
	{
		if(!$this->core->db->select_assoc_ex($runbook, rpv("SELECT r.`id`, r.`guid`, r.`folder_id`, f.`guid` AS `folder_guid`, r.`name`, r.`description`, r.`wiki_url`, r.`flags` FROM @runbooks_jobs AS j LEFT JOIN @runbooks AS r ON r.`id` = j.`pid` LEFT JOIN @runbooks_folders AS f ON f.`id` = r.`folder_id` WHERE j.`guid` = ! AND r.`flags` & # LIMIT 1", $guid, $type_flag)))
		{
			$this->core->error('Job '.$guid.' not found!');
			return FALSE;
		}

		return $runbook[0];
	}

	public function get_job($id)
	{
		return $this->get_runbook_class_by_job_id($id)->get_job($id);
	}

	public function get_activity($guid)
	{
		return $this->get_runbook_class_by_type(RBF_TYPE_SCO2022)->get_activity($guid);
	}

	public function get_custom_job($id)
	{
		if(!$this->core->db->select_assoc_ex($job, rpv('
			SELECT
				j.`id`,
				j.`guid`,
				DATE_FORMAT(j.`date`, \'%d.%m.%Y %H:%i:%s\') AS `run_date`,
				r.`name`,
				r.`id` AS `runbook_id`,
				r.`guid` AS `runbook_guid`,
				r.`folder_id`,
				r.`flags`,
				u.`login`
			FROM @runbooks_jobs AS j
			LEFT JOIN @runbooks AS r ON r.`id` = j.`pid`
			LEFT JOIN @users AS u ON u.`id` = j.`uid`
			WHERE
				j.`id` = #
				AND (r.`flags` & {%RBF_TYPE_CUSTOM})
			LIMIT 1
		', $id)))
		{
			$this->core->error('Job '.$id.' not found!');
			return FALSE;
		}

		$job = &$job[0];

		!$this->core->db->select_assoc_ex($job_params, rpv('
			SELECT
				jp.`guid`,
				jp.`value`
			FROM @runbooks_jobs_params AS jp
			WHERE jp.`pid` = #
		', $job['id']));

		$job_info = array(
			'id' => $job['id'],
			'guid' => $job['guid'],
			'name' => $job['name'],
			'run_date' => $job['run_date'],
			'runbook_id' => $job['runbook_id'],
			'runbook_guid' => $job['runbook_guid'],
			'status' => 'Completed',
			'folder_id' => $job['folder_id'],
			'user' => $job['login'],
			'params' => &$job_params
		);

		return $job_info;
	}

	public function get_runbook_params($id)
	{
		return $this->get_runbook_class($id)->get_runbook_params($id);
	}

	public function get_runbook_form($id, $job_id)
	{
		return $this->get_runbook_class($id)->get_runbook_form($id, $job_id);
	}

	public function load_tree_childs($id, $check_permissions)
	{
		$childs = NULL;

		if($this->core->db->select_assoc_ex($folders, rpv('SELECT f.`id`, f.`guid`, f.`name`, f.`flags` FROM @runbooks_folders AS f WHERE f.`pid` = {d0} AND (f.`flags` & {%RBF_DELETED}) = 0 ORDER BY f.`name`', $id)))
		{
			$childs = array();

			foreach($folders as $folder)
			{
				if(!$check_permissions || $this->core->UserAuth->check_permission($folder['id'], RB_ACCESS_LIST))     // || ($folder['id'] == 0) - if top level always allow list
				{
					$childs[] = array(
						'name' => $folder['name'],
						'id' => $folder['id'],
						// 'guid' => $folder['guid'],
						'flags' => $folder['flags'],
						'childs' => $this->load_tree_childs($folder['id'], $check_permissions)
					);
				}
			}
		}

		return $childs;
	}

	public function get_folders_tree($check_permissions)
	{
		return array(
			array(
				'name' => 'Root folder',
				// 'guid' => '00000000-0000-0000-0000-000000000000',
				'id' => 0,
				'flags' => 0,
				'childs' => $this->load_tree_childs(0, $check_permissions)
			)
		);
	}
}
