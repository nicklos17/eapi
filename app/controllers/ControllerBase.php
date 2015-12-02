<?php
use core\RuleBase;

class ControllerBase
{

	private $di;

	/**
	 * @var \core\driver\Socket
	 */
	protected $socket;

	function __construct($di)
	{
		$this->di = $di;
	}

	/**
	 * 获取SOCKET连接
	 */
	protected function getSocket()
	{

		return new \core\driver\Socket();
	}

}
