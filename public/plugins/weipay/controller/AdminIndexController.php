<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\weipay\controller; //Demo插件英文名，改成你的插件英文就行了

use think\facade\Db;
use cmf\controller\PluginBaseController;

class AdminIndexController extends PluginBaseController
{

    function _initialize()
    {
        $adminId = cmf_get_current_admin_id();//获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign("admin_id", $adminId);
        } else {
            $this->error('未登录');
        }
    }

    function index()
    {
        $type = $this->request->param('type',1);
        $info = cmf_get_option('weipay');

        $this->assign("info", $info);
        $this->assign("type", $type);
        return $this->fetch('/admin_index');
    }

    public function update()
    {
        $data = $this->request->param();
        $type = $this->request->param('type',1);
        unset($data['type']);
        $info = cmf_get_option('weipay');

        $field = [
            'wx_mp_app_id',
            'wx_mini_app_id',
            'wx_mp_app_secret',
            'wx_mini_app_secret',
            'wx_token',
            'wx_encodingaeskey',
            'wx_app_id',
            'wx_mch_id',
            'wx_v2_mch_secret_key',
            'wx_v3_mch_secret_key',
            'wx_mch_secret_cert',
            'wx_mch_public_cert_path',
            'wx_notify_url',
            'ali_app_id',
            'ali_app_secret_cert',
            'ali_app_public_cert_path',
            'ali_alipay_public_cert_path',
            'ali_alipay_root_cert_path',
            'ali_notify_url',
            'ali_return_url',
            'wx_system_type',
            'wx_v3_key',
            'transfer_notify_url',
            'wx_certificates',
        ];

        $config = [];
        foreach ($field as &$v) {
            if (isset($info[$v])) {
                if (isset($data[$v])) {
                    $config[$v] = $data[$v];
                } else {
                    $config[$v] = $info[$v];
                }
            } else {
                if (isset($data[$v])) {
                    $config[$v] = $data[$v];
                } else {
                    $config[$v] = '';
                }
            }
        }
//        $config = json_encode($config);
        cmf_set_option('weipay', $config);

        $this->success('保存成功！', cmf_plugin_url('Weipay://AdminIndex/index', ['type' => $type]));
    }
}
