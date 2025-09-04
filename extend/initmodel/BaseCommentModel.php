<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"BaseComment",
 *     "name_underline"   =>"base_comment",
 *     "table_name"       =>"base_comment",
 *     "model_name"       =>"BaseCommentModel",
 *     "remark"           =>"评论管理",
 *     "author"           =>"",
 *     "create_time"      =>"2025-04-10 17:44:35",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\BaseCommentModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class BaseCommentModel extends Model
{

    protected $name = 'base_comment';//评论管理

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
