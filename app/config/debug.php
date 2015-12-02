<?php

return array(
    'ename_trans' => array(
        'host'      => '192.168.10.216',
    	'port'      => '3306',
        'user'  => 'php',
        'password'  => 'enamephp',
        'dbname'      => 'ename_trans'
    ),
	'apiInfo'=>array(
		'ip'=>'http://192.168.10.115:802',
		'user'=>'newtrans',
		'key'=>'be84ac6016bf7cee2b3a27f88532sdfdf'
	),
	'dcInfo'=>array(
		'ip'=>'http://192.168.10.115:800',
		'user'=>'newtrans',
	),
	'socket'=>array(
		'server'=>'127.0.0.1',
		'port'=>'2015',
		'timeout' => '60',
		'receiveByte' => '1024'
	),
	'elasticSearch'=>array(
		'server'=>'192.168.10.114:9200',
		'type'=>'tao',
		'index'=>'trans'
	),
	'redis'=>array(
		'server'=>'192.168.10.214',
		'port'=>'6379',
                'timeout' => 5
	)
);
