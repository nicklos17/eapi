<?php
namespace common;

class Client
{

	/**
	 * 获取客户端的IP 
	 * 因为是RPC后端不可能获取到用户的IP 所以使用RPC客户端传来的IP参数
	 */
	public static function getIp()
	{
		$ip = isset($_GET['ip']) ?$_GET['ip'] :'0.0.0.0';
		return substr($ip, 0, 15);
	}
}