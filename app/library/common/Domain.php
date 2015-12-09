<?php
namespace common;

class Domain
{
	private function getDcObj($method)
	{
		return new \core\DataCenter($method);
	}
	
	/**
	 * 交易成功PUSH域名给买家
	 * @param int $seller
	 * @param int $buyer
	 * @param string $domain
	 */
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
		
	/**
	 * 调用Dc接口锁定/解锁域名
	 * @param string $domain
	 * @param int $status 
	 * @return true:操作成功 array(false,'错误原因')
	 */
	public function lockDomain($domain,$status) 
	{
		$resultMsg = array('322002'=>'提示用户域名在临时模板下不能交易','322020'=>'非白名单模板域名不能交易');
		$rs = self::getDcObj('domain/setdomainmystatus')->getPostData(array('domain'=> $domain, 'status'=> $status));
		if($rs['msg']['result'] !== true)
		{
			\core\Logger::write("DOMAIN_LOG",
				array(__METHOD__,'域名 ' . $domain . ' 设置状态为 ' . $status . ' 失败,msg信息为：' . $rs['msg']['msg']));
			$code = $rs['msg']['code'];
			if(isset($resultMsg[$code]))
			{
				return array(false,$resultMsg[$code]);
			}
			else
			{
				return array(false,'系统错误，请联系客服处理');
			}
		}
		return true;
	}
	
	/**
	 * 检测域名是否在用户ID下，非我司交易卖家确认使用
	 * @param int $enameId
	 * @param string $dn
	 * @return bool
	 */
	public function checkDnInUser($enameId,$dn)
	{
		$data = array('enameid'=> $enameId,'domain'=> $dn);
		$result = self::getDcObj('domain/getDomainForUser')->getData();
		if(10000 == $result['code'] && true == $result['msg'])
		{
			return true;
		}
		return false;
	}

	/**
	 * 域名上架和下架
	 * @param str $domain domain
	 * @param int $status status
	 */
	public function setDomainStatus($domain, $status)
	{
		return $this->lockDomain($domain, $status);
	}
}