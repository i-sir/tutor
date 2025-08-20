<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2018 http://www.wuwuseo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\hu_yi;

use cmf\lib\Plugin;
use plugins\hu_yi\lib\SendMsg;
use think\facade\Log;

//以下参数不需要修改

class HuYiPlugin extends Plugin
{

    public $info = array(
        'name'        => 'HuYi',
        'title'       => '互亿短信',
        'description' => '互亿短信',
        'status'      => 1,
        'author'      => 'wjb',
        'datetime'    => '2025-04-08',
        'version'     => '1.0'
    );

    public $has_admin = 1;//插件是否有后台管理界面

    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    //发送短信验证码
    public function sendMobileVerificationCode($params): array
    {
        $config = $this->getConfig();
        if (empty($params['mobile']) || empty($params['code'])) {
            return ['code' => 0, 'msg' => '手机号或验证码不能为空！'];
        }
        $client = new SendMsg($config['apiid'], $config['apikey']);
        $res    = $client->sendTextMsg($params['mobile'], $params['code']);
        if (isset($res['code']) && $res['code'] == 2) {
            return ['code' => 1, 'msg' => '短信发送成功！'];
        } else {
            return ['code' => 0, 'msg' => $res['msg']];
        }
    }

    //发送语音通知
    public function sendVoiceMsg($params)
    {
        $config = $this->getConfig();
        if (empty($params['mobile'])) {
            return ['code' => 0, 'msg' => '手机号不能为空！'];
        }

        $content = $params['content'] ?? '';

        $client = new SendMsg($config['voice_apiid'], $config['voice_apikey']);
        $res    = $client->sendVoiceMsg($params['mobile'], $content);
        if (isset($res['code']) && $res['code'] == 2) {
            return ['code' => 1, 'msg' => '语音推送成功！'];
        } else {
            return ['code' => 0, 'msg' => $res['msg']];
        }
    }
}
