<?php
try{
$client = new Yar_Client("http://test.ntrans.com/demo");

var_dump($client->testAction());
}
catch(\Exception $e)
{
	echo $e->getMessage();
}