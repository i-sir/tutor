<?php

namespace app\admin\validate;

use think\Validate;


/**
    * @AdminModel(
    *     "name"             =>"TeacherOrder",
    *     "name_underline"   =>"teacher_order",
    *     "table_name"       =>"teacher_order",
    *     "validate_name"    =>"TeacherOrderValidate",
    *     "remark"           =>"订单管理",
    *     "author"           =>"",
    *     "create_time"      =>"2025-09-03 10:05:20",
    *     "version"          =>"1.0",
    *     "use"              =>   $this->validate($params, TeacherOrder);
    * )
    */

class TeacherOrderValidate extends Validate
{

protected $rule = [];




protected $message = [];




//软删除(delete_time,0)  'action'     => 'require|unique:AdminMenu,app^controller^action,delete_time,0',

//    protected $scene = [
//        'add'  => ['name', 'app', 'controller', 'action', 'parent_id'],
//        'edit' => ['name', 'app', 'controller', 'action', 'id', 'parent_id'],
//    ];


}
