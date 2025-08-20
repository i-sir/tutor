<?php

namespace plugins\hu_yi\lib;

use think\facade\Log;

class SendMsg
{


    protected $apiid;
    protected $apikey;

    public function __construct($apiid, $apikey)
    {
        $this->apiid  = !empty($apiid) ? $apiid : '';
        $this->apikey = !empty($apikey) ? $apikey : '';
    }

    //发送短信验证码
    public function sendTextMsg($mobile, $code)
    {
        //短信接口地址
        if (empty($mobile) || empty($code)) {
            return ['code' => 0, 'msg' => '手机号或验证码为空'];
        }
        $target = "http://106.ihuyi.com/webservice/sms.php?method=Submit";

        $post_data = 'account=' . $this->apiid . '&password=' . $this->apikey . '&mobile=' . $mobile . '&content=' . rawurlencode("您的验证码是：" . $code . "。请不要把验证码泄露给其他人。");
        $gets      = self::xml_to_array(self::curl_post($post_data, $target));

        Log::write($mobile, '互亿短信发送手机号');
        Log::write($code, '互亿短信发送code');
        Log::write($gets['SubmitResult'], '互亿短信发送结果');

        return $gets['SubmitResult'];
    }


    //发送语音通知
    public function sendVoiceMsg($mobile, $content = '')
    {
        if (empty($content)) {
            $content = '温馨提示，您有新的订单，请及时处理';
        }
        $target    = "http://api.vm.ihuyi.com/webservice/voice.php?method=Submit";
        $post_data = "account=" . $this->apiid . "&password=" . $this->apikey . "&mobile=" . $mobile . "&content=" . $content;

        $gets = self::xml_to_array(self::curl_post($post_data, $target));

        Log::write($mobile, '推送语音手机号');
        Log::write($gets, '推送结果');

        return $gets['SubmitResult'];
    }


    //请求数据到短信接口，检查环境是否 开启 curl init。
    static function curl_post($curlPost, $url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }

    //将 xml数据转换为数组格式。
    static function xml_to_array($xml)
    {
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $subxml = $matches[2][$i];
                $key    = $matches[1][$i];
                if (preg_match($reg, $subxml)) {
                    $arr[$key] = self::xml_to_array($subxml);
                } else {
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }

}

?>