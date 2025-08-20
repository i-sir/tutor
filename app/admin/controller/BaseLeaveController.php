<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"BaseLeave",
 *     "name_underline"      =>"base_leave",
 *     "controller_name"     =>"BaseLeave",
 *     "table_name"          =>"base_leave",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"投诉建议",
 *     "author"              =>"",
 *     "create_time"         =>"2025-06-11 10:54:52",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\BaseLeaveController();
 * )
 */


use think\facade\Db;
use cmf\controller\AdminBaseController;


class BaseLeaveController extends AdminBaseController
{

    // public function initialize(){
    //	//投诉建议
    //	parent::initialize();
    //	}


    /**
     * 首页基础信息
     */
    protected function base_index()
    {

    }

    /**
     * 编辑,添加基础信息
     */
    protected function base_edit()
    {


    }


    /**
     * 首页列表数据
     * @adminMenu(
     *     'name'             => 'BaseLeave',
     *     'name_underline'   => 'base_leave',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '投诉建议',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $this->base_index();//处理基础信息


        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议    (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();

        /** 查询条件 **/
        $where = [];
        if ($params["keyword"]) $where[] = ["username|phone|title|content", "like", "%{$params["keyword"]}%"];
        if ($params["test"]) $where[] = ["test", "=", $params["test"]];
        //if($params["status"]) $where[]=["status","=", $params["status"]];
        //$where[]=["type","=", 1];


        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $params["field"]         = "*";//过滤字段


        /** 导出数据 **/
        if ($params["is_export"]) $this->export_excel($where, $params);


        /** 查询数据 **/
        $result = $BaseLeaveInit->get_list_paginate($where, $params);


        /** 数据渲染 **/
        $this->assign("list", $result);
        $this->assign("pagination", $result->render());//单独提取分页出来
        $this->assign("page", $result->currentPage());//当前页码


        return $this->fetch();
    }


    //添加
    public function add()
    {
        $this->base_edit();//处理基础信息

        return $this->fetch();
    }


    //添加提交
    public function add_post()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();


        /** 插入数据 **/
        $result = $BaseLeaveInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //查看详情
    public function find()
    {
        $this->base_edit();//处理基础信息

        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议    (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $BaseLeaveInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        /** 数据格式转数组 **/
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //回复
    public function reply()
    {
        $this->base_edit();//处理基础信息

        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议    (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $BaseLeaveInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        /** 数据格式转数组 **/
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //编辑详情
    public function edit()
    {
        $this->base_edit();//处理基础信息

        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议  (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $BaseLeaveInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        /** 数据格式转数组 **/
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //提交编辑
    public function edit_post()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();


        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        /** 提交数据 **/
        $result = $BaseLeaveInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //提交(副本,无任何操作) 编辑&添加
    public function edit_post_two()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();

        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        /** 提交数据 **/
        $result = $BaseLeaveInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //驳回
    public function refuse()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议  (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];


        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $BaseLeaveInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        /** 数据格式转数组 **/
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //驳回,更改状态
    public function audit_post()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();

        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $item                    = $BaseLeaveInit->get_find($where);
        if (empty($item)) $this->error("暂无数据");

        /** 通过&拒绝时间 **/
        if ($params['status'] == 2) $params['reply_time'] = time();

        /** 提交数据 **/
        $result = $BaseLeaveInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("操作成功");
    }

    //删除
    public function delete()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        /** 删除数据 **/
        $result = $BaseLeaveInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功");//   , "index{$this->params_url}"
    }


    //批量操作
    public function batch_post()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $BaseLeaveInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功");//   , "index{$this->params_url}"
    }


    //更新排序
    public function list_order_post()
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)
        $params         = $this->request->param("list_order/a");

        //提交更新
        $result = $BaseLeaveInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功"); //   , "index{$this->params_url}"
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $BaseLeaveInit  = new \init\BaseLeaveInit();//投诉建议   (ps:InitController)
        $BaseLeaveModel = new \initmodel\BaseLeaveModel(); //投诉建议   (ps:InitModel)


        $result = $BaseLeaveInit->get_list($where, $params);

        $result = $result->toArray();
        foreach ($result as $k => &$item) {

            //订单号过长问题
            if ($item["order_num"]) $item["order_num"] = $item["order_num"] . "\t";

            //图片链接 可用默认浏览器打开   后面为展示链接名字 --单独,多图特殊处理一下
            if ($item["image"]) $item["image"] = '=HYPERLINK("' . cmf_get_asset_url($item['image']) . '","图片.png")';


            //用户信息
            $user_info        = $item['user_info'];
            $item['userInfo'] = "(ID:{$user_info['id']}) {$user_info['nickname']}  {$user_info['phone']}";


            //背景颜色
            if ($item['unit'] == '测试8') $item['BackgroundColor'] = 'red';
        }

        $headArrValue = [
            ["rowName" => "ID", "rowVal" => "id", "width" => 10],
            ["rowName" => "用户信息", "rowVal" => "userInfo", "width" => 30],
            ["rowName" => "名字", "rowVal" => "name", "width" => 20],
            ["rowName" => "年龄", "rowVal" => "age", "width" => 20],
            ["rowName" => "测试", "rowVal" => "test", "width" => 20],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];


        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => count($headArrValue)/2],
        //            ["rowName" => "列2", "acrossCells" => count($headArrValue)/2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "导出"]);
    }


}
