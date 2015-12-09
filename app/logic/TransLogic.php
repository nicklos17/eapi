<?php
class TransLogic extends LogicBase {

    /**
     * 根据交易id返回交易信息
     * @params $transId int
     *
     */
    public function getTransById($transId) {
    	$taoModel = new NewTaoModel();
    	$res = $taoModel->getTaoRow($transId);
        return $res ?: false;
    }

    /**
     * 根据交易信息判断买卖家是否同一个，价格是否一致
     * @params $transId int 交易id
     * @params $buyId int 买家id
     * @params $curId int 当前页面显示的价格
     *
     */
    public function checkTransInfo($transId, $buyId, $curId) {
    	$taoModel = new NewTaoModel();
    	$res = $taoModel->getTaoRow($transId);
    	$msg = '';
    	if($res && $res->t_status == 1){
    		if($res->t_enameId == $buyId){
    			$msg = \core\Config::item('fixedBuy')->sameUser;
    		}elseif($res->t_now_price != $curId){
    			$msg = \core\Config::item('fixedBuy')->invalidPrice;
    		}
    	}else{
    		$msg = \core\Config::item('fixedBuy')->notExists;
    	}

    	return $msg ? array('flag' => false, 'msg' => $msg) : array('flag' => true, 'msg' => $res);
    }

    /**
     * 创建冻结订单
     * @params $domain string 域名
     * @params $buyId int 买家id
     * @params $money int 冻结金额
     *
     *
     */
    public function freezeMoney($domain, $buyId, $money) {
        return (new \finance\Orders($buyId))->addOrder($domain, \core\Config::item('base : finance')->type->bondAuction, $money);
    }

    /**
     * 解冻保证金
     * @param  [type] $enameId [description]
     * @param  [type] $orderId [description]
     * @return [type]          [description]
     */
    public function unfreezeMoney($enameId, $orderId) {
        if (!(new \finance\Orders($enameId))->cancelOrder($orderId)) // 解冻失败，写入日志
            $this->sendMsgNotice('TransLogic::unfreezeMoney', $enameId, '解冻保证金', $orderId);
    }

    /**
     * calculate break contract time
     * @param  enum $transType  trans type
     * @param  enum $domainType domain type
     * @return unix_timestamp             break contract time
     */
    private function calBreTime($transType, $domainType)
    {
        $tType = \core\Config::item('transType');
        $dType = \core\Config::item('inEname');
        switch ($transType) {
            case $tType['yikoujia']:
                switch ($domainType) {
                    case $dType['inEname']:
                        return $_SERVER['REQUEST_TIME'];
                        break;
                    case $dType['notInEname']:
                        return $_SERVER['REQUEST_TIME'] + \core\Config::item('breTime')['notInEname'];
                        break;
                    default:
                        return false;
                        break;
                }
                break;
            /**
             * 后面还会有竞价的情况
             */
            default:
                return false;
                break;
        }
    }

    /**
     * calculate trans status
     * @param  enum $transType  trans type
     * @param  enum $domainType domain type
     * @return int             trans status code
     */
    private function calSta($transType, $domainType)
    {
        $tType = \core\Config::item('transType');
        $dType = \core\Config::item('inEname');
        $transStaCode = \core\Config::item('transStaCode');
        switch ($transType) {
            case $tType['yikoujia']:
                switch ($domainType) {
                    case $dType['inEname']:
                        return $transStaCode['tranSuc'];
                        break;
                    case $dType['notInEname']:
                        return $transStaCode['buyHasCon'];
                        break;
                    default:
                        return false;
                        break;
                }
                break;
            /**
             * 后面还会有竞价的情况
             */
            default:
                return false;
                break;
        }
    }

    /**
     * 更新交易记录的状态和买家以及卖家的违约截止时间，买家id，买家昵称，交易结束时间，最后更新时间
     * @params $transId int 交易id
     * @params $transType int 交易类型
     * @params $domainType int 域名类型 我司或者非我司
     * @params $buyerId int 买家id
     * @params $buyerNick string 买家昵称
     * @params $buyerIp string 买家IP
     *
     * @Notice 根据交易类型判断买家和买家的违约截止时间
     *          根据交易类型和域名类型更新交易状态值
     *
     */
    public function updateTransInfo($transId, $transType, $domainType, $buyerId, $buyerNick, $buyerIp, $orderId) {
        // 处理买家和卖家的违约时间可以根据交易类型拆分成不同内部函数处理
        // 处理交易状态也可以根据交易类型拆分成不同的内部函数处理判断
        $newTao = new NewTaoModel();
        $tType = \core\Config::item('transType');
        $update = array();
        /**
         * 选择update字段
         */
        if ($breTime = $this->calBreTime($transType, $domainType)) {
            $update['t_seller_end'] = $breTime;
        }
        if ($traSta = $this->calSta($transType, $domainType)) {
            $update['t_status'] = $traSta;
        }
        $update['t_buyer'] = $buyerId;
        if (!empty($buyerNick)) {
            $update['t_nickname'] = $buyerNick;
        }
        $update['t_buyer_ip'] = $buyerIp;
        $update['t_complate_time'] = $update['t_last_time'] = $_SERVER['REQUEST_TIME'];
        $update['t_order_id'] = $orderId;
        
        if ($newTao->updateTrans($update, array('t_id' => $transId))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 异步处理交易step5后面的操作
     * @params $transInfo array 包含交易的信息和买家卖家的相关信息
     * @desc 该函数要根据域名信息里面的我司和非我司信息进行分开操作
     *
     */
    public function asyncDeal($transInfo) {
        $goServ = new \core\driver\GoServer();
        if ($transInfo['t_is_our'] == 1) {
            // 我司
            // 1、调用接口解锁和Push域名
            $goServ->call($transInfo['t_id'], 'TransLogic::unLockAndPush', array($transInfo['t_dn'], $transInfo['t_enameId'], $transInfo['t_buyer']));

            // 2、调用接口确认冻结财务订单
            $goServ->call($transInfo['t_id'], 'TransLogic::confirmFinance', array($transInfo['financeId'], $transInfo['t_buyer']));

            // 3、将该交易记录插入到new_trans_history表
            unset($transInfo['financeId']);
            $goServ->call($transInfo['t_id'], 'TransLogic::copyToHistory', array($transInfo, \core\Config::item('transStaCode')->tranSuc));

            // 4、写入待评价表
            $goServ->call($transInfo['t_id'], 'TransLogic::addToComment', array($transInfo));

            // 5、更新其他用户关注表的交易结束时间，买家信息
            $goServ->call($transInfo['t_id'], 'TransLogic::updateWatchInfo', array($transInfo['t_id'], $transInfo['t_now_price'], $transInfo['buyerNick']));

            // 6、发送通知买家邮件和站内信
            $goServ->call($transInfo['t_id'], 'TransLogic::noticeBuyer', array($transInfo['t_enameId'], $transInfo['t_dn'], $transInfo['t_now_price'], $transInfo['t_id']));

            // 7、根据卖家设置，通知卖家
            $goServ->call($transInfo['t_id'], 'TransLogic::noticeSellerIsOk', array($transInfo['t_enameId'], $transInfo['t_dn'], $transInfo['t_now_price'], $transInfo['t_id']));
        } else {
            // 非我司
            // 1、发送通知邮件、发送站内信、短信通知卖家
            $goServ->call($transInfo['t_id'], 'TransLogic::noticeSellerAction', array());

            // 4、复制该交易id的记录到new_trans_result表
            unset($transInfo['financeId']);
            unset($transInfo['buyerNick']);
            $goServ->call($transInfo['t_id'], 'TransLogic::copyToResult', array($transInfo, \core\Config::item('transStaCode')->buyHasCon));
        }

        $goServ->asyncSend();
    }

    /**
     * 解锁和push域名
     * @params $domain string 域名
     * @params $sellId int 卖家id
     * @params $buyId int 买家id
     *
     */
    public function unLockAndPush($domain, $sellId, $buyId) {
        $rs = array();
        if($rs['flag'] = (new \common\Domain())->PushDomain($sellId, $buyerId, $domain)) {
            $rs['msg'] = '购买成功，域名过户成功';
        } else {
            $rs['msg'] = '购买成功，域名过户失败';
        }
        return $rs;
    }

    /**
     * 确认冻结的财务订单
     * @params $orderId int 财务的订单id
     * @params $buyId int 买家id
     *
     */
    public function confirmFinance($orderId, $buyId) {
        return (new \finance\Orders($buyId))->confirmOrder($orderId);
    }

    /**
     *  将原来交易信息写入到new_trans_history表
     *  NOTICE： 这步骤处理必须修改$transInfo里面的状态字段为交易成功的状态值
     * @params $transInfo array 为交易记录完整信息
     * @param $status int [修改交易状态]
     * @param $finishTime int [交易完成时间]
     * @return int|boolean [lastid or false]
     */
    public function copyToHistory($transInfo, $status, $finishTime = false) {
        $transInfo['t_status'] = $status;
        $transInfo['t_last_time'] = $transInfo['t_complate_time'] = !$finishTime ? $_SERVER['REQUEST_TIME'] : $finishTime;
        $historyModel = new NewTransHistoryModel();
        $res = $historyModel->setTransHistory($transInfo);
        if(!$res)
        {
            \core\Logger::write("domain.log",
                array(__METHOD__,"域名{$transInfo['t_dn']}插入到交易历史表new_trans_history失败,发布时间:". date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])));
        }

        return $res;
    }

    /**
     * 增加待评论记录
     * @params $taoData array 为交易记录信息
     *
     */
	public function addToComment($taoData) {
		//$taoModel = new NewTaoModel();
		//$taoData = $taoModel->getTaoRow($transId, array('*'));
		$flag = false;
		if($taoData){
			$data = array(
					'Seller' => $taoData['t_enameId'],
					'Buyer' => $taoData['t_buyer'],
					'NickName' => $taoData['t_nickname'],
					'BuyerRateLevel' => 3,
					'SellerRateLevel' => 3,
					'AuditListId' => $taoData['t_id'],
					'DomainName' => $taoData['t_dn'],
					'Price' => $taoData['t_now_price'],
					'CreateDate' => date("Y-m-d H:i:s"),
			);
			$transCustomerShopRate = new TransCustomerShopRateModel();
			$res = $transCustomerShopRate->insert($data);
			if($res){
				$flag = true;
			}else{
				$this->sendMsgNotice('TransLogic::addToComment', $taoData['t_enameId'], '增加待评论记录', $taoData['t_id']);
			}
		}

		return $flag;
	}

    /**
     * 更新用户关注域名的记录，写入交易结束时间和买家信息
     * @param $auditListid int [交易id]
     * @param $price string [成交价格]
     * @param $buyer string [买家昵称]
     * @param $endTime int [交易结束时间]
     *
     */
    public function updateWatchInfo($auditListid, $price, $buyer = false, $endTime = false) {
        $data = array(
                'BidPrice' => $price,
                'FinishDate' => date('Y-m-d H:i:s', $endTime ? $endTime : $_SERVER['REQUEST_TIME'])
            );
        if($buyer)
            $data['Buyer'] = $buyer;

        $favoriteModel = new FavoriteModel();
        $res = $favoriteModel->updateFavoriteInfo($data, array('AuditListid' => $auditListid));
        if(!$res)
        {
            \core\Logger::write("domain.log",
                array(__METHOD__,"域名{$domain}更新到域名收藏表trans_domain_favorite失败,发布时间:". date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])));
        }

        return $res;
    }

    /**
     * 发送邮件和站内短信给买家，只要调用接口即可
     *
     *
     */
    public function noticeBuyer($enameId, $domain, $price, $transId){
        if($rs = (new \common\Message)->sendBuyNowBuyer($enameId, $domain, $price, $transId))
            return $rs;
        else
            $this->sendMsgNotice('TransLogic::noticeBuyer', $enameId, '发送邮件和站内短信给买家', $transId);
    }

    /**
     * 根据卖家设置通知卖家，首先要调用dc接口获取卖家设置然后调用通知接口
     *
     *@param $enameid 卖家id
     */
    public function noticeSellerIsOk($enameid, $domain, $price, $transId){

        $sms = (new \common\Users())->noticeSellerIsOk($enameid, \core\Config::item('noticeCode')->toArray()[0]);
        //通知是否有抛异常
        return (new \common\Message)->sendBuyNowSeller($enameId, $domain, $price, $transId, $sms);

        /*
         * 是否需要记录日志
         *if($rs['msg']['result'] !== true)
         *{
         *    \core\Logger::write("DOMAIN_LOG",
         *        array(__METHOD__,'域名 ' . $domain . ' 设置状态为 ' . $status . ' 失败,msg信息为：' . $rs['msg']['msg']));
         *}
         */
    }

    /**
     * 发送站内信、邮件、短信通知卖家转入域名，只需要调用接口
     *
     *
     *
     */
    public function noticeSellerAction() {
        return array('msg' => '您的域名不在我司,请尽快转移');
    }

    /**
     * 复制非我司域名的交易信息到new_trans_result表，状态需要修改
     * @param $transInfo array [交易信息]
     * @param $status int [修改交易状态]
     * @param $finishTime int [交易完成时间]
     * @return int|boolean [lastid or false]
     */
    public function copyToResult($transInfo, $status, $finishTime = false) {
        $transInfo['t_status'] = $status;
        $transInfo['t_last_time'] = $transInfo['t_complate_time'] = !$finishTime ? $_SERVER['REQUEST_TIME'] : $finishTime;
        $resModel = new NewTransResultModel();
        $res = $resModel->setTransResult($transInfo);
        if(!$res)
        {
            \core\Logger::write("domain.log",
                array(__METHOD__,"域名{$domain}插入到交易结果表new_trans_result失败,发布时间:". date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])));
        }

        return $res;
    }

    /**
    * 根据tid删除数据
    * return effected_rows
    */
    public function delByTid($tid)
    {
        $taoModel = new NewTaoModel();
        return $taoModel->delByTid($tid);
    }
}
