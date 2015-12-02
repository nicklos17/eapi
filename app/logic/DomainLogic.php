<?php
class DomainLogic
{

	/**
	 * 从tld判断域名
	 */
	public function checkFromTld($domain)
	{
		$tld = (array)\Core\Config::item('tld');
		$provinceTld = (array)\Core\Config::item('provinceTld');

		$suffix = \common\Common::getDomainAllTld($domain);
		return in_array($suffix, $tld) || in_array($suffix, $provinceTld) ? array('flag' => 1) : array('flag' => 0);
	}

	/**
	 * 检查所给域名是否属于用户
	 * 调用DC接口判断
	 *
	 * @param s $uId
	 *        	string 用户id
	 * @param s $domain
	 *        	string 对应的域名
	 *        	内容包括：我司域名以及这些域名对应的过期时间和注册时间，还有是否可交易状态
	 *        	我司域名但不属于改用户的
	 *        	非我司域名
	 */
	public function checkMyDomain($uId, $domain)
	{
		// step1: 调用DC接口
		$apiCom = new \core\ApiCom('domaintrade/checkdomaininfo');
		try
		{
			$domainInfo = $apiCom->getPostData(array('domains' => $domain))[0]['msg'];
			if($domainInfo == '域名信息查找失败') // 非我司 , 2
            			return array('flag' => 2,'msg' => $domainInfo);

                        if (!isset($domainInfo['EnameId']))     // 不交易
                                return array('flag' => 4,'msg' => $domainInfo);

			if(isset($domainInfo['EnameId']) && $domainInfo['EnameId'] == $uId)  // 我司，属于用户, flag 1
				return array('flag' => 1,'msg' => array('expireTime' => $domainInfo['ExpDate'], 'regTime' => $domainInfo['RegDate'], 'domianStatus' => $domainInfo['DomainMyStatus']));
			else
				return array('flag' => 3,'in ename, not belong this user');
		}
		catch(Exception $e)
		{
			return array('flag' => 4,'msg' => $e->getMessage());
		}
	}

	/**
	 * 检查所给域名是否正在交易
	 * 1、询价交易判断是否询价中
	 * 2、易拍易卖、专题拍卖判断是否在审核中
	 * 3、一口价判断是否在已经在交易库中
	 */
	public function isDomainTrans($domain, $type)
	{
		// 根据$type来判断调用isTrans和isInquiryTrans返回交易中的域名
		// $type只要两种状态值，一种是询价类型一种是非询价类型
        // 询价
        if($type != 8)
            return $this->isTrans($domain);
	}

	/**
	 * 判断准备发布一口价的域名是否已经在交易中
	 * 交易表中域名状态为1，2，3，9的域名为交易中的域名
	 *
	 * @param s $domain
	 *        	string
	 */
	public function isTrans($domain)
	{
		$taoModel = new NewTaoModel();
		return $taoModel->isTrans($domain);
	}

	/**
	 * 判断准备发布的询价域名是否在询价中
	 * 询价表中域名状态为1，2的说明正在询价中
	 *
	 * @param s $domain
	 *        	string
	 */
	protected function isInquiryTrans($domain)
	{}

	/**
	 * 根据域名到期时间距离现在多长时间
	 *
	 * @param s $expireTime
	 *        	int 到期时间戳
	 *
	 */
	public function checkDomainByDate($expireTime)
	{
		return   $expireTime -$_SERVER['REQUEST_TIME'];
	}

	/**
	 * 检查当前离域名注册时间多少天
	 *
	 * @param s $domain
	 *        	string
	 * @param s $regTime
	 *        	int 域名注册时间
	 *
	 */
	public function checkDomainByRegtime($regTime)
	{
		return $_SERVER['REQUEST_TIME'] - $regTime;
	}

	/**
	 * 检查非我司域名是否在黑名单中
	 *
	 * @param s $domain
	 *        	string
	 *
	 */
    public function checkBlackList($domain) {
        $apiCom = new \core\ApiCom('blacklistadmin/isinblacklist');
        $res = $apiCom->getPostData(array('enameIdOrDoamin'=> $domain));
        return (isset($res) && isset($res[0]) && isset($res[0]['falg']) && $res[0]['falg'])? true: false;
    }


    /**
     * 对应步骤为非我司域名判断是否在不支持的TLD里面(和上一步不一样 有特殊后缀是不支持交易的) 目前不支持的tw,in,cd,us,info
     *
     *
     */
    public function nonComCheckTld($domain) {
    	$tld = \common\Common::getDomainAllTld($domain);
    $notInEnameTldConf = \core\Config::item('notInEnameTld')->toArray();
    return in_array($tld, $notInEnameTldConf)? true:false;
    }

    /**
     * 非我司域名检查黑名单和特殊的tld后缀
     *
     *
     */
    public function nonComCheck($domain) {

    	if($this->checkBlackList($domain))
    	{
    		return array('flag'=>false,'msg'=>'域名在黑名单里,无法发布交易');
    	}
    	if($this->nonComCheckTld($domain))
    	{
    		return array('flag'=>false,'msg'=>'非我司域名后缀为:tw,in,cd,us,info,无法发布交易');
    	}
    	$newTransResult = new NewTransResult();
    	$domainInfo = $newTransResult->getDescAndHot($domain);
    	if ($domainInfo)
    	{
    		// is hot  写入redis，  promote:uid.domain
    		if ($domainInfo->t_hot)
    		{
    			$redis = \core\driver\Redis::getInstance('default');
    			$redis->set('promote:' . $domainInfo->t_enameId . $domain, $domainInfo->t_hot);
    		}
    		return array('flag' => true, 'msg' => $domainInfo->t_desc);
    	}
    	else
    	{
    		return array('flag' => true, 'msg' => '');
    	}
    }

	/**
	 * 我司域名根据交易类型检查是否符合发布交易条件，并从历史交易表取出可交易的域名的历史简介和是否推荐标志
	 *
	 * @param s $domain 格式:array('abc.com'=>array('expireTime'=>121212,'regTime'=>454545,'domianStatus'=>2))
	 *
	 * @param s $type
	 *        	int
	 *
	 */
	public function comCheck($domain, $type,$info)
	{
			//我司域名  判断过期时间  注册时间  域名状态
			// 判断是否是 cn 域名   是的 必须注册满7天
			$isCnDomain = substr_count(strtolower($domain),'.cn') ? TRUE:FALSE;
			if($isCnDomain)
			{
				if($this->checkDomainByRegtime(strtotime($info['regTime']))<604800)
				{
					return array('flag' => false, 'msg' => '该域名注册未满7天不能发布交易!');
				}
			}
			//一口价
			if($type==1)
			{
				if($this->checkDomainByDate(strtotime($info['expireTime']))<86400)
				{
					return array('flag' => false, 'msg' => '域名将在1天内过期时间,无法发布!');
				}
			}
			//拍卖会
			elseif($type==7)
			{
				if($this->checkDomainByDate($info['expireTime'])<2592000)
				{
					return array('flag' => false, 'msg' => '域名将在30天内过期时间,无法发布!');
				}
			}
			else
			{
				if($this->checkDomainByDate($info['expireTime'])<1382400)
				{
					return array('flag' => false, 'msg' => '域名将在16天内过期时间,无法发布!');
				}
			}
			// 检测我司的域名 状态 获取域名状态对应的msg
			$status = $info['domianStatus'];
			$domainStatusConf = \core\Config::item('domainStatus');
			if($status!=1)
			{
				return array('flag' => false, 'msg' => '域名状态为:'.$domainStatusConf[$status].'中,无法发布!');
			}
		$newTransResult = new NewTransResult();
		$domainInfo = $newTransResult->getDescAndHot($domain);

	    // 获取可交易域名的简介和推荐标识
            if ($domainInfo) {
                // is hot  写入redis，  promote:uid.domain
                if ($domainInfo->t_hot) {
                    $redis = \core\driver\Redis::getInstance('default');
                    $redis->setex('promote:' . $domainInfo->t_enameId . $domain, 30 * 60 , $domainInfo->t_hot);
                }
                return array('flag' => true, 'msg' => $domainInfo->t_desc);
            }
            return array('flag' => true, 'msg' => '');
        }

    /**
     * 调用Dc接口锁定域名
     * @params $domain string
     *
     */
    public function lockDomain($domain) {
        $dataCenter = new \core\DataCenter('domain/setdomainmystatus');
        $status = 2;
        $rs = $dataCenter->getPostData(array('domain'=> $domain, 'status'=> $status));
        if(!$rs['msg']['result'])
        {
            \core\Logger::write("DOMAIN_LOG",
                array(__METHOD__,"域名 {$domain} 设置状态为 {$status} 失败,msg信息为：{" . $rs['msg']['msg'] . "}"));
        }
        return $rs['msg']['result'];
    }

    /**
     * 检查域名简介是否有关键词
     * @params $domainMemo array
     *          该数组每个数据包含提交的域名简介、发布的天数和发布的价格
     *
     */
    public function checkBaseInfo($domainMemo)
    {
        $redis = \core\driver\Redis::getInstance('default');
        if(!$words = $redis->get('trans:keywords'))
        {
            $words = (new KeywordsModel)->getKeywords();
            $redis->setex('trans:keywords', 86400, $words);
        }
        foreach($words as $word)
        {
            if(stristr($domainMemo['description'], $word->word) !== false)
				return array('flag' => false,'msg' => $word->word);
        }
		return array('flag' => true);
    }

	/**
	 * 非我司域名的发布交易调用该函数冻结保证金
	 *
	 * @param s $uId
	 *        	int
	 * @param s $domain
	 *        	string
	 *
	 */
	public function freezeMoney($uId, $domain, $money)
    {
        return (new \finance\Orders($uId))->addOrder($domain, $type = 110 , $money);
    }

	/**
	 * 写入交易表
	 * $uId int [用户uid]
	 * $domain string [域名]
	 * $desc string [域名简介]
	 * $expireTime int [域名到期时间]
	 * $type int [交易类型:1-一口价;2-竞价;3-竞价(预订竞价);4-竞价(专题拍卖);5-竞价(易拍易卖);]
	 * $price int [价格]
	 * $endTime int [交易结束时间]
	 * $moneyType int [是否提现:1-可提现;2-不可提现]
	 * $isOur int [是否我司域名:1-是;2-否]
	 * $isHot int [是否用户推荐:0-否;1-是]
     * $orderId int [保证金订单id]
	 * $startTime int [拍卖时间]
	 */
	public function publicDomain($uId, $domain, $desc, $expireTime, $type, $price, $endTime, $moneyType,
		$isOur, $isHot, $orderId = false, $startTime = false)
	{
		$domainInfo = new \common\Common;
		$body = $domainInfo->getDomainBody($domain);
		$data = array(
            't_dn' => $domain,
            't_body' => $body,
            't_type' => $type,
            't_enameid' => $uId,
			't_start_price' => $price,
            't_now_price' => $price,
            't_create_time' => $_SERVER['REQUEST_TIME'],
			't_start_time' => $startTime? $startTime : $_SERVER['REQUEST_TIME'],
            't_end_time' => $endTime,
			't_tld' => $domainInfo->getTldType($domain),
            't_len' => strlen($body),
            't_desc' => $desc,
			't_money_type' => $moneyType,
            't_ip' => \common\Client::getIp(),
            't_is_our' => $isOur,
			't_exp_time' => $expireTime,
            't_hot' => $isHot
        );
		// 获取域名类型
        $domainClass = \common\domain\Domain::getDomainClass($domain);
		$data['t_class_name'] = $domainClass[0];
		$data['t_two_class'] = !$domainClass[1] ? 0 : $domainClass[1];
		$data['t_three_class'] = !$domainClass[2] ? 0 : $domainClass[2];

		// 非我司域名，获取保证金订单信息
		if($isOur !== 1)
        {
            if($orderId)
                $data['t_seller_order'] = $orderId;
            else
                return array('flag' => '10001', 'msg' => '非我司域名须提交保证金订单id');
        }

        $taoModel = new NewTaoModel();
		if($taoModel->setDoaminInfo($data))
            return TRUE;
        else
        {
            \core\Logger::write("domain.log",
                array(__METHOD__,"域名{$domain}发布插入到交易表new_tao失败，用户id:{".$uId."},发布时间:". date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])));
            return FALSE;
        }
	}

	/**
	 * 判断 非我司的域名是否 Godaddy和ENOM 注册商
	 */
	public function checkIsGodaddyOrENOM($SponsoringRegistrar)
	{
		if(strpos(strtolower($SponsoringRegistrar), 'godaddy') !== false ||
			 strpos(strtolower($SponsoringRegistrar), 'enom') !== false)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 非我司域名的邮箱验证
	 *
	 * @param int $uId
	 *        	enameId
	 * @param string $domain
	 *        	域名
	 * @param array $whoisInfo
	 *        	域名的信息
	 * @param array $userEmails
	 *        	用户认证的邮箱
	 * @return boolean
	 */
	public function checkUserEmail($uId, $domain, $whoisInfo, $userEmails)
	{
		if($userEmails)
		{
			$emails = $this->verifyEmailConv($userEmails);
			if(isset($whoisInfo['AdministrativeEmail']) &&
				 !in_array(strtolower($whoisInfo['AdministrativeEmail']), $userEmails))
			{
				return false;
			}
			else
			{	
				$redis = \core\driver\Redis::getInstance('default');
				$redis->setex('whois:' . $uId . $domain, 30 * 60 , $whoisInfo['ExpirationDate']);
				return TRUE;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * 将接口获取的验证邮箱数组转换为一维数组
	 */
	public function verifyEmailConv($verifyEmails)
	{
		$newVerifyEmails = array();
		if($verifyEmails['emailinfo'])
		{
			foreach($verifyEmails['emailinfo'] as $verifyEmail)
			{
				foreach($verifyEmail as $key => $value)
				{
					if($key == 'Email')
					{
						$newVerifyEmails[] = strtolower($value);
						break;
					}
				}
			}
		}
		return $newVerifyEmails;
	}

	public function preOneDomain($domain, $type, $uId)
	{
		if(!$this->checkFromTld($domain))
		{
			return array('succ' => array(),'fail' => array($domain => '该域名后缀不能发布交易!'));
		}
		// 判断 域名是 flag 1 我司域名(注册时间,过期时间 和域名状态)   2非我司域名   3 我司非用户域名
		$res = $this->checkMyDomain($uId, $domain);
		$isEnameDomain = true;
		if($res['flag'] == 1)
		{
			// 我司域名 判断过期时间 注册时间 域名状态
			// 判断是否是 cn 域名 是的 必须注册满7天 获取历史简介
			$checkRes = $this->comCheck($domain, $type,$res['msg']);
			if(!$checkRes['flag'])
			{
				return array('succEname' => array(),'succNotInEname' => array(),'fail' => array($domain => $checkRes['msg']));
			}
			$tmp = array($domain=>$checkRes['msg']); //域名和简介
		}
		elseif($res['flag'] == 2)
		{
			//非我司域名判断 不可发布的后缀 及 是否在黑名单
			$checkRes = $this->nonComCheck($domain);
			if(!$checkRes['flag'])
			{
				return array('succEname' => array(),'succNotInEname' => array(),'fail' => array($domain => $checkRes['msg']));
			}
			$tmp = array($domain=>$checkRes['msg']); //域名和简介
			$isEnameDomain = false;
		}
		elseif($res['flag'] == 3)
		{
			return array('succEname' => array(),'succNotInEname' => array(),'fail' => array($domain => '域名不属于您,无法发布交易'));
		}
		else
		{
			return array('succEname' => array(),'succNotInEname' => array(),'fail' => array($domain => $res['msg']));
		}
		// 检测域名是否正在交易中
		$res = $this->isDomainTrans($domain, $type);
		if($res['flag'])
		{
			return array('succEname' => array(),'succNotInEname' => array(),'fail' => array($domain => '该域名正在交易中!'));
		}
		else
		{
			if($isEnameDomain)
			{
				return  array('succEname' => $tmp,'succNotInEname' => array(),'fail' => array()) ;				
			}
			else 
			{
				return array('succEname' => array(),'succNotInEname' => $tmp,'fail' => array());
			}
		}
	}

	/**
	 * 检测 我司域名的状态  注册时间和过期时间
	 * @param unknown $domains
	 */
	public function  checkEnameDomains($domains,$type)
	{
		$enameDomains = $errorDomains = array();
		foreach ($domains as $k=>$v)
		{
			//我司域名  判断过期时间  注册时间  域名状态
			// 判断是否是 cn 域名   是的 必须注册满7天
			$isCnDomain = substr_count(strtolower($k),'.cn') ? TRUE:FALSE;
			if($isCnDomain)
			{
				if($this->checkDomainByRegtime($v['regTime'])<604800)
				{
					$errorDomains[$k]= '该域名注册未满7天不能发布交易!';
					continue;
				}
			}
			//一口价
			if($type==1)
			{
				if($this->checkDomainByDate($v['expireTime'])<86400)
				{
					$errorDomains[$k]= '域名将在1天内过期时间,无法发布!';
					continue;
				}
			}
			//拍卖会
			elseif($type==7)
			{
				if($this->checkDomainByDate($v['expireTime'])<2592000)
				{
					$errorDomains[$k]= '域名将在30天内过期时间,无法发布!';
					continue;
				}
			}
			else
			{
				if($this->checkDomainByDate($v['expireTime'])<1382400)
				{
					$errorDomains[$k]= '域名将在16天内过期时间,无法发布!';
					continue;
				}
			}
			// 检测我司的域名 状态 获取域名状态对应的msg
			$status = $v['domianStatus'];
			$domainStatusConf = \core\Config::item('domainStatus');
			if($status!=1)
			{
				$errorDomains[$k]= '域名状态为:'.$domainStatusConf[$status].'中,无法发布!';
				continue;
			}
			$enameDomains[] = $k;
		}
		return array($enameDomains,$errorDomains);
	}
}
