<?php

namespace plugins\tencentcloud_sms;

use cmf\lib\Plugin;
use think\facade\Log;

class TencentcloudSmsPlugin extends Plugin
{
    public $info = array(
        'name'=>'TencentcloudSms',
        'title'=>'腾讯云短信',
        'description'=>'腾讯云短信',
        'status'=>1,
        'author'=>'五五',
        'version'=>'1.0'
    );
    
    public $has_admin=0;

    protected $error = '';
    
    public function install()
    {
        return true;
    }
    
    public function uninstall()
    {
        return true;
    }

    public function getError(){
        return $this->error;
    }


    public function sendMobileVerificationCode($param)
    {

        $mobile        = $param['mobile'];
        $code          = $param['code'];
        $config        = $this->getConfig();
        if ($code!==false) {
            $params['PhoneNumbers'] = $mobile;
            $params['SignName'] = $config['SignName'];
            $params['TemplateID'] = isset($param['template'])?$param['template']:$config['TemplateID'];
            $params['TemplateParam'] = array(
                (string)$code
            );
            $sms = new \plugins\tencentcloud_sms\lib\Sms(
                $config['accessKeyId'],
                $config['accessKeySecret'],
                $config['AppIDSdk']
            );

            $reponse = $sms->sendSms($params);

            if ($reponse) {
                return true;
            } else {
                $this->error = $sms->getError();
                Log::error('[ sms ] '.$mobile.'-'.$this->error);
                return false;
            }
        } else {
            $this->error = '发送次数过多，不能再发送';
            Log::error('[ sms ] '.$mobile.'-'.$this->error);
            return false;
        }
        return $result;
    }
}
