<?php

class Test extends \core\ModelBase
{
	protected $table="e_vas";
	
	function __construct()
	{
		parent::__construct();
	}
	
	public function testsql()
	{
		$t = $this->select("select * from e_domains where domainid = :domainid", array(':domainid'=>5742318));
		var_dump($t);
		$s=$this->exec("insert into ".$this->table.
			"(enameid,VasbusinessId,Createtime)values(:enameid,:VasbusinessId,:Createtime)", 
			array(':enameid'=>387901,':VasbusinessId'=>1,':Createtime'=>time()));
		var_dump($s);
		var_dump($this->getError());
	}
}