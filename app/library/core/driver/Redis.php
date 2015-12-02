<?php
namespace core\driver;

class Redis
{

	private static $conn;

	private function __construct($config,$configName='default')
	{
		$redis = new \Redis();
		$redisConfig = \core\Config::item('redis');
		$timeOut = isset($redisConfig->timeout)? intval($redisConfig->timeout) :3;
		if($redis->connect($redisConfig->server, $redisConfig->port, $timeOut))
		{
			$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
			self::$conn[$configName] = $redis;
		}
		else
		{
			throw new \Exception("redis is down");
		}
	}

	public static function getInstance($configName)
	{
		if(isset(self::$conn[$configName]) && self::$conn[$configName])
		{
			return self::$conn[$configName];
		}
		$redis = new Redis($configName);
		return self::$conn[$configName];
	}
}