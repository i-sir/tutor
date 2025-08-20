<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"ShopCouponUser",
 *     "name_underline"   =>"shop_coupon_user",
 *     "table_name"       =>"shop_coupon_user",
 *     "model_name"       =>"ShopCouponUserModel",
 *     "remark"           =>"优惠券领取记录",
 *     "author"           =>"",
 *     "create_time"      =>"2025-02-21 15:17:23",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\ShopCouponUserModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class ShopCouponUserModel extends Model
{

    protected $name = 'shop_coupon_user';//优惠券领取记录

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
