<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"Teacher",
 *     "name_underline"          =>"teacher",
 *     "controller_name"         =>"Teacher",
 *     "table_name"              =>"teacher",
 *     "remark"                  =>"教师管理"
 *     "api_url"                 =>"/api/wxapp/teacher/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2025-08-25 15:19:53",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\TeacherController();
 *     "test_environment"        =>"http://tutor.ikun:9090/api/wxapp/teacher/index",
 *     "official_environment"    =>"https://xcxkf159.aubye.com/api/wxapp/teacher/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class TeacherController extends AuthController
{

    //public function initialize(){
    //	//教师管理
    //	parent::initialize();
    //}


    /**
     * 默认接口
     * /api/wxapp/teacher/index
     * https://xcxkf159.aubye.com/api/wxapp/teacher/index
     */
    public function index()
    {
        $TeacherInit  = new \init\TeacherInit();//教师管理   (ps:InitController)
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理   (ps:InitModel)

        $result = [];

        $this->success('教师管理-接口请求成功', $result);
    }


    /**
     * 授课(年级,科目)列表
     * @OA\Post(
     *     tags={"教师管理"},
     *     path="/wxapp/teacher/find_class_list",
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
     *    @OA\Parameter(
     *         name="is_index",
     *         in="query",
     *         description="true 首页推荐",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="类型:1年级,2科目 (为空返回所有)",
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
     *         name="ids",
     *         in="query",
     *         description="数组或者字符串   筛选对应id值",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher/find_class_list
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher/find_class_list
     *   api:  /wxapp/teacher/find_class_list
     *   remark_name: 授课(年级,科目)列表
     *
     */
    public function find_class_list()
    {
        $TeacherClassInit  = new \init\TeacherClassInit();//授课类型   (ps:InitController)
        $TeacherClassModel = new \initmodel\TeacherClassModel(); //授课类型   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ['id', '>', 0];
        if (empty($params['is_index'])) $where[] = ['pid', '=', 0];
        $where[] = ['is_show', '=', 1];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];
        if ($params['is_index']) $where[] = ['is_index', '=', 1];
        if ($params['type']) $where[] = ['type', '=', $params['type']];
        if ($params['ids']) $where[] = ['id', 'in', is_array($params['ids']) ? $params['ids'] : explode(',', $params['ids'])];


        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $params["field"]         = "*";//过滤字段
        $result                  = $TeacherClassInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 教师管理 列表
     * @OA\Post(
     *     tags={"教师管理"},
     *     path="/wxapp/teacher/find_teacher_list",
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
     *    @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="上课方式:1线上,2线下 数组[],或字符串都可以",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="is_recommend",
     *         in="query",
     *         description="true 推荐",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="性别: 男,女",
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
     *         name="course_ids",
     *         in="query",
     *         description="科目  数组[],或字符串都可以",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="grade_ids",
     *         in="query",
     *         description="年级  数组[],或字符串都可以",
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
     *     @OA\Parameter(
     *         name="score",
     *         in="query",
     *         description="评分排序    asc正序  desc倒序",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="distance",
     *         in="query",
     *         description="距离排序    asc正序  desc倒序",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="total_duration",
     *         in="query",
     *         description="授课时长   文字100-200 ",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="online_price",
     *         in="query",
     *         description="线上课时费   文字100-200 ",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="offline_price",
     *         in="query",
     *         description="线下课时费   文字100-200 ",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher/find_teacher_list
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher/find_teacher_list
     *   api:  /wxapp/teacher/find_teacher_list
     *   remark_name: 教师管理 列表
     *
     */
    public function find_teacher_list()
    {
        $TeacherInit  = new \init\TeacherInit();//教师管理   (ps:InitController)
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['status', '=', 2];
        $where[] = ['work_status', '=', 1];
        if ($params["keyword"]) $where[] = ["username|introduce", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];
        if ($params['online_price']) $where[] = ['online_price', 'between', $this->getParams($params['online_price'], '-')];
        if ($params['offline_price']) $where[] = ['offline_price', 'between', $this->getParams($params['offline_price'], '-')];
        if ($params['order_number']) $where[] = ['order_number', 'between', $this->getParams($params['order_number'], '-')];

        // 添加 授课类型 查询条件
        if (!empty($params['type'])) {
            $types = is_array($params['type']) ? $params['type'] : explode(',', $params['type']);
            if (!empty($types)) {
                // 创建 grade_ids 的 OR 条件组
                $typeWhere = [];
                foreach ($types as $type) {
                    $typeWhere[] = ['', 'exp', Db::raw("FIND_IN_SET('" . intval($type) . "', type)")];
                }

                // 将 OR 条件组添加到主查询条件中
                $where[] = $typeWhere;
            }
        }


        // 添加 年级 查询条件
        if (!empty($params['grade_ids'])) {
            $grade_ids = is_array($params['grade_ids']) ? $params['grade_ids'] : explode(',', $params['grade_ids']);
            if (!empty($grade_ids)) {
                // 创建 grade_ids 的 OR 条件组
                $gradeWhere = [];
                foreach ($grade_ids as $grade_id) {
                    $gradeWhere[] = ['', 'exp', Db::raw("FIND_IN_SET('" . intval($grade_id) . "', grade_ids)")];
                }

                // 将 OR 条件组添加到主查询条件中
                $where[] = $gradeWhere;
            }
        }


        // 添加 科目 查询条件
        if (!empty($params['course_ids'])) {
            $course_ids = is_array($params['course_ids']) ? $params['course_ids'] : explode(',', $params['course_ids']);
            if (!empty($course_ids)) {
                // 创建 grade_ids 的 OR 条件组
                $courseWhere = [];
                foreach ($course_ids as $course_id) {
                    $courseWhere[] = ['', 'exp', Db::raw("FIND_IN_SET('" . intval($course_id) . "', course_ids)")];
                }
                // 将 OR 条件组添加到主查询条件中
                $where[] = $courseWhere;
            }
        }


        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $params["field"]         = "*";//过滤字段


        //距离
        $field_lat = 'lat';// 数据库字段名 - 纬度  -90°到90°
        $field_lng = 'lng';// 数据库字段名 - 经度  -180°到180°
        $lat       = $params['lat'];// 数据库字段名 - 纬度  -90°到90°
        $lng       = $params['lng'];// 数据库字段名 - 经度  -180°到180°
        if (!empty($lat) && !empty($lng)) {
            $field           = "*, (6378.138 * 2 * asin(sqrt(pow(sin(({$field_lng} * pi() / 180 - {$lng} * pi() / 180) / 2),2) + cos({$field_lng} * pi() / 180) * cos({$lng} * pi() / 180) * pow(sin(({$field_lat} * pi() / 180 - {$lat} * pi() / 180) / 2),2))) * 1000) as distance";
            $params['field'] = $field;
        }


        //排序
        if ($params['score']) $params['order'] = "score {$params['score']}";
        if ($params['distance']) $params['distance'] = "distance {$params['distance']}";


        if ($params['is_paginate']) $result = $TeacherInit->get_list($where, $params);
        if (empty($params['is_paginate'])) $result = $TeacherInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 教师管理 详情
     * @OA\Post(
     *     tags={"教师管理"},
     *     path="/wxapp/teacher/find_teacher",
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
     *    @OA\Parameter(
     *         name="is_me",
     *         in="query",
     *         description="true 查询自己教师信息",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher/find_teacher
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher/find_teacher
     *   api:  /wxapp/teacher/find_teacher
     *   remark_name: 教师管理 详情
     *
     */
    public function find_teacher()
    {
        $TeacherInit  = new \init\TeacherInit();//教师管理    (ps:InitController)
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where = [];
        if ($params['is_me']) $where[] = ["user_id", "=", $this->user_id];
        if (empty($params['is_me'])) $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $TeacherInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 教师管理 编辑&添加
     * @OA\Post(
     *     tags={"教师管理"},
     *     path="/wxapp/teacher/edit_teacher",
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
     *         description="姓名",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="age",
     *         in="query",
     *         description="年龄",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="性别",
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
     *         description="所在位置",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="school",
     *         in="query",
     *         description="毕业院校",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="introduce",
     *         in="query",
     *         description="个人介绍",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="qualification",
     *         in="query",
     *         description="资质证书",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="qualification_images",
     *         in="query",
     *         description="资质证书     (数组格式)",
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
     *         description="个人照片     (数组格式)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="video",
     *         in="query",
     *         description="个人视频",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="tag",
     *         in="query",
     *         description="标签,数组格式",
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
     *         name="online_price",
     *         in="query",
     *         description="线上课时费(元/30分钟)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="offline_price",
     *         in="query",
     *         description="线下课时费(元/30分钟)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="package_price",
     *         in="query",
     *         description="套餐原价",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="上课方式:1线上,2线下 (数组)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="receive_address",
     *         in="query",
     *         description="接单地址",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="receive_lng",
     *         in="query",
     *         description="经度",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="receive_lat",
     *         in="query",
     *         description="纬度",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="receive_km",
     *         in="query",
     *         description="接单公里",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="km_price",
     *         in="query",
     *         description="公里/元",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="grade_ids",
     *         in="query",
     *         description="年级 数组传id值",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="course_ids",
     *         in="query",
     *         description="科目 数组传id值",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher/edit_teacher
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher/edit_teacher
     *   api:  /wxapp/teacher/edit_teacher
     *   remark_name: 教师管理 编辑&添加
     *
     */
    public function edit_teacher()
    {
        $this->checkAuth();
        $TeacherInit  = new \init\TeacherInit();//教师管理    (ps:InitController)
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;


        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where        = [];
        $where[]      = ['user_id', '=', $this->user_id];
        $teacher_info = $TeacherModel->where($where)->find();
        //if (empty($teacher_info)) $this->error("请先申请成为教师");

        //未提交过,或者已驳回,在提交需要审核.
        if (empty($teacher_info) || $teacher_info['status'] == 3) $params["status"] = 1;


        $teacher_id = $teacher_info['id'];

        //id条件
        $map = [];
        if ($teacher_id) $map[] = ['id', '=', $teacher_id];


        //每次编辑都需要待审核
        //$params["status"] = 1;


        /** 提交更新 **/
        $result = $TeacherInit->api_edit_post($params, $map);
        if (empty($result)) $this->error("失败请重试");


        if (empty($teacher_id)) $msg = "申请成功,等待审核";
        if (!empty($teacher_id)) $msg = "编辑成功";
        $this->success($msg);
    }


    /**
     * 更新工作状态,自动取反
     * @OA\Post(
     *     tags={"教师管理"},
     *     path="/wxapp/teacher/work_status",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher/work_status
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher/work_status
     *   api:  /wxapp/teacher/work_status
     *   remark_name: 更新工作状态,自动取反
     *
     */
    public function work_status()
    {
        $this->checkAuth();
        $TeacherInit  = new \init\TeacherInit();//教师管理    (ps:InitController)
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理   (ps:InitModel)

        /** 获取参数 **/
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        /** 查询条件 **/
        $where   = [];
        $where[] = ["user_id", "=", $this->user_id];


        $teacher_info = $TeacherModel->where($where)->find();
        if (empty($teacher_info)) $this->error("请先申请成为教师");


        $update['work_status'] = $teacher_info['work_status'] == 1 ? 2 : 1;
        $update['update_time'] = time();


        $result = $TeacherModel->where($where)->update($update);
        if (empty($result)) $this->error("失败请重试");

        $this->success("操作成功");
    }


    /**
     * 评价列表
     * @OA\Post(
     *     tags={"教师管理"},
     *     path="/wxapp/teacher/find_comment_list",
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
     *    @OA\Parameter(
     *         name="pid",
     *         in="query",
     *         description="教师id",
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
     *   test_environment: http://tutor.ikun:9090/api/wxapp/teacher/find_comment_list
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/teacher/find_comment_list
     *   api:  /wxapp/teacher/find_comment_list
     *   remark_name: 评价列表
     *
     */
    public function find_comment_list()
    {
        $BaseCommentInit = new \init\BaseCommentInit();//商品评价    (ps:InitController)
        $params          = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["type", "=", 'teacher'];
        $where[] = ["pid", "=", $params["pid"]];


        /** 查询数据 **/
        $params["InterfaceType"] = "api";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $params["field"]         = "*";//过滤字段

        /** 查询数据 **/
        $result = $BaseCommentInit->get_list_paginate($where, $params);


        $this->success("详情数据", $result);
    }
}
