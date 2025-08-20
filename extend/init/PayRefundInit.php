<?php

namespace init;

use plugins\weipay\lib\PayController;
use think\facade\Log;

class PayRefundInit
{

    /**
     * 订单退款
     * @param $order_num
     * @param $amount 退款金额
     * @param $total  订单总金额
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function wx_pay_refund_admin($order_num, $amount = 0, $total = 0)
    {

        //调用方法
        //         $pay       = new OrderPayController();
        //         $order_pay = $pay->wx_pay_refund_admin($result['order_num'],0,0);
        //         if (!isset($order_pay['amount'])) $this->error($order_pay['message']);


        //        $Pay    = new PayController();
        //        $res    = $Pay->wx_pay_refund($order_num, $amount, $total);
        //        $result = json_decode($res['data'], true);

        $result = time();
        Log::write('wx_pay_refund_admin');
        Log::write($result);


        return $result;
    }

}