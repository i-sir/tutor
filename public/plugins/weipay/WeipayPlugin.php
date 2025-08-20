<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------

namespace plugins\weipay;

//Demo插件英文名，改成你的插件英文就行了
use cmf\lib\Plugin;

//Demo插件英文名，改成你的插件英文就行了
class WeipayPlugin extends Plugin
{
    public $info = [
        'name' => 'Weipay',
        'title' => 'APP信息&支付信息',
        'description' => '微信小程序&公众号&支付宝支付信息',
        'status' => 1,
        'author' => 'wjb',
        'version' => '1.0.0'
    ];

    public $hasAdmin = 1; //插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true; //安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true; //卸载成功返回true，失败false
    }
}
