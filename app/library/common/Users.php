<?php
namespace common;

class Users
{
	private function getDcObj($method)
	{
		return new \core\DataCenter($method);
	}
	
	/**
	 * 获取卖家设置 是否需要短信通知
	 *
	 *@param $enameid 卖家id
	 *@return boolean
	 */
	public function noticeSellerIsOk($enameid,$type)
	{
		$rs = self::getDcObj('User/getMemberSettingByIdentifier')->getPostData(array('enameid'=> $enameid, 'code'=> $type));
		if(isset($ss['code']) && ($ss['code'] == 100000) && isset($ss['msg']['IsMobile']) && $ss['msg']['IsMobile'])
		{
			return true;
		}
		return false;
	}
}