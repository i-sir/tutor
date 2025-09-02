<?php

namespace api\wxapp\validate;

use think\Validate;


/**
    * @AdminModel(
    *     "name"             =>"Teacher",
    *     "name_underline"   =>"teacher",
    *     "table_name"       =>"teacher",
    *     "validate_name"    =>"TeacherValidate",
    *     "remark"           =>"教师管理",
    *     "author"           =>"",
    *     "create_time"      =>"2025-08-25 15:19:53",
    *     "version"          =>"1.0",
    *     "use"              =>   $this->validate($params, Teacher);
    * )
    */

class TeacherValidate extends Validate
{

protected $rule = [];




protected $message = [];





//软删除(delete_time,0)  'action'     => 'require|unique:AdminMenu,app^controller^action,delete_time,0',


//    protected $scene = [
//        'add'  => ['name', 'app', 'controller', 'action', 'parent_id'],
//        'edit' => ['name', 'app', 'controller', 'action', 'id', 'parent_id'],
//    ];


}
