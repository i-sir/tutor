<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"Member",
 *     "table_name"       =>"member",
 *     "model_name"       =>"MemberModel",
 *     "remark"           =>"测试生成crud",
 *     "author"           =>"",
 *     "create_time"      =>"2023-06-14 15:16:47",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\MemberModel();
 * )
 */

use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class MemberModel extends Model
{
    protected $name = 'member';//用户信息

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;


}