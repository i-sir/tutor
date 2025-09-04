<?php

namespace init;

use api\wxapp\controller\InitController;
use api\wxapp\controller\WxBaseController;
use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * 定时任务
 */
class TaskInit
{


    /**
     * 更新优惠券状态   定时任务
     */
    public function operation_coupon()
    {
        $ShopCouponModel     = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)

        /** 处理优惠券状态 **/
        $map   = [];
        $map[] = ['type', '=', 1];//时间段,按分钟正常显示
        $map[] = ['start_time', '<=', time()];
        $map[] = ['end_time', '>', time()];
        //优惠券列表,更新状态
        $ShopCouponModel->where($map)->update(['status' => 1, 'update_time' => time()]);


        /** 处理优惠券状态 **/
        $map3   = [];
        $map3[] = ['type', '=', 2];
        //优惠券列表,更新状态
        $ShopCouponModel->where($map3)->update(['status' => 1, 'update_time' => time()]);


        /** 处理优惠券领取记录状态 **/
        $map2   = [];
        $map2[] = ['used', '=', 1];
        $map2[] = ['end_time', '<', time()];
        //优惠券领取记录,更新状态  已过期
        $ShopCouponUserModel->where($map2)->update(['used' => 3, 'update_time' => time()]);


        echo("更新置顶,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 自动取消订单
     */
    public function operation_cancel_order()
    {
        $ShopOrderModel   = new \initmodel\ShopOrderModel(); //商城订单   (ps:InitModel)
        $Pay              = new PayController();
        $OrderPayModel    = new \initmodel\OrderPayModel();
        $WxBaseController = new WxBaseController();//微信基础类


        $map   = [];
        $map[] = ['auto_cancel_time', '<', time()];
        $map[] = ['status', '=', 1];
        $list  = $ShopOrderModel->where($map)->select();
        if ($list) {

            foreach ($list as $k => $order_info) {
                //微信支付取消 && 不让再次支付了
                if (empty($order_info['pay_num'])) {
                    $map300   = [];
                    $map300[] = ['order_num', '=', $order_info['order_num']];
                    $pay_num  = $OrderPayModel->where($map300)->value('pay_num');
                } else {
                    $pay_num = $order_info['pay_num'];
                }
                $Pay->close_order($pay_num);
            }


            //更新订单状态
            $ShopOrderModel->where($map)->strict(false)->update([
                'status'      => 10,
                'cancel_time' => time(),
                'update_time' => time(),
            ]);
        }


        //取消老师接单,并退款
        $map100   = [];
        $map100[] = ['status', '=', 2];
        $map100[] = ['auto_cancel_receive_time', '<', time()];
        $list2    = $ShopOrderModel->where($map100)->select();
        if ($list2) {

            foreach ($list2 as $key => $order_info) {
                $pay_num       = $order_info['pay_num'];
                $refund_result = $WxBaseController->wx_refund($pay_num, $order_info['amount']);//退款测试&输入单号直接退
                if ($refund_result['code'] == 0) {
                    Log::write('订单退款失败：' . $pay_num);
                    Log::write($refund_result['msg']);
                }
            }


            //更新订单状态
            $ShopOrderModel->where($map100)->strict(false)->update([
                'status'      => 10,
                'cancel_receive_time' => time(),
                'update_time' => time(),
            ]);
        }


        echo("自动取消订单,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 自动完成订单
     */
    public function operation_accomplish_order()
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //商城订单   (ps:InitModel)
        $InitController = new InitController();//基础接口


        $map   = [];
        $map[] = ['auto_accomplish_time', '<', time()];
        $map[] = ['status', '=', 4];

        $list = $ShopOrderModel->where($map)->field('id,order_num')->select();
        foreach ($list as $k => $order_info) {
            //这里处理订单完成后的逻辑
            //$InitController->sendShopOrderAccomplish($order_info['order_num']);
        }

        $ShopOrderModel->where($map)->strict(false)->update([
            'status'          => 8,
            'accomplish_time' => time(),
            'update_time'     => time(),
        ]);


        echo("自动取消订单,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 更新vip状态
     */
    public function operation_vip()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理

        //操作vip   vip_time vip到期时间
        //$MemberModel->where('vip_time', '<', time())->update(['is_vip' => 0]);
        echo("更新vip状态,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 将公众号的official_openid存入member表中
     */
    public function update_official_openid()
    {
        $gzh_list = Db::name('member_gzh')->select();
        foreach ($gzh_list as $k => $v) {
            Db::name('member')->where('unionid', '=', $v['unionid'])->update(['official_openid' => $v['openid']]);
        }

        echo("将公众号的official_openid存入member表中,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }

}