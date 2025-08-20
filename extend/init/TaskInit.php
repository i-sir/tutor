<?php

namespace init;

use api\wxapp\controller\InitController;
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
     * 自动取消订单
     */
    public function operation_cancel_order()
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //商城订单   (ps:InitModel)
        $Pay            = new PayController();
        $OrderPayModel  = new \initmodel\OrderPayModel();


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
                }else{
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