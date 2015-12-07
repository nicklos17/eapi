<?php

class LogicBase
{

	function __construct()
	{
		
	}

	/**
	 * 异步逻辑处理失败时发送报警信息到日志系统
	 * 
	 * @param string $option 相关操作
	 * @param int $enameId 相关用户ID
	 * @param string $msg 详细描述
	 * @param int $transId 涉及的交易ID
	 */
	protected function sendMsgNotice($option, $enameId, $msg, $transId)
	{
		return true;
	}
}