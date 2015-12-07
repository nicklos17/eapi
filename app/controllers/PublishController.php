<?php
use core\ApiCom;
use core\DataCenter;
use core\driver\Redis;
class PublishController extends ControllerBase
{
	private $server;
	
	public function __construct($di) {
		parent::__construct($di);
		$this->goSer = $this->getGoServer();
	} 

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
				// 多个域名的时候
				$server = new core\driver\GoServer();
				$succTldDomains = $failTldDomains = $errorTldDomains=array();
				// 检测域名后缀
				list($succTldDomains , $failTldDomains,$errorTldDomains) = $this->checkDomainTld( $domains);
				
			
				// 后缀的都不通过的时候
				if(empty($succTldDomains))
				{
					return $msg = array('succEname' => array(),'fail' => $failTldDomains,'succNotInEname' => array());
				}
				$enameDomains = $notUserDomains = $notInEnameDomains = $errorDomains = array();
				
				list($enameDomains , $notUserDomains , $notInEnameDomains , $errorDomains)= $this->checkMyDomain( $uId, $succTldDomains);
			
				$cEnameDomains = $cErrorEnameDomains = array();
				
				if($enameDomains)
				{
					list($cEnameDomains , $cErrorEnameDomains) = $this->comCheck( $enameDomains, $type);
				}
				$cNotInEnameDomains = $cErrorNotInEnameDomains = array();
				if($notInEnameDomains)
				{
					// 非我我司域名检测 tld 和 是否在黑名单
				    list($cNotInEnameDomains ,$cErrorNotInEnameDomains) =$this->nonComCheck( $notInEnameDomains);
				}
				$tSuccEnameDomains = $tFailEnameDomains = array();
				if ($cEnameDomains)
				{
							// 我司域名 检测是否在交易中
					list($tSuccEnameDomains,$tFailEnameDomains)=$this->isDomainTrans( $cEnameDomains, $type);

				}
				$tSuccNotINEnameDomains = $tFailNotInEnameDomain = array();
				if($cNotInEnameDomains)
				{
						// 非我司域名 检测是否在交易中
						list($tSuccNotINEnameDomains, $tFailNotInEnameDomain)=$this->isDomainTrans( $cNotInEnameDomains, $type);
				}
				// step5 合并 不能发布交易的域名
				// $succDomains = array_merge($tSuccEnameDomains, $tSuccNotINEnameDomains);
				// 后缀不允许的域名 ,正在交易的我司域名和非我司域名, 域名不可发布(不满足发布条件的)的我司域名 和非我司域名, 及 获取域名信息失败的域名
				
				$failDomains = array_merge($failTldDomains, $tFailEnameDomains, $tFailNotInEnameDomain, 
					$cErrorEnameDomains, $cErrorNotInEnameDomains, $errorDomains,$notUserDomains,$errorTldDomains);
				$msg = array('succEname' => $tSuccEnameDomains,'fail' => $failDomains,
					'succNotInEname' => $tSuccNotINEnameDomains);
				return array('flag' => TRUE,'msg' => $msg);
			
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
		$regTime = \core\Config::item('notInEnameRegTime');
		$expTime = \core\Config::item('notInEnameExpTime');
		if($domainLogic->checkDomainByDate(strtotime($whoisInfo['ExpirationDate'])) < $expTime)
		{
			//'域名将在30天内过期时间,无法发布'
			return array('flag' => false,'code' =>1002 );
		}
		if($domainLogic->checkDomainByRegtime(strtotime($whoisInfo['RegistrationDate'])) < $regTime)
		{
			//'域名注册未满60天，无法发布'
			return array('flag' => false,'code' => 1003);
		}
		// step2：根据whois返回的数据判断注册时间和到期时间以及注册商判断是否允许发布
		// 域名必须符合注册满60天，并且到期前30天并且不在Godaddy和ENOM的才允许发布
		if($domainLogic->checkIsGodaddyOrENOM($whoisInfo['SponsoringRegistrar']))
		{
			//'godaddy或enom域名暂时无法发布交易'
			return array('flag' => false,'code' =>1004);
		}
		// step3: 根据$uId获取用户认证邮箱
		// 判断域名whois里面的邮箱是否在用户的认证邮箱中
		// 如果是的话将该域名存储到redis的SET中
		$dc = new DataCenter('interfaces/getEmailByEnameId');
		$userEmails = $dc->getData(array('enameId' => $uId))['msg'];
		if($domainLogic->checkUserEmail($uId, $domain, $whoisInfo, $userEmails))
		{
			//'域名验证通过'
			return array('flag' => TRUE,'code' =>1001 );
		}
		else
		{
			//'域名邮箱验证失败'
			return array('flag' => false,'code' =>1005 );
		}
		
		// step4：返回是否允许发布
	}

	/**
	 * 非法关键词检测
	 *
	 * @param s $domains
	 * 
	 **/
	public function checkDesc($domains)
	{
		// step2：检测域名简介是否包含关键词
		// 检测出售天数，价格
		// 使用go并行处理
		foreach($domains as $k => $v)
		{
			$this->goSer->call($k, 'DomainLogic::checkBaseInfo', array($v));
		}
		$res = $this->goSer->send();
		$succInfoDomains = $failDomains = array();
		foreach($res as $k => $v)
		{
			$v = $v['DomainLogic::checkBaseInfo'];
			if(isset($v['goError'])){
				$failDomains[$k] = '系统繁忙,请重试';
			}else{
				if($v['flag'] === true)
				{
					$succInfoDomains[] = $k;
				}
				else
				{
					$failDomains[$k] = $v['msg'];
				}
			}
		}

		
		return array('flag' => TRUE,'msg' => array('succ' => $succInfoDomains,'fail' => $failDomains));
	}
	
	/**
	 * 交易发布
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
				$domains = array_merge($domains['domainEname'], $domains['domainNoEname']);
				
				// step2：检测域名简介是否包含关键词
				// 检测出售天数，价格
				// 使用go并行处理
				foreach($domains as $k => $v)
				{
					$this->goSer->call($k, 'DomainLogic::checkBaseInfo', array($v));
				}
				$res = $this->goSer->send();
				$succInfoDomains = $failDomains = array();
				foreach($res as $k => $v)
				{
					$v = $v['DomainLogic::checkBaseInfo'];
					if(isset($v['goError'])){
						$failDomains[$k] = '系统繁忙,请重试';
					}else{
						if($v['flag'] === true)
						{
							$succInfoDomains[] = $k;
						}
						else
						{
							$failDomains[$k] = '该域名简介包含非法词'.$v['msg'];
						}
					}
				}
				if(empty($succInfoDomains)){
					return array('flag' => TRUE,'msg' => array('succ' => array(),'fail' => $failDomains));
				}
				
								
				// 检测域名后缀
				$res = $this->checkDomainTld($succInfoDomains);
				$succTmpDomains = $res[0];
				$failDomains = array_merge($failDomains, $res[1], $res[2]);
				if(empty($succTmpDomains)){
					return array('flag' => TRUE,'msg' => array('succ' => array(),'fail' => $failDomains));
				}
			
				
				// step3：存在我司域名调用判断我司域名是否可发布
				// 非我司域名判断是否$domains里面有非我司域名，有的话从redis中取出
				// 上一步缓存的已经认证过的域名，排除$domains里面非我司域名那个数组
				// 里面不在缓存中得域名
				// 通过go并行调用返回数据
				foreach($succTmpDomains as $k => $v)
				{
					$this->goSer->call($v, 'DomainLogic::checkMyDomain', array($uId,$v));
				}
				$res = $this->goSer->send();
				$enameDomains = $notInEnameDomains = array();
				foreach($res as $k => $v)
				{
					$v = $v['DomainLogic::checkMyDomain'];
					if(isset($v['goError'])){
						$failDomains[$k] = '系统繁忙,请重试';
					}else{
						$inEname = $v['flag'];
						if($inEname == 1){
							$enameDomains[$k] = $v['msg'];
							$domains[$k]['expireTime'] = strtotime($v['msg']['expireTime']);
							$domains[$k]['isOur'] = 1;
						}elseif($inEname == 2){
							$whois = $redis->get('whois:'.$uId.$k);
							if($whois){
								$notInEnameDomains[] = $k;
								$domains[$k]['expireTime'] = strtotime($whois);
								$domains[$k]['isOur'] = 2;
							}else{
								$failDomains[$k] = '非我司域名认证失败';
							}
						}else{
							$failDomains[$k] = $v['msg'];
						}
					}
				}
				if(empty($enameDomains) && empty($notInEnameDomains)){
					return array('flag' => TRUE,'msg' => array('succ' => array(),'fail' => $failDomains));
				}
				
				
				// 我司域名检测 状态 注册时间和 过期时间
				$succComDomains = array();
				if(!empty($enameDomains)){
					$res = $this->comCheck($enameDomains, $type);
					$succComDomains = $res[0];
					$failDomains = array_merge($failDomains, $res[1]);
				}

				
				// 非我我司域名检测 tld 和 是否在黑名单
				$succNonComDomains = array();
				if(!empty($notInEnameDomains)){
					$res = $this->nonComCheck($notInEnameDomains);
					$succNonComDomains = $res[0];
					$failDomains = array_merge($failDomains, $res[1]);					
				}

				
				if(empty($succComDomains) && empty($succNonComDomains)){
					return array('flag' => TRUE,'msg' => array('succ' => array(),'fail' => $failDomains));
				}				
				
				

				// 域名 检测是否在交易中
				$succComTranDomains = array();
				if(!empty($succComDomains)){
					$res = $this->isDomainTrans($succComDomains, $type);
					$succComTranDomains = $res[0];
					$failDomains = array_merge($failDomains, $res[1]);
				}
				
				$succNonComTranDomains = array();
				if(!empty($succNonComDomains)){
					$res = $this->isDomainTrans($succNonComDomains, $type);
					$succNonComTranDomains = $res[0];
					$failDomains = array_merge($failDomains, $res[1]);
				}

				if(empty($succComTranDomains) && empty($succNonComTranDomains)){
					return array('flag' => TRUE,'msg' => array('succ' => array(),'fail' => $failDomains));
				}
				
				
				
				$succLockDomains = array();
				$succFreezeDomains = array();
				// step6：对于可发布的我司域名和非我司域名
				// 处理我司域名锁定
				if(!empty($succComTranDomains)){
					foreach($succComTranDomains as $k => $v)
					{
						$this->goSer->call($k, 'DomainLogic::lockDomain', array($k));
					}
					$res = $this->goSer->send();
					foreach($res as $k => $v)
					{
						$v = $v['DomainLogic::lockDomain'];
						if(isset($v['goError'])){
							$failDomains[$k] = '系统繁忙,请重试';
						}else{
							$domains[$k]['orderId'] = 0;
							if($v)
							{
								$succLockDomains[$k] = 1;
							}
							else
							{
								$failDomains[$k] = '域名锁定失败';
							}
						}
					}
				}
	
			
				//非我司域名冻结保证金
				if(!empty($succNonComTranDomains)){
					foreach($succNonComTranDomains as $k => $v)
					{
						$this->goSer->call($k, 'DomainLogic::freezeMoney', array($uId, $k, \core\Config::item('baozhengjin')->fabu));
					}
					$res = $this->goSer->send();		
					foreach($res as $k => $v)
					{
						$v = $v['DomainLogic::freezeMoney'];
						if(isset($v['goError'])){
							$failDomains[$k] = '系统繁忙,请重试';
						}else{
							if($v)
							{
								$domains[$k]['orderId'] = $v;
								$succFreezeDomains[$k] = 1;
							}
							else
							{
								$failDomains[$k] = '保证金冻结失败';
							}
						}
					}
				}

				// step5：从step3和step4里面合并获取可发布的我司域名和非我司域名
				// 调用go去并行处理我司和非我司的可发布情况返回来		
				$succDomains = array_merge($succFreezeDomains, $succLockDomains);
				if(empty($succDomains)){
					return array('flag' => TRUE,'msg' => array('succ' => array(),'fail' => $failDomains));
				}

				// 从redis里面获取域名是否推荐数据进行标识
				// 同时将推荐域名推到redis中做bbs推荐
				// 调用go并发处理
				foreach($succDomains as $k => $v)
				{
					$promote = $redis->exists('promote:' . $uId . $k);
					if($promote)
					{
						$domains[$k]['isHot'] = 1;
						$bbs = $redis->lRange('bbs:domain', 0, -1);
						if(!in_array($uId, $bbs))
						{
							$redis->lPush('bbs:domain', $uId);
						}
					}
					else
					{
						$domains[$k]['isHot'] = 0;
					}
				}
				
				// step7：将域名写入交易表
				// 调用go并行处理
				foreach($succDomains as $k => $v)
				{
					$minute = rand(0, 59);
					$domains[$k]['endTime'] = strtotime(date('Y-m-d', time()+86400*$domains[$k]['day']).' '.$domains[$k]['hour'].':'.$minute.':00');
					if($domains[$k]['endTime'] > $domains[$k]['expireTime']){
						$domains[$k]['endTime'] = $domains[$k]['expireTime'];
						$domains[$k]['endTimeChange'] = true;
					}
					$this->goSer->call($k, 'DomainLogic::publicDomain', array($uId, $k, $domains[$k]['description'], $domains[$k]['expireTime'], $type, $domains[$k]['price'], $domains[$k]['endTime'], $cashType, $domains[$k]['isOur'], $domains[$k]['isHot'], $domains[$k]['orderId']));
				}
				$res = $this->goSer->send();
				$succInsertDomains = array();
				foreach($res as $k => $v)
				{
					$v = $v['DomainLogic::publicDomain'];
					if(isset($v['goError'])){
						$failDomains[$k] = '系统繁忙,请重试';
					}else{
						if($v)
						{
							$succInsertDomains[$k] = isset($domains[$k]['endTimeChange']) ? $domains[$k]['endTime'] : 0;
						}
						else
						{
							$failDomains[$k] = '写入交易表失败';
						}
					}
				}
				
		
				$msg = array('succ' => $succInsertDomains,'fail' => $failDomains);
				return array('flag' => TRUE,'msg' => $msg);
			
		}
		
		catch(\Exception $e)
		{
			\core\Logger::write('FABU', array('出现异常',$e->getMessage(),$e->getFile(),$e->getLine()));
			return array('flag' => FALSE,'msg' => $e->getMessage());
		}
	}

	private function fixedPriceOne($uId, $domains, $cashType, $type)
	{
		$logic = new DomainLogic();
		$redis = core\driver\Redis::getInstance('default');
		foreach ($domains as $k => $v){
			$domain = $k;
			$value = $v;
		}
	
	
	
		// step2：检测域名简介是否包含关键词
		// 检测出售天数，价格
		$res = $logic->checkBaseInfo($value);
		if(!$res['flag'])
		{
			return array('succ' => array(),'fail' => array($domain => '该域名简介包含非法词'.$res['msg']));
		}
	
	
		$res = $this->preChk($uId, array($domain), $type);
		if(count($res['msg']['fail'])){
			return $res['msg'];
		}
			
	
		// step3：存在我司域名调用判断我司域名是否可发布
		// 非我司域名判断是否$domains里面有非我司域名，有的话从redis中取出
		// 上一步缓存的已经认证过的域名，排除$domains里面非我司域名那个数组
		// 里面不在缓存中得域名
		$res = $logic->checkMyDomain($uId, $domain);
		$inEname = $res['flag'];
		$value['expireTime'] = 0;
		if($inEname == 1){
			//我司判断时间
			$value['expireTime'] = strtotime($res['msg']['expireTime']);
			$value['isOur'] = 1;
		}elseif($inEname == 2){
			$whois = $redis->get('whois:'.$uId.$domain);
			if($whois){
				$value['expireTime'] = strtotime($whois);
				$value['isOur'] = 2;
			}else{
				return array('succ' => array(),'fail' => array($domain => '非我司域名认证失败'));
			}
		}else{
			return array('succ' => array(),'fail' => array($domain => '该域名不属于您'));
		}
	
	
	
		// step5：从step3和step4里面合并获取可发布的我司域名和非我司域名
		// 调用go去并行处理我司和非我司的可发布情况返回来
	
		// step6：对于可发布的我司域名和非我司域名
		// 处理我司域名锁定和非我司域名冻结保证金
		$value['orderId'] = 0;
		if($inEname == 1){
			$res = $logic->lockDomain($domain);
			if(!$res)
			{
				return array('succ' => array(),'fail' => array($domain => '域名锁定失败'));
			}
		}elseif($inEname == 2){
			$bondAuction =  \Core\Config::item('base : finance')->type->bondAuction;
			$res = $logic->freezeMoney($uId, $domain, $bondAuction);
			if(!$res)
			{
				return array('succ' => array(),'fail' => array($domain => '保证金冻结失败'));
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
				$redis->lPush('bbs:domain', $uId);
			}
		}else{
			$value['isHot'] = 0;
		}
	
	
		$minute = rand(0, 59);
		$value['endTime'] = strtotime(date('Y-m-d', time()+86400*$value['day']) . " {$value['hour']}:{$minute}:00");
		if($value['endTime'] > $value['expireTime']){
			$value['endTime'] = $value['expireTime'];
		}
		// step7：将域名写入交易表
		// 调用go并行处理
		$tao_id = $logic->publicDomain($uId, $domain, $value['description'], $value['expireTime'], $type, $value['price'], $value['endTime'], $cashType, $value['isOur'], $value['isHot'], $value['orderId']);
	
		return array('succ' => array($domain => $tao_id),'fail' => array());
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
	

	/**
	 * 验证 一口价是否符合最低
	 * @param unknown $domains
	 */
	public function checkBuyNowPrice($domains)
	{
		$data = array();
		$domainLogic = new DomainLogic();
		foreach ($domains as $k=>$v)
		{
			$flag = false;
			if($v<=10)
			{
				$flag = true;
				$data[$k] = $flag;
				continue;
			}
			$lowestPrice = $domainLogic->getDomainLowestPrice($k, $v);
			if($lowestPrice&&$v <= $lowestPrice)
			{
						$flag = true;
			}
			$data[$k] = $flag;
		}
		return $data;
	}
	
	private function checkDomainTld($domains)
	{
		// 检测域名后缀
		foreach($domains as $v)
		{
			$this->goSer->call($v, 'DomainLogic::checkFromTld', array($v));
		}
		$res = $this->goSer->send();
		$succTldDomains = $failTldDomains = $errorDomains =array();
		foreach($res as $k => $v)
		{
			if(isset($v['DomainLogic::checkFromTld']['goError']))
			{
				$errorDomains[$k] = '系统繁忙,请重试!';
			}
			elseif($v['DomainLogic::checkFromTld']['flag'])
			{
				$succTldDomains[] = $k;
			}
			else
			{
				$failTldDomains[$k] = '该域名后缀不能发布交易!';
			}
		}
		return array($succTldDomains ,$failTldDomains,$errorDomains);
	}
	
	private function checkMyDomain($uId,$succTldDomains)
	{
		// 获取非我司 ,我司 ,我司非用户的
		foreach($succTldDomains as $v)
		{
			$this->goSer->call($v, 'DomainLogic::checkMyDomain', array($uId,$v));
		}
		$res = $this->goSer->send();
		$enameDomains = $notUserDomains = $notInEnameDomains = $errorDomains = array();
		
		foreach($res as $k => $v)
		{
			if(isset($v['DomainLogic::checkMyDomain']['goError']))
			{
				$errorDomains[$k] = '系统繁忙,请重试!';
			}
			else
			{
				switch($v['DomainLogic::checkMyDomain']['flag'])
				{
					case 1:
						// 我司域名
						$enameDomains[$k] = $v['DomainLogic::checkMyDomain']['msg'];
						break;
					case 2:
						// 非我司域名
						$notInEnameDomains[] = $k;
						break;
					case 3:
						// 我司域名 但不属于用户
						$notUserDomains[$k] = $v['DomainLogic::checkMyDomain']['msg'];
						break;
					case 4:
						// 获取信息不到或者失败的域名
						$errorDomains[$k] = $v['DomainLogic::checkMyDomain']['msg'];
						break;
				}
			}
		}
		return array($enameDomains , $notUserDomains , $notInEnameDomains , $errorDomains);
	}
	
	private function comCheck($enameDomains,$type)
	{
		$cEnameDomains = $cErrorEnameDomains = array();
		// 我司域名检测 状态 注册时间和 过期时间
		foreach($enameDomains as $k => $v)
		{
			$this->goSer->call($k, 'DomainLogic::comCheck', array($k,$type,$v));
		}
		$cres = $this->goSer->send();
		
		foreach($cres as $k => $v)
		{
			if(isset($v['DomainLogic::comCheck']['goError']))
			{
				$cErrorEnameDomains[$k] = '系统繁忙,请重试!';
			}
			elseif($v['DomainLogic::comCheck']['flag'])
			{
				$cEnameDomains[$k] = $v['DomainLogic::comCheck']['msg'];
			}
			else
			{
				$cErrorEnameDomains[$k] = $v['DomainLogic::comCheck']['msg'];
			}
		}
		return array($cEnameDomains , $cErrorEnameDomains);
	}
	
	private function nonComCheck($notInEnameDomains)
	{
		// 非我我司域名检测 tld 和 是否在黑名单
		$cNotInEnameDomains = $cErrorNotInEnameDomains = array();
		foreach($notInEnameDomains as $v)
		{
			$this->goSer->call($v, 'DomainLogic::nonComCheck', array($v));
		}
		$res = $this->goSer->send();
		foreach($res as $k => $v)
		{
			if(isset($v['DomainLogic::nonComCheck']['goError']))
			{
				$cErrorNotInEnameDomains[$k] = '系统繁忙,请重试!';
			}
			elseif($v['DomainLogic::nonComCheck']['flag'])
			{
				$cNotInEnameDomains[$k] = $v['DomainLogic::nonComCheck']['msg'];
			}
			else
			{
				$cErrorNotInEnameDomains[$k] = $v['DomainLogic::nonComCheck']['msg'];
			}
		}
		return array($cNotInEnameDomains, $cErrorNotInEnameDomains);
	}
	
	private function isDomainTrans($cDomains,$type)
	{
		// 域名 检测是否在交易中
		$tSuccDomains = $tFailDomains = array();
		foreach($cDomains as $k => $v)
		{
			$this->goSer->call($k, 'DomainLogic::isDomainTrans', array($k,$type));
		}
		$res = $this->goSer->send();
		foreach($res as $k => $v)
		{
			if(isset($v['DomainLogic::isDomainTrans']['goError']))
			{
				$tFailDomains[$k] = '系统繁忙,请重试!';
			}
			elseif(!$v['DomainLogic::isDomainTrans'])
			{
				$tSuccDomains[$k] = $cDomains[$k]; // 简介
			}
			else
			{
				$tFailDomains[$k] = '域名正在交易中,无法发布交易';
			}
		}
		return array($tSuccDomains ,$tFailDomains);
	}
}
