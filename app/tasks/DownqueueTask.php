<?php
class DownqueueTask {

    /**
     * 从new_tao表中找出状态为15的交易，请求dc接口解锁域名，解锁成功后，将该记录复制到new_trans_history表，同时从new_tao表删除该记录
     *
     *
     *
     */
    public function downAction() {
    	$taoModel = new NewTaoModel();
    	$taoData = $taoModel->getDataByStatus(15);
    	if(!empty($taoData))
    	{
            $dLogic = new DomainLogic();
    		foreach ($taoData as $v)
    		{
    			$data[$v->t_id] = (array)$v;
                $res = $dLogic->setDomainStatus(\core\Config::item('doPubSta')->toArray()['down'],1);
                if($res !== true)
                {
                    unset($data[$v->t_id]);
                    echo "解锁new_tao状态为15的域名id{$v->t_id}失败,解锁时间:". date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . "\n";
                }
    		}

            if(!empty($data))
            {
	            $data = array_values($data);
                $TransLogic = new TransLogic();
	            //将信息更新到历史表中，并在new_tao中删除
	            foreach ($data as $v)
	    		{
                    $copyRes = $TransLogic->copyToHistory($v, $v['t_status']);
                    $delRes = $TransLogic->delByTid($v['t_id']);
                    if(!$copyRes)
                        echo "域名id{$v['t_id']}解锁成功,但在插入new_trans_history时发生错误.时间:". date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . "\n";

                    if(!$delRes)
                        echo "域名id{$v['t_id']}解锁成功,但在从new_tao删除该条记录时发生错误.时间:". date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . "\n";
	    		}
	        }
    	}
    }
}
