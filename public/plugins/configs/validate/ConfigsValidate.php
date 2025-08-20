<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace plugins\configs\validate;

use think\Validate;

class ConfigsValidate extends Validate
{
    protected $rule = [
        'group_id' => 'require',
        'name'     => 'require',
        'label'    => 'require',
    ];

    protected $message = [
        'group_id.require' => "请选择参数分组",
        'name.require'     => "请填写参数名",
        'label.require'    => "请填写参数说明",
    ];


}