<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\dict\controller; //Demo插件英文名，改成你的插件英文就行了
use cmf\controller\PluginBaseController;
use think\facade\Db;
use think\facade\Config;
error_reporting(0);

class IndexController extends PluginBaseController
{

    function index()
    {

        if (APP_DEBUG || cmf_get_current_admin_id() > 0) {
            $this->outputHTML();
        } else {
            return "请打开开发者模式，或者登录后台";
        }
    }

    public function selectTables()
    {
      
        $database =  env('DATABASE.DATABASE');
        $tables   = Db::query('show tables');
        //循环取得所有表的备注及表中列消息
        foreach ($tables as $k => $v) {
            $table_name               = array_values($v)[0];
            $tables[$k]['TABLE_NAME'] = $table_name;
            $sql                      = 'SELECT * FROM ';
            $sql .= 'INFORMATION_SCHEMA.TABLES ';
            $sql .= 'WHERE ';
            $sql .= "table_name = '{$table_name}'  AND table_schema = '{$database}'";
            // echo $sql;
            $table_result = Db::query($sql);
            // var_dump($table_result);
            foreach ($table_result as $value) {
                $tables[$k]['TABLE_COMMENT'] = $value['TABLE_COMMENT'];
            }
            $sql = 'SELECT * FROM ';
            $sql .= 'INFORMATION_SCHEMA.COLUMNS ';
            $sql .= 'WHERE ';
            $sql .= "table_name = '{$table_name}' AND table_schema = '{$database}'";
            $fields       = array();
            $field_result = Db::query($sql);
            foreach ($field_result as $value) {
                $fields[] = $value;
            }
            $tables[$k]['COLUMN'] = $fields;
        }
        return $tables;
    }

    /**
     * [outputHTML 输出html]
     * @AuthorHTL
     * @DateTime  2019-08-14T10:47:51+0800
     * @return    [type]                   [description]
     */
    public function outputHTML()
    {
        $tables = $this->selectTables();
        $html   = '';
        $title  = '数据字典';
        foreach ($tables as $v) {
            //$html .= '<p><h2>'. $v['TABLE_COMMENT'] . ' </h2>';
            $html .= '<table  border="1" cellspacing="0" cellpadding="0" align="center">';
            $html .= '<caption>' . $v['TABLE_NAME'] . '  ' . $v['TABLE_COMMENT'] . '</caption>';
            $html .= '<tbody><tr><th>字段名</th><th>数据类型</th><th>默认值</th>
            <th>允许非空</th>
            <th>自动递增</th><th>索引</th><th>备注</th></tr>';
            $html .= '';
            foreach ($v['COLUMN'] as $f) {
                $html .= '<tr><td class="c1">' . $f['COLUMN_NAME'] . '</td>';
                $html .= '<td class="c2">' . $f['COLUMN_TYPE'] . '</td>';
                $html .= '<td class="c3"> ' . $f['COLUMN_DEFAULT'] . '</td>';
                $html .= '<td class="c4"> ' . $f['IS_NULLABLE'] . '</td>';
                $html .= '<td class="c5">' . ($f['EXTRA'] == 'auto_increment' ? '是' : ' ') . '</td>';
                $html .= '<td class="c6"> ' . $f['COLUMN_KEY'] . '</td>';
                $html .= '<td class="c7"> ' . $f['COLUMN_COMMENT'] . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></p>';
        }
        //输出
        $file = '<html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>' . $title . '</title>
        <style>
        body,td,th {font-family:"宋体"; font-size:12px;padding:0 0 0 5px;}
        table{border-collapse:collapse;border:1px solid #CCC;background:#efefef;}
        table caption{text-align:left; background-color:#fff; line-height:2em; font-size:14px; font-weight:bold; }
        table th{text-align:left; font-weight:bold;height:26px; line-height:26px; font-size:12px; border:1px solid #CCC;}
        table td{height:20px; font-size:12px; border:1px solid #CCC;background-color:#fff;}
        .c1{ width: 120px;}
        .c2{ width: 120px;}
        .c3{ width: 70px;}
        .c4{ width: 80px;}
        .c5{ width: 80px;}
        .c6{ width: 70px;}
        .c7{ width: 270px;}
        </style>
        </head>
        <body>';
        $file .= '<h1 style="text-align:center;">' . $title . '</h1>';
        $file .= $html;
        $file .= '</body></html>';
        echo $file;
    }

}
