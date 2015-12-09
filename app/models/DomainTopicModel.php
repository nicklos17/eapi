<?php

class DomainTopicModel extends \core\ModelBase
{
    protected $table='trans_domain_topic';

	function __construct()
	{
		parent::__construct();
	}

	/**
	* 获取专题信息
	* @param $id int[专题id]
	*/
	public function getTopic($id)
	{
        $this->query("SELECT TopicId FROM {$this->table} WHERE TopicId = :id", array('id' => $id));
        return $this->getRow();
	}
}
