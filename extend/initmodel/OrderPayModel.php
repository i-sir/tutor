<?php

namespace initmodel;

use think\facade\Db;
use think\Model;

class OrderPayModel extends Model
{
    protected $name  = 'base_order_pay'; //支付管理
    public    $field = '*';


    /**
     * 支付信息
     * @param $openid     身份标识openid
     * @param $order_num  订单号
     * @param $amount     订单金额
     * @param $order_type 订单类型 10商城
     * @param $pay_type   支付类型 1微信支付,2余额支付,3积分支付,4支付宝支付
     * @param $order_id   订单id
     * @param $new        是否使用新支付单号 0否 1是
     * @param $is_pay     是否已支付订单 0否 1是
     * @return void
     */
    public function add($openid = 0, $order_num = 0, $amount = 0.01, $order_type = 1, $pay_type = 1, $order_id = 0, $new = 1, $is_pay = 0)
    {
        $map   = [];
        $map[] = ['order_num', '=', $order_num];
        if ($is_pay) $map[] = ['status', '=', 2];//已支付


        $pay_num = (new self())->where($map)->order('id desc')->value('pay_num');
        if ($pay_num && $new == 0) return $pay_num;

        //获取用户id
        $map100   = [];
        $map100[] = ['openid', '=', $openid];
        $map100[] = ['delete_time', '=', 0];
        $user_id  = Db::name('member')->where($map100)->value('id');


        $prefix_num = '5550';//微信支付单号
        if ($pay_type == 2) $prefix_num = '6660';//余额支付单号
        if ($pay_type == 3) $prefix_num = '7770';//支付宝支付单号
        //生成支付单号,检测是否重复
        $pay_num = $this->getPayNum($prefix_num . cmf_order_sn(), $pay_type);

        $log = [
            'user_id'     => $user_id,
            'openid'      => $openid,
            'order_id'    => $order_id,
            'order_num'   => $order_num,
            'order_type'  => $order_type,
            'pay_type'    => $pay_type,
            'amount'      => $amount,
            'pay_num'     => $pay_num,
            'create_time' => time(),
        ];

        (new self())->strict(false)->insert($log);

        return $log['pay_num'];
    }


    /**
     * 生成支付单号
     * @param $pay_num
     * @param $pay_type 支付类型
     * @return mixed|null
     */
    function getPayNum($pay_num, $pay_type)
    {
        $map      = [];
        $map[]    = ['pay_num', '=', $pay_num];
        $pay_info = Db::name('base_order_pay')->where($map)->find();
        if ($pay_info) {
            $prefix_num = '5550';//微信支付单号
            if ($pay_type == 2) $prefix_num = '6660';//余额支付单号
            if ($pay_type == 3) $prefix_num = '7770';//支付宝支付单号
            return $this->getPayNum($prefix_num . cmf_order_sn());
        } else {
            return $pay_num;
        }
    }

}