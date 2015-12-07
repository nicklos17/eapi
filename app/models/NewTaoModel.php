<?php

class NewTaoModel extends \core\ModelBase
{
	protected $table='new_tao';
	
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * 将发布的域名信息入库
	 * @param $data array [域名信息]
	 * @return int [last inserted id]
	 *
	 */
	public function setDoaminInfo($data)
	{
		return $this->insert($data);
	}

	/**
     * 判断准备发布一口价的域名是否已经在交易中
     * @params $domain string
     * @desc 交易表中域名状态为1，2，3，9的域名为交易中的域名
     *
     */
    public function isTrans($domain) {
        $this->query("SELECT t_dn FROM {$this->table} WHERE t_dn = :domain AND t_status IN(1, 2, 3, 9)", array(':domain' => $domain));
        return ($this->getRow())? true: false;
    }
    
    /**
     * 获取一条交易记录
     * @params $transId int 交易id
     *
     */
    public function getTaoRow($transId) {
    	//$select = implode(',', $selectData);
    	$select = 't_id,t_dn,t_body,t_status,t_type,t_topic_type,t_topic,t_enameId,t_buyer,t_start_price,t_nickname,t_now_price,t_agent_price,t_create_time,t_start_time,t_end_time,t_last_time,t_tld,t_len,t_desc,t_count,t_money_type,t_ip,t_buyer_ip,t_is_our,t_exp_time,t_class_name,t_two_class,t_three_class,t_seller_order,t_complate_time,t_order_id,t_people,t_hot,t_admin_hot,t_seller_end,t_buyer_end';
    	$this->query("SELECT {$select} FROM {$this->table} WHERE t_id = :t_id", array(':t_id' => $transId));
    	return $this->getRow();
    }

    /**
     * 更新交易信息
     * @param  array $update 更新的字段
     * @param  array $where  条件
     * @return bool         ture or false
     */
    public function updateTrans($update, $where)
    {
        $this->update($update, $where);
        if (1 === $this->affectRow()) {  
            return true;
        } else {
            return false;
        }
    }

    /**
     * 根据条件从ES里面删除的数据
     * @param int $startTime 上次处理到的t_last_time
     * @param int $limit
     * @return array
     */
    public function getListForDeleteEs($startTime,$limit)
    {
    	$sql="SELECT t_id,t_last_time FROM {$this->table} WHERE t_last_time >= :start and t_status!=1 and t_status!=3 ORDER BY t_last_time ASC LIMIT :limit";
   		$this->query($sql,array(':start'=>$startTime,':limit'=>$limit));
   		return $this->getAll();
    }
}