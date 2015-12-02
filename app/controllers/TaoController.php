<?php

class TaoController extends ControllerBase
{

	function __construct($di)
	{
		parent::__construct($di);
	}

	/**
	 * 淘域名查询数据 如果前台用户没有提交这个参数，全部传0 或者 false
	 *
	 * @param array $dn array(1域名|2简介OR域名,搜索的内容,开头，结尾)
	 * @param int $type 交易类型 1：一口价 2：竞价 3：竞价(预订竞价) 4：竞价(专题拍卖) 5：竞价(易拍易卖)
	 * 6:一口价(sedo) 8:拍卖会
	 * @param array $page array(开始页，每页多少条)
	 * @param int $sort 排序参数 1:剩余时间 2：当前价格 3：出价次数
	 * @param int $regisar 注册商 1:我司 2：非我司
	 * @param array $price array(开始价格，结束价格)
	 * @param array $bidding array(竞价是否有人出价,竞价1元起拍)
	 * @param array $exclude 必须是2维数组 array(array(内容，开头，结尾),array(内容，开头，结尾))
	 * @param array $class 分类 array(二级分类，三级分类)
	 * @param array $tld array(后缀1，后缀2)
	 * @param array $endTime array(起始时间，结束时间) 起始时间可以是0 直接传结束时间的unix_time
	 * @param array $len array(开始长度，结束长度)
	 * @return false:查询失败 || array('total','data') total:文档数量,data:二维数组
	 */
	public function index($dn = NULL, $type = 0, $page = NULL, $sort = NULL, $regisar = NULL, $price = NULL, $bidding = NULL, $exclude = NULL, 
		$class = NULL, $tld = NULL, $endTime = NULL, $len = NULL)
	{
		$es = \core\Config::item('elasticSearch');
		$client = \Elasticsearch\ClientBuilder::create()->setHosts(array($es['server']))
			->build();
		$must = $notMust = $should = array();
		$from = 0;
		$size = 10;
		if(is_array($dn) && 3 == count($dn))
		{
			if($dn[1] && $dn[2])
			{ // 只搜索域名
				$should[] = array("query_string"=> array("query"=> "t_body:{$dn[0]}*{$dn[0]}"));
			}
			elseif($dn[1])
			{
				$should[] = array("query_string"=> array("query"=> "t_body:{$dn[0]}*"));
			}
			elseif($dn[2])
			{
				$should[] = array("query_string"=> array("query"=> "t_body:*{$dn[0]}"));
			}
			if(2 == $dn[0])
			{
				if($dn[1] && $dn[2])
				{ // 搜索域名OR简介
					$should[] = array("query_string"=> array("query"=> "t_desc:{$dn[0]}*{$dn[0]}"));
				}
				elseif($dn[1])
				{
					$should[] = array("query_string"=> array("query"=> "t_desc:{$dn[0]}*"));
				}
				elseif($dn[2])
				{
					$should[] = array("query_string"=> array("query"=> "t_desc:*{$dn[0]}"));
				}
			}
		}
		if($type)
		{
			$must[] = array("term"=> array("t_type"=> $type));
		}
		if($regisar)
		{
			$must[] = array("term"=> array("t_is_our"=> $regisar));
		}
		if(is_array($price) && ($price[0] || $price[1]))
		{
			$range = array();
			intval($price[0]) >= 0? $range['gte'] = $price[0]: '';
			intval($price[1]) > 0? $range['lte'] = $price[1]: '';
			$must[] = array("range"=> array("t_now_price"=> $range));
		}
		if(is_array($bidding) && 2 == $type && ($bidding[0] || $bidding[1]))
		{
			if($bidding[0])
			{
				$must[] = array("range"=> array("t_buyer"=> array("gt"=> 0)));
			}
			if($bidding[1])
			{
				$must[] = array("term"=> array("t_start_price"=> 1));
			}
		}
		if(is_array($exclude))
		{
			foreach($exclude as $v)
			{
				list($c, $s, $e) = $v;
				if($c)
				{
					if($s)
					{
						$notMust[] = array('query_string'=> array("query"=> "t_body:{$c}*"));
					}
					if($e)
					{
						$notMust[] = array('query_string'=> array("query"=> "t_body:*{$c}"));
					}
				}
			}
		}
		if(is_array($class) && ($class[0] || $class[1]))
		{
			if($class[0])
			{
				if(10 == $class[0])
				{
					$must[] = array("terms"=> array("t_two_class"=> array(10,12)));
				}
				elseif(2 == $class[0])
				{
					$must[] = array("terms"=> array("t_two_class"=> array(2,12)));
				}
				$must[] = array('term'=> array('t_two_class'=> $class[0]));
			}
			if($class[1])
			{
				$must[] = array('term'=> array('t_three_class'=> $class[1]));
			}
		}
		if(is_array($tld))
		{
			foreach($tld as $v)
			{
				$should[] = array("term"=> array("t_tld"=> $v));
			}
		}
		if(is_array($endTime))
		{
			$endMust = array("range"=> array("t_end_time"=> array("lte"=> $endTime[1])));
			if($endTime[0])
			{
				$endMust['range']['t_end_time'] = array('gte'=> $endTime[0]);
			}
			$must[] = $endMust;
		}
		if(is_array($len))
		{
			$lenRange = array("range"=> array("t_len"=> array("gte"=> $len[0])));
			if(intval($len[1]))
			{
				$lenRange['range']['t_len']['lte'] = $len[1];
			}
			$must[] = $lenRange;
		}
		if(is_array($page))
		{
			$from = intval($page[0]);
			$size = intval($page[1]);
		}
		$arrayData = array(
				"from"=>$from,
				"size"=>$size,
				"query"=> array(
						"filtered"=> array(
								"filter"=> array(
										"bool"=> array("must"=> $must,"must_not"=> $notMust,"should"=> $should)))));
		if(3 == $sort)
		{
			$arrayData['sort'] = array('t_count'=> 'asc');
		}
		elseif(2 == $sort)
		{
			$arrayData['sort'] = array('t_now_price'=> 'asc');
		}
		else
		{
			$arrayData['sort'] = array('t_end_time'=> 'asc');
		}
		
		$params = ['index'=> $es['index'],'type'=> $es['type'],'body'=> json_encode($arrayData)];
		
		$result = $client->search($params);
		if(isset($result['hits']))
		{
			$total = isset($result['hits']['total'])? $result['hits']['total']: false;
			if(false === $total)
			{
				return false;
			}
			else
			{
				return array('total'=> $total,'data'=> $result['hits']['hits']);
			}
		}
	}
}