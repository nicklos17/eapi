<?php
namespace common;

class Domain
{
	private function getDcObj($method)
	{
		return new \core\DataCenter($method);
	}
	
	public function PushDomain($seller,$buyer,$domain)
	{
		$data = array('seller'=>$oldId,'buyer'=>$newId,'domain'=>$domain,'isnewinter'=>1);
		$result=self::getDcObj('domain/domainPushTrans')->getPostData($data);
		if(10000 == $result['code'])
		{
			return true;
		}
		else
		{
			\core\Logger::writeLogSystem($seller, $domain, $data, 372000,$result,$buyer,'域名交易过户走DC的新接口');
			return false;
		}
	}
}