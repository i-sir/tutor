<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Fengiy <75445822@qq.com>
// +----------------------------------------------------------------------
namespace plugins\fengiy_login;

use cmf\lib\Plugin;
use think\Db;

class FengiyLoginPlugin extends Plugin
{

    public $info = [
        'name'        => 'FengiyLogin',
        'title'       => '微巨宝自定义登录页',
        'description' => '支持大背景/轮播图/Logo/名称自定义',
        'status'      => 1,
        'author'      => 'Fengiy',
        'version'     => '1.0'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    public function adminLogin()
    {
        $config=$this->getConfig();
        $this->assign('config',$config);
        return $this->fetch('widget');
    }

}
