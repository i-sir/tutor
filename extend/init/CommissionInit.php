<?php

namespace init;

/**
 * 处理分销规则
 */
class CommissionInit
{
 
    /**
     * 处理订单分销,加佣金
     * @param $order_num 订单号
     * @return void
     */
    public function order_commission_inc($order_num)
    {


        return true;
    }


    /**
     * 处理订单分销,扣除佣金
     * @param $order_num 订单号
     * @return void
     */
    public function order_commission_dec($order_num)
    {


        return true;
    }

}