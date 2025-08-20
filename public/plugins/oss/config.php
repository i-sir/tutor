<?php
return [
    'accessKey'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => 'AccessKey', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'AccessKey不能为空'
        ],
        'tip'     => 'OSS后台AccessKey ID' //表单的帮助提示
    ],
    'secretKey'                 => [// 在后台插件配置表单中的键名 ,会是config[password]
        'title'   => 'SecretKey',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => 'SecretKey不能为空'
        ],
        'tip'     => 'OSS后台Access Key Secret'
    ],
    'protocol'                  => [// 在后台插件配置表单中的键名 ,会是config[select]
        'title'   => '域名协议',
        'type'    => 'select',
        'options' => [//select 和radio,checkbox的子选项
            'http'  => 'http',// 值=>显示
            'https' => 'https',
        ],
        'value'   => 'http',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '域名协议不能为空'
        ],
        'tip'     => ''
    ],
    'domain'                    => [
        'title'   => '访问域名 （ Bucket 域名 ）',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '访问域名不能为空'
        ],
        'tip'     => '阿里云后台查看 Bucket 域名, 例如 aaa-test.oss-cn-beijing.aliyuncs.com'
    ],
    'bucket'                    => [
        'title'   => '空间名称',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '空间名称不能为空'
        ],
        'tip'     => '阿里云后台创建的Bucket名称'
    ],
    'style_separator'           => [
        'title'   => '样式分隔符',
        'type'    => 'text',
        'value'   => '?',
//        "rule"    => [
//            "require" => true
//        ],
//        "message" => [
//            "require" => '样式分隔符不能为空'
//        ],
        'tip'     => ''
    ],
    'dir'           => [
        'title'   => '要上传的文件夹',
        'type'    => 'text',
        'value'   => '',
//        "rule"    => [
//            "require" => true
//        ],
//        "message" => [
//            "require" => '样式分隔符不能为空'
//        ],
        'tip'     => '要上传的文件夹'
    ],

];