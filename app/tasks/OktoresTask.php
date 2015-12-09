<?php
class OktoresTask extends MainTask {
    /**
     * 定时处理竞价结束的数据处理
     *
     *
     */
    public function checkAcuctionStatusAction() {
        // step1 取出已过交易结束时间，类型为竞价的并且有买家id的交易记录
        $taoModel = new NewTaoModel;
    	$taoData = $taoModel->getExpiredByType(\core\Config::item('isInquiry')->toArray()[1], time());

        // step2 将step1的记录修改状态为等待买卖双方确认的状态，存到new_trans_result表，并更新用户关注表
        $historyModel = new NewTransHistoryModel;
        $transLogic= new TransLogic;
        if(!empty($taoData)){
            foreach($taoData as $data)
            {
                $data = get_object_vars($data);
                $data['t_status'] = 2;

                //存表
                if($historyModel->setTransHistory($data))
                {
                    //更新用户关注表
                    if($transLogic->updateWatchInfo($data['t_id'], $data['t_now_price'], $data['t_nickname'], $endTime = $data['t_end_time']))
                    {
                    // step3 删除new_tao里面该条交易记录
                        if($taoModel->delByTid($data['t_id']))
                            echo 'tid 为' . $data['t_id'] . '操作成功';
                        else
                            echo 'tid 为' . $data['t_id'] . '删除net_tao原表数据失败';
                     }
                     else
                        echo 'tid 为' . $data['t_id'] . '更新用户关注表失败';
                }
                else
                    echo 'tid 为' . $data['t_id'] . '复制到history 失败';
            }
        }
    }

    /**
     * 获取new_trans_result表里面状态为成功或者失败的记录
     * 将取出的记录原封不动的存入new_trans_history表
     * 存入成功后删除new_trans_result表里面对应的记录
     *
     */
    public function checkResultStatus() {
    	$newTransResultModel = new NewTransResultModel();
    	$newTransHistoryModel = new NewTransHistoryModel();
    	$res = $newTransResultModel->getSuccAndFail();
    	if($res){
    		foreach($res as $k => $v){
    			$newTransHistoryModel->begin();
    			$v = (array)$v;
    			$newTransHistoryModel->insert($v);
    			$rs = $newTransHistoryModel->affectRow();
    			if($rs){
    				$del = $newTransResultModel->delSuccAndFail($v['t_id']);
    				if($del){
    					$newTransHistoryModel->commit();
    					echo 't_id为'.$v['t_id'].'的交易记录从result转存history成功' . PHP_EOL;
    				}else{
    					$newTransHistoryModel->rollback();
    					echo 't_id为'.$v['t_id'].'的交易记录从result删除失败' . PHP_EOL;
    				}
    			}else{
    				$newTransHistoryModel->rollback();
    				echo 't_id为'.$v['t_id'].'的交易记录从result写入history失败' . PHP_EOL;
    			}
    	
    		}
    	}
    }
}
