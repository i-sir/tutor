<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"AdminUser",
 *     "name_underline"   =>"admin_user",
 *     "table_name"       =>"user",
 *     "model_name"       =>"AdminUserModel",
 *     "remark"           =>"管理员",
 *     "author"           =>"",
 *     "create_time"      =>"2024-12-28 09:53:42",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\AdminUserModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class AdminUserModel extends Model
{

    protected $name = 'user';//管理员

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
