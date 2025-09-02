<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"Teacher",
    *     "name_underline"   =>"teacher",
    *     "table_name"       =>"teacher",
    *     "model_name"       =>"TeacherModel",
    *     "remark"           =>"教师管理",
    *     "author"           =>"",
    *     "create_time"      =>"2025-08-25 15:19:53",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\TeacherModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class TeacherModel extends Model{

	protected $name = 'teacher';//教师管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
