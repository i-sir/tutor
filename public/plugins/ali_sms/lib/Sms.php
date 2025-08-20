<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.wuwuseo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 五五 <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\ali_sms\lib;

class Sms
{
    protected $error;
    protected $accessKeyId;
    protected $accessKeySecret;

    public function __construct($accessKeyId, $accessKeySecret)
    {
        $this->accessKeyId = !empty($accessKeyId)?$accessKeyId:'';
        $this->accessKeySecret = !empty($accessKeySecret)?$accessKeySecret:'';
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 发送短信
     */
    public function sendSms($params = array())
    {
        if (empty($params['PhoneNumbers'])) {
            $this->error = '缺失手机号$params["PhoneNumbers"]';
            return false;
        }
        if (empty($params['SignName'])) {
            $this->error = '缺失短信签名$params["SignName"]';
            return false;
        }
        if (empty($params['TemplateCode'])) {
            $this->error = '缺失模版CODE$params["TemplateCode"]';
            return false;
        }

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;

        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"]);
        }

        try {
            $content = $this->request(
                $accessKeyId,
                $accessKeySecret,
                "dysmsapi.aliyuncs.com",
                array_merge($params, array(
                    "RegionId" => "cn-hangzhou",
                    "Action" => "SendSms",
                    "Version" => "2017-05-25",
                ))
            );
            if ($content->Code == 'OK') {
                return true;
            } else {
                $this->error = $content->Message;
                return false;
            };
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 生成签名并发起请求
     *
     * @param $accessKeyId string AccessKeyId (https://ak-console.aliyun.com/)
     * @param $accessKeySecret string AccessKeySecret
     * @param $domain string API接口所在域名
     * @param $params array API具体参数
     * @return bool|\stdClass 返回API接口调用结果，当发生错误时返回false
     */
    public function request($accessKeyId, $accessKeySecret, $domain, $params)
    {
        $apiParams = array_merge(array(
            "SignatureMethod" => "HMAC-SHA1",
            "SignatureNonce" => uniqid(mt_rand(0, 0xffff), true),
            "SignatureVersion" => "1.0",
            "AccessKeyId" => $accessKeyId,
            "Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
            "Format" => "JSON",
        ), $params);
        ksort($apiParams);

        $sortedQueryStringTmp = "";
        foreach ($apiParams as $key => $value) {
            $sortedQueryStringTmp .= "&" . $this->encode($key) . "=" . $this->encode($value);
        }

        $stringToSign = "GET&%2F&" . $this->encode(substr($sortedQueryStringTmp, 1));

        $sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&", true));

        $signature = $this->encode($sign);

        $url = "http://{$domain}/?Signature={$signature}{$sortedQueryStringTmp}";

        try {
            $content = $this->fetchContent($url);
            return json_decode($content);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    private function encode($str)
    {
        $res = urlencode($str);
        $res = preg_replace("/\+/", "%20", $res);
        $res = preg_replace("/\*/", "%2A", $res);
        $res = preg_replace("/%7E/", "~", $res);
        return $res;
    }

    private function fetchContent($url)
    {
        if (function_exists("curl_init")) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "x-sdk-client" => "php/2.0.0"
            ));
            $rtn = curl_exec($ch);

            if ($rtn === false) {
                trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
            }
            curl_close($ch);

            return $rtn;
        }

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => array("x-sdk-client: php/2.0.0"),
            )
        ));

        return file_get_contents($url, false, $context);
    }
}
