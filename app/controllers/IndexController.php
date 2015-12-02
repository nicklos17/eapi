<?php

class IndexController extends ControllerBase
{

	function __construct($di)
	{
		parent::__construct($di);
	}
	
	public function indexAction()
	{
		echo time();
       	var_dump('some');
	}

	public function showAction($year, $postTitle)
	{
		$yearT = $this->dispatcher->getParam('year');
		$postTitleT = $this->dispatcher->getParam('postTitle');
		// var_dump($year);
		var_dump($yearT);
		// var_dump($postTitle);
		var_dump($postTitleT);
	}
}
