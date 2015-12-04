<?php
namespace common\domain;

class Domain
{

	/**
	 * 获取域名分组
	 *
	 * @param string $domain
	 * @return array(class,two,three,长度,域名主体);
	 */
	public static function getDomainClass($domain)
	{
		$domainBody = \common\Common::getDomainBody($domain);
		$class = self::getClass($domainBody);
		$two = self::getTwoClass($class, $domainBody);
		$three = self::getThreeClass($class, $domainBody);
		return array($class,$two,$three,mb_strlen($domainBody,'utf8'),$domainBody);
	}

	private static function getThreeClass($class, $domainBody)
	{
		$len = strlen($domainBody);
		$dnArr = str_split($domainBody);
		$unArr = array_values(array_unique($dnArr));
		$unCount = count(array_unique($dnArr)); // 数字里面有多少个唯一的数组
		if($class < 3) // 只处理数字和字母
		{
			if($unCount > 3 || ($len < 4 && $unCount == 3))
			{
				return 0; // 目前不处理唯一字符还有4个的情况
			}
			$code = '';
			foreach($dnArr as $v)
			{
				foreach($unArr as $key => $val)
				{
					if($val == $v)
					{
						$code .= (string)$key + 1;
					}
				}
			}
			return $class . $code; // 为了得到唯一的ID 拼接上 $class
		}
		if(3 == $class && 3 == $len)
		{
			$code = '';
			foreach($dnArr as $v)
			{
				if(is_numeric($v))
				{
					$code .= '1'; // 数字 1 字母 2
				}
				else
				{
					$code .= '2';
				}
			}
			return $class . $code; // 为了得到唯一的ID 拼接上 $class
		}
		return 0;
	}

	private static function getTwoClass($class, $domainBody)
	{
		$two = 0;
		if(1 == $class)
		{
			$two = 7;
		}
		elseif (4 == $class)
		{
			$two = 13;
		}
		elseif(2 == $class)
		{
			if(! preg_match("/[a|e|e|i|o|u|v]+/", $domainBody))
			{
				$two = 6;
			}
			else
			{
				$py = self::getPinCount($domainBody, \common\domain\Config::getPinyin());
				if($py)
				{
					$two = count($py);
					$two = $two > 4? 0: $two;
				}
				if(4 == strlen($domainBody))
				{
					$domainBodyArr = str_split($domainBody);
					if(preg_match("/^[a|e|e|i|o|u]+/", $domainBodyArr[0] . $domainBodyArr[2]))
					{
						if(preg_match("/[a|e|e|i|o|u]+/", $domainBodyArr[1] . $domainBodyArr[3]))
						{
							$two = $two == 2? 12: 10; // 12：如果同时是CVCV和双拼
						}
					}
				}
			}
			$two = !$two ? 11 :$two;//如果没有拼配上2级分录记录成字母
		}
		elseif(3 == $class)
		{
			$two = 8;
		}
		return $two;
	}

	private static function getClass($domainBody)
	{
		$class = 3;
		if(preg_match("/^\d+$/", $domainBody))
		{
			$class = 1;
		}
		elseif(\common\Common::isCnBody($domainBody))
		{
			$class = 4;
		}
		elseif(preg_match("/^[a-z]+$/", $domainBody))
		{
			$class = 2;
		}
		return $class;
	}

	private static function getPinCount($domainBody, $domainPinyin)
	{
		$pyDic = explode('|', $domainPinyin);
		$domainBody = strtolower($domainBody);
		$len = strlen($domainBody);
		$p = array_fill(0, $len + 1, $len);
		$p[$len] = 0;
		$s = array_fill(0, $len, 0);
		
		for($i = $len - 1; $i >= 0; $i--)
		{
			for($j = 0, $max = $len - $i; $j < $max; $j++)
			{
				if($j == 0 or
					 array_search(substr($domainBody, $i, $j + 1), $pyDic) !== FALSE and $p[$i + $j + 1] + 1 < $p[$i])
				{
					$p[$i] = $p[$i + $j + 1] + 1;
					$s[$i] = $j + 1;
				}
			}
		}
		$tmp = 0;
		$result = array();
		while($tmp < $len)
		{
			$py = substr($domainBody, $tmp, $s[$tmp]);
			$tmp += $s[$tmp];
			if(array_search($py, $pyDic) !== FALSE)
			{
				array_push($result, $py);
			}
			else
			{
				return FALSE;
			}
		}
		return $result;
	}
}