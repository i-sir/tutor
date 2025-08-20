<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"ShopCoupon",
 *     "name_underline"          =>"shop_coupon",
 *     "controller_name"         =>"ShopCoupon",
 *     "table_name"              =>"shop_coupon",
 *     "remark"                  =>"优惠券"
 *     "api_url"                 =>"/api/wxapp/shop_coupon/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2025-02-21 15:10:22",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\ShopCouponController();
 *     "test_environment"        =>"http://lscs.ikun:9090/api/wxapp/shop_coupon/index",
 *     "official_environment"    =>"https://lscs001.jscxkf.net/api/wxapp/shop_coupon/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);

//需要处理定时任务1

class ShopCouponController extends AuthController
{
    //    public function initialize()
    //    {
    //        //优惠券
    //        parent::initialize();
    //    }


    /**
     * 默认接口
     * /api/wxapp/shop_coupon/index
     * https://lscs001.jscxkf.net/api/wxapp/shop_coupon/index
     */
    public function index()
    {
        $ShopCouponInit      = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel     = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)

        $result = [];


        $this->success('优惠券-接口请求成功', $result);
    }


    /**
     * 优惠券可领取 列表
     * @OA\Post(
     *     tags={"优惠券"},
     *     path="/wxapp/shop_coupon/find_coupon_list",
     *
     *
     *
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="(选填)关键字搜索",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/shop_coupon/find_coupon_list
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/shop_coupon/find_coupon_list
     *   api:  /wxapp/shop_coupon/find_coupon_list
     *   remark_name: 优惠券 列表
     *
     */
    public function find_coupon_list()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['status', '=', 1];
        $where[] = ['is_show', '=', 1];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $result                  = $ShopCouponInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 领取优惠券
     * @OA\Post(
     *     tags={"优惠券"},
     *     path="/wxapp/shop_coupon/add_coupon",
     *
     *
     *
     *     @OA\Parameter(
     *         name="coupon_id",
     *         in="query",
     *         description="优惠券id",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/shop_coupon/add_coupon
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/shop_coupon/add_coupon
     *   api:  /wxapp/shop_coupon/add_coupon
     *   remark_name: 领取优惠券
     *
     */
    public function add_coupon()
    {
        $this->checkAuth();
        $ShopCouponModel     = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //优惠券信息
        $coupon_info = $ShopCouponModel->where(['id' => $params['coupon_id']])->find();
        if (empty($coupon_info)) $this->error("优惠券不存在!");


        /** 查询条件 **/
        $map        = [];
        $map[]      = ['user_id', '=', $this->user_id];
        $map[]      = ['coupon_id', '=', $params['coupon_id']];
        $coupon_log = $ShopCouponUserModel->where($map)->find();
        if ($coupon_log) $this->error("您已领取过该优惠券!");


        //处理优惠券到期时间 && 按天计算
        if ($coupon_info['type'] == 2) $coupon_info['end_time'] = time() + (86400 * $coupon_info['day']);


        //生成优惠券码
        $code     = $this->get_num_only('code', 8, 2, 'Y', $ShopCouponUserModel);
        $qr_image = '';//二维码

        /** 领取优惠券 **/
        $result = $ShopCouponUserModel->strict(false)->insert([
            'user_id'     => $this->user_id,
            'coupon_id'   => $params['coupon_id'],
            'name'        => $coupon_info['name'],
            'full_amount' => $coupon_info['full_amount'],
            'amount'      => $coupon_info['amount'],
            'discount'    => $coupon_info['discount'],
            'type'        => $coupon_info['type'],
            'coupon_type' => $coupon_info['coupon_type'],
            'end_time'    => $coupon_info['end_time'],
            'code'        => $code,
            'qr_image'    => $qr_image,
            'start_time'  => time(),
            'create_time' => time(),
        ]);

        if (empty($result)) $this->error("失败请重试!");

        $this->success("领取成功!");
    }


    /**
     * 已领取优惠列表
     * @OA\Post(
     *     tags={"优惠券"},
     *     path="/wxapp/shop_coupon/my_coupon_list",
     *
     *
     *
     *     @OA\Parameter(
     *         name="amount",
     *         in="query",
     *         description="金额筛选",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/shop_coupon/my_coupon_list
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/shop_coupon/my_coupon_list
     *   api:  /wxapp/shop_coupon/my_coupon_list
     *   remark_name: 已领取优惠列表
     *
     */
    public function my_coupon_list()
    {
        $this->checkAuth();

        $ShopCouponUserInit = new \init\ShopCouponUserInit();//优惠券领取记录   (ps:InitController)

        $params = $this->request->param();

        $map   = [];
        $map[] = ['user_id', '=', $this->user_id];
        //筛选可使用优惠券
        if ($params['amount']) {
            $map[] = ['end_time', '>', time()];
            $map[] = ['used', '=', 1];
            $map[] = ['full_amount', '<=', $params['amount']];
        }

        $result = $ShopCouponUserInit->get_list_paginate($map, $params);

        if (empty($result)) $this->error("失败请重试!");

        $this->success("获取成功!", $result);
    }


    /**
     * 核销优惠券
     * @OA\Post(
     *     tags={"优惠券"},
     *     path="/wxapp/shop_coupon/verification_coupon",
     *
     *
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="优惠券记录id,二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="优惠券唯一编号,二选一",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/shop_coupon/verification_coupon
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/shop_coupon/verification_coupon
     *   api:  /wxapp/shop_coupon/verification_coupon
     *   remark_name: 核销优惠券
     *
     */
    public function verification_coupon()
    {
        $this->checkAuth();
        $params = $this->request->param();

        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)


        $map   = [];
        $map[] = ['used', '=', 1];
        if ($params['id']) $map[] = ['id', '=', $params['id']];
        if ($params['code']) $map[] = ['code', '=', $params['code']];
        $result = $ShopCouponUserModel->where($map)->update(['used' => 2, 'update_time' => time()]);
        if (empty($result)) $this->error("失败请重试!");

        $this->success("核销成功!");
    }


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
}
