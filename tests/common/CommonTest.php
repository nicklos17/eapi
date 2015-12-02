<?php
namespace test;

class CommonTest extends \UnitTestCase
{
	public function test()
	{
		$domain = '9933.net';
		$s = \common\domain\Domain::getDomainClass($domain);
		var_dump($s);
		$domain = '9333.net';
		$s = \common\domain\Domain::getDomainClass($domain);
		var_dump($s);
		$domain = 'aabb.net';
		$s = \common\domain\Domain::getDomainClass($domain);
		var_dump($s);
		$domain = '3a9.net';
		$s = \common\domain\Domain::getDomainClass($domain);
		var_dump($s);
		$domain = '3ab.net';
		$s = \common\domain\Domain::getDomainClass($domain);
		var_dump($s);
	}

}