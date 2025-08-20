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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/wx_pay_mini
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/wx_pay_mini
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

        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];


        //订单支付
        if ($params['order_type'] == 10) {
            //修改订单,支付类型
            $ShopOrderModel->where($map)->strict(false)->update([
                'pay_type'    => 1,
                'update_time' => time(),
            ]);
            $order_info = $ShopOrderModel->where($map)->find();
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
     * 余额支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/balance_pay",
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/balance_pay
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/balance_pay
     *   api: /wxapp/order_pay/balance_pay
     *   remark_name: 余额支付
     *
     */
    public function balance_pay()
    {
        $this->checkAuth();

        $params = $this->request->param();
        $openid = $this->user_info['openid'];

        $Pay              = new PayController();
        $OrderPayModel    = new \initmodel\OrderPayModel();
        $ShopOrderModel   = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $NotifyController = new NotifyController();

        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];


        //订单支付
        if ($params['order_type'] == 10) {
            //修改订单,支付类型
            $ShopOrderModel->where($map)->strict(false)->update([
                'pay_type'    => 2,
                'update_time' => time(),
            ]);
            $order_info = $ShopOrderModel->where($map)->find();
        }


        if (empty($order_info)) $this->error('订单不存在');
        if ($order_info['amount'] < 0.01) $this->error('订单错误');
        if ($order_info['status'] != 1) $this->error('订单状态错误');


        //订单金额&&订单号
        $amount    = $order_info['amount'] ?? 0.01;
        $order_num = $order_info['order_num'] ?? cmf_order_sn();

        //检测余额是否充足
        if ($this->user_info['balance'] < $amount) $this->error('余额不足');


        //支付记录插入一条记录
        $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['order_type'], 2, $order_info['id']);


        $remark = "操作人[用户ID:{$this->user_info['id']};昵称:{$this->user_info['nickname']};手机号:{$this->user_info['phone']}];操作说明[支付订单:{$order_num};金额:{$amount}];操作类型[下单扣除余额];";//备注
        AssetModel::decAsset('用户扣除余额,支付订单 [100]', [
            'operate_type'  => 'balance',//操作类型，balance|point ...
            'identity_type' => 'member',//身份类型，member| ...
            'user_id'       => $this->user_id,
            'price'         => $amount,
            'order_num'     => $order_num,
            'order_type'    => 100,
            'content'       => '支付订单',
            'remark'        => $remark,
            'order_id'      => $order_info['id'],
        ]);


        //余额 支付回调
        $NotifyController->balancePayNotify($pay_num);


        //将订单号,支付单号返回给前端
        $result['order_num'] = $order_num;
        $result['pay_num']   = $pay_num;

        $this->success('支付成功', $result);
    }


    /**
     * 积分支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/point_pay",
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/point_pay
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/point_pay
     *   api: /wxapp/order_pay/point_pay
     *   remark_name: 积分支付
     *
     */
    public function point_pay()
    {
        $this->checkAuth();

        $params = $this->request->param();
        $openid = $this->user_info['openid'];

        $Pay              = new PayController();
        $OrderPayModel    = new \initmodel\OrderPayModel();
        $ShopOrderModel   = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $NotifyController = new NotifyController();

        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];


        //订单支付
        if ($params['order_type'] == 10) {
            //修改订单,支付类型
            $ShopOrderModel->where($map)->strict(false)->update([
                'pay_type'    => 3,
                'update_time' => time(),
            ]);
            $order_info = $ShopOrderModel->where($map)->find();
        }


        if (empty($order_info)) $this->error('订单不存在');
        if ($order_info['amount'] < 0.01) $this->error('订单错误');
        if ($order_info['status'] != 1) $this->error('订单状态错误');


        //订单金额&&订单号
        $amount    = $order_info['amount'] ?? 0.01;
        $order_num = $order_info['order_num'] ?? cmf_order_sn();

        //检测积分是否充足
        if ($this->user_info['point'] < $amount) $this->error('积分不足');


        //支付记录插入一条记录
        $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['order_type'], 2, $order_info['id']);


        $remark = "操作人[用户ID:{$this->user_info['id']};昵称:{$this->user_info['nickname']};手机号:{$this->user_info['phone']}];操作说明[支付订单:{$order_num};金额:{$amount}];操作类型[下单扣除余额];";//备注
        AssetModel::decAsset('用户扣除积分,支付订单 [200]', [
            'operate_type'  => 'point',//操作类型，balance|point ...
            'identity_type' => 'member',//身份类型，member| ...
            'user_id'       => $this->user_id,
            'price'         => $amount,
            'order_num'     => $order_num,
            'order_type'    => 200,
            'content'       => '支付订单',
            'remark'        => $remark,
            'order_id'      => $order_info['id'],
        ]);


        //积分 支付回调
        $NotifyController->pointPayNotify($pay_num);


        //将订单号,支付单号返回给前端
        $result['order_num'] = $order_num;
        $result['pay_num']   = $pay_num;

        $this->success('支付成功', $result);
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/free_pay
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/free_pay
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

        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];


        //订单支付
        if ($params['order_type'] == 10) {
            //修改订单,支付类型
            $ShopOrderModel->where($map)->strict(false)->update([
                'pay_type'    => 6,
                'update_time' => time(),
            ]);
            $order_info = $ShopOrderModel->where($map)->find();
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


    // 测试用 https://lscs001.jscxkf.net/api/wxapp/order_pay/wx_pay_mini2
    public function wx_pay_mini2()
    {

        $params = $this->request->param();
        $openid = $this->user_info['mini_openid'];


        $Pay = new PayController();


        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];


        //查询订单信息
        if ($params['order_type'] == 10) {
            $order_info = null;
        }


        //订单金额&&订单号
        $amount = $order_info['amount'] ?? 0.01;

        //支付记录插入一条记录
        $pay_num = cmf_order_sn();
        $openid  = 'o46yr4hMXOae1P0ZAfMa9Z9HtL3Y';
        $result  = $Pay->wx_pay_mini($pay_num, $amount, $openid);


        if ($result['code'] != 1) $this->error($result['msg']);
        $this->success('请求成功', $result['data']);
    }


    /**
     * 微信公众号支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/wx_pay_mp",
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/wx_pay_mp
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/wx_pay_mp
     *   api: /wxapp/order_pay/wx_pay_mp
     *   remark_name: 微信公众号支付
     *
     */
    public function wx_pay_mp()
    {
        $this->checkAuth();

        $Pay            = new PayController();
        $OrderPayModel  = new \initmodel\OrderPayModel();
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)


        $params = $this->request->param();
        $openid = $this->user_info['official_openid'];


        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];

        //订单支付
        if ($params['order_type'] == 10) {
            //修改订单,支付类型
            $ShopOrderModel->where($map)->strict(false)->update([
                'pay_type'    => 1,
                'update_time' => time(),
            ]);
            $order_info = $ShopOrderModel->where($map)->find();
        }


        if (empty($order_info)) $this->error('订单不存在');
        if ($order_info['amount'] < 0.01) $this->error('订单错误');
        if ($order_info['status'] != 1) $this->error('订单状态错误');


        //订单金额&&订单号
        $amount    = $order_info['amount'] ?? 0.01;
        $order_num = $order_info['order_num'] ?? cmf_order_sn();

        //支付记录插入一条记录
        $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['order_type'], 1, $order_info['id']);
        $result  = $Pay->wx_pay_mp($pay_num, $amount, $openid);


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
     * 微信App支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/wx_pay_app",
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/wx_pay_app
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/wx_pay_app
     *   api: /wxapp/order_pay/wx_pay_app
     *   remark_name: 微信App支付
     *
     */
    public function wx_pay_app()
    {
        $Pay       = new PayController();
        $order_num = cmf_order_sn();
        $result    = $Pay->wx_pay_app($order_num, 0.01);
        if ($result['code'] != 1) $this->error($result['msg']);
        $this->success('请求成功', $result['data']);
    }


    /**
     *   微信订单退款 测试
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/wx_pay_refund_test
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/wx_pay_refund_test
     *   api: /wxapp/order_pay/wx_pay_refund_test
     *   remark_name: 微信订单退款
     *
     */
    public function wx_pay_refund_test()
    {
        $params = $this->request->param();
        //给用户退款
        $WxBaseController = new WxBaseController();//微信基础类
        $OrderPayModel    = new \initmodel\OrderPayModel();//支付记录表

        $map      = [];
        $map[]    = ['order_num', '=', $params['order_num']];//实际订单号
        $map[]    = ['status', '=', 2];//已支付
        $pay_info = $OrderPayModel->where($map)->find();//支付记录表

        $amount        = $pay_info['amount'];//支付金额&全部退款
        $refund_amount = $pay_info['amount'];//支付金额&全部退款
        $pay_num       = $pay_info['pay_num'];//支付单号


        $pay_num       = '5550250512336893783811';
        $refund_amount = '0.02';//退款金额
        $amount        = '0.09';//总金额

        $refund_result = $WxBaseController->wx_refund($pay_num, $refund_amount, $amount);//退款测试&输入单号直接退
        if ($refund_result['code'] == 0) $this->error($refund_result['msg']);


        $this->success('请求成功', $refund_result);
    }


    /**
     * 支付宝h5支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/ali_pay_wap",
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
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/ali_pay_wap
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/ali_pay_wap
     *   api: /wxapp/order_pay/ali_pay_wap
     *   remark_name: 支付宝h5支付
     *
     */
    public function ali_pay_wap()
    {
        $Pay = new PayController();

        $OrderPayModel  = new \initmodel\OrderPayModel();
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)


        $params = $this->request->param();
        $openid = $this->openid;


        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];

        //订单支付
        if ($params['order_type'] == 10) {
            //修改订单,支付类型
            $ShopOrderModel->where($map)->strict(false)->update([
                'pay_type'    => 4,
                'update_time' => time(),
            ]);
            $order_info = $ShopOrderModel->where($map)->find();
        }

        if (empty($order_info)) $this->error('订单不存在');
        if ($order_info['amount'] < 0.01) $this->error('订单错误');
        if ($order_info['status'] != 1) $this->error('订单状态错误');


        //订单金额&&订单号
        $amount    = $order_info['amount'] ?? 0.01;
        $order_num = $order_info['order_num'] ?? cmf_order_sn();


        //支付记录插入一条记录
        $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['order_type'], 4, $order_info['id']);
        $result  = $Pay->ali_pay_wap($pay_num, $amount);
        if ($result['code'] != 1) $this->error($result['msg']);
        exit($result['data']);
        $this->success('请求成功', $result);
    }


    /**
     * 支付宝APP支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/ali_pay_app",
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/ali_pay_app
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/ali_pay_app
     *   api: /wxapp/order_pay/ali_pay_app
     *   remark_name: 支付宝APP支付
     *
     */
    public function ali_pay_app()
    {
        $Pay       = new PayController();
        $order_num = cmf_order_sn();
        $result    = $Pay->ali_pay_app($order_num, 0.01);
        if ($result['code'] != 1) $this->error($result['msg']);
        $this->success('请求成功', $result['data']);
    }


    /**
     * 支付宝订单退款
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/ali_pay_refund",
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
     * 	   @OA\Parameter(
     *         name="amount",
     *         in="query",
     *         description="amount",
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
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/ali_pay_refund
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/ali_pay_refund
     *   api: /wxapp/order_pay/ali_pay_refund
     *   remark_name: 支付宝订单退款
     *
     */
    public function ali_pay_refund()
    {
        $Pay       = new PayController();
        $order_num = $this->request->param('order_num');
        $amount    = $this->request->param('amount');
        $result    = $Pay->ali_pay_refund($order_num, $amount);
        if ($result['code'] != 1) $this->error($result['msg']);
        $this->success('请求成功', $result['data']);
    }


    /**
     * 支付宝转账
     * @return void
     *
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/ali_transfer_accounts
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/ali_transfer_accounts
     *   api: /wxapp/order_pay/ali_transfer_accounts
     *   remark_name: 支付宝转账
     *
     */
    public function ali_transfer_accounts()
    {
        $Pay = new PayController();

        $amount    = 0.1;//最少一毛钱
        $identity  = 18888888888;
        $name      = '11';
        $order_num = cmf_order_sn();
        $result    = $Pay->ali_pay_transfer($amount, $identity, $name, $order_num);


        if ($result['code'] != 1) $this->error($result['msg']);
        $this->success('请求成功', $result['data']);
    }


    /**
     * 微信转账  此方法已作废
     * @return void
     *
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/order_pay/wx_pay_transfer
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/order_pay/wx_pay_transfer
     *   api: /wxapp/order_pay/wx_pay_transfer
     *   remark_name: 微信转账
     *
     */
    public function wx_pay_transfer()
    {
        $Pay = new PayController();

        $amount = 0.1;//最少5毛钱
        $openid = $this->openid;
        $result = $Pay->wx_pay_transfer($amount, $openid);


        if ($result['code'] != 1) $this->error($result['msg']);
        $this->success('请求成功', $result['data']);
    }

}