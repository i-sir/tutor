<?php

namespace init;


/**
 * @Init(
 *     "name"            =>"ShopOrder",
 *     "table_name"      =>"shop_order",
 *     "model_name"      =>"ShopOrderModel",
 *     "remark"          =>"订单管理",
 *     "author"          =>"",
 *     "create_time"     =>"2023-09-29 09:57:21",
 *     "version"         =>"1.0",
 *     "use"             => new \init\ShopOrderInit();
 * )
 */

use think\facade\Db;


class ShopOrderInit extends Base
{
    //后台展示状态列表,统计数量
    public $status_list = [1 => '待付款', 2 => '已付款', 4 => '已发货', 6 => '已收货', 8 => '已完成', 10 => '已取消', 12 => '退款申请', 14 => '退款不通过', 16 => '退款通过'];

    //后台状态,名字,条件
    public $admin_status       = [1 => '待付款', 2 => '待发货', 4 => '已发货', 6 => '已收货', 8 => '已评价(完成)', 10 => '已取消', 12 => '退款审核中', 14 => '退款驳回', 16 => '退款成功'];
    public $admin_status_where = [1 => [1], 2 => [2], 4 => [4], 6 => [6], 8 => [8], 10 => [10], 12 => [12], 14 => [14], 15 => [15], 16 => [16]];

    //前端状态,名字,条件
    public $api_status       = [1 => '待付款', 2 => '待发货', 4 => '已发货', 6 => '待评价', 8 => '已完成', 10 => '已取消', 12 => '退款审核中', 14 => '退款驳回', 16 => '退款成功'];
    public $api_status_where = [1 => [1], 2 => [2, 20], 4 => [4], 6 => [6], 8 => [8], 10 => [10], 12 => [12, 14, 15, 16]];

    public $pay_type = [1 => '微信支付', 2 => '余额支付', 3 => '积分支付', 4 => '支付宝支付', 5 => '组合支付'];


    protected $Field         = '*';//过滤字段,默认全部
    protected $Limit         = 100000;//如不分页,展示条数
    protected $PageSize      = 15;//分页每页,数据条数
    protected $Order         = 'id desc';//排序
    protected $InterfaceType = 'api';//接口类型:admin=后台,api=前端


    //本init和model
    public function _init()
    {
        $ShopOrderInit        = new \init\ShopOrderInit();//订单管理
        $ShopOrderDetailInit  = new \init\ShopOrderDetailInit();//订单详情   (ps:InitController)
        $ShopOrderModel       = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)
        $ShopOrderDetailModel = new \initmodel\ShopOrderDetailModel();//订单详情  (ps:InitModel)
    }

    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $ShopOrderDetailInit = new \init\ShopOrderDetailInit();//订单详情   (ps:InitController)
        $MemberInit          = new \init\MemberInit();//会员管理 (ps:InitController)
        $OrderPayModel       = new \initmodel\OrderPayModel();


        //获取支付单号 && 支付回调时候已经同步
        //        $map      = [];
        //        $map[]    = ['order_num', '=', $item['order_num']];
        //        $map[]    = ['status', '=', 2];
        //        $pay_info = $OrderPayModel->where($map)->find();
        //        if ($pay_info) $item['pay_num'] = $pay_info['pay_num'];


        //状态,支付方式,信息
        $item['pay_type_name'] = $this->pay_type[$item['pay_type']];

        //用户,商品信息
        $item["user_info"] = $MemberInit->get_find(['id' => $item['user_id']]);

        //订单详情
        $map                = [];
        $map[]              = ['order_num', '=', $item['order_num']];
        $item["goods_list"] = $ShopOrderDetailInit->get_list($map);


        //导出数据处理
        if (isset($params['is_export']) && $params['is_export']) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api') {
            //api处理文件
            $item['status_name'] = $this->api_status[$item['status']];


        } else {
            //admin处理文件
            $item['status_name'] = $this->admin_status[$item['status']];

        }


        return $item;
    }


    /**
     * 获取列表
     * @param $where    条件
     * @param $params   扩充参数
     * @return false|mixed
     */
    public function get_list($where = [], $params = [])
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)


        $result = $ShopOrderModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use ($params) {

                //处理数据
                $item = $this->common_item($item, $params);

                return $item;
            });

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && empty(count($result))) return false;

        return $result;
    }


    /**
     * 分页查询
     * @param $where    条件
     * @param $params   扩充参数
     * @return mixed
     */
    public function get_list_paginate($where = [], $params = [])
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)

        $MemberInit           = new \init\MemberInit();//会员管理 (ps:InitController)
        $ShopOrderDetailModel = new \initmodel\ShopOrderDetailModel();//订单详情 (ps:InitModel)


        $result = $ShopOrderModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
            ->each(function ($item, $key) use ($params) {


                //处理数据
                $item = $this->common_item($item, $params);

                return $item;
            });

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && $result->isEmpty()) return false;


        return $result;
    }


    /**
     * 获取详情
     * @param $where    条件
     * @param $params   扩充参数
     * @return false|mixed
     */
    public function get_find($where = [], $params = [])
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)

        //传入id直接查询
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];
        if (empty($where)) return false;

        $item = $ShopOrderModel
            ->where($where)
            ->field($params['field'] ?? $this->Field)
            ->find();

        if (empty($item)) return false;

        //处理数据
        $item = $this->common_item($item, $params);


        return $item;
    }


    /**
     * 前端  编辑&添加
     * @param $params 参数
     * @return void
     */
    public function api_edit_post($params = [])
    {
        $result = false;

        //处理共同数据


        $result = $this->edit_post($params);//api提交

        return $result;
    }


    /**
     * 后台  编辑&添加
     * @param $model  类
     * @param $params 参数
     * @return void
     */
    public function admin_edit_post($params = [])
    {
        $result = false;

        //处理共同数据


        $result = $this->edit_post($params);//admin提交

        return $result;
    }


    /**
     * 提交 编辑&添加
     * @param $params
     * @return void
     */
    public function edit_post($params)
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)

        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $ShopOrderModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $ShopOrderModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $ShopOrderModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 删除数据
     * @param $where     where 条件
     * @param $type      1真实删除 2软删除
     * @param $params    扩充参数
     * @return void
     */
    public function delete_post($where, $type = 1, $params = [])
    {
        $model = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)

        if ($type == 1) $result = $model->where($where)->delete();//真实删除

        if ($type == 2) $result = $model->where($where)->strict(false)->update(['delete_time' => time()]);//软删除


        return $result;
    }


    /**
     * 后台  推荐
     * @param $id
     * @param $is_recommend 修改值
     * @param $params       扩充参数
     * @return void
     */
    public function recommend_post($id, $is_recommend, $params = [])
    {
        $model = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)


        $where   = [];
        $where[] = ['id', 'in', $id];//$id 为数组

        $result = $model->where($where)->strict(false)->update(['is_recommend' => $is_recommend, 'update_time' => time()]);//设为推荐

        return $result;
    }


    /**
     * 后台  状态
     * @param $id
     * @param $status 状态值
     * @param $params 扩充参数
     * @return void
     */
    public function status_post($id, $status, $params = [])
    {
        $model = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)


        $where   = [];
        $where[] = ['id', 'in', $id];//$id 为数组

        $result = $model->where($where)->strict(false)->update(['status' => $status, 'update_time' => time()]);//修改状态

        return $result;
    }


    /**
     * 后台  排序
     * @param $list_order 排序
     * @param $params     扩充参数
     * @return void
     */
    public function list_order_post($list_order, $params = [])
    {
        $model = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ['id', '=', $k];
            $result  = $model->where($where)->strict(false)->update(['list_order' => $v, 'update_time' => time()]);//排序
        }

        return $result;
    }


}
