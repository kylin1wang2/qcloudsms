<?php
/**
 * 短信SMS（Short Message Service）
 * 沉淀腾讯十年短信服务技术和经验，帮助广大开发者快速灵活的接入高质量的文字或语音短信服务。
 * API文档详情请参考链接：https://www.qcloud.com/document/product/382/5808
 * Created by PhpStorm.
 * User: kylinwang
 * Date: 2018/09/09
 * Time: 00:23
 */

namespace src\sms;


class QcloudSmsApi
{
    private $url_single;                //（指定模板）单发
    private $url_multi;                 //（指定模板）群发
    private $url_voice_prompt;          //语音通知
    private $url_voice_verifyCode;      //语音验证码
    private $appid;                     //短信SMS APPID
    private $appkey;                    //短信SMS 密钥
    private $util;                      //工具类

    public function __construct($appid, $appkey)
    {
        $this->url_single = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';
        $this->url_multi = 'https://yun.tim.qq.com/v5/tlssmssvr/sendmultisms2';
        $this->url_voice_prompt = 'https://yun.tim.qq.com/v5/tlsvoicesvr/sendvoiceprompt';
        $this->url_voice_verifyCode = 'https://yun.tim.qq.com/v5/tlsvoicesvr/sendvoice';
        $this->appid = $appid;
        $this->appkey = $appkey;
        $this->util = new SmsSenderUtil();
    }

    /**
     * 普通单发，明确指定内容，如果有多个签名，请在内容中以【】的方式添加到信息内容中，否则系统将使用默认签名
     * @param int $type 短信类型，0 为普通短信，1 营销短信
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumber 不带国家码的手机号
     * @param string $msg 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    public function singleSend($type, $nationCode, $phoneNumber, $msg, $extend = "", $ext = "")
    {
        /*
        请求包体
        {
            "tel": {
                "nationcode": "86",
                "mobile": "13788888888"
            },
            "type": 0,
            "msg": "你的验证码是1234",
            "sig": "fdba654e05bc0d15796713a1a1a2318c",
            "time": 1479888540,
            "extend": "",
            "ext": ""
        }
        应答包体
        {
            "result": 0,
            "errmsg": "OK",
            "ext": "",
            "sid": "xxxxxxx",
            "fee": 1
        }
        */
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url_single . "?sdkappid=" . $this->appid . "&random=" . $random;

        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "" . $nationCode;
        $tel->mobile = "" . $phoneNumber;

        $data->tel = $tel;
        $data->type = (int)$type;
        $data->msg = $msg;
        $data->sig = hash("sha256", "appkey=" . $this->appkey . "&random=" . $random . "&time=" . $curTime . "&mobile=" . $phoneNumber, FALSE);
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }

    /**
     * 指定模板单发
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumber 不带国家码的手机号
     * @param int $templId 模板 id
     * @param array $params 模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数
     * @param string $sign 签名，如果填空串，系统会使用默认签名
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx"  ... }，被省略的内容参见协议文档
     */
    public function singleSendWithParam($nationCode, $phoneNumber, $templId = 0, $params, $sign = "", $extend = "", $ext = "")
    {
        /*
        请求包体
        {
            "tel": {
                "nationcode": "86",
                "mobile": "13788888888"
            },
            "sign": "腾讯云",
            "tpl_id": 19,
            "params": [
                "验证码",
                "1234",
                "4"
            ],
            "sig": "fdba654e05bc0d15796713a1a1a2318c",
            "time": 1479888540,
            "extend": "",
            "ext": ""
        }
        应答包体
        {
            "result": 0,
            "errmsg": "OK",
            "ext": "",
            "sid": "xxxxxxx",
            "fee": 1
        }
        */
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url_single . "?sdkappid=" . $this->appid . "&random=" . $random;

        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "" . $nationCode;
        $tel->mobile = "" . $phoneNumber;

        $data->tel = $tel;
        $data->sig = $this->util->calculateSigForTempl($this->appkey, $random, $curTime, $phoneNumber);
        $data->tpl_id = $templId;
        $data->params = $params;
        $data->sign = $sign;
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }


    /**
     * 普通群发，明确指定内容，如果有多个签名，请在内容中以【】的方式添加到信息内容中，否则系统将使用默认签名
     * 【注意】海外短信无群发功能
     * @param int $type 短信类型，0 为普通短信，1 营销短信
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumbers 不带国家码的手机号列表
     * @param string $msg 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    public function multiSend($type, $nationCode, $phoneNumbers, $msg, $extend = "", $ext = "")
    {
        /*
        请求包体
        {
            "tel": [
                {
                    "nationcode": "86",
                    "mobile": "13788888888"
                },
                {
                    "nationcode": "86",
                    "mobile": "13788888889"
                }
            ],
            "type": 0,
            "msg": "你的验证码是1234",
            "sig": "fdba654e05bc0d15796713a1a1a2318c",
            "time": 1479888540,
            "extend": "",
            "ext": ""
        }
        应答包体
        {
            "result": 0,
            "errmsg": "OK",
            "ext": "",
            "detail": [
                {
                    "result": 0,
                    "errmsg": "OK",
                    "mobile": "13788888888",
                    "nationcode": "86",
                    "sid": "xxxxxxx",
                    "fee": 1
                },
                {
                    "result": 0,
                    "errmsg": "OK",
                    "mobile": "13788888889",
                    "nationcode": "86",
                    "sid": "xxxxxxx",
                    "fee": 1
                }
            ]
        }
        */
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url_multi . "?sdkappid=" . $this->appid . "&random=" . $random;
        $data = new \stdClass();
        $data->tel = $this->util->phoneNumbersToArray($nationCode, $phoneNumbers);
        $data->type = $type;
        $data->msg = $msg;
        $data->sig = $this->util->calculateSig($this->appkey, $random, $curTime, $phoneNumbers);
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }

    /**
     * 指定模板群发
     * 【注意】海外短信无群发功能
     * @param string $nationCode 国家码，如 86 为中国
     * @param array $phoneNumbers 不带国家码的手机号列表
     * @param int $templId 模板 id
     * @param array $params 模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数
     * @param string $sign 签名，如果填空串，系统会使用默认签名
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    public function multiSendWithParam($nationCode, $phoneNumbers, $templId, $params, $sign = "", $extend = "", $ext = "")
    {
        /*
        请求包体
        {
            "tel": [
                {
                    "nationcode": "86",
                    "mobile": "13788888888"
                },
                {
                    "nationcode": "86",
                    "mobile": "13788888889"
                }
            ],
            "sign": "腾讯云",
            "tpl_id": 19,
            "params": [
                "验证码",
                "1234",
                "4"
            ],
            "sig": "fdba654e05bc0d15796713a1a1a2318c",
            "time": 1479888540,
            "extend": "",
            "ext": ""
        }
        应答包体
        {
            "result": 0,
            "errmsg": "OK",
            "ext": "",
            "detail": [
                {
                    "result": 0,
                    "errmsg": "OK",
                    "mobile": "13788888888",
                    "nationcode": "86",
                    "sid": "xxxxxxx",
                    "fee": 1
                },
                {
                    "result": 0,
                    "errmsg": "OK",
                    "mobile": "13788888889",
                    "nationcode": "86",
                    "sid": "xxxxxxx",
                    "fee": 1
                }
            ]
        }
        */
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url_multi . "?sdkappid=" . $this->appid . "&random=" . $random;
        $data = new \stdClass();
        $data->tel = $this->util->phoneNumbersToArray($nationCode, $phoneNumbers);
        $data->sign = $sign;
        $data->tpl_id = $templId;
        $data->params = $params;
        $data->sig = $this->util->calculateSigForTemplAndPhoneNumbers($this->appkey, $random, $curTime, $phoneNumbers);
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }


    /**
     * 语言验证码发送
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumber 不带国家码的手机号
     * @param string $prompttype 语音类型目前固定值，2
     * @param string $msg 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param string $playtimes 播放次数
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    public function voicePromptSend($nationCode, $phoneNumber, $prompttype, $msg, $playtimes = 2, $ext = "")
    {
        /*
         {
         "tel": {
         "nationcode": "86", //国家码
         "mobile": "13788888888" //手机号码
         },
         "prompttype": 2, //语音类型，目前固定为2
         "promptfile": "语音内容文本", //通知内容，utf8编码，支持中文英文、数字及组合，需要和语音内容模版相匹配
         "playtimes": 2, //播放次数，可选，最多3次，默认2次
         "sig": "30db206bfd3fea7ef0db929998642c8ea54cc7042a779c5a0d9897358f6e9505", //app凭证，具体计算方式见下注
         "time": 1457336869, //unix时间戳，请求发起时间，如果和系统时间相差超过10分钟则会返回失败
         "ext": "" //用户的session内容，腾讯server回包中会原样返回，可选字段，不需要就填空。
         }
         }*/
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url_voice_prompt . "?sdkappid=" . $this->appid . "&random=" . $random;

        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "" . $nationCode;
        $tel->mobile = "" . $phoneNumber;

        $data->tel = $tel;
        $data->msg = $msg;
        $data->prompttype = $prompttype;//固定值
        $data->playtimes = $playtimes;
        $data->sig = hash("sha256", "appkey=" . $this->appkey . "&random=" . $random . "&time=" . $curTime . "&mobile=" . $phoneNumber, FALSE);
        $data->time = $curTime;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }


    /**
     * 语音验证码发送
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumber 不带国家码的手机号
     * @param string $msg 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param intger $playtimes 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    public function sendVoiceVerifyCode($nationCode, $phoneNumber, $msg, $playtimes = 2, $ext = "")
    {
        /*
           {
           "tel": {
               "nationcode": "86", //国家码
               "mobile": "13788888888" //手机号码
           },
           "msg": "1234", //验证码，支持英文字母、数字及组合；实际发送给用户时，语音验证码内容前会添加"您的验证码是"语音提示。
           "playtimes": 2, //播放次数，可选，最多3次，默认2次
           "sig": "30db206bfd3fea7ef0db929998642c8ea54cc7042a779c5a0d9897358f6e9505", //app凭证，具体计算方式见下注
           "time": 1457336869, //unix时间戳，请求发起时间，如果和系统时间相差超过10分钟则会返回失败
           "ext": "" //用户的session内容，腾讯server回包中会原样返回，可选字段，不需要就填空。
       }*/
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url_voice_verifyCode . "?sdkappid=" . $this->appid . "&random=" . $random;

        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "" . $nationCode;
        $tel->mobile = "" . $phoneNumber;

        $data->tel = $tel;
        $data->msg = $msg;
        $data->playtimes = $playtimes;
        $data->sig = hash("sha256", "appkey=" . $this->appkey . "&random=" . $random . "&time=" . $curTime . "&mobile=" . $phoneNumber, FALSE);
        $data->time = $curTime;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }
}