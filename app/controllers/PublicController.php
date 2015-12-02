<?php
use core\ApiCom;
use core\DataCenter;
use core\driver\Redis;
class PublicController extends ControllerBase
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
		$this->getStock
		$domainLogic = new DomainLogic();
		// step1：判断TLD的域名，排除不能发布的
		list($succTldDomains, $failTldDomains) = $domainLogic->checkFromTld($domains);
		// step2: 判断提交域名的类别，获取域名过期时间、注册时间和状态
		// 该步能得到我司域名、我司非用户域名、非我司域名，
		// 获得结果后可以去除满足不可发布的域名
		// 域名数超过20个则拆分成每20个一组调用go并发请求
		// 调用go获取结果
		$count = count($succTldDomains);
		$server =new \GoServer();
		$server->call($key,$domainLogic->checkFromTld
		*/
		class ClassName extends AnotherClass
		{
			
			function __construct(argument)
			{
				# code...
			}
		})
		$enameDomains = $notUserDomains = $notInEnameDomain = array();
		if($count > 40)
		{
			// go 并行
			$succTldDomains = array_chunk($succTldDomains, 3);
			foreach($succTldDomains as $val)
			{
				list($temp1, $temp2, $temp3) = $domainLogic->checkMyDomain($uId, $succTldDomains);
				$enameDomains = array_merge($temp1, $enameDomains);
				$notUserDomains = array_merge($temp2, $notUserDomains);
				$notInEnameDomain = array_merge($temp3, $notInEnameDomain);
			}
		}
		if($count > 20 && $count <= 40)
		{
			$succTldDomains = array_chunk($succTldDomains, 2);
			foreach($succTldDomains as $val)
			{
				list($temp1, $temp2, $temp3) = $domainLogic->checkMyDomain($uId, $succTldDomains);
				$enameDomains = array_merge($temp1, $enameDomains);
				$notUserDomains = array_merge($temp2, $notUserDomains);
				$notInEnameDomain = array_merge($temp3, $notInEnameDomain);
			}
		}
		else
		{
			list($enameDomains, $notUserDomains, $notInEnameDomain) = $domainLogic->checkMyDomain($uId, $succTldDomains);
		}
		
		// step3：判断交易域名是否在交易中
		list($tSuccEnameDomains, $tFailEnameDomains) = $domainLogic->isDomainTrans($enameDomains, $type);
		list($tSuccNotINEnameDomains, $tFailNotInEnameDomain) = $domainLogic->isDomainTrans($notInEnameDomain, $type);
		// step4：从step2里面获取可发布的我司域名和非我司域名
		// 调用go去并行处理我司和非我司的可发布情况以及历史简介和推荐域名
		// 并把简介和推荐域名写入到redis缓存
		// 域名数超过20个则拆分成每20个一组调用go并发请求
		list($succEnameDomains, $failEnameDomains) = $domainLogic->comCheck($tSuccEnameDomains, $type);
		list($succNotInEnameDomain, $failNotInEnameDomain) = $domainLogic->comCheck($tSuccNotINEnameDomains, $type);
		
		// step5 合并可以发布的域名 ,不能发布交易的域名
		$succDomains = array_merge($succEnameDomains, $succNotInEnameDomain);
		// 后缀不允许的域名 正在交易的我司域名和非我司域名 域名不可发布的我司域名 和非我司域名
		$failDomains = array_merge($failTldDomains, $tFailEnameDomains, $tFailNotInEnameDomain, $failNotInEnameDomain, 
			$failEnameDomains);
		return array('succ' => $succDomains,'fail' => $failDomains);
		// return array('succInEname'=>$succEnameDomains,'fail'=>$failDomains,'succNotInEname'=>$succNotInEnameDomain);
	}

	/**
	 * 非我司查询whois信息
	 * 
	 * @param s $uId  int
	 * @param s $domains	string
	 *        	
	 */
	public function whoisChk($uId, $domain)
	{
		$domainLogic = new DomainLogic();
		// step1：通过dc直接请求返回域名whois信息
		$dc = new DataCenter('interfaces/getWhoisBaseHaveEmail');
		$whoisInfo = $dc->getData(array('domain' => $domain));
		if($domainLogic->checkDomainByDate($whoisInfo['ExpirationDate']) < 86400 * 30)
		{
			return array('flag' => false,'msg' => '域名将在30天内过期时间,无法发布');
		}
		if($domainLogic->checkDomainByDate($whoisInfo['RegistrationDate']) < 86400 * 60)
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
		$userEmails = $dc->getData(array('enameId' => $uId));
		
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
	 * @params $uId int 
	 * @params $domains array  包含我司和非我司域名的二维数组，一个域名包含价格和简介以及出售周期 
	 * @params $cashType int  提现类型，在控制器检查，2 不可提现 3 可体现
	 */
	public function fixedPrice($uId, $domains, $cashType)
	{
		$logic = new DomainLogic();
		$domains = array(
			'inEname' => array('baidu.com' => array('price' => '100','endTime' => '32432422','description' => '简介'),
				'ename.com' => array('price' => '100','endTime' => '32432422','description' => '简介')),
			'notInEname' => array());
		// step1：判断TLD的域名，排除不能发布的
		$logic->checkFromTld($domains);
		
		// step2：检测域名简介是否包含关键词
		// 检测出售天数，价格
		// 使用go并行处理
		$logic->checkBaseInfo($domains);
		
		// step3：存在我司域名调用判断我司域名是否可发布
		// 非我司域名判断是否$domains里面有非我司域名，有的话从redis中取出
		// 上一步缓存的已经认证过的域名，排除$domains里面非我司域名那个数组
		// 里面不在缓存中得域名
		// 通过go并行调用返回数据
		
		// step4：取得所有域名判断是否在交易中
		// 用go直接调用
		$logic->isDomainTrans($domains, $type = 3);
		
		// step5：从step3和step4里面合并获取可发布的我司域名和非我司域名
		// 调用go去并行处理我司和非我司的可发布情况返回来
		
		// step6：对于可发布的我司域名和非我司域名
		// 处理我司域名锁定和非我司域名冻结保证金
		// 从redis里面获取域名是否推荐数据进行标识
		// 同时将推荐域名推到redis中做bbs推荐
		// 调用go并发处理
		$logic->lockDomain($domains);
		$logic->freezeMoney($uId, $domains);
		
		// step7：将域名写入交易表
		// 调用go并行处理
		$logic->publicDomain($uId, $domains);
		
		return 1;
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
