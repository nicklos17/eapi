<?php
return array(
    'tld' => array(
    	1 => 'com',
    	2 => 'cn',
    	3 => 'com.cn',
    	4 => 'net.cn',
    	5 => 'org.cn',
    	7 => 'net',
    	8 => 'org',
    	9 => 'cc',
    	10 => 'wang',
    	11 => 'top',
    	12 => 'biz',
    	13 => 'info',
    	14 => 'asia',
    	15 => 'me',
    	16 => 'tv',
    	17 => 'tw',
    	18 => 'in',
    	19 => 'cd',
    	20 => 'pw',
    	21 => 'me',
    	22 => '中国',
    	23 => '公司',
    	24 => '网络'
    ),
    'provinceTld' => array(
    	'gd.cn', 'zj.cn', 'he.cn', '.tw.cn', 'gz.cn', 'ha.cn', 'jl.cn', 'sh.cn', 'qh.cn', 'gx.cn', 'ah.cn', 'sx.cn','hk.cn', 'xz.cn', 'hb.cn', 'hl.cn', 'tj.cn', 'nx.cn','hi.cn', 'jx.cn', 'nm.cn', 'ac.cn', 'mo.cn', 'sn.cn','hn.cn', 'js.cn', 'cq.cn', 'xj.cn', 'sc.cn', 'sd.cn','fj.cn', 'ln.cn', 'bj.cn', 'yn.cn', 'gs.cn'
	),
	'domainStatus'=>array(
		1=>'正常',
		2=>'交易',
		3=>'经纪中介',
		4=>'正在转移',
		5=>'正在转出',
		6=>'已转出',
		7=>'域名锁定',
		9=>'后台锁定',
		10=>'SEDO交易',
		11=>'注册商安全锁',
		12=>'注册局安全锁',
		13=>'安全锁申请',
		14=>'交易锁定',


	),
	'notInEnameTld'=> array('tw','in','cd','us','info'),
	// cn域名的注册时间 7天
	'cnDomainRegTime'=>604800,
	'expTimeConf'=>array(
		1=>array(86400,1),//一口价1天
		2=>array(1382400,16),// 竞价16
		3=>array(1382400,16),//预订竞价 16天
		4=>array(1382400,16),//专题拍卖 16天
		5=>array(1382400,16),//易拍易卖16天
		6=>array(1382400,16),//sedo 一口价
		7=>array(2592000,30),//拍卖会30天
		8=>array(1382400,16),//询价
	),
	// 非我司的域名的过期时间要满30天
	'notInEnameExpTime'=>2592000,
	// 非我司的域名的注册时间要满60天
	'notInEnameRegTime'=>5184000,

	'buynow_lowest' => 	array(//1。数字,2。字母,3。杂米，4.声母
		'com'=>array(
			2=>array(
				1=>100,//两数字.com：100万
				2=>100,//两字母.com：100万
				3=>10//两杂.com：10万
			),
			3=>array(
				1=>20,//三数字.com：20万
				2=>5,//三字母.com：5万
				4=>10//三声母.com：10万
			),
			4=>array(
				1=>2,//四数字.com：2万
			),
		),
		'cn'=>array(
			1=>array(
				1=>400,//单数字.cn：400万
				2=>300,//单字母.cn：300万
			),
			2=>array(
				1=>30,//两数字.cn：30万
				2=>10,//两字母.cn：10万
				4=>25//两声母.cn：25万
			),
			3=>array(
				1=>2,//三数字.cn：2万
			),
		),
		'net'=>array(
			2=>array(
				1=>10,//两数字.net：10万
				2=>8//两字母.net：8万
			),
		),
		'com.cn'=>array(
			1=>array(
				1=>40,//单数字.com.cn：40万
				2=>40//单字母.com.cn：40万
			),
			2=>array(
				1=>10//两数字.com.cn：10万
			)
		),'cc'=>array(
			1=>array(
				1=>50,//单数字.cc：50万
				2=>20,//单字母.cc：20万
			),
			2=>array(
				1=>8//两数字.cc：8万
			),
		)),
    //域名上下架状态值
    'doPubSta'=>array(
        'down'=>1,//域名下架
        'up'=>2//域名上架
    ),
    'isInquiry'=>array(
        0=>1,//非询价 一口价
        1=>8//询价
    ),
	//保证金
	'baozhengjin'=>array(
        'fabu'=>50,//发布时保证金
    ),
	//通知买家卖家
	'noticeCode'=>array(
        0=>'T204',//一口价
        1=>'T203',//竞价
    ),
    //交易类型
    'transType'=>array(
        'yikoujia' => 1,
        'jingjia' => 2,
        'xunjia' => 3,
        'zhuantipaimai' => 4,
        'yipaiyimai' => 5
     ),
    //是否我司域名
    'inEname'=>array(
        'inEname' => 1,
        'notInEname' => 0
    ),
    //违约截止时间
    'breTime'=>array(
        'yikoujia'=>array(
            //一口价，非我司，10天
            'notInEname'=>864000
        ),
    ),
    'transStaCode'=>array(
        '买家已确认'=>4,
        '交易成功'=>14,
    ),
);
