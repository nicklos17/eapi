<?php
use core\RuleBase;

class ControllerBase
{

	private $di;
	

	/**
	 * @var \core\driver\GoServer
	 */
	protected $goSer;

	function __construct($di)
	{
		$this->di = $di;
	}

	/**
	 * 获取SOCKET连接
	 */
	protected function getGoServer()
	{

		return new \core\driver\GoServer();
	}
}
