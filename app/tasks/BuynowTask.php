<?php
use \core\Logger;

class BuynowTask
{

	/**
	 * 判断一口价交易卖家是否违约
	 */
	public function IndexAction()
	{
		$limit = 100;
		$condition = array('t_status'=> 4,'t_end_time <'=> time());
		$transResult = new \NewTransResultModel();
		$data = $transResult->getListByWhere($condition, '0,100', '*');
		$domains = new \common\Domain();
		foreach($data as $v)
		{
			$transId = $v['t_id'];
			// 判断域名是否在卖家ID下
			$result = $domains->checkDnInUser($v['t_enameId'], $v['t_dn']);
			// 如果如果存在 则执行“一口价交易流程”
			if($result)
			{
				$lock = $domains->lockDomain($v['t_dn'], 2); // 设置成交易状态
				if(! $lock)
				{
					Logger::write('buynowtask_index', 'lock domain false ' . $v['t_id'] . '-' . $v['t_dn'].'--'.$lock[1]);
					continue;
				}
				$finance = new \finance\Orders($v['t_enameId']);
				if(!$finance->cancelOrder($v['t_seller_order']))//取消卖家保证金订单
				{
					Logger::write('buynowtask_index', 'cancel order false .'.$v['t_seller_order']);
				}
				//PUSH域名
				$domains->PushDomain($v['t_enameId'], $v['t_buyer'],  $v['t_dn']);
				//确认订单 扣掉买家的钱给卖家
				unset($finance);
				$finance = new \finance\Orders($v['t_buyer']);
				$finance->confirmOrder($v['t_order_id'],$v['t_money_type'],$v['t_enameId']);
				//更新关注表
				$transLogic = new \TransLogic();
				$transLogic->updateWatchInfo($v['t_id'], $v['t_now_price']);
				$messageObj = new \common\Message();
				//通知卖家
				$sms = (new \common\Users())->noticeSellerIsOk($v['t_enameId'], 'T204');
				$messageObj->sendBuyNowSeller($v['t_enameId'], $v['t_dn'], $v['t_now_price'], $v['t_id'], $sms);
				//通知买家
				$messageObj->sendBuyNowBuyer($v['t_buyer'], $v['t_dn'], $v['t_now_price'], $v['t_id']);
			}
			else // 如果域名不在卖家ID下
			{
				Logger::write('buynowtask_index', 'lock domain false not at user id ' . $v['t_id'] . '-' . $v['t_dn']);
				$trans = new \trans\Weiyue();
				$trans->main($v);
			}
		}
	}
}