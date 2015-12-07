<?php

class FavoriteModel extends \core\ModelBase
{
    protected $table='trans_domain_favorite';

	function __construct()
	{
		parent::__construct();
	}

	/**
	* 更新域名的发布信息
	* @param $updateInfo array [要更新的信息]
	* @param $where array [更新条件]
	*/
	public function updateFavoriteInfo($updateInfo, $where)
	{
		return $this->update($updateInfo, $where);
	}
}
