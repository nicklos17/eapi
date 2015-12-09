<?php

class DemoController extends ControllerBase
{

	function __construct($di)
	{
		parent::__construct($di);
	}

	/**
	 * 请求API.ENAME.COM 方法演示
	 */
	public function apicomAction()
	{
		$apiCom = new \core\ApiCom('domaintrade/getDomainInfo');
		return $apiCom->getPostData(array('domains'=> 'abc.com,aaaa.com'));
	}

	/**
	 * 请求DC的方法演示 批量获取域名状态信息
	 */
	public function dcAction()
	{
		// $dcUrl = 'http://192.168.10.115:800';
		// 见debug.php
		$dataCenter = new \core\DataCenter();
		return $dataCenter->getPostData(array('domains'=> 'abc.com,aaaa.com'));
	}

	public function indexAction()
	{
		$logic = new DomainLogic();
	var_dump($logic->publicDomain(50000, '3399.net', '测试的域名', '1232343435', 1, 200, 3245646567, 2, 2, 0, '127.0.3.1', 283));
	}
}