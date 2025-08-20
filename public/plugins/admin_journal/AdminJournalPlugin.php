<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 https://www.wzxaini9.cn/ All rights reserved.
// +----------------------------------------------------------------------
// | Author: Powerless <wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace plugins\admin_journal;

use cmf\lib\Plugin;
use think\facade\Db;

class AdminJournalPlugin extends Plugin
{
    public $info = [
        'name'        => 'AdminJournal',
        'title'       => '操作日志',
        'description' => '后台操作日志',
        'status'      => 1,
        'author'      => 'Powerless',
        'version'     => '1.2.0',
        'demo_url'    => 'https://www.wzxaini9.cn/',
        'author_url'  => 'https://www.wzxaini9.cn/'
    ];

    public $hasAdmin = 1;//插件是否有后台管理界面




    public function test(){
        //清除日志
        $AdminJournalPlugin = new AdminJournalPlugin();
        $AdminJournalPlugin->delete_log();
    }




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

    public function adminInit()
    {
        // 缓存不需要记录日志的控制器和方法
        static $NoController = [
            'admin/shop_order/order_notification',
            'plugin/configs/admin_index/index',
            'admin/member/test',
            'admin/member/test1',
        ];

        $adminId = cmf_get_current_admin_id();

        // 获取当前请求的路径信息
        $pathinfo = str_replace(".html", "", request()->pathinfo());

        // 如果当前请求的路径在不需要记录日志的列表中，直接返回
        if (in_array($pathinfo, $NoController)) {
            return false;
        }

        // 获取请求参数
        $params = request()->param();

        // 构建访问的完整 URL
        $visit_url = request()->url() . '?' . http_build_query($params);


        $menu_name  = $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        $menu_name  = str_replace("index.php?s=", "", $menu_name);
        $url_detail = explode('&', $menu_name, 2);
        $menu_name  = $url_detail[0];

        // 准备日志数据
        $logData = [
            'admin_id'    => $adminId,
            'ip'          => get_client_ip(),
            'admin_name'  => $this->filterEmoji(session('name')),
            'date'        => date('Y-m-d H:i:s'),
            'create_time' => time(),
            'menu_name'   => $menu_name,
            'visit_url'   => $visit_url,
            'param'       => json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];


        // 插入新日志
        Db::name('base_admin_log')->insert($logData);
    }

    /**
     * 删除日志记录
     */
    public function delete_log()
    {
        // 删除旧日志
        $log_file_days = ($log_file_days ?? 10) * 3;
        $deleteDateDb  = strtotime("-{$log_file_days} days", strtotime(date('Y-m-d')));
        Db::name('base_admin_log')->where('create_time', '<', $deleteDateDb)->delete();


        // 删除资源表
        Db::name('asset')->where('id', '<>', 0)->delete();


//        //日志文件保留天数
//        $log_file_days = cmf_config('log_file_days') ?? 10;
//        // 指定日志文件目录
//        $logDirectory = CMF_ROOT . 'data/journal/';
//        // 获取当前日期和时间戳
//        $currentDate = strtotime(date('Y-m-d'));
//        // 计算n天前的日期时间戳
//        $deleteDate = strtotime("-{$log_file_days} days", $currentDate);
//        // 获取日志文件列表
//        $files = scandir($logDirectory);
//        // 遍历日志文件列表
//        foreach ($files as $file) {
//            // 排除当前目录和上级目录
//            if ($file != '.' && $file != '..') {
//                // 获取文件的最后修改时间
//                $fileLastModified = filemtime($logDirectory . '/' . $file);
//                // 如果文件的最后修改时间早于要删除的日期时间戳，则删除文件
//                if ($fileLastModified < $deleteDate) {
//                    unlink($logDirectory . '/' . $file);
//                    //echo "Deleted: " . $logDirectory . '/' . $file . "\n";
//                }
//            }
//        }



    }


    //去除昵称的表情问题
    function filterEmoji($str)
    {
        $str = preg_replace_callback('/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return $str;
    }
}