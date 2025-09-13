<?php
// +----------------------------------------------------------------------
// | 数据字典插件
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace plugins\dict;//Demo插件英文名，改成你的插件英文就行了
use cmf\lib\Plugin;

//Demo插件英文名，改成你的插件英文就行了
class DictPlugin extends Plugin
{

    public $info = [
        'name'        => 'Dict',//Demo插件英文名，改成你的插件英文就行了
        'title'       => '数据字典',
        'description' => '数据字典',
        'status'      => 1,
        'author'      => '微巨宝',
        'version'     => '1.0',
        'demo_url'    => '#',
        'author_url'  => '#'
    ];

    public $hasAdmin = 1;//插件是否有后台管理界面

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


}