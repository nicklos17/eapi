<?php
class BreaktransTask {
    /**
     * 从new_trans_result表里面获取交易结束时间过期的并且状态不为成功或者失败的记录
     * 数据库中有一个买家最后违约时间和卖家最后违约时间，根据这两个时间判断谁先违约进行后续处理
     * 违约处理：如果是买家违约，则保证金卖家和平台各得一半，如果是卖家违约，则保证金买家和平台各得一半
     *
     *
     *
     */
    public function checkTransBreak() {

    }

    /**
     * 确认一口价交易非我司域名是否违约
     * 一口价非我司交易 卖家时间到了 如果用户没有点违约，也没有确认的情况下，系统执行自动确认操作，如果域名已经在我司用户ID下，更新交易状态为“卖家已经确认”，
     * 当买家时间到了，如果用户没有违约也没有确认，并且金额足够，自动完成交易，如果金额不够执行违约流程
     *
     */
    public function checkFixed() {

    }

    public function checkBidCom() {

    }

    public function checkBidNotCom() {

    }
}
