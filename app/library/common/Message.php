<?php
namespace common;

class Message
{

	/**
	 *
	 * @return \core\DataCenter;
	 */
	private function getDcObj($method)
	{
		return new \core\DataCenter($method);
	}
	
	
	/**
	 * 走数据中心获取用户的详细信息
	 *
	 * @param int 用户id
	 * @return object
	 */
	public function getUserInfo($enameid)
	{
		$param = array();
		$param['enameid'] = $enameid;
		$rs=self::getDcObj('interfaces/getUserInfo')->getData($param);
		if($rs->code==100000 && $rs->msg)
		{
			return $rs->msg->msg;
		}
		return false;
	}
	
	/**
	 * 卖家回复报价 ,发送信息给买家
	 *
	 * @param int $noticeCode 标识码
	 * @param int $enameId 买家
	 * @param int $price 卖家报价
	 * @param string $domain 域名
	 * @param int $replyId 询价回复id
	 * @param int $flag 1 卖家 报价 2 买家出价
	 * @author xiongyy
	 */
	public function sendReplyMess($noticeCode, $enameId, $price, $domain, $replyId, $flag)
	{
		$param = array();
		$param['noticeCode'] = $noticeCode;
		$param['enameId'] = $enameId;
		$param['price'] = $price;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['flag'] = $flag;
		return self::getDcObj('user/sendReplyMess')->getPostData($param);
	}
	
	/**
	 * 卖家同意报价 ,发送信息给买家
	 *
	 * @param int $noticeCode 标识码
	 * @param int $enameId 买家
	 * @param int $price 卖家报价
	 * @param string $domain 域名
	 * @param int $replyId 询价回复id
	 * @param int $flag 1 卖家 报价 2 买家出价
	 * @author xiongyy
	 */
	public function sendAgreeMess($noticeCode, $enameId, $price, $domain, $replyId, $flag)
	{
		$param = array();
		$param['noticeCode'] = $noticeCode;
		$param['enameId'] = $enameId;
		$param['price'] = $price;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['flag'] = $flag;
		return self::getDcObj('user/sendAgreeMess')->getPostData($param);
	}
	
	/**
	 * 拒绝询价 1 卖家拒绝 发送给买家 2 买家拒绝 发送给卖家
	 *
	 * @param unknown $enameId
	 * @param unknown $domain
	 * @param unknown $replyId
	 * @param unknown $flag
	 * @return Ambigous <multitype:, object, mixed>
	 */
	public function sendRefuseMess($enameId, $domain, $replyId, $flag)
	{
		$param = array();
		$param['enameId'] = $enameId;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['flag'] = $flag;
		return self::getDcObj('user/sendRefuseMess')->getPostData($param);
	}
	
	/**
	 * 询价转经纪 1 卖家转经纪 发送给买家 2 买家转经纪 发送给卖家
	 *
	 * @param unknown $domain
	 * @param unknown $enameId
	 * @param unknown $replyId
	 * @param unknown $esid
	 * @param unknown $flag
	 * @return Ambigous <multitype:, object, mixed>
	 */
	public function sendToEscrowMess($domain, $enameId, $replyId, $esid, $flag)
	{
		$param = array();
		$param['enameId'] = $enameId;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['esid'] = $esid;
		$param['flag'] = $flag;
		return self::getDcObj('user/sendToEscrowMess')->getPostData($param);
	}
	
	/**
	 * 终止询价 1 卖家终止 发送给买家 2 买家终止 发送给卖家
	 *
	 * @param unknown $domain
	 * @param unknown $enameId
	 * @param unknown $replyId
	 * @param unknown $flag
	 * @return Ambigous <multitype:, object, mixed>
	 */
	public function sendCancelMess($domain, $enameId, $replyId, $flag)
	{
		$param = array();
		$param['enameId'] = $enameId;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['flag'] = $flag; // 1 卖家取消 ,发卖家 2 卖家取消 ,发买家 3买家取消 ,发卖家 4买家取消 ,发买家
		return self::getDcObj('user/sendCancelMess')->getPostData($param);
	}
	
	/**
	 * 申请延期 1 是卖家申请 发送给买家 2 是买家申请 发送给卖家
	 *
	 * @param unknown $domain
	 * @param unknown $enameId
	 * @param unknown $replyId
	 * @param unknown $day
	 * @param unknown $flag
	 * @return Ambigous <multitype:, object, mixed>
	 */
	public function sendApplyMess($domain, $enameId, $replyId, $day, $flag)
	{
		$param = array();
		$param['enameId'] = $enameId;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['day'] = $day;
		$param['flag'] = $flag;
		return self::getDcObj('user/sendApplyMess')->getPostData($param);
	}
	
	/**
	 * 同意申请 1 卖家同意 发送给买家 2 买家 同意 发送给卖家
	 *
	 * @param unknown $domain
	 * @param unknown $enameId
	 * @param unknown $replyId
	 * @param unknown $day
	 * @param unknown $deadLine
	 * @param unknown $flag
	 * @return Ambigous <multitype:, object, mixed>
	 */
	public function sendAgreeApplyMess($domain, $enameId, $replyId, $day, $deadLine, $flag)
	{
		$param = array();
		$param['enameId'] = $enameId;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['day'] = $day;
		$param['deadLine'] = $deadLine;
		$param['flag'] = $flag;
		return self::getDcObj('user/sendAgreeApplyMess')->getPostData($param);
	}
	
	/**
	 * 卖家非我司 域名转入后 操作 发送给买家
	 *
	 * @param unknown $enameId
	 * @param unknown $price
	 * @param unknown $replyId
	 * @param unknown $domain
	 * @param unknown $flag
	 * @return Ambigous <multitype:, object, mixed>
	 */
	public function sendInquirySuccessMess($enameId, $price, $replyId, $domain, $flag)
	{
		$param = array();
		$param['enameId'] = $enameId;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['price'] = $price;
		$param['flag'] = $flag;
		return self::getDcObj('user/sendInquirySuccessMess')->getPostData($param);
	}
	
	/**
	 * 拒绝延期申请 1 卖家拒绝 发送给买家 2 买家 拒绝 发送给卖家
	 *
	 * @param unknown $domain
	 * @param unknown $enameId
	 * @param unknown $replyId
	 * @param unknown $day
	 * @param unknown $flag
	 * @return Ambigous <multitype:, object, mixed>
	 */
	public function sendRefuseApplyMess($domain, $enameId, $replyId, $day, $flag)
	{
		$param = array();
		$param['enameId'] = $enameId;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['day'] = $day;
		$param['flag'] = $flag;
		return self::getDcObj('user/sendRefuseApplyMess')->getPostData($param);
	}
	
	/**
	 * 买家同意报价 的 发送信息给卖家
	 *
	 * @param unknown $domain
	 * @param unknown $enameId
	 * @param unknown $replyId
	 * @param unknown $price
	 * @param unknown $date
	 * @return Ambigous <multitype:, object, mixed>
	 */
	public function sendBuyerConfirmMess($domain, $enameId, $replyId, $price, $date)
	{
		$param = array();
		$param['enameId'] = $enameId;
		$param['domain'] = $domain;
		$param['replyId'] = $replyId;
		$param['price'] = $price;
		$param['date'] = $date;
		return self::getDcObj('user/sendBuyerConfirmMess')->getPostData($param);
	}
	
	
	/**
	 * 一口价购买成功发送消息给买家
	 * @param int $enameId
	 * @param string $domainName
	 * @param int $price
	 * @param int $trnasId
	 * @return
	 */
	public function sendBuyNowBuyer($enameId,$domainName,$price,$trnasId)
	{
		$sendBuyer = array('buyer'=> $enameId,'email'=> TRUE,'message'=> TRUE,'sms'=> FALSE,
				'domain'=> $domainName,'nowtime'=> date("Y-m-d H:i:s"),'price'=> $price,'id'=> $trnasId);
		return self::getDcObj('interfaces/buynowSuccessBuyer')->getData($sendBuyer);
	}
	
	/**
	 * 一口价购买成功发送消息给卖家
	 * @param int $enameId
	 * @param string $domainName
	 * @param int $price
	 * @param int $trnasId
	 * @param bool $sms
	 * @return 
	 */
	public function sendBuyNowSeller($enameId,$domainName,$price,$trnasId,$sms)
	{
		$sendSeller = array('seller'=> $enameId,'email'=> TRUE,'message'=> TRUE,'sms'=> $sms,
				'domain'=> $domainName,'nowtime'=> date("Y-m-d H:i:s"),'price'=> $price,'id'=> $trnasId);
		return self::getDcObj('interfaces/buynowSuccessSeller')->getData($sendSeller);
	}
	
	
	/**
	 * 确认交易后发送信息给买家
	 *
	 * @param string $dn 域名
	 * @param int $enameId
	 * @param int $price
	 * @param int $transId
	 * @param string $time 当前时间
	 */
	public static function sendBuynowSuccessBuyer($dn, $enameId, $price, $transId, $time = false)
	{
		$time = ! $time? date("Y-m-d H:i:s"): $time;
		$messageData = array('domainName'=> $dn,'enameid'=> $enameId,'nowTime'=> $time,'price'=> $price,'id'=> $transId);
		return self::getDcObj('user/sendBuynowSuccessBuyer')->getData($messageData)->msg;
	}

	/**
	 * 确认交易发送信息给卖家
	 *
	 * @param array $data
	 * @return boolean
	 */
	public static function sendBuynowSuccessSeller($dn, $enameId, $price, $transId, $time = false)
	{
		$time = ! $time? date("Y-m-d H:i:s"): $time;
		$messageData = array('domainName'=> $dn,'enameid'=> $enameId,'nowTime'=> $time,'price'=> $price,'id'=> $transId);
		return self::getDcObj('user/sendBuynowSuccessSeller')->getData($templateData);
	}
	
	/**
	 * 发信息给卖家
	 *
	 * @param unknown $domain
	 * @param unknown $id
	 * @param unknown $enameid
	 * @return boolean
	 */
	public static function sendAuctionSuccessSeller($domain, $id, $enameid)
	{
		$message = array('domainName'=> $domain,'id'=> $id,'enameid'=> $enameid);
		return self::getDcObj('user/sendAuctionSuccessSeller')->getPostData($message);
	}
	
	/**
	 * 发信息给买家
	 *
	 * @param unknown $domain
	 * @param unknown $id
	 * @param unknown $enameid
	 * @return boolean
	 */
	public static function sendAuctionSuccessBuyer($domain, $id, $enameid)
	{
		$message = array('domainName'=> $domain,'id'=> $id,'enameid'=> $enameid);
		return self::getDcObj('user/sendAuctionSuccessBuyer')->getData($message);
	}
	
	/**
	 * 买家申请延期，通知卖家
	 *
	 * @param unknown $domain
	 * @param unknown $id
	 * @param unknown $enameid
	 * @return boolean
	 */
	public static function sendDeliveryBuyerapply($domain, $day, $id, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'day'=> $day,'id'=> $id);
		return self::getDcObj('user/sendDeliveryBuyerapply')->getData($messageData);
	}
	
	/**
	 * 卖家申请延期，通知买家
	 *
	 * @param string $domain
	 * @param int $id
	 * @param int $enameid
	 * @return boolean
	 */
	public static function sendDeliverySellerapply($domain, $day, $id, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'day'=> $day,'id'=> $id);
		return self::getDcObj('user/sendDeliverySellerapply')->getData($messageData);
	}
	
	/**
	 * 买家拒绝延期，通知卖家
	 *
	 * @param unknown $domain
	 * @param unknown $id
	 * @param unknown $enameid
	 * @return boolean
	 */
	public static function sendDeliverySellerapllyBuyerrefuse($domain, $day, $id, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'day'=> $day,'id'=> $id);
		return self::getDcObj('user/sendDeliverySellerapllyBuyerrefuse')->getData($messageData);
	}
	
	/**
	 * 卖家拒绝延期，通知买家
	 *
	 * @param string $domain
	 * @param int $id
	 * @param int $enameid
	 * @return boolean
	 */
	public static function sendDeliveryBuyerapllySellerefuse($domain, $day, $id, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'day'=> $day,'id'=> $id);
		return self::getDcObj('user/sendDeliveryBuyerapllySellerefuse')->getData($messageData);
	}
	
	/**
	 * 买家同意延期，通知卖家
	 *
	 * @param unknown $domain
	 * @param int $day
	 * @param unknown $id
	 * @param dateitme $deadLine
	 * @param unknown $enameid
	 * @return boolean
	 * @author zhangsc
	 */
	public static function sendDeliverySellerapllyBuyeragree($domain, $day, $id, $deadLine, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'day'=> $day,'deadLine'=> $deadLine,
				'id'=> $id);
		return self::getDcObj('user/sendDeliverySellerapplyBuyeragree')->getData($messageData);
	}
	
	/**
	 * 卖家同意延期，通知买家
	 *
	 * @param string $domain
	 * @param int $day
	 * @param int $id
	 * @param dateitme $deadLine
	 * @param int $enameid
	 * @return boolean
	 */
	public static function sendDeliveryBuyerapllySelleragree($domain, $day, $id, $deadLine, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'day'=> $day,'deadLine'=> $deadLine,
				'id'=> $id);
		return self::getDcObj('user/sendDeliveryBuyerapllySelleragree')->getData($messageData);
	}
	
	
	/**
	 * 买家违约，通知买家
	 *
	 * @param unknown $domain
	 * @param datetime $nowTime
	 * @param unknown $id
	 * @param unknown $enameid
	 * @return boolean
	 */
	public static function sendAuctionBuyerrefuseBuyer($domain, $nowTime, $id, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'nowTime'=> $nowTime,'id'=> $id);
		return self::getDcObj('user/sendAuctionBuyerrefuseBuyer')->getData($messageData);
	}
	
	/**
	 * 买家违约，通知卖家
	 *
	 * @param unknown $domain
	 * @param datetime $nowTime
	 * @param unknown $id
	 * @param unknown $enameid
	 * @return boolean
	 */
	public static function sendAuctionBuyerrefuseSeller($domain, $nowTime, $id, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'nowTime'=> $nowTime,'id'=> $id);
		return self::getDcObj('user/sendAuctionBuyerrefuseSeller')->getData($messageData);
	}
	
	/**
	 * 卖家违约，消息通知卖家
	 * @param string $domain
	 * @param date $nowTime
	 * @param int $id
	 * @param int $enameid
	 * @return boolean
	 */
	public static function sendAuctionSellerrefuseSeller($domain, $nowTime, $id, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'nowTime'=> $nowTime,'id'=> $id);
		return self::getDcObj('user/sendAuctionSellerrefuseSeller')->getData($messageData);
	}
	
	/**
	 * 卖家违约，消息通知卖家
	 * @param string $domain
	 * @param date $nowTime
	 * @param int $id
	 * @param int $enameid
	 * @return boolean
	 */
	public static function sendAuctionSellerrefuseBuyer($domain, $nowTime, $id, $enameid)
	{
		$messageData = array('domainName'=> $domain,'enameid'=> $enameid,'nowTime'=> $nowTime,'id'=> $id);
		return self::getDcObj('user/sendAuctionSellerrefuseBuyer')->getData($messageData);
	}
	
	/**
	 * 获取操作保护信息
	 * @param int $enameId
	 * @param string $identify
	 */
	public  function getOperateData($enameId, $identify)
	{
		$data = array('enameId'=> $enameId,'identify'=> $identify);
		return self::getDcObj('user/getOperateData')->getPostData($data);
	}
	
	
	/**
	 * 走数据中心获取用户的详细信息
	 *
	 * @param int 用户id
	 * @return object
	 */
	public function getEmailByEnameId($enameid)
	{
		$param = array();
		$param['enameId'] = $enameid;
		$rs=self::getDcObj('interfaces/getEmailByEnameId')->getData($param);
		if($rs->code==100000)
		{
			return $rs->msg;
		}
		return false;
	}
}