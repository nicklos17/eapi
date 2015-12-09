<?php
namespace trans;

class WeiyueBase
{
	/**
	 * 生成卖家违约金
	 *
	 * @param number $transMoney
	 * @return number
	 */
	private function createSellerAbandonMoney($transMoney)
	{
		$temp = intval($transMoney);
		if($temp < 200)
		{
			return 50;
		}
		else
		{
			return intval($temp / 10 + 50);
		}
	}
}