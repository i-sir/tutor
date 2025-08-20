<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://www.wuwuseo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 五五 <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\tencentcloud_sms\lib;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Sms\V20190711\SmsClient;

class Sms
{
    protected $error;
    protected $accessKeyId;
    protected $accessKeySecret;
    protected $appId;

    public function __construct($accessKeyId, $accessKeySecret,$appId)
    {
        $this->accessKeyId = !empty($accessKeyId)?$accessKeyId:'';
        $this->accessKeySecret = !empty($accessKeySecret)?$accessKeySecret:'';
        $this->appId = !empty($appId)?$appId:'';
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
        $accessKeyId = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;

        try {
            $cred = new Credential($accessKeyId, $accessKeySecret);
            //$cred = new Credential(getenv("TENCENTCLOUD_SECRET_ID"), getenv("TENCENTCLOUD_SECRET_KEY"));
            // 实例化 SMS 的 client 对象，clientProfile 是可选的
            $client = new SmsClient($cred, "ap-shanghai");
            // 实例化一个 sms 发送短信请求对象，每个接口都会对应一个 request 对象。
            $req = new SendSmsRequest();
            /* 填充请求参数，这里 request 对象的成员变量即对应接口的入参
            * 您可以通过官网接口文档或跳转到 request 对象的定义处查看请求参数的定义
            * 基本类型的设置:
              * 帮助链接：
              * 短信控制台：https://console.cloud.tencent.com/smsv2
              * sms helper：https://cloud.tencent.com/document/product/382/3773 */
            /* 短信应用 ID: 在 [短信控制台] 添加应用后生成的实际 SDKAppID，例如1400006666 */
            $req->SmsSdkAppid = $this->appId;
            /* 短信签名内容: 使用 UTF-8 编码，必须填写已审核通过的签名，可登录 [短信控制台] 查看签名信息 */
            $req->Sign = $params['SignName'];

            $req->PhoneNumberSet = array("+86" . $params['PhoneNumbers']);
            /* 用户的 session 内容: 可以携带用户侧 ID 等上下文信息，server 会原样返回 */
            $req->SessionContext = "sms";
            /* 模板 ID: 必须填写已审核通过的模板 ID。可登录 [短信控制台] 查看模板 ID */
            $req->TemplateID = $params['TemplateID'];
            /* 模板参数: 若无模板参数，则设置为空*/
            $req->TemplateParamSet = $params["TemplateParam"];
            // 通过 client 对象调用 SendSms 方法发起请求。注意请求方法名与请求对象是对应的
            $resp = $client->SendSms($req);
            // 输出 JSON 格式的字符串回包
            //return json_decode($resp->toJsonString(),true);
            print_r($resp->toJsonString());
            // 可以取出单个值，您可以通过官网接口文档或跳转到 response 对象的定义处查看返回字段的定义
            print_r($resp->TotalCount);
//            if ($content->Code == 'OK') {
//                return true;
//            } else {
//                $this->error = $content->Message;
//                return false;
//            };
        } catch (TencentCloudSDKException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}
