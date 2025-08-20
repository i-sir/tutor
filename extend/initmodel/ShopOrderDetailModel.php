<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"ShopOrderDetail",
 *     "table_name"       =>"shop_order_detail",
 *     "model_name"       =>"ShopOrderDetailModel",
 *     "remark"           =>"订单详情",
 *     "author"           =>"",
 *     "create_time"      =>"2023-09-29 10:00:41",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\ShopOrderDetailModel();
 * )
 */


use think\facade\Db;
use think\Model;

class ShopOrderDetailModel extends Model
{
    protected $name = 'shop_order_detail';//订单详情
}
