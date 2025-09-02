<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"TeacherClass",
    *     "name_underline"   =>"teacher_class",
    *     "table_name"       =>"teacher_class",
    *     "model_name"       =>"TeacherClassModel",
    *     "remark"           =>"授课类型",
    *     "author"           =>"",
    *     "create_time"      =>"2025-08-25 15:20:15",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\TeacherClassModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class TeacherClassModel extends Model{

	protected $name = 'teacher_class';//授课类型

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
