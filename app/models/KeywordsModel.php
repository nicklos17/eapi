<?php

class KeywordsModel extends \core\ModelBase
{
    protected $table='trans_keywords';

	function __construct()
	{
		parent::__construct();
	}

	public function getKeywords()
	{
        $this->query("SELECT `word` FROM {$this->table}");
        return  $this->getAll();
	}
}
