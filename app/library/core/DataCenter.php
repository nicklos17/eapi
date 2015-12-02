<?php
namespace core;

class DataCenter
{

	private $curl;

	private $user;

	private $method;

	function __construct($method)
	{
		$dcUrl = Config::item('dcInfo');
		$this->ip = $dcUrl['ip'];
		$this->method = $method;
		$this->user = array('user'=> $dcUrl['user'],'ip'=> \common\Client::getIp());
	}

	/**
	 * get方式访问获取数据
	 */
	public function getData($data = array())
	{
		$this->curl = new AppCurl($this->ip . '/trans/' . $this->method);
		return $this->setResult($this->curl->get(array_merge($this->user, $data)));
	}

	/**
	 * post方式传递数据
	 */
	public function getPostData($data = array())
	{
		$this->curl = new AppCurl($this->ip . '/trans/' . $this->method . '?' . http_build_query($this->user));
		return $this->setResult($this->curl->post($data));
	}

	/**
	 * 数据格式化
	 */
	private function setResult($result)
	{
		return ! empty($result)? json_decode($result, true): false;
	}
}