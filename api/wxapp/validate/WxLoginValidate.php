<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace api\wxapp\validate;

use think\Validate;

class WxLoginValidate extends Validate
{
    protected $rule = [
        'code' => 'require',
        'encrypted_data' => 'require',
        'iv' => 'require',
//        'raw_data' => 'require',
//        'signature' => 'require',
    ];

    protected $message = [
        'code.require'           => '缺少参数code!',
        'encrypted_data.require' => '缺少参数encrypted_data!',
        'iv.require'             => '缺少参数iv!',
//        'raw_data.require'       => '缺少参数raw_data!',
//        'signature.require'      => '缺少参数signature!',
    ];


//    protected $scene = [
//        'add'  => ['name', 'app', 'controller', 'action', 'parent_id'],
//        'edit' => ['name', 'app', 'controller', 'action', 'id', 'parent_id'],
//    ];
}
