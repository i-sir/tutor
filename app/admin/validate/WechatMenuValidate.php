<?php

namespace app\admin\validate;

use think\Validate;


/**
 * @AdminModel(
 *     "name"             =>"WechatMenu",
 *     "name_underline"   =>"wechat_menu",
 *     "table_name"       =>"wechat_menu",
 *     "validate_name"    =>"WechatMenuValidate",
 *     "remark"           =>"wechat_menu",
 *     "author"           =>"",
 *     "create_time"      =>"2025-04-06 11:46:22",
 *     "version"          =>"1.0",
 *     "use"              =>   $this->validate($params, WechatMenu);
 * )
 */
class WechatMenuValidate extends Validate
{

    protected $rule = [
        'name' => 'require',
    ];


    protected $message = [
        'name.require' => '菜单名称不能为空!',
    ];


    //软删除(delete_time,0)  'action'     => 'require|unique:AdminMenu,app^controller^action,delete_time,0',

    //    protected $scene = [
    //        'add'  => ['name', 'app', 'controller', 'action', 'parent_id'],
    //        'edit' => ['name', 'app', 'controller', 'action', 'id', 'parent_id'],
    //    ];


}
