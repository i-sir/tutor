<?php

namespace app\admin\validate;

use think\Validate;


/**
    * @AdminModel(
    *     "name"             =>"TeacherClass",
    *     "name_underline"   =>"teacher_class",
    *     "table_name"       =>"teacher_class",
    *     "validate_name"    =>"TeacherClassValidate",
    *     "remark"           =>"授课类型",
    *     "author"           =>"",
    *     "create_time"      =>"2025-08-25 15:20:15",
    *     "version"          =>"1.0",
    *     "use"              =>   $this->validate($params, TeacherClass);
    * )
    */

class TeacherClassValidate extends Validate
{

protected $rule = [];




protected $message = [];




//软删除(delete_time,0)  'action'     => 'require|unique:AdminMenu,app^controller^action,delete_time,0',

//    protected $scene = [
//        'add'  => ['name', 'app', 'controller', 'action', 'parent_id'],
//        'edit' => ['name', 'app', 'controller', 'action', 'id', 'parent_id'],
//    ];


}
