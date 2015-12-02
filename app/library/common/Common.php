<?php
namespace common;

class Common
{

	/**
	 * 根据用户ID和交易ID得到唯一随机昵称
	 *
	 * @param int $enameId
	 * @param int $transId
	 * @return string
	 */
	public static function getNickname($enameId, $transId = 0)
	{
		return base_convert($enameId . $transId, 10, 36);
	}

	/**
	 * 判断是否是CNNIC管辖的域名
	 *
	 * @param sting $domain
	 * @return boolean
	 */
	public static function isCnnicDomain($domain)
	{
		$tld = self::getDomainTld($domain);
		if(in_array($tld, array('cn','中国','公司','网络')))
		{
			return true;
		}
		return false;
	}

	/**
	 * 获取域名最后一级后缀
	 *
	 * @param string $domain
	 * @return string
	 */
	public static function getDomainTld($domain)
	{
		$domainArr = explode('.', $domain);
		return strtolower($domainArr[count($domainArr) - 1]);
	}

	/**
	 * 获取域名主体部分
	 *
	 * @param string $domain
	 * @return string
	 */
	public static function getDomainBody($domain)
	{
		$domainArr = explode('.', $domain);
		return strtolower($domainArr[0]);
	}

	/**
	 * 查看一个域名的主体是否包含中文
	 * 
	 * @param string $domain
	 * @return boolean
	 */
	public static function isCnBody($domain)
	{
		if(preg_match("/[\x{4e00}-\x{9fa5}]+/u", self::getDomainBody($domain)))
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 获取域名全s后缀
	 * @param string $domain
	 */
	public static function getDomainAllTld($domain)
	{
		if($domain)
		{
			$domainArr=explode('.',$domain);
			return count($domainArr)==3 ?($domainArr[1].'.'.$domainArr[2]):$domainArr[1];
		}
	}

	/**
	 * 获取域名后缀类型
	 * @param string $domain
	 */
	public static function getTldType($domain)
	{
		$tld = self::getDomainAllTld($domain);
		return in_array($tld, (array)\Core\Config::item('provinceTld')) ? 6 : array_search($tld, (array)\Core\Config::item('tld'));
	}
}