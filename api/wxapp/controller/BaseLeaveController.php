<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"BaseLeave",
 *     "name_underline"          =>"base_leave",
 *     "controller_name"         =>"BaseLeave",
 *     "table_name"              =>"base_leave",
 *     "remark"                  =>"投诉建议"
 *     "api_url"                 =>"/api/wxapp/base_leave/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2025-06-11 10:54:52",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\BaseLeaveController();
 *     "test_environment"        =>"http://lscs.ikun:9090/api/wxapp/base_leave/index",
 *     "official_environment"    =>"https://lscs001.jscxkf.net/api/wxapp/base_leave/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class BaseLeaveController extends AuthController
{

    //public function initialize(){
    //	//投诉建议
    //	parent::initialize();
    //}


    /**
     * 默认接口
     * /api/wxapp/base_leave/index
     * https://lscs001.jscxkf.net/api/wxapp/base_leave/index
     */
    public function index()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)

        $result = [];

        $this->success('投诉建议-接口请求成功', $result);
    }


    /**
     * 投诉建议 列表
     * @OA\Post(
     *     tags={"投诉建议"},
     *     path="/wxapp/base_leave/find_leave_list",
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
     *         description="状态:1待处理,2已处理",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/base_leave/find_leave_list
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/base_leave/find_leave_list
     *   api:  /wxapp/base_leave/find_leave_list
     *   remark_name: 投诉建议 列表
     *
     */
    public function find_leave_list()
    {
        $this->checkAuth();

        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['user_id', '=', $this->user_id];
        if ($params["keyword"]) $where[] = ["username|phone|title|content", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];


        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $params["field"]         = "*";//过滤字段
        if ($params['is_paginate']) $result = $BaseLeaveInit->get_list($where, $params);
        if (empty($params['is_paginate'])) $result = $BaseLeaveInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 投诉建议 详情
     * @OA\Post(
     *     tags={"投诉建议"},
     *     path="/wxapp/base_leave/find_leave",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/base_leave/find_leave
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/base_leave/find_leave
     *   api:  /wxapp/base_leave/find_leave
     *   remark_name: 投诉建议 详情
     *
     */
    public function find_leave()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议    (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $BaseLeaveInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 投诉建议 添加
     * @OA\Post(
     *     tags={"投诉建议"},
     *     path="/wxapp/base_leave/add_leave",
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
     *         name="username",
     *         in="query",
     *         description="联系人 (选)",
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
     *         description="手机号 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="标题 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="content",
     *         in="query",
     *         description="留言内容",
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
     *         description="图集     (数组格式)",
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/base_leave/add_leave
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/base_leave/add_leave
     *   api:  /wxapp/base_leave/add_leave
     *   remark_name: 投诉建议 编辑&添加
     *
     */
    public function add_leave()
    {
        $this->checkAuth();

        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议    (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        
        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        /** 提交更新 **/
        $result = $BaseLeaveInit->api_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");


        if (empty($params["id"])) $msg = "添加成功";
        if (!empty($params["id"])) $msg = "编辑成功";
        $this->success($msg);
    }


    /**
     * 投诉建议 删除
     * @OA\Post(
     *     tags={"投诉建议"},
     *     path="/wxapp/base_leave/delete_leave",
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
     *         name="id",
     *         in="query",
     *         description="id",
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/base_leave/delete_leave
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/base_leave/delete_leave
     *   api:  /wxapp/base_leave/delete_leave
     *   remark_name: 投诉建议 删除
     *
     */
    public function delete_leave()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议    (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)

        /** 获取参数 **/
        $params = $this->request->param();

        /** 删除数据 **/
        $result = $BaseLeaveInit->delete_post($params["id"]);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功");
    }


}
