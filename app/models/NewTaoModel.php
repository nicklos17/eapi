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
}