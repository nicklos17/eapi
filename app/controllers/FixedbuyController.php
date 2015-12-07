<?php

class FixedbuyController extends ControllerBase
{
    private $logic;
    function __construct($di) {
        parent::__construct($di);
        $this->logic = new TransLogic();
    }

    /**
     * 返回对应交易id的交易信息
     * @params $transId int 交易id
     *
     */
    public function getTransInfo($transId) {
        $res = $this->logic->getTransById($transId);
        return $res ?: false;
    }

    /**
     * [fixedBuy description]
     * @param  int $uId 买家id
     * @param  bool $isUseNickName 买家是否选择使用昵称
     * @param  string $buyerIp  买家ip
     * @param  array $transIds  交易id
     * 
     * @return  array('accessTrans' => $accessTrans, 'denyTrans' => $denyTrans);
     */
    public function fixedBuy($uId, $transIds, $isUseNickName = false, $buyerIp = '') {
        try {
            $goServ = new \core\driver\GoServer();
            // 以下步骤使用goServer并行处理
            //
            // step 调用logic方法，一次判断下面step1,step2,step3返回结果
            // step1 获取该交易id的信息，不存在则该交易结束

            // step2 判断交易里买家和卖家是否同一个

            // step3 判断交易里的当前价格和提交上来的价格是否一致
            $accessTrans = $denyTrans = array();
            foreach ($transIds as $transId => $curId) {
                $goServ->call($transId, 'TransLogic::checkTransInfo', array($transId, $uId, $curId));
                $res = $goServ->send()[$transId]['TransLogic::checkTransInfo'];

                if (isset($res['goError']) || !$res['flag']) {
                    $denyTrans[$transId] = isset($res['goError']) ? $res['goError'] : $res['msg'];
                } else {
                    $accessTrans[$transId] = $res['msg'];
                }
            }
            // step4 创建冻结订单
            foreach ($accessTrans as $transId => $info) {
                $goServ->call($transId, 'TransLogic::freezeMoney', array($info['t_dn'], $uId, $info['t_now_price']));
                $res = $goServ->send()[$transId]['TransLogic::freezeMoney'];

                if (isset($res['goError']) || !$res) {
                    $denyTrans[$transId] = isset($res['goError']) ? $res['goError'] : '您账户余额不足一口价标价';
                    unset($accessTrans[$transId]);
                } else {
                    // 冻结成功，记录财务订单id
                    $info['financeId'] = $res;
                    $accessTrans[$transId] = $info;
                }
            }
            
            // step5 根据交易里的我司和非我司标志更新交易记录状态，我司域名状态直接更新为14，非我司更新为等待卖家确认
            //       同时非我司的交易记录要更新买家违约时间和卖家违约时间
            //       若step5失败，则发起解冻step4里面的冻结订单
            foreach ($accessTrans as $transId => $info) {
                $buyerNick = $isUseNickName ? \common\common::getNickname($uId, $transId) : false;

                $goServ->call($transId, 'TransLogic::updateTransInfo', array($transId, $info['t_type'], $info['t_is_our'], $uId, $buyerNick, $buyerIp));
                $res = $goServ->send()[$transId]['TransLogic::updateTransInfo'];

                if (isset($res['goError']) || !$res) {  // 更新信息失败，解冻订单
                    $goServ->call($transId, 'TransLogic::unfreezeMoney', array($info['t_enameId'], $info['financeId']));
                    $goServ->asyncSend()[$transId]['TransLogic::unfreezeMoney'];
                    
                    $denyTrans[$transId] = '交易记录更新失败';
                    unset($accessTrans[$transId]);
                } else {
                    // step6 我司域名 使用go 异步并行处理下面多个流程
                    //      调用logic里面的asyncDeal处理下面步骤
                    //      1、调用接口解锁和Push域名
                    //      2、调用接口确认冻结财务订单
                    //      3、将该交易记录插入到new_trans_history表
                    //      4、写入待评价表
                    //      5、更新其他用户关注表的交易结束时间，买家信息
                    //      6、发送通知买家邮件和站内信
                    //      7、根据卖家设置，通知卖家
                    //
                    // step6: 非我司域名 使用go异步并行处理下面多个流程
                    //      调用logic里面的asyncDeal处理下面步骤
                    //      1、发送通知邮件、发送站内信、短信通知卖家
                    //      4、复制该交易id的记录到new_trans_result表
                    $goServ->call($transId, 'TransLogic::asyncDeal', array($info, $buyerNick));
                    $goServ->asyncSend()[$transId]['TransLogic::asyncDeal'];
                }
            }

            return array('accessTrans' => $accessTrans, 'denyTrans' => $denyTrans);
        } catch(\Exception $e) {
            \core\Logger::write('FixedbuyController.log', array('出现异常',$e->getMessage(),$e->getFile(),$e->getLine()));
            return false;
        }
    }
}
