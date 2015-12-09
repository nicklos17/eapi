<?php
class EnquiryController {

    /**
     * 询价发布时的确认检测
     * @params $uId int 用户id
     * @params $domains array 域名
     *
     */
    public function preChk($uId, $domains) {
        // step1 根据配置检测TLD支持后缀

        // step2 判断域名是否已经发布询价中

        // step3 调用Dc接口区分出我司和非我司域名

        // step4-1 如果存在我司域名判断状态是否可发布询价调用logic里面comCheck

        // step4-2 如果存在非我司域名调用logic里面的nonComCheck

        // step5 判断我司非我司域名是否存在询价表中

        // step6 合并返回询价域名的处理结果和信息，我司域名必须返回域名到期时间
    }


    /**
     * 非我司域名whois信息查询，同时返回域名到期时间
     * @params $uId int 用户id
     * @params $domain string 域名
     *
     * @Notice：具体流程参考PulbicController里面的whoidChk函数
     *
     */
    public function whoisChk($uId, $domain) {

    }

    /**
	 * 询价发布
	 *
	 * @param $uId int 用户id
	 * @param $domains array 包含我司和非我司域名的二维数组，一个域名包含价格和简介以及出售周期
	 * @param $cashType int 提现类型，在控制器检查，2 不可提现 3 可体现
	 * @param $ip 用户ip
	 */
    public function publish($uId, $domains, $cashType, $ip) {

        // step1 判断TLD的域名，排除不能发布的
        //
        // step2 检车域名简介是否包含关键词
        //       检测出售天数、最低价格
        //
        // step3 存在我司域名调用判断我司域名是否可发布
        //       非我司域名判断是否$domains里面有非我司域名，有的话从redis重取出
        //       上一步缓存的已经认证过的域名，排除$domains里面非我司域名那个数组
        //       里面不在缓存中得域名
        //
        // step4 取得所有域名判断是否在询价中
        //
        // step5 将域名写入询价表中

    }
}
