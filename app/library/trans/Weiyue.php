<?php
namespace trans;

class Weiyue extends WeiyueBase
{

	/**
	 * 卖家违约流程 
	 * @param array $data 交易结果表全部字段的记录
	 */
	public function main(array $data)
	{
		if(!in_array($data['t_status'], array(2,4)))
		{
			throw new \Exception('交易状态错误不能执行违约操作');
		}
		$dn = $data['t_dn'];
		$money = $data['t_now_price'];
		$buyer = $data['t_buyer'];
		$seller = $data['t_enameId'];
		$sellerOrderId = $data['t_seller_order'];//卖家保证金订单
		$orderId = $this->createOrder($dn, $money, $buyer, $seller, $sellerOrderId);
		if(!$orderId)
		{
			return false;//违约失败
		}
		// 设置卖家违约
		$newTao = new \NewTaoModel();
		$newTao->updateTrans(array("t_status"=>7), array("t_id"=>$data['t_id']));
		// 如果是我司域名 解锁域名
		if(1 == $data['t_is_out'])
		{
			$domains = new \common\Domain();
			$domains->lockDomain($data['t_dn'], 1);
		}
		// 如果买家已经确认 取消买家支付订单 一口价买家肯定是确认状态
		$finance = new \finance\Orders($buyer);
		if(!$finance->cancelOrder($data['t_order_id']))//买家确认购买创建的订单
		{
			//log
		}
		// 如果买家未确认 取消买家保证金订单 一口价没有这个 竞价才有这个
		
		// 确认违约订单
		// $moneyType['UNWITHDRAWAL']
		$finance = new \finance\Orders($seller);
		if($finance->confirmOrder($orderid,2,$buyer))
		{
			//log
		}
		// 违约是否要评论
		
		//移动交易记录 到历史表
		$newHistory = new \NewTransHistoryModel();
		if(!$newHistory->setTransHistory($data))
		{
			//log
		}
		// 通知卖家
		$nowTime = date('Y-m-d H:i:s');
		$message = new \common\Message();
		$message->sendAuctionBuyerrefuseSeller($dn, $nowTime, $id, $seller);
		// 通知买家
		$message->sendAuctionBuyerrefuseBuyer($dn, $nowTime, $id, $buyer);
		return true;
	}

	private function createOrder($dn, $money, $buyer, $seller, $sellerOrderId)
	{
		$weiyuejinType = 109;
		$finance = new \finance\Orders($seller);
		return $finance->addOrder($dn, $weiyuejinType, $this->createSellerAbandonMoney($money), $buyer, $sellerOrderId);
	}
}