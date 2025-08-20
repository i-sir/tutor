<?php

namespace plugins\Configs;

use cmf\lib\Plugin;
use think\facade\Db;

class ConfigsPlugin extends Plugin
{
    public $info = [
        'name'        => 'Configs',
        'title'       => '系统参数设置',
        'description' => 'config读取配置参数扩展',
        'status'      => 1,
        'author'      => 'lampzww',
        'version'     => '1.0',
        'demo_url'    => '',
        'author_url'  => '',
    ];

    public $hasAdmin = 1;

    // 插件安装
    public function install()
    {
        if(!Db::query("SHOW TABLES LIKE '".config('database.prefix')."config'")){
            Db::execute("CREATE TABLE `".config('database.prefix')."config` (
                          `name` varchar(32) NOT NULL COMMENT '参数名',
                          `value` blob COMMENT '参数值,序列化数据',
                          `groupid` int(4) DEFAULT NULL COMMENT '分组ID',
                          `label` varchar(32) DEFAULT NULL COMMENT '参数说明',
                          `uridata` varchar(32) DEFAULT NULL,
                          `data` blob COMMENT '数据源',
                          `type` varchar(32) DEFAULT 'text' COMMENT '设置类型 ',
                          `about` varchar(64) DEFAULT NULL,
                          `list_order` smallint(2) DEFAULT '0' COMMENT '排序',
                          PRIMARY KEY (`name`),
                          UNIQUE KEY `config_name` (`name`) USING BTREE
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
            /*示例配置参数*/
            Db::execute('INSERT INTO `'.config('database.prefix').'config` (`name`, `value`, `groupid`, `label`, `uridata`, `data`, `type`, `about`, `list_order`) VALUES (\'test_title\', NULL, \'1\', \'海报标题\', NULL, \'s:0:\"\";\', \'text\', \'这是一个示例\', \'1\');');
            Db::execute('INSERT INTO `'.config('database.prefix').'config` (`name`, `value`, `groupid`, `label`, `uridata`, `data`, `type`, `about`, `list_order`) VALUES (\'test_logo\', NULL, \'1\', \'海报背景图\', NULL, \'s:0:\"\";\', \'img\', \'这是一个示例\', \'2\');');
            Db::execute('INSERT INTO `'.config('database.prefix').'config` (`name`, `value`, `groupid`, `label`, `uridata`, `data`, `type`, `about`, `list_order`) VALUES (\'test_desc\', NULL, \'1\', \'海报描述\', NULL, \'s:0:\"\";\', \'textarea\', \'这是一个示例\', \'3\');');
            Db::execute('INSERT INTO `'.config('database.prefix').'config` (`name`, `value`, `groupid`, `label`, `uridata`, `data`, `type`, `about`, `list_order`) VALUES (\'test_attr\', NULL, \'1\', \'这是一个复选\', NULL, \'a:3:{i:0;a:2:{s:5:\"value\";s:1:\"1\";s:4:\"name\";s:4:\"值1\";}i:1;a:2:{s:5:\"value\";s:1:\"2\";s:4:\"name\";s:4:\"值2\";}i:2;a:2:{s:5:\"value\";s:1:\"3\";s:4:\"name\";s:4:\"值3\";}}\', \'checkbox\', \'返回选中值 数组\', \'4\');');
            Db::execute('INSERT INTO `'.config('database.prefix').'config` (`name`, `value`, `groupid`, `label`, `uridata`, `data`, `type`, `about`, `list_order`) VALUES (\'test_line\', NULL, \'1\', \'这是一条分割线\', NULL, \'s:0:\"\";\', \'line\', \'\', \'5\');');
            Db::execute('INSERT INTO `'.config('database.prefix').'config` (`name`, `value`, `groupid`, `label`, `uridata`, `data`, `type`, `about`, `list_order`) VALUES (\'test_tpl\', NULL, \'1\', \'首页模板\', NULL, \'a:3:{i:0;a:2:{s:5:\"value\";s:11:\"index/index\";s:4:\"name\";s:12:\"默认模板\";}i:1;a:2:{s:5:\"value\";s:12:\"index/index2\";s:4:\"name\";s:9:\"国庆节\";}i:2;a:2:{s:5:\"value\";s:12:\"index/index3\";s:4:\"name\";s:12:\"春节首页\";}}\', \'select\', \'这是一个下拉\', \'6\');');
            Db::execute('INSERT INTO `'.config('database.prefix').'config` (`name`, `value`, `groupid`, `label`, `uridata`, `data`, `type`, `about`, `list_order`) VALUES (\'test_dx\', NULL, \'1\', \'我是一个单选\', NULL, \'a:2:{i:0;a:2:{s:5:\"value\";s:1:\"1\";s:4:\"name\";s:6:\"开启\";}i:1;a:2:{s:5:\"value\";s:1:\"2\";s:4:\"name\";s:6:\"关闭\";}}\', \'radio\', \'\', \'7\');');
        }

        return true; //安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true; //卸载成功返回true，失败false
    }

    public function appBegin($param)
    {
        //读取数据库基本参数
        $configs 	= cache("DB_CONFIG_DATA");
        if(!$configs){
            $configs    =   Db::name('base_config')->column('name,value');
            cache("DB_CONFIG_DATA", $configs);
        }
        foreach ($configs as $key=>$value){
            config($key, unserialize($value));
        }
    }
}
