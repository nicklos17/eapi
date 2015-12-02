<?php
/**
 * mysql数据库类
 */
namespace core\driver;

class PdoMysql 
{
	/**
	 * 数据库实例
	 *
	 * @var array
	 */
	private static $instance = NULL;

	/**
	 * 创建数据库实例
	 *
	 * @param object 数据库配置属性,包含 host|port|user|password|dbname|charset 6个属性
	 * @param string DB编号key
	 */
	private function __construct($config, $key)
	{
		try
		{
			// 数据库连接信息
			$dsn = "mysql:host={$config->host};port={$config->port};dbname={$config->dbname};charset=utf8";
			// 驱动选项
			$option = array(\PDO::ATTR_ERRMODE=> \PDO::ERRMODE_EXCEPTION, // 如果出现错误抛出错误警告
							\PDO::ATTR_ORACLE_NULLS=> \PDO::NULL_TO_STRING, // 把所有的NULL改成""
							\PDO::ATTR_TIMEOUT=> 30);
			// 创建数据库驱动对象
			self::$instance[$key] = new \Pdo($dsn, $config->user, $config->password, $option);
		}
		catch(\Exception $e)
		{
			\core\Handler::appException($e);
			throw new \Exception('system error', '49901');
		}
	}

	/**
	 * 创建数据库实例
	 *
	 * @return object 当前对象
	 */
	public static function getInstance($dbName = 'ename_trans')
	{
		if(isset(self::$instance[$dbName]) && self::$instance[$dbName] !== null)
		{
			return self::$instance[$dbName];
		}
		$config = \core\Config::item($dbName);
		new PdoMysql($config, $dbName);
		return self::$instance[$dbName];
	}

}