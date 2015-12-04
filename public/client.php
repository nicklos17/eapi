<?php
try{
	$client = new Yar_Client('http://test.ntrans.com/demo/');
	var_dump($client->indexAction(274, array('oli.com.cn' => array('price' => 100, 'day' => 10, 'hour' => 21, 'description' => '简介'),
				'oliq.com.cn' => array('price' => 100, 'day' => 10, 'hour' => 21, 'description' => '简介'),
				), 1, 1));
}
catch(\Exception $e)
{
	echo $e->getMessage();
}
