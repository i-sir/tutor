<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"TeacherOrder",
 *     "name_underline"          =>"teacher_order",
 *     "controller_name"         =>"TeacherOrder",
 *     "table_name"              =>"teacher_order",
 *     "remark"                  =>"订单管理"
 *     "api_url"                 =>"/api/wxapp/teacher_order/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2025-09-03 10:05:20",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\TeacherOrderController();
 *     "test_environment"        =>"http://tutor.ikun:9090/api/wxapp/teacher_order/index",
 *     "official_environment"    =>"https://xcxkf159.aubye.com/api/wxapp/teacher_order/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class TeacherOrderController extends AuthController
{

    //public function initialize(){
    //	//订单管理
    //	parent::initialize();
    //}


    /**
     * 默认接口
     * /api/wxapp/teacher_order/index
     * https://xcxkf159.aubye.com/api/wxapp/teacher_order/index
     */
    public function index()
    {
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)

        $result = [];

        $this->success('订单管理-接口请求成功', $result);
    }


    /**
     * 订单管理 列表
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/find_order_list",
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
     *
     *
     *
     *
     *
     *
     *
     *    @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="状态:1待付款,2待接单,4待上课,8已完成,10已取消,12退款申请,14退款不通过,16退款通过",
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
     *    @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="上课方式:1线上,2线下",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Parameter(
     *         name="is_teacher",
     *         in="query",
     *         description="true 教师订单列表",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
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
     *     @OA\Parameter(
     *         name="is_paginate",
     *         in="query",
     *         description="false=分页(不传默认分页),true=不分页",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/find_order_list
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/find_order_list
     *   api:  /wxapp/teacher_order/find_order_list
     *   remark_name: 订单管理 列表
     *
     */
    public function find_order_list()
    {
        $this->checkAuth();

        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ['id', '>', 0];
        if (empty($params['is_teacher'])) $where[] = ['user_id', '=', $this->user_id];
        if ($params['is_teacher']) $where[] = ['teacher_id', '=', $this->teacher_id];
        if ($params["keyword"]) $where[] = ["order_num", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];
        if ($params['type']) $where[] = ['type', '=', $params['type']];


        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $params["field"]         = "*";//过滤字段
        if ($params['is_paginate']) $result = $TeacherOrderInit->get_list($where, $params);
        if (empty($params['is_paginate'])) $result = $TeacherOrderInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 订单管理 详情
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/find_order",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号  二选一",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/find_order
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/find_order
     *   api:  /wxapp/teacher_order/find_order
     *   remark_name: 订单管理 详情
     *
     */
    public function find_order()
    {
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理    (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $TeacherOrderInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 获取价格
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/get_amount",
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
     *
     *
     *    @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="上课方式:1线上,2线下",
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
     *    @OA\Parameter(
     *         name="teacher_id",
     *         in="query",
     *         description="教师id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="address_id",
     *         in="query",
     *         description="地址id",
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
     *
     *
     *    @OA\Parameter(
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
     *    @OA\Parameter(
     *         name="is_package",
     *         in="query",
     *         description="套餐下单:1是,2否",
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
     *         name="duration",
     *         in="query",
     *         description="课程时长 文字",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="grade_name",
     *         in="query",
     *         description="年级 文字",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="course_name",
     *         in="query",
     *         description="科目 文字",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/get_amount
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/get_amount
     *   api:  /wxapp/teacher_order/get_amount
     *   remark_name: 获取价格
     *
     */
    public function get_amount()
    {
        $this->checkAuth();

        $TeacherOrderModel   = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $TeacherModel        = new \initmodel\TeacherModel(); //教师管理   (ps:InitModel)
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)
        $ShopAddressModel    = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)


        /** 获取参数 **/
        $params = $this->request->param();

        $amount         = 0;//实际支付金额
        $freight_amount = 0;//路费
        $coupon_amount  = 0;//优惠金额
        $goods_amount   = 0;//课程费用


        //教师信息
        $map          = [];
        $map[]        = ['id', '=', $params['teacher_id']];
        $teacher_info = $TeacherModel->where($map)->find();
        if (empty($teacher_info)) $this->error("暂无此教师信息");


        //地址信息
        if ($params['type'] == 2) {
            $map100       = [];
            $map100[]     = ['id', '=', $params['address_id']];
            $address_info = $ShopAddressModel->where($map100)->find();
            //if (empty($address_info)) $this->error("暂无此地址信息");

            if ($address_info) {
                $insert['km_price'] = $teacher_info['km_price'];
                $insert['km']       = $this->getDistance($teacher_info['receive_lng'], $teacher_info['receive_lat'], $address_info['lng'], $address_info['lat']);
                $freight_amount     = $insert['km'] * $teacher_info['km_price'];
            }
        }


        //优惠券信息
        if ($params['coupon_id']) {
            $coupon_info = $ShopCouponUserModel->where('id', '=', $params['coupon_id'])->find();
            if (empty($coupon_info) || $coupon_info['used'] != 1) $this->error('优惠券信息错误');
            $coupon_amount = $coupon_info['amount'];
        }


        if ($params['is_package'] == 1) {
            //套餐
            $package_discount = cmf_config('package_discount'); //下套餐享折扣(%)
            $goods_amount     = $teacher_info['package_price'] * ($package_discount / 100);

        } else {
            //课时
            if ($params['type'] == 1) {
                //线上
                $goods_amount = ($teacher_info['online_price'] * (float)$params['duration']) * 2;

            } elseif ($params['type'] == 2) {
                //线下
                $goods_amount = ($teacher_info['offline_price'] * (float)$params['duration']) * 2;
            }
        }


        //所需支付金额
        $insert['amount']         = round($goods_amount + $freight_amount - $coupon_amount, 2);
        $insert['freight_amount'] = round($freight_amount, 2);
        $insert['goods_amount']   = round($goods_amount, 2);
        $insert['coupon_amount']  = round($coupon_amount, 2);


        $this->success('计算成功!', $insert);
    }


    /**
     * 下单
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/add_order",
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
     *
     *
     *    @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="上课方式:1线上,2线下",
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
     *    @OA\Parameter(
     *         name="teacher_id",
     *         in="query",
     *         description="教师id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="address_id",
     *         in="query",
     *         description="地址id",
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
     *
     *
     *    @OA\Parameter(
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
     *    @OA\Parameter(
     *         name="is_package",
     *         in="query",
     *         description="套餐下单:1是,2否",
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
     *         name="duration",
     *         in="query",
     *         description="课程时长 文字",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="grade_name",
     *         in="query",
     *         description="年级 文字",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="course_name",
     *         in="query",
     *         description="科目 文字",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/add_order
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/add_order
     *   api:  /wxapp/teacher_order/add_order
     *   remark_name: 下单
     *
     */
    public function add_order()
    {
        $this->checkAuth();

        // 启动事务
        Db::startTrans();


        $TeacherOrderModel   = new \initmodel\TeacherOrderModel();
        $TeacherModel        = new \initmodel\TeacherModel();
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel();
        $ShopAddressModel    = new \initmodel\ShopAddressModel(); // 添加地址模型

        $params = $this->request->param();

        // 基础验证
        if (empty($params['teacher_id'])) $this->error("教师ID不能为空");

        if ($params['type'] == 2 && empty($params['address_id'])) $this->error("线下课程需要选择地址");


        // 教师信息
        $teacher_info = $TeacherModel->where('id', $params['teacher_id'])->find();
        if (empty($teacher_info)) $this->error("暂无此教师信息");


        // 初始化费用
        $freight_amount = 0;
        $coupon_amount  = 0;
        $goods_amount   = 0;
        $total_amount   = 0;

        // 地址信息处理（线下课程）
        $address_info = null;
        if ($params['type'] == 2) {
            $address_info = $ShopAddressModel->where('id', $params['address_id'])->find();
            if (empty($address_info)) $this->error("暂无此地址信息");


            // 计算距离和路费
            $distance = $this->getDistance(
                $teacher_info['receive_lng'],
                $teacher_info['receive_lat'],
                $address_info['lng'],
                $address_info['lat']
            );

            $freight_amount = $distance * $teacher_info['km_price'];

            // 地址信息
            $insert['km_price'] = $teacher_info['km_price'];
            $insert['km']       = $distance;
            $insert['username'] = $address_info['username'];
            $insert['phone']    = $address_info['phone'];
            $insert['province'] = $address_info['province'];
            $insert['city']     = $address_info['city'];
            $insert['county']   = $address_info['county'];
            $insert['address']  = $address_info['address'];
            $insert['lng']      = $address_info['lng'];
            $insert['lat']      = $address_info['lat'];
            $insert['lnglat']   = $address_info['lnglat'];
        }

        // 优惠券处理
        if (!empty($params['coupon_id'])) {
            $coupon_info = $ShopCouponUserModel->where('id', $params['coupon_id'])->find();
            if (empty($coupon_info) || $coupon_info['used'] != 1) $this->error('优惠券信息错误或已使用');

            // 核销优惠券
            $ShopCouponUserModel->where('id', $params['coupon_id'])->update([
                'used'        => 2,
                'update_time' => time()
            ]);
            $coupon_amount = $coupon_info['amount'];
        }

        // 计算课程费用
        if ($params['is_package'] == 1) {
            // 套餐费用
            $package_discount = cmf_config('package_discount');
            $goods_amount     = ($teacher_info['package_price'] * ($package_discount / 100));

            //检测是否对这个老师已经下单过,下单过不让下单了
            $map100           = [];
            $map100[]         = ['teacher_id', '=', $params['teacher_id']];
            $map100[]         = ['is_package', '=', 1];
            $map100[]         = ['user_id', '=', $this->user_id];
            $map100[]         = ['status', 'in', [2, 4, 8]];
            $is_package_order = $TeacherOrderModel->where($map100)->find();
            if ($is_package_order) $this->error('套餐仅限购一次!');

        } else {
            // 课时费用
            if ($params['type'] == 1) {
                $goods_amount = ($teacher_info['online_price'] * (float)$params['duration']) * 2;
            } elseif ($params['type'] == 2) {
                $goods_amount = ($teacher_info['offline_price'] * (float)$params['duration']) * 2;
            }
        }

        // 构建订单数据
        $insert['user_id']     = $this->user_id;
        $insert['openid']      = $this->openid;
        $insert['order_num']   = $this->get_num_only();
        $insert['type']        = $params['type'];
        $insert['teacher_id']  = $params['teacher_id'];
        $insert['course_name'] = $params['course_name'];
        $insert['grade_name']  = $params['grade_name'];
        $insert['address_id']  = $params['address_id'] ?? 0;
        $insert['coupon_id']   = $params['coupon_id'] ?? 0;
        $insert['is_package']  = $params['is_package'] ?? 0;
        $insert['duration']    = $params['duration'] ?? 0;

        // 金额计算
        $insert['total_amount']   = round($goods_amount + $freight_amount, 2);
        $insert['goods_amount']   = round($goods_amount, 2);
        $insert['freight_amount'] = round($freight_amount, 2);
        $insert['coupon_amount']  = round($coupon_amount, 2);
        $insert['amount']         = round($goods_amount + $freight_amount - $coupon_amount, 2);
        $insert['create_time']    = time();
        //单位/分钟
        $order_automatic_cancellation_time = cmf_config('order_automatic_cancellation_time');
        $insert['auto_cancel_time']        = time() + $order_automatic_cancellation_time * 60;


        // 保存订单
        $result = $TeacherOrderModel->strict(false)->insert($insert);
        if (!$result) $this->error('失败请重试!');


        // 提交事务
        Db::commit();


        $this->success('下单成功,请支付!', [
            'order_type' => 10,
            'order_num'  => $insert['order_num'],
            'amount'     => $insert['amount']
        ]);


    }


    /**
     * 老师接单
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/receive_order",
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号  二选一",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/receive_order
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/receive_order
     *   api:  /wxapp/teacher_order/receive_order
     *   remark_name: 老师接单
     *
     */
    public function receive_order()
    {
        $this->checkAuth(2);
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理    (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        //查新订单信息
        $order_info = $TeacherOrderModel->where($where)->find();
        if (empty($order_info)) $this->error("暂无数据");
        if ($order_info['status'] != 2) $this->error("订单状态错误");


        //更新订单信息
        $update['status']       = 4;
        $update['receive_time'] = time();
        $update['update_time']  = time();
        $result                 = $TeacherOrderModel->where($where)->strict(false)->update($update);
        if (!$result) $this->error("操作失败!");


        $this->success("操作成功!");
    }


    /**
     * 老师取消接单
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/cancel_receive_order",
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号  二选一",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/cancel_receive_order
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/cancel_receive_order
     *   api:  /wxapp/teacher_order/cancel_receive_order
     *   remark_name: 老师取消接单
     *
     */
    public function cancel_receive_order()
    {
        $this->checkAuth(2);
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理    (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $WxBaseController  = new WxBaseController();//微信基础类

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        //查新订单信息
        $order_info = $TeacherOrderModel->where($where)->find();
        if (empty($order_info)) $this->error("暂无数据");
        if ($order_info['status'] != 2) $this->error("订单状态错误");


        //退款
        $refund_result = $WxBaseController->wx_refund($order_info['pay_num'], $order_info['amount']);//退款测试&输入单号直接退
        if ($refund_result['code'] == 0) $this->error($refund_result['msg']);


        //更新订单信息
        $update['status']              = 10;
        $update['cancel_receive_time'] = time();
        $update['update_time']         = time();
        $result                        = $TeacherOrderModel->where($where)->strict(false)->update($update);
        if (!$result) $this->error("操作失败!");


        $this->success("操作成功!");
    }


    /**
     * 完成订单
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/accomplish_order",
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号  二选一",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/accomplish_order
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/accomplish_order
     *   api:  /wxapp/teacher_order/accomplish_order
     *   remark_name: 完成订单
     *
     */
    public function accomplish_order()
    {
        $this->checkAuth();
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理    (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $InitController    = new InitController();

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        //查新订单信息.
        $order_info = $TeacherOrderModel->where($where)->find();
        if (empty($order_info)) $this->error("暂无数据");
        if ($order_info['status'] != 4) $this->error("订单状态错误");


        //更新订单信息
        $update['status']          = 8;
        $update['accomplish_time'] = time();
        $update['update_time']     = time();
        $result                    = $TeacherOrderModel->where($where)->strict(false)->update($update);
        if (!$result) $this->error("操作失败!");


        //发佣金
        $InitController->sendTeacherOrderAccomplish($order_info['order_num']);


        $this->success("操作成功!");
    }


    /**
     * 评论订单  (订单已完成并且 is_comment==2 可评价)
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/comment_order",
     *
     *
     *
     *     @OA\Parameter(
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
     *  @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号 或 id二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 或 订单号二选一",
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
     *         name="content",
     *         in="query",
     *         description="评论内容",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="images",
     *         in="query",
     *         description="图片数组",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="star",
     *         in="query",
     *         description="星级1-5",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://shop_template.ikun:9090/api/wxapp/teacher_order/comment_order
     *   official_environment: http://shop_template.com/api/wxapp/teacher_order/comment_order
     *   api: /wxapp/teacher_order/comment_order
     *   remark_name: 评论订单
     *
     */
    public function comment_order()
    {
        $this->checkAuth();
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $BaseCommentModel  = new \initmodel\BaseCommentModel(); //评价   (ps:InitModel)


        $params = $this->request->param();

        $where   = [];
        $where[] = ['user_id', '=', $this->user_id];
        if ($params['id']) $where[] = ['id', '=', $params['id']];
        if ($params['order_num']) $where[] = ['order_num', '=', $params['order_num']];

        //取消订单
        $order_info = $TeacherOrderModel->where($where)->find();
        if (empty($order_info)) $this->error('暂无数据!');
        if ($order_info['is_comment'] == 1) $this->error('已评价!');
        $TeacherOrderModel->where($where)->strict(false)->update([
            'is_comment'   => 1,
            'update_time'  => time(),
            'comment_time' => time(),
        ]);


        $BaseCommentModel->strict(false)->insert([
            'type'        => 'teacher',
            'user_id'     => $order_info['user_id'],
            'pid'         => $order_info['teacher_id'],
            'ext_id'      => $order_info['id'],
            'star'        => $params['star'],
            'content'     => $params['content'],
            'images'      => $this->setParams($params['images']),
            'create_time' => time(),
        ]);


        $this->success("评价成功");
    }


    /**
     * 申请退款
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/refund_order",
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号  二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Parameter(
     *         name="refund_why",
     *         in="query",
     *         description="退款原因",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="refund_describe",
     *         in="query",
     *         description="退款说明",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="refund_amount",
     *         in="query",
     *         description="退款金额",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/refund_order
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/refund_order
     *   api:  /wxapp/teacher_order/refund_order
     *   remark_name: 申请退款
     *
     */
    public function refund_order()
    {
        $this->checkAuth();
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理    (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        //查新订单信息
        $order_info = $TeacherOrderModel->where($where)->find();
        if (empty($order_info)) $this->error("暂无数据");
        if (in_array($order_info['status'], [1, 10, 12, 14, 16])) $this->error("订单状态错误");
        if ($params['refund_amount'] > $order_info['amount'] || $params['refund_amount'] < 0) $this->error("退款金额错误");


        //更新订单信息
        $update['refund_why']      = $params['refund_why'];
        $update['refund_describe'] = $params['refund_describe'];
        $update['refund_amount']   = $params['refund_amount'];
        $update['status']          = 12;
        $update['refund_time']     = time();
        $update['update_time']     = time();
        $result                    = $TeacherOrderModel->where($where)->strict(false)->update($update);
        if (!$result) $this->error("操作失败!");


        $this->success("操作成功!");


    }


    /**
     * 同意退款
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/refund_pass_order",
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号  二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/refund_pass_order
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/refund_pass_order
     *   api:  /wxapp/teacher_order/refund_pass_order
     *   remark_name: 同意退款
     *
     */
    public function refund_pass_order()
    {
        $this->checkAuth(2);

        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理    (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $WxBaseController  = new WxBaseController();//微信基础类

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        //查新订单信息
        $order_info = $TeacherOrderModel->where($where)->find();
        if (empty($order_info)) $this->error("暂无数据");
        if ($order_info['status'] != 12) $this->error("订单状态错误");


        //退款
        $refund_result = $WxBaseController->wx_refund($order_info['pay_num'], $order_info['refund_amount'], $order_info['amount']);//退款测试&输入单号直接退
        if ($refund_result['code'] == 0) $this->error($refund_result['msg']);


        //更新订单信息
        $update['status']           = 16;
        $update['refund_pass_time'] = time();
        $update['update_time']      = time();
        $result                     = $TeacherOrderModel->where($where)->strict(false)->update($update);
        if (!$result) $this->error("操作失败!");


        $this->success("操作成功!");

    }


    /**
     * 拒绝退款
     * @OA\Post(
     *     tags={"订单管理"},
     *     path="/wxapp/teacher_order/refund_refuse_order",
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号  二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="refund_refuse",
     *         in="query",
     *         description="退款理由",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher_order/refund_refuse_order
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher_order/refund_refuse_order
     *   api:  /wxapp/teacher_order/refund_refuse_order
     *   remark_name: 拒绝退款
     *
     */
    public function refund_refuse_order()
    {
        $this->checkAuth(2);

        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理    (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        //查新订单信息
        $order_info = $TeacherOrderModel->where($where)->find();
        if (empty($order_info)) $this->error("暂无数据");
        if ($order_info['status'] != 12) $this->error("订单状态错误");


        //更新订单信息
        $update['refund_refuse']      = $params['refund_refuse'];
        $update['status']             = 14;
        $update['refund_refuse_time'] = time();
        $update['update_time']        = time();
        $result                       = $TeacherOrderModel->where($where)->strict(false)->update($update);
        if (!$result) $this->error("操作失败!");


        $this->success("操作成功!");


    }


    /**
     * 求两个经纬度之间的距离
     * @param float $lng1 经度1
     * @param float $lat1 纬度1
     * @param float $lng2 经度2
     * @param float $lat2 纬度2
     * @return float 距离 (单位：km)
     * @edit www.jbxue.com
     **/
    public function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        // 将角度转为弧度
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);

        // 计算两点之间的差值
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        // 使用 Haversine 公式计算距离
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) +
                cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378137; // 地球半径为6378137米

        return round($s / 1000, 2); // 返回距离，单位为千米
    }


}
