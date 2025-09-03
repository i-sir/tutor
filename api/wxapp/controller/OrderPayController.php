<?php

namespace api\wxapp\controller;

use initmodel\AssetModel;
use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;

class OrderPayController extends AuthController
{

    //    public function initialize()
    //    {
    //        parent::initialize();//初始化方法
    //    }


    /**
     * 微信小程序支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/wx_pay_mini",
     *
     *
     * 	   @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="order_type",
     *         in="query",
     *         description="10商城,90充值余额",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/order_pay/wx_pay_mini
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/order_pay/wx_pay_mini
     *   api: /wxapp/order_pay/wx_pay_mini
     *   remark_name: 微信小程序支付
     *
     */
    public function wx_pay_mini()
    {
        $this->checkAuth();

        $params = $this->request->param();
        $openid = $this->user_info['mini_openid'];

        $Pay            = new PayController();
        $OrderPayModel  = new \initmodel\OrderPayModel();
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $TeacherOrderModel   = new \initmodel\TeacherOrderModel();

        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];


        //订单支付
        if ($params['order_type'] == 10) {
            //修改订单,支付类型
            $TeacherOrderModel->where($map)->strict(false)->update([
                'pay_type'    => 1,
                'update_time' => time(),
            ]);
            $order_info = $TeacherOrderModel->where($map)->find();
        }


        if (empty($order_info)) $this->error('订单不存在');
        if ($order_info['amount'] < 0.01) $this->error('订单错误');
        if ($order_info['status'] != 1) $this->error('订单状态错误');


        //订单金额&&订单号
        $amount    = $order_info['amount'] ?? 0.01;
        $order_num = $order_info['order_num'] ?? cmf_order_sn();

        //支付记录插入一条记录
        $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['order_type'], 1, $order_info['id']);
        $result  = $Pay->wx_pay_mini($pay_num, $amount, $openid);


        if ($result['code'] != 1) {
            if (strstr($result['msg'], '此商家的收款功能已被限制')) $this->error('支付失败,请联系客服!错误码:pay_limit');
            $this->error($result['msg']);
        }


        //将订单号,支付单号返回给前端
        $result['data']['order_num'] = $order_num;
        $result['data']['pay_num']   = $pay_num;

        $this->success('请求成功', $result['data']);
    }


    /**
     * 免费兑换
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/free_pay",
     *
     *
     * 	   @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="order_type",
     *         in="query",
     *         description="10商城,90充值余额",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/order_pay/free_pay
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/order_pay/free_pay
     *   api: /wxapp/order_pay/free_pay
     *   remark_name: 免费兑换
     *
     */
    public function free_pay()
    {
        $this->checkAuth();

        $params = $this->request->param();
        $openid = $this->user_info['openid'];

        $Pay              = new PayController();
        $OrderPayModel    = new \initmodel\OrderPayModel();
        $ShopOrderModel   = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $NotifyController = new NotifyController();
        $TeacherOrderModel   = new \initmodel\TeacherOrderModel();

        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];


        //订单支付
        if ($params['order_type'] == 10) {
            //修改订单,支付类型
            $TeacherOrderModel->where($map)->strict(false)->update([
                'pay_type'    => 6,
                'update_time' => time(),
            ]);
            $order_info = $TeacherOrderModel->where($map)->find();
        }


        if (empty($order_info)) $this->error('订单不存在');
        if ($order_info['amount'] < 0.01) $this->error('订单错误');
        if ($order_info['status'] != 1) $this->error('订单状态错误');


        //订单金额&&订单号
        $amount    = $order_info['amount'] ?? 0.01;
        $order_num = $order_info['order_num'] ?? cmf_order_sn();


        //支付记录插入一条记录
        $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['order_type'], 6, $order_info['id']);


        //积分 支付回调
        $NotifyController->freePayNotify($pay_num);


        //将订单号,支付单号返回给前端
        $result['order_num'] = $order_num;
        $result['pay_num']   = $pay_num;

        $this->success('支付成功', $result);
    }


}