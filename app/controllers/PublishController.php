<?php
use core\ApiCom;
use core\DataCenter;
use core\driver\Redis;
class PublishController extends ControllerBase
{

	/**
	 * 用户发布域名时预检测
	 *
	 * @param s $uId
	 *        	int
	 * @param s $domains
	 *        	array
	 * @param s $type
	 *        	发布类型
	 *        	
	 */
	public function preChk($uId, $domains, $type)
	{
		$domainLogic = new DomainLogic();
		try
		{
			// 只有一个域名检测
			if(count($domains) == 1)
			{
				$msg = $domainLogic->preOneDomain($domains[0], $type, $uId);
				return	 array('flag' => TRUE,'msg' => $msg);
			}
			else
			{
				// 多个域名的时候
				$server = new core\driver\GoServer();
				// 检测域名后缀
				foreach($domains as $v)
				{
					$server->call($v, 'DomainLogic->checkFromTld', array($v));
				}
				$res = $server->send();
				$succTldDomains = $failTldDomains = array();
				foreach($res as $k => $v)
				{
					if($v)
					{
						$succTldDomains[] = $k;
					}
					else
					{
						$failTldDomains[$k] = '该域名后缀不能发布交易!';
					}
				}
				
				// 获取非我司 ,我司 ,我司非用户的
				foreach($succTldDomains as $v)
				{
					$server->call($v, 'DomainLogic->checkMyDomain', array($uId,$v));
				}
				$res = $server->send();
				$enameDomains = $notUserDomains = $notInEnameDomains = $errorDomains = array();
				foreach($res as $k => $v)
				{
					switch($v['flag'])
					{
						case 1:
							// 我司域名
							$enameDomains[$k] = $v['msg'];
							break;
						case 2:
							// 非我司域名
							$notInEnameDomains[] = $k;
							break;
						case 3:
							// 我司域名 但不属于用户
							$notUserDomains[$k] = $v['msg'];
							break;
						case 4:
								//获取信息不到或者失败的域名
							$errorDomains[$k] = $v['msg'];
							break;
					}
				}
				// 我司域名检测 状态 注册时间和 过期时间
				$cEnameDomains = $cErrorEnameDomains = array();
				foreach ($enameDomains as $k =>$v)
				{
					$server->call($k, 'DomainLogic->comCheck', array($k, $type,$v));
				}
				$res = $server->send();
				foreach ($res as $k=>$v)
				{
					if($v['flag'])
					{
						$cEnameDomains[$k]= $v['msg'];
					}
					else 
					{
						$cErrorEnameDomains[$k]=$v['msg'];
					}
				}
				
				// 非我我司域名检测 tld  和 是否在黑名单
				$cNotInEnameDomains = $cErrorNotInEnameDomains = array();
				foreach ($notInEnameDomains as $v)
				{
					$server->call($v, 'DomainLogic->nonComCheck', array($v));
					$domainLogic->comCheck($v, $type);
				}
				$res = $server->send();
				foreach ($res as $k=>$v)
				{
					if($v['flag'])
					{
						$cNotInEnameDomains[$k]= $v['msg'];
					}
					else
					{
						$cErrorNotInEnameDomains[$k]=$v['msg'];
					}
				}
				// 我司域名 检测是否在交易中
				foreach($cEnameDomains as $k=>$v)
				{
					$server->call($k, 'DomainLogic->isDomainTrans', array($k,$type));
				}
				$res = $server->send();
				$tSuccEnameDomains = $tFailEnameDomains = array();
				foreach($res as $k => $v)
				{
					if($v)
					{
						$tSuccEnameDomains[$k] = $cEnameDomains[$k];// 简介
					}
					else
					{
						$tFailEnameDomains[$k] = '域名正在交易中,无法发布交易';
					}
				}
				// 非我司域名 检测是否在交易中
				foreach($cNotInEnameDomains as $k=>$v)
				{
					$server->call($k, 'DomainLogic->isDomainTrans', array($k,$type));
				}
				$res = $server->send();
				$tSuccNotINEnameDomains = $tFailNotInEnameDomain = array();
				foreach($res as $k => $v)
				{
					if($v)
					{
						$tSuccNotINEnameDomains[$k] = $cNotInEnameDomains[$k];// 简介
					}
					else
					{
						$tFailNotInEnameDomain[$k] = '域名正在交易中,无法发布交易';;
					}
				}
				

				// step5 合并可以发布的域名 ,不能发布交易的域名
				$succDomains = array_merge($tSuccEnameDomains, $tSuccNotINEnameDomains);
				// 后缀不允许的域名 ,正在交易的我司域名和非我司域名, 域名不可发布(不满足发布条件的)的我司域名 和非我司域名, 及 获取域名信息失败的域名
				$failDomains = array_merge($failTldDomains, $tFailEnameDomains, $tFailNotInEnameDomain, 
					$cErrorEnameDomains, $cErrorNotInEnameDomains, $errorDomains);
				$msg = array('succ' => $succDomains,'fail' => $failDomains);
				return array('flag' => TRUE,'msg' => $msg);
			}
		}
		
		catch(\Exception $e)
		{
			\core\Logger::write('FABU', array('出现异常',$e->getMessage(),$e->getFile(),$e->getLine()));
			return array('flag' => FALSE,'msg' => $e->getMessage());
		}
	}

	/**
	 * 非我司查询whois信息
	 *
	 * @param s $uId
	 *        	int
	 * @param s $domains        	
	 *
	 */
	public function whoisChk($uId, $domain)
	{
		$domainLogic = new DomainLogic();
		// step1：通过dc直接请求返回域名whois信息
		$dc = new DataCenter('interfaces/getWhoisBaseHaveEmail');
		$whoisInfo = $dc->getData(array('domain' => $domain))['msg'];
		if($domainLogic->checkDomainByDate(strtotime($whoisInfo['ExpirationDate'])) < 2592000)
		{
			return array('flag' => false,'msg' => '域名将在30天内过期时间,无法发布');
		}
		if($domainLogic->checkDomainByRegtime(strtotime($whoisInfo['RegistrationDate'])) < 5184000)
		{
			return array('flag' => false,'msg' => '域名注册未满60天，无法发布');
		}
		// step2：根据whois返回的数据判断注册时间和到期时间以及注册商判断是否允许发布
		// 域名必须符合注册满60天，并且到期前30天并且不在Godaddy和ENOM的才允许发布
		if($domainLogic->checkIsGodaddyOrENOM($whoisInfo['SponsoringRegistrar']))
		{
			return array('flag' => false,'msg' => 'godaddy或enom域名暂时无法发布交易');
		}
		// step3: 根据$uId获取用户认证邮箱
		// 判断域名whois里面的邮箱是否在用户的认证邮箱中
		// 如果是的话将该域名存储到redis的SET中
		$dc = new DataCenter('interfaces/getEmailByEnameId');
		$userEmails = $dc->getData(array('enameId' => $uId))['msg'];
		if($domainLogic->checkUserEmail($uId, $domain, $whoisInfo, $userEmails))
		{
			return array('flag' => TRUE,'msg' => '域名验证通过');
		}
		else
		{
			return array('flag' => false,'msg' => '域名邮箱验证失败');
		}
		
		// step4：返回是否允许发布
	}

	/**
	 * 一口价发布
	 *
	 * @param s $uId
	 *        	int
	 * @param s $domains
	 *        	array 包含我司和非我司域名的二维数组，一个域名包含价格和简介以及出售周期
	 * @param s $cashType
	 *        	int 提现类型，在控制器检查，2 不可提现 3 可体现
	 * @param s $type
	 *        	发布类型
	 */
	public function fixedPrice($uId, $domains, $cashType, $type)
	{
		try
		{
			$redis = core\driver\Redis::getInstance('default');
			$server = new core\driver\GoServer();
	
			// step2：检测域名简介是否包含关键词
			// 检测出售天数，价格
			// 使用go并行处理
			foreach($domains as $k => $v)
			{
				$server->call($k, 'DomainLogic->checkBaseInfo', array($v));
			}
			$res = $server->send();
			$succInfoDomains = $failInfoDomains = array();
			foreach($res as $k => $v)
			{
				if($v['flag'])
				{
					$succInfoDomains[] = $k;
				}
				else
				{
					$failInfoDomains[$k] = '该域名简介包含非法词'.$v['msg'];
				}
			}
	
	
	
			$res = $this->preChk($uId, $succInfoDomains, $type);
			$preSuccDomains = $res['msg']['succ'];
			$preFailDomains = $res['msg']['fail'];
	
	
	
	
			// step3：存在我司域名调用判断我司域名是否可发布
			// 非我司域名判断是否$domains里面有非我司域名，有的话从redis中取出
			// 上一步缓存的已经认证过的域名，排除$domains里面非我司域名那个数组
			// 里面不在缓存中得域名
			// 通过go并行调用返回数据
			foreach($preSuccDomains as $k => $v)
			{
				$server->call($k, 'DomainLogic->checkMyDomain', array($uId, $k));
			}
			$res = $server->send();
			$enameDomains = $notInEnameDomains = $failMyDomains = array();
			foreach($res as $k => $v)
			{
				$inEname = $v['flag'];
				if($inEname == 1){
					$enameDomains[] = $k;
					$domains[$k]['expireTime'] = $res['msg']['ExpDate'];
					$domains[$k]['isOur'] = 1;
				}elseif($inEname == 2){
					$whois = $redis->get(md5(trim($uId.$k)));
					if($whois){
						$notInEnameDomains[] = $k;
						$domains[$k]['expireTime'] = $whois;
						$domains[$k]['isOur'] = 2;
					}else{
						$failMyDomains[$k] = '非我司域名认证失败';
					}
				}else{
					$failMyDomains[$k] = '该域名不属于您';
				}
			}
	
	
	
			// step5：从step3和step4里面合并获取可发布的我司域名和非我司域名
			// 调用go去并行处理我司和非我司的可发布情况返回来
	
			// step6：对于可发布的我司域名和非我司域名
			// 处理我司域名锁定和非我司域名冻结保证金
			foreach($enameDomains as $v)
			{
				$server->call($k, 'DomainLogic->lockDomain', array($v));
			}
			$res = $server->send();
			$succLockDomains = $failLockDomains = array();
			foreach($res as $k => $v)
			{
				if($v)
				{
					$succLockDomains[$k] = 1;
				}
				else
				{
					$failLockDomains[$k] = '域名锁定失败';
				}
			}
	
			foreach($notInEnameDomains as $v)
			{
				$server->call($k, 'DomainLogic->freezeMoney', array($uId, $v, 50));
			}
			$res = $server->send();
			$succFreezeDomains = $failFreezeDomains = array();
			foreach($res as $k => $v)
			{
				if($v)
				{
					$domains[$k]['orderId'] = $v;
					$succFreezeDomains[$k] = 1;
				}
				else
				{
					$failFreezeDomains[$k] = '保证金冻结失败';
				}
			}
	
	
			$succDomains = array_merge($succFreezeDomains, $succLockDomains);
			$failDomains = array_merge($failInfoDomains, $preFailDomains, $failMyDomains, $failLockDomains, $failFreezeDomains);
	
	
			// 从redis里面获取域名是否推荐数据进行标识
			// 同时将推荐域名推到redis中做bbs推荐
			// 调用go并发处理
			foreach($succDomains as $k => $v)
			{
				$promote = $redis->exists('promote:'.$uId.$k);
				if($promote){
					$domains[$k]['isHot'] = 1;
					$bbs = $redis->lRange('bbs:domain', 0, -1);
					if(!in_array($uId, $bbs)){
						$redis->lPush("bbs:domain", $uId);
					}
				}else{
					$domains[$k]['isHot'] = 0;
				}
			}
	
			// step7：将域名写入交易表
			// 调用go并行处理
			foreach($succDomains as $k => $v)
			{
				$orderId = $domains[$k]['orderId'] ? $domains[$k]['orderId'] : 0;
				$server->call($k, 'DomainLogic->publicDomain', array($uId, $k, $domains[$k]['description'], $domains[$k]['expireTime'], $type = 1, $domains[$k]['price'], $domains[$k]['endTime'], $cashType, $domains[$k]['isOur'], $domains[$k]['isHot'], $orderId));
			}
			$res = $server->send();
	
			$msg = array('succ' => $succDomains,'fail' => $failDomains);
			return array('flag' => TRUE,'msg' => $msg);
		}
	
		catch(\Exception $e)
		{
			\core\Logger::write('FABU', array('出现异常',$e->getMessage(),$e->getFile(),$e->getLine()));
			return array('flag' => FALSE,'msg' => $e->getMessage());
		}
	}
	
	public function fixedPriceOne($uId, $domains, $cashType, $type)
	{
		try
		{
			$logic = new DomainLogic();
			$redis = core\driver\Redis::getInstance('default');
			foreach ($domains as $k => $v){
				$domain = $k;
				$value = $v;
			}
			//$res = $logic->publicDomain($uId, $domain, $value['description'], '123', $type = 1, $value['price'], $value['endTime'], $cashType, 1, 0, 0);
			$res = $logic->freezeMoney($uId, $domain, 50);
			var_dump($res);exit();
	
	
	
			// step2：检测域名简介是否包含关键词
			// 检测出售天数，价格
			$res = $logic->checkBaseInfo($value);
			if(!$res['flag'])
			{
				return array('flag' => false,'msg' => '该域名简介包含非法词'.$res['msg']);
			}
	
	
			//$res = $this->preChk($uId, array($domain), $type);
			//if(count($res['msg']['fail'])){
			//	return array('flag' => false,'msg' => 'pre失败');
				//}
					
	
				// step3：存在我司域名调用判断我司域名是否可发布
				// 非我司域名判断是否$domains里面有非我司域名，有的话从redis中取出
				// 上一步缓存的已经认证过的域名，排除$domains里面非我司域名那个数组
				// 里面不在缓存中得域名
				$res = $logic->checkMyDomain($uId, $domain);var_dump($res);
				$inEname = $res['flag']; //1 我司 2 我司非用户 3 非我司
				$value['expireTime'] = '';
				if($inEname == 1){
					//我司判断时间
					$value['expireTime'] = $res['msg']['ExpDate'];
					$value['isOur'] = 1;
				}elseif($inEname == 2){
					$whois = $redis->get(md5(trim($uId.$domain)));
					if($whois){
						$value['expireTime'] = $whois;
						$value['isOur'] = 2;
					}else{
						return array('flag' => false,'msg' => '非我司域名认证失败');
					}
				}else{
					return array('flag' => false,'msg' => '该域名不属于您');
				}
	
	
				echo 1;exit();
	
				// step5：从step3和step4里面合并获取可发布的我司域名和非我司域名
				// 调用go去并行处理我司和非我司的可发布情况返回来
	
				// step6：对于可发布的我司域名和非我司域名
				// 处理我司域名锁定和非我司域名冻结保证金
				if($inEname == 1){
					$res = $logic->lockDomain($domain);
					if(!$res)
					{
						return array('flag' => false,'msg' => '域名锁定失败');
					}
				}elseif($inEname == 2){
					$res = $logic->freezeMoney($uId, $domain, 50);
					if(!$res)
					{
						return array('flag' => false,'msg' => '保证金冻结失败');
					}else{
						$value['orderId'] = $res;
					}
				}
	
				// 从redis里面获取域名是否推荐数据进行标识
				// 同时将推荐域名推到redis中做bbs推荐
				// 调用go并发处理
				$promote = $redis->exists('promote:'.$uId.$domain);
				if($promote){
					$value['isHot'] = 1;
					$bbs = $redis->lRange('bbs:domain', 0, -1);
					if(!in_array($uId, $bbs)){
						$redis->lPush("bbs:domain", $uId);
					}
				}else{
					$value['isHot'] = 0;
				}
	
	
	
				// step7：将域名写入交易表
				// 调用go并行处理
				$orderId = $value['orderId'] ? $value['orderId'] : 0;
				$logic->publicDomain($uId, $domain, $value['description'], $value['expireTime'], $type = 1, $value['price'], $value['endTime'], $cashType, $value['isOur'], $value['isHot'], $orderId);
	
				return array('flag' => TRUE);
		}
	
		catch(\Exception $e)
		{
			\core\Logger::write('FABU', array('出现异常',$e->getMessage(),$e->getFile(),$e->getLine()));
			return array('flag' => FALSE,'msg' => $e->getMessage());
		}
	}
	
	/**
	 * todo：易拍易卖和专题拍卖
	 */
	public function easyBuyAndSubjectAuction()
	{}

	/**
	 * todo：询价
	 */
	public function enquire()
	{}
}
