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
    public function getDescAndHot($domain) {
        $this->query("SELECT t_desc, t_hot, t_enameId FROM {$this->table} WHERE t_dn = :domain", array('domain' => $domain));
        return $this->getRow();
    }

    /**
    * 交易结果入库
    * @param $transInfo [交易信息]
    * @return int|boolean [lastid or false]
    */
    public function setTransResult($transInfo) {
        return $this->insert($transInfo);
    }
}