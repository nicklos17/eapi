<?php

class SettingController extends ControllerBase
{
	function __construct($di)
	{
		parent::__construct($di);
	}

	public function index($param)
	{
		$this->showSuccess('success', array('version'=> '0.1'));
	}
}