<?php
namespace finance;

class Orders
{
	private $enameId;

	function __construct($enameId)
	{
		$this->enameId = $enameId;
	}

	/**
	 * 发送数据到DC
	 *
	 * @param string $method
	 * @param array $data
	 */
	private function sendToDC($method, $data, $type = 'get')
	{
		$dc = new \core\DataCenter($method);
		if('get' == $type)
		{
			return $dc->getData($data);
		}
		return $dc->getPostData($data);
	}

	/**
	 * 获取用户还差多少钱能完成交易
	 *
	 * @param int $money
	 */
	public function unEnoughPay($money)
	{
		$param = array();
		$param['enameId'] = $this->enameId;
		$param['money'] = $money;
		return $this->sendToDC('finance/unEnoughPay', $param, 'post');
	}

	/**
	 * 创建订单
	 *
	 * @param string $domain
	 * @param int $type
	 * @param int $price
	 * @param string $linkEnameId
	 * @param string $oldOrderId
	 * @param string $remark
	 * @param string $remarkHide
	 * @return int 订单ID
	 */
	public function addOrder($domain, $type, $price, $linkEnameId = '', $oldOrderId = '', $remark = '', $remarkHide = '')
	{
		$param = array('domain'=> $domain,'type'=> $type,'price'=> $price,'enameid'=> $this->enameId);
		if($linkEnameId != '')
		{
			$param['linkEnameId'] = $linkEnameId;
		}
		if($oldOrderId != '')
		{
			$param['oldOrderId'] = $oldOrderId;
		}
		if($remark != '')
		{
			$param['remark'] = $remark;
		}
		if($remarkHide !== '')
		{
			$param['remarkHide'] = $remarkHide;
		}
		return $this->sendToDC('finance/addorder',$param)['msg'];
	}

	/**
	 * 解冻资金
	 *
	 * @param int $orderId
	 * @return boolean
	 * @author zhangsc
	 */
	public function cancelOrder($orderId, $enameid = null)
	{
		if(!$enameid)
		{
			$enameid = $this->enameId;
		}
		$rs = $this->sendToDC('finance/addorder', array('sellerorderid'=> $orderId,'enameid'=> $enameid));
		if($rs['flag'] && $rs['msg'])
		{
			return true;
		}
		return false;
	}

	/**
	 * 确认订单
	 *
	 * @param int $orderid
	 * @param int $type
	 * @param int $seller 卖家
	 * @return array
	 */
	public function confirmOrder($orderid, $type = '', $seller = '')
	{
		$param = array('enameid'=> $this->enameId,'orderid'=> $orderid,'type'=> $type,'seller'=> $seller);
		$rs = $this->sendToDC('finance/confirmOrder', $param);
		if($rs['flag'] && $rs['msg'])
		{
			return true;
		}
		return false;
	}

	/**
	 * 买家确认交易 生成保证金订单
	 */
	public function transBuyerOrder($seller = '', $enameId, $domainname, $financeType, $transmoney, $oldOrderId = '', 
		$transType = '')
	{
		$seller && $param['seller'] = $seller;
		$param['buyer'] = $enameId;
		$param['domain'] = $domainname;
		$param['type'] = $financeType;
		$oldOrderId && $param['oldOrderId'] = $oldOrderId;
		$param['price'] = $transmoney;
		$transType && $param['transType'] = $transType; // 交易类型
		$rs = $this->sendToDC('finance/transBuyerOrder', $param, 'post');
		if($rs['code'] == 100000)
		{
			return $rs['msg'];
		}
		if($rs['code'] == 410070)
		{
			throw new \Exception('系统繁忙，请重试');
		}
		return false;
	}

	/**
	 * 添加支付订单
	 *
	 * @param int $enameId
	 * @param int $transId
	 * @param string $domain
	 * @param int $type
	 * @param int $price
	 * @return boolean
	 */
	public function addPayOrder($enameId, $transId, $domain, $type, $price)
	{
		$payOrder = $this->sendToDC('finance/addPayOrder', 
			array('enameid'=> $enameId,'transId'=> $transId,'domain'=> $domain,'type'=> $type,'price'=> $price));
		if($payOrder['code'] == 100000)
		{
			return $payOrder['msg'];
		}
		return false;
	}

	/**
	 * 获取用户的可用余额
	 *
	 * @return number
	 */
	public function getUserBalance()
	{
		$userFinance = $this->sendToDC('finance/getuserfinance', array('enameid'=> $this->enameid))['msg'];
		$balance = $userFinance->UnWithdrawalMoney + $userFinance->WithdrawalMoney + $userFinance->MarginMoney; // 可用余额
		return $balance;
	}

	/**
	 * 买家同意报价的时候 生成保证金
	 *
	 * @param int $seller
	 * @param int $buyer
	 * @param string $domain
	 * @param int $type
	 * @param int $price
	 * @return int
	 */
	public function createBuyerOrder($seller, $buyer, $domain, $type, $price)
	{
		$param = array();
		$param['seller'] = $seller;
		$param['buyer'] = $buyer;
		$param['domain'] = $domain;
		$param['price'] = $price;
		$param['type'] = $type; // 113 询价卖家保证金 114 询价保证金 买家 115 询价交易 116 卖家违约
		return $this->sendToDC('finance/createBuyerOrder', $param, 'post')['msg'];
	}

	/**
	 * 确认卖家的询价订单
	 *
	 * @param int $seller
	 * @param int $sellerOrderId
	 * @param int $moneyType
	 * @return boolean
	 */
	public function confirmInquiryOrder($seller, $sellerOrderId, $moneyType = '')
	{
		$param = array();
		$param['seller'] = $seller;
		$param['orderId'] = $sellerOrderId;
		$param['moneyType'] = $moneyType;
		return $this->sendToDC('finance/confirmInquiryOrder', $param, 'post')['msg'];
	}

	/**
	 * 取消询价订单
	 *
	 * @param int $seller
	 * @param int $sellerOrderId
	 * @return boolan
	 */
	public function cancelInquiryOrder($seller, $sellerOrderId)
	{
		$param = array();
		$param['seller'] = $seller;
		$param['orderId'] = (int)$sellerOrderId;
		return $this->sendToDC('finance/cancelInquiryOrder', $param, 'post')['msg'];
	}

	/**
	 * 询价 域名不在我司,卖家 回复报价生保证金订单
	 *
	 * @param int $seller
	 * @param int $buyer
	 * @param string $domain
	 * @return int orderId 保证金订单号
	 */
	public function createInquiryOrder($seller, $buyer, $domain, $type, $oldOrderId = '')
	{
		$param = array();
		$param['seller'] = $seller;
		$param['buyer'] = $buyer;
		$param['domain'] = $domain;
		$param['type'] = $type; // 113 询价卖家保证金 114 询价保证金 买家 115 询价交易 116 卖家违约
		$param['oldOrderId'] = $oldOrderId;
		return $this->sendToDC('finance/createInquiryOrder', $param, 'post')['msg'];
	}
}