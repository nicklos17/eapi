<?php
namespace core;

class Logger
{

	/**
	 * 写文本日志
	 * @param string $fileName 文件名
	 * @param string|array $data
	 * @param string $path 路径 可选
	 */
	public static function write($fileName, $data, $path = false)
	{
		$logPath = ROOT_PATH . 'app/logs/';
		if($path)
		{
			$newPath = $logPath . $path;
			if(! file_exists($newPath))
			{
				mkdir($newPath);
				chmod($newPath, 0777);
			}
		}
		$data = is_array($data)? json_encode($data): $data;
		file_put_contents($logPath . $fileName, $data. "\r\n", FILE_APPEND);
	}
}