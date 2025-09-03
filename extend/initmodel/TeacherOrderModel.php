<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"TeacherOrder",
    *     "name_underline"   =>"teacher_order",
    *     "table_name"       =>"teacher_order",
    *     "model_name"       =>"TeacherOrderModel",
    *     "remark"           =>"订单管理",
    *     "author"           =>"",
    *     "create_time"      =>"2025-09-03 10:05:20",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\TeacherOrderModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class TeacherOrderModel extends Model{

	protected $name = 'teacher_order';//订单管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
