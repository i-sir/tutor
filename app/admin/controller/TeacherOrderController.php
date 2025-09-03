<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"TeacherOrder",
 *     "name_underline"      =>"teacher_order",
 *     "controller_name"     =>"TeacherOrder",
 *     "table_name"          =>"teacher_order",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"订单管理",
 *     "author"              =>"",
 *     "create_time"         =>"2025-09-03 10:05:20",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\TeacherOrderController();
 * )
 */


use think\facade\Db;
use cmf\controller\AdminBaseController;


class TeacherOrderController extends AdminBaseController
{

    // public function initialize(){
    //	//订单管理
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


        $TeacherOrderInit = new \init\TeacherOrderInit();//teacher_order     (ps:InitController)
        $this->assign('type_list', $TeacherOrderInit->type);
    }


    /**
     * 首页列表数据
     * @adminMenu(
     *     'name'             => 'TeacherOrder',
     *     'name_underline'   => 'teacher_order',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '订单管理',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $params            = $this->request->param();
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)

        $where = [];
        if ($params['keyword']) $where[] = ['phone|username|order_num', 'like', "%{$params['keyword']}%"];
        if ($params['order_num']) $where[] = ['order_num', 'like', "%{$params['order_num']}%"];
        if ($params['goods_name']) $where[] = ['goods_name', 'like', "%{$params['goods_name']}%"];
        if ($params['user_id']) $where[] = ['user_id', '=', $params['user_id']];


        if ($params['order_date']) {
            $order_date_arr = explode(' - ', $params['order_date']);
            $where[]        = $this->getBetweenTime($order_date_arr[0], $order_date_arr[1]);
        }


        //状态筛选
        $status_where = [];
        if ($params['status']) $status_where[] = ['status', 'in', $TeacherOrderInit->admin_status_where[$params['status']]];
        //if (empty($params['status'])) $status_where[] = ['status', 'in', [2, 3]];


        //数据类型
        $params['InterfaceType'] = 'admin';//身份类型,后台


        //导出数据
        if ($params["is_export"]) $TeacherOrderInit->export_excel(array_merge($where, $status_where), $params);
        $result = $TeacherOrderInit->get_list_paginate(array_merge($where, $status_where), $params);


        $this->assign("list", $result);
        $this->assign('pagination', $result->render());//单独提取分页出来
        $this->assign("page", $result->currentPage());

        //全部数量
        $this->assign("total", $TeacherOrderModel->where($where)->count());//总数量


        //数据统计
        $status_arr = $TeacherOrderInit->status_list;
        $count      = [];
        foreach ($status_arr as $key => $status) {
            $map                    = [];
            $map[]                  = ['status', '=', $key];
            $map                    = array_merge($map, $where);
            $count[$key]['count']   = $TeacherOrderModel->where($map)->count();
            $count[$key]['key']     = $key;
            $count[$key]['name']    = $status;
            $count[$key]['is_ture'] = false;
            if ($params['status'] == $key) $count[$key]['is_ture'] = true;
        }


        $this->assign('count', $count);


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
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();


        /** 检测参数信息 **/
        $validateResult = $this->validate($params, 'TeacherOrder');
        if ($validateResult !== true) $this->error($validateResult);


        /** 插入数据 **/
        $result = $TeacherOrderInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //查看详情
    public function find()
    {
        $this->base_edit();//处理基础信息

        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理    (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $TeacherOrderInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        /** 数据格式转数组 **/
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //查看详情
    public function details()
    {
        $this->base_edit();//处理基础信息

        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理    (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();

        /** 查询条件 **/
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $TeacherOrderInit->get_find($where, $params);
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

        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理  (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $TeacherOrderInit->get_find($where, $params);
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
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();


        /** 检测参数信息 **/
        $validateResult = $this->validate($params, 'TeacherOrder');
        if ($validateResult !== true) $this->error($validateResult);


        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        /** 提交数据 **/
        $result = $TeacherOrderInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //提交(副本,无任何操作) 编辑&添加
    public function edit_post_two()
    {
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();

        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        /** 提交数据 **/
        $result = $TeacherOrderInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //驳回
    public function refuse()
    {
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理  (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];


        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $TeacherOrderInit->get_find($where, $params);
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
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();

        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $item                    = $TeacherOrderInit->get_find($where);
        if (empty($item)) $this->error("暂无数据");

        /** 通过&拒绝时间 **/
        if ($params['status'] == 2) $params['pass_time'] = time();
        if ($params['status'] == 3) $params['refuse_time'] = time();

        /** 提交数据 **/
        $result = $TeacherOrderInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("操作成功");
    }

    //删除
    public function delete()
    {
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        /** 删除数据 **/
        $result = $TeacherOrderInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功");//   , "index{$this->params_url}"
    }


    //批量操作
    public function batch_post()
    {
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $TeacherOrderInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功");//   , "index{$this->params_url}"
    }


    //更新排序
    public function list_order_post()
    {
        $TeacherOrderInit  = new \init\TeacherOrderInit();//订单管理   (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $params            = $this->request->param("list_order/a");

        //提交更新
        $result = $TeacherOrderInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功"); //   , "index{$this->params_url}"
    }


}
