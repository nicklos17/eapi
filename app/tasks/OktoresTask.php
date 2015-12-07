<?php
class OktoresTask {
    /**
     * 定时处理竞价结束的数据处理
     *
     *
     */
    public function checkAcuctionStatus() {
        // step1 取出已过交易结束时间，类型为竞价的并且有买家id的交易记录
        // step2 将step1的记录修改状态为等待买卖双方确认的状态，存到new_trans_result表
        // step3 删除new_tao里面该条交易记录
    }

    /**
     * 获取new_trans_result表里面状态为成功或者失败的记录
     * 将取出的记录原封不动的存入new_trans_history表
     * 存入成功后删除new_trans_result表里面对应的记录
     *
     */
    public function checkResultStatus() {

    }
}
