<?php
/**
 * 历史交易
 */
class NewTransHistoryModel extends \core\ModelBase
{
    protected $table="new_trans_history";
    
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
        return $this->affectRow();
    }

    /**
    * 历史交易记录入库
    * @param $transInfo [交易信息]
    * @return int|boolean [lastid or false]
    */
    public function setTransHistory($transInfo) {
        try {
            $this->insert($transInfo);
            return $this->affectRow();
        } catch (Exception $e) {
            return false;
        }
    }
}