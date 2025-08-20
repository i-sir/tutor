<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"ShopCoupon",
 *     "name_underline"   =>"shop_coupon",
 *     "table_name"       =>"shop_coupon",
 *     "model_name"       =>"ShopCouponModel",
 *     "remark"           =>"优惠券",
 *     "author"           =>"",
 *     "create_time"      =>"2025-02-21 15:10:22",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\ShopCouponModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class ShopCouponModel extends Model
{

    protected $name = 'shop_coupon';//优惠券

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
