<?php
namespace core;

class Logger
{

	/**
	 * 写文本日志
	 *
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
		file_put_contents($logPath . $fileName, $data . "\r\n", FILE_APPEND);
	}

	/**
	 * 写日志到日志系统 http://192.168.10.217/root/log/wikis/home
	 *
	 * @param int $enameId
	 * @param string $domain
	 * @param string $requestData
	 * @param int $msgType
	 * @param string $result
	 * @param number $linkId
	 * @param string $note
	 * @param number $logType
	 * @param number $flag
	 * @param string $formUri
	 * @param string $userName
	 */
	public static function writeLogSystem($enameId, $domain, $requestData, $msgType, $result, $linkId, $note = '', 
		$logType = 3, $flag = 1, $formUri = '', $userName = '')
	{
		$data = array('enameId'=> $enameId,'domain'=> $domain,'logType'=> $logType);
		$data['ip'] = \common\Client::getIp();
		$data['addTime'] = time();
		$data['resultFlag'] = $flag;
		$data['formUri'] = $formUri;
		$data['requestData'] = $requestData;
		$data['note'] = $note;
		$data['platform'] = 2;
		$data['linkId'] = $linkId;
		$data['resultContent'] = $result;
		$data['clientIp'] = '';
		$data['msgType'] = $msgType;
		return true;
	}
}