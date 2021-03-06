<?php
class MtohistTask {
    /**
     * 定时从new_tao表拷贝交易表的交易流拍记录和卖家取消的记录到new_trans_history表
     * 拷贝成功后删除原new_tao表对应的原始记录，若删除失败直接输入对应记录id
     *
     *
     */
    public function cpActToHistoryAction() {
        $status = \core\Config::item('transStaCode');
        $status = array($status->failure, $status->sellerCancel);

        $newTaoModel = new NewTaoModel();
        $res = $newTaoModel->getDatasByStatus($status);
        
        $newTransHistoryModel = new NewTransHistoryModel();
        foreach ($res as $value) {
            $ins = $newTransHistoryModel->setTransHistory((array)$value);
            if ($ins === false) echo $value->t_id . PHP_EOL;
        }
    }

    /**
     * 定时从new_tao表删除指定状态的数据
     * 指定状态为：交易成功，等待卖家确认，管理员取消交易
     *
     *
     *
     */
    public function delFromTaoAction() {
        $status = \core\Config::item('transStaCode');
        $status = array($status->tranSuc, $status->buyHasCon, $status->adminCanceled);
        $newTaoModel = new NewTaoModel();
        $res = $newTaoModel->delDataByStatus($status);
        echo "删除{$res}条记录" . PHP_EOL;
    }
}
