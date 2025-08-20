<?php

namespace app\admin\validate;

use think\Validate;
 
class MemberValidate extends Validate
{
    protected $rule = [
        'nickname' => 'require',
        'phone'    => 'require',
    ];

    protected $message = [
        'nickname.require' => '昵称不能为空!',
        'phone.require'    => '手机号不能为空!',
    ];
}