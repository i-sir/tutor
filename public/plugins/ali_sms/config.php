<?php
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://www.wuwuseo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 五五 <15093565100@163.com>
// +----------------------------------------------------------------------
return array(
    'accessKeyId' => array(
        'title' => 'accessKeyId',
        'type' => 'text',
        'value' => '',
        'tip' => 'https://ak-console.aliyun.com/ 取得您的AK信息'
    ),
    'accessKeySecret' => array(
        'title' => 'accessKeySecret',
        'type' => 'text',
        'value' => '',
        'tip' => 'https://ak-console.aliyun.com/ 取得您的AK信息'
    ),
    'SignName' => array(
        'title' => '短信签名',
        'type' => 'text',
        'value' => '',
        'tip' => '请填写阿里运通讯审核通过的短信签名'
    ),
    'TemplateCode' => array(
        'title' => '短信模板CODE',
        'type' => 'text',
        'value' => '',
        'tip' => '请填写阿里运通讯审核通过的短信模板的CODE'
    ),
    'codeKey' => array(
        'title' => '短信验证码模板变量',
        'type' => 'text',
        'value' => 'code',
        'tip' => '请填写阿里运通讯审核通过的短信模板的短信验证码模板变量'
    ),
    'expire_minute' => array(// 在后台插件配置表单中的键名 ,会是config[text]
        'title' => '有效期', // 表单的label标题
        'type' => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value' => '30',// 表单的默认值
        'tip' => '短信验证码过期时间，单位分钟' //表单的帮助提示
    ),
);
