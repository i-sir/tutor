<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"ShopOrder",
 *     "table_name"       =>"shop_order",
 *     "model_name"       =>"ShopOrderModel",
 *     "remark"           =>"订单管理",
 *     "author"           =>"",
 *     "create_time"      =>"2023-09-29 09:57:21",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\ShopOrderModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class ShopOrderModel extends Model
{
    protected $name = 'shop_order';//订单管理

    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
