<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"BaseLeave",
    *     "name_underline"   =>"base_leave",
    *     "table_name"       =>"base_leave",
    *     "model_name"       =>"BaseLeaveModel",
    *     "remark"           =>"投诉建议",
    *     "author"           =>"",
    *     "create_time"      =>"2025-06-11 10:54:52",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\BaseLeaveModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class BaseLeaveModel extends Model{

	protected $name = 'base_leave';//投诉建议

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
