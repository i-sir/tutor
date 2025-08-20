<?php
namespace plugins\oss;

use cmf\lib\Plugin;

class OssPlugin extends Plugin{

    public $info = [
        'name'        => 'Oss',
        'title'       => 'OSS上传',
        'description' => 'OSS上传',
        'status'      => 1,
        'author'      => 'zsl',
        'version'     => '1.0.0'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        $storageOption = cmf_get_option('storage');
        if (empty($storageOption)) {
            $storageOption = [];
        }

        $storageOption['storages']['Oss'] = ['name' => '阿里云OSS存储', 'driver' => '\\plugins\\oss\\lib\\Oss'];

        cmf_set_option('storage', $storageOption);
        return true;//安装成功返回true，失败false
    }


    // 插件卸载
    public function uninstall()
    {
        $storageOption = cmf_get_option('storage');
        if (empty($storageOption)) {
            $storageOption = [];
        }

        unset($storageOption['storages']['Oss']);

        cmf_set_option('storage', $storageOption);
        return true;//卸载成功返回true，失败false
    }


    public function fetchUploadView(){

        $config     = $this->getConfig();

    }


}