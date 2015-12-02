<?php
namespace core;

class ApiCom
{

	private $curl;

	private $user;

	function __construct($method)
	{
		$dcUrl = Config::item('apiInfo');
		$this->curl = new AppCurl($dcUrl['ip'] . '/' . $method);
		$this->user = array('user'=> $dcUrl['user'],'appkey'=> $dcUrl['key']);
	}

	/**
	 * get方式访问获取数据
	 */
	public function getData($data = array())
	{
		return $this->setResult($this->curl->get(array_merge($this->user, $data)));
	}

	/**
	 * post方式传递数据
	 */
	public function getPostData($data = array())
	{
		return $this->setResult($this->curl->post(array_merge($this->user, $data)));
	}

	/**
	 * 如果请求成功，返回对应的消息，如果请求失败或者返回的CODE不是10000 直接抛错
	 */
	private function setResult($result)
	{
		if($result)
		{
			$arrayResult = json_decode($result, true);
			if(100000 == $arrayResult['code'])
			{
				return $arrayResult['msg'];
			}
			else
			{
				throw new \Exception($arrayResult['msg']);
			}
		} else {
			throw new \Exception('请求失败，请稍后重试!');
		}
	}
}