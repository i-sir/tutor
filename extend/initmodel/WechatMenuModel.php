<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"WechatMenu",
 *     "name_underline"   =>"wechat_menu",
 *     "table_name"       =>"wechat_menu",
 *     "model_name"       =>"WechatMenuModel",
 *     "remark"           =>"wechat_menu",
 *     "author"           =>"",
 *     "create_time"      =>"2025-04-06 11:46:22",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\WechatMenuModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class WechatMenuModel extends Model
{

    protected $name = 'wechat_menu';//wechat_menu

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
