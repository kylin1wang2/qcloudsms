<?php
/**
 * 腾讯云短信接口调用示例
 * Created by PhpStorm.
 * User: kylinwang
 * Date: 2018/09/09
 * Time: 00:58
 */

namespace src\sample;


use src\sms\QcloudSmsApi;
use src\sms\SmsSenderUtil;

class TestController
{
    const APPID = '1400000000';                         //示例APPID
    const APPKEY = '2a9be03118ba8f4eda26f51b30000000';  //示例APPKEY
    const SMS_SIGN_ID = '29001';                        //短信签名id
    const SMS_TEMP_ID = '59407';                        //短信正文模板id
    private $api;
    private $util;

    public function __construct()
    {
        $this->api = new QcloudSmsApi(self::APPID, self::APPKEY);
        $this->util = new SmsSenderUtil();
    }

    /**
     * 指定模板单发测试
     */
    public function testSingleSend()
    {

        $phone = $_GET['phone'];
        if(!$this->isMobile($phone)){
            exit('无效的手机号码！');
        }

        //请求包体所需参数
        $nationCode = '86';
        $templId = self::SMS_TEMP_ID;
        $v_code = "" . $this->util->getRandom();
        $params = array($v_code, "5");
        $sign = '腾讯科技';

        $result = $this->api->singleSendWithParam($nationCode, $phone, $templId, $params, $sign);

        print_r($result);
    }


    /**
     * 验证手机号码是否有效
     * @param $mobile
     * @return bool
     */
    private function isMobile($mobile)
    {
        if (!is_numeric($mobile)) {
            return false;
        }
        $pattern = '#^13[\d]{9}$|^14[5,6,7,8,9]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[6]{1}\d{8}$|^17[^4,^9]{1}\d{8}$|^18[\d]{9}$|19[8,9]{1}\d{8}#';
        return preg_match($pattern, $mobile) ? true : false;
    }

}