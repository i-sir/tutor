<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"ShopAddress",
 *     "table_name"       =>"shop_address",
 *     "model_name"       =>"ShopAddressModel",
 *     "remark"           =>"地址管理",
 *     "author"           =>"",
 *     "create_time"      =>"2023-12-16 11:34:00",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\ShopAddressModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class ShopAddressModel extends Model
{

    protected $name = 'shop_address';//地址管理

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
