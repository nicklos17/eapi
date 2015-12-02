<?php
namespace common;

class Client
{

	/**
	 * 获取客户端的IP 
	 * 
	 */
	public static function getIp()
	{
		if(getenv('HTTP_CLIENT_IP'))
		{
			$ip = getenv('HTTP_CLIENT_IP');
		}
		elseif(getenv('HTTP_X_FORWARDED_FOR'))
		{
			// 获取客户端用代理服务器访问时的真实ip 地址
			if(strpos(getenv('HTTP_X_FORWARDED_FOR'), ',') !== false)
			{
				$ips = explode(',', getenv('HTTP_X_FORWARDED_FOR'));
				$ip = trim($ips[count($ips) - 1]);
			}
			else
			{
				$ip = getenv('HTTP_X_FORWARDED_FOR');
			}
		}
		elseif(getenv('HTTP_X_FORWARDED'))
		{
			$ip = getenv('HTTP_X_FORWARDED');
		}
		elseif(getenv('HTTP_FORWARDED_FOR'))
		{
			$ip = getenv('HTTP_FORWARDED_FOR');
		}
		elseif(getenv('HTTP_FORWARDED'))
		{
			$ip = getenv('HTTP_FORWARDED');
		}
		if(empty($ip))
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return substr($ip, 0, 15);
	}
}