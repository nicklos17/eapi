<?php
/**
 * 历史交易
 */
class NewTransResultModel extends \core\ModelBase
{
    protected $table="new_trans_result";

    function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取简介和是否推荐
     * @param  [type]  $domain [description]
     * @return boolean         [description]
     */
    public function getDescAndHot($uId, $domain) {
        $this->query("SELECT t_desc, t_hot, t_enameId FROM {$this->table} WHERE t_dn = :domain AND t_enameId = :uId", array('domain' => $domain, 'uId' => $uId));
        return $this->getRow();
    }

    /**
    * 交易结果入库
    * @param $transInfo [交易信息]
    * @return int|boolean [lastid or false]
    */
    public function setTransResult($transInfo) {
        $this->insert($transInfo);
        return $this->affectRow();
    }

    /**
     * 根据状态获取指定数据
     * @param array $condition 只支持and拼接
     * @param string $limit
     * @return \driver\mixed
     */
    public function getListByWhere(array $condition,$limit,$field)
    {
    	$where='';
    	$values = array();
    	list($where,$values) = $this->where($condition);
    	list($limitSql,$values) = $this->limit($limit,$values);
    	$where .= $limitSql;
    	$sql="SELECT {$field} FROM {$this->table} WHERE {$where}";
   		$this->query($sql,$values);
   		return $this->getAll();
    }

    /**
     * 获取成功或失败的记录
     */
    public function getSuccAndFail() {
    	$select = 't_id,t_dn,t_body,t_status,t_type,t_topic_type,t_topic,t_enameId,t_buyer,t_start_price,t_nickname,t_now_price,t_agent_price,t_create_time,t_start_time,t_end_time,t_last_time,t_tld,t_len,t_desc,t_count,t_money_type,t_ip,t_buyer_ip,t_is_our,t_exp_time,t_class_name,t_two_class,t_three_class,t_seller_order,t_complate_time,t_order_id,t_people,t_hot,t_admin_hot,t_seller_end,t_buyer_end';
    	$this->query("SELECT {$select} FROM {$this->table} WHERE t_status IN(6,7,14)");
        return $this->getAll();
    }

    /**
     * 删除成功或失败的记录
     */
	public function delSuccAndFail($t_id) {
		$this->query('delete from '.$this->table.' where t_id = :t_id', array(':t_id' => $t_id));
		return $this->affectRow();
	}
}
