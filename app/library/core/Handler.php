<?php
namespace core;

class Handler
{

	/**
	 * 抛出异常的处理方式
	 *
	 * @param string 错误信息
	 * @return void
	 */
	public static function appException($msg)
	{
		self::writeLog($msg);
	}
	
	/**
	 * 错误记录
	 *
	 * @param string 错误信息
	 * @return void
	 */
	private static function writeLog($msg)
	{
		file_put_contents("/tmp/new-trans-server.log", date("Y-m-d H:i:s").'-'.var_export($msg,true)."\n",FILE_APPEND);
	}
}