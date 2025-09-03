<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"ShopAddress",
 *     "controller_name"         =>"ShopAddress",
 *     "table_name"              =>"shop_address",
 *     "remark"                  =>"地址管理"
 *     "api_url"                 =>"/api/wxapp/shop_address/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2023-12-16 11:34:00",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\ShopAddressController();
 *     "test_environment"        =>"http://tutor.ikun:9090/api/wxapp/shop_address/index",
 *     "official_environment"    =>"https://xcxkf159.aubye.com/api/wxapp/shop_address/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class ShopAddressController extends AuthController
{


    public function initialize()
    {
        //地址管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/shop_address/index
     * https://xcxkf159.aubye.com/api/wxapp/shop_address/index
     */
    public function index()
    {
        $this->success("地址管理-接口请求成功");
    }


    /**
     * 地址列表
     * @OA\Post(
     *     tags={"地址管理"},
     *     path="/wxapp/shop_address/find_address_list",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/shop_address/find_address_list
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/shop_address/find_address_list
     *   api:  /wxapp/shop_address/find_address_list
     *   remark_name: 地址列表
     *
     */
    public function find_address_list()
    {
        $this->checkAuth();

        $ShopAddressInit = new \init\ShopAddressInit();//地址管理   (ps:InitController)


        $where   = [];
        $where[] = ["user_id", "=", $this->user_id];


        $result = $ShopAddressInit->get_list($where, "is_default,id desc");
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 地址详情
     * @OA\Post(
     *     tags={"地址管理"},
     *     path="/wxapp/shop_address/find_address",
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
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="如不穿,返回自己默认地址",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/shop_address/find_address
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/shop_address/find_address
     *   api:  /wxapp/shop_address/find_address
     *   remark_name: 地址详情
     *
     *
     */
    public function find_address()
    {
        $this->checkAuth();
        $ShopAddressInit = new \init\ShopAddressInit();//地址管理   (ps:InitController)
        $params          = $this->request->param();

        $where   = [];
        $where[] = ["user_id", "=", $this->user_id];
        if ($params["id"]) $where[] = ["id", "=", $params["id"]];//如id存在查找对应地址
        if (empty($params["id"])) $where[] = ["is_default", "=", 1];//如地址为空,返回默认地址


        $result = $ShopAddressInit->get_find($where);
        if (empty($result)) {
            //如果默认地址被删除,返回最后添加的地址
            $where   = [];
            $where[] = ["user_id", "=", $this->user_id];
            $result  = $ShopAddressInit->get_find($where, 'id desc');
        }

        $this->success("请求成功!", $result);
    }


    /**
     * 地址管理 编辑&添加&删除&默认
     * @OA\Post(
     *     tags={"地址管理"},
     *     path="/wxapp/shop_address/edit_address",
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
     *         name="province",
     *         in="query",
     *         description="省",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="市",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="county",
     *         in="query",
     *         description="区",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="address",
     *         in="query",
     *         description="详细地址",
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
     *         name="province_code",
     *         in="query",
     *         description="省code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="city_code",
     *         in="query",
     *         description="市code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="county_code",
     *         in="query",
     *         description="区code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="名字",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="电话",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="is_default",
     *         in="query",
     *         description="1默认地址,2非默认",
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
     *         name="operation",
     *         in="query",
     *         description="add添加(无需传id) edit编辑(传id) delete删除(删除id可为数组) default默认(传id,添加时不用传) ",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/shop_address/edit_address
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/shop_address/edit_address
     *   api:  /wxapp/shop_address/edit_address
     *   remark_name: 地址管理 编辑&添加&删除&默认
     *
     *
     */
    public function edit_address()
    {
        $this->checkAuth();

        $ShopAddressInit  = new \init\ShopAddressInit();//地址管理    (ps:InitController)
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理   (ps:InitModel)


        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;


        //删除
        if ($params["operation"] == "delete") {
            if (empty($params["id"])) $this->error("错误!");

            $map100       = [];
            $map100[]     = ["user_id", "=", $this->user_id];
            $map100[]     = ["id", "=", $params["id"]];//如id存在查找对应地址
            $address_info = $ShopAddressInit->get_find($map100);
            if (empty($address_info)) $this->error("非法操作!");


            $result = $ShopAddressInit->delete_post($params["id"]);
            if (empty($result)) $this->error("删除失败!");
            $this->success("删除成功!");
        }


        //默认地址
        if ($params["operation"] == "default" || $params['is_default'] == 1) {
            $map   = [];
            $map[] = ["user_id", "=", $this->user_id];
            $ShopAddressModel
                ->where($map)
                ->strict(false)
                ->update([
                    "is_default" => 2,
                ]);
            $params["update_time"] = time();
            if ($params["id"]) $msg = "编辑成功";
            if (empty($params["id"])) $msg = "添加成功";
        }

        //添加
        if ($params["operation"] == "add") {
            $msg                   = "添加成功";
            $params["create_time"] = time();
        }

        //编辑
        if ($params["operation"] == "edit") {
            if (empty($params["id"])) $this->error("错误!");
            $map100       = [];
            $map100[]     = ["user_id", "=", $this->user_id];
            $map100[]     = ["id", "=", $params["id"]];//如id存在查找对应地址
            $address_info = $ShopAddressInit->get_find($map100);
            if (empty($address_info)) $this->error("非法操作!");


            $msg                   = "编辑成功";
            $params["update_time"] = time();
        }

        //提交数据
        $result = $ShopAddressInit->api_edit_post($params);
        if (empty($result)) $this->error("失败请重试!");

        $this->success($msg);
    }


}
