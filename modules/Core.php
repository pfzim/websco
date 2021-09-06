<?php

class Core
{
	private $error_msg = '';
	private $rise_exception = FALSE;
	private $config = NULL;

	function __construct($rise_exception = FALSE)
	{
		$this->config = NULL;
		$this->error_msg = '';
		$this->rise_exception = $rise_exception;
	}

	public function load_ex($name, $module)
	{
		if(isset($this->$module))
		{
			return TRUE;
		}

		$filepath = MODULES_DIR.$module.'.php';
		if(!file_exists($filepath))
		{
			$this->error('ERROR: Module '.$filepath.' not found!');
			return FALSE;
		}
		
		require_once($filepath);

		$this->$name = new $module($this);

		return TRUE;
	}

	public function load($module)
	{
		return $this->load_ex($module, $module);
	}

	public function get_config($name)
	{
		if(!$this->config)
		{
			$json_raw = file_get_contents(ROOT_DIR.'config.json');
			$this->config = json_decode($json_raw, TRUE);
		}
		
		if(!isset($this->config[$name]))
		{
			return NULL;
		}

		return $this->config[$name];
	}

	public function get_last_error()
	{
		return $this->error_msg;
	}

	public function error($str)
	{
		if($this->rise_exception)
		{
			throw new Exception(__CLASS__.': '.$str);
		}
		else
		{
			$this->error_msg = $str;
		}
	}
}
