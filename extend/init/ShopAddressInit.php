<?php

namespace init;


/**
 * @Init(
 *     "name"            =>"ShopAddress",
 *     "table_name"      =>"shop_address",
 *     "model_name"      =>"ShopAddressModel",
 *     "remark"          =>"地址管理",
 *     "author"          =>"",
 *     "create_time"     =>"2023-12-16 11:34:00",
 *     "version"         =>"1.0",
 *     "use"             => new \init\ShopAddressInit();
 * )
 */

use think\facade\Db;


class ShopAddressInit extends Base
{

    public $is_default = [1 => '默认', 2 => '不默认'];//是否默认
    public $status     = [1 => '正常'];//状态

    public $findField = '*';
    public $listField = '*';


    /**
     * 获取列表
     * @param $where    条件
     * @param $order    排序
     * @param $params   扩充参数
     * @param $is_admin 访问类型
     * @return false|mixed
     */
    public function get_list($where = [], $order = 'id desc', $params = [], $is_admin = false)
    {
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)
        $MemberModel      = new \initmodel\MemberModel();//(ps:InitModel)


        $result = $ShopAddressModel
            ->where($where)
            ->order($order)
            ->field($this->listField)
            ->limit($params["limit"] ?? 10000)
            ->select()
            ->each(function ($item, $key) use ($params, $is_admin, $MemberModel) {

                //处理数据
                $item['is_default_text'] = $this->is_default[$item['is_default']];//是否默认
                $item['status_text']     = $this->status[$item['status']];//状态


                if ($params["is_export"]) {
                    //导出数据处理
                    $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
                }

                return $item;
            });

        if (!$is_admin && empty(count($result))) return false;

        return $result;
    }


    /**
     * 分页查询
     * @param $where    条件
     * @param $order    排序
     * @param $params   扩充参数
     * @param $is_admin 访问类型
     * @return mixed
     */
    public function get_list_paginate($where = [], $order = "id desc", $params = [], $is_admin = false)
    {
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)
        $MemberModel      = new \initmodel\MemberModel();//(ps:InitModel)


        $result = $ShopAddressModel
            ->where($where)
            ->order($order)
            ->field($this->listField)
            ->paginate(["list_rows" => $params["page_size"] ?? 15, "query" => $params])
            ->each(function ($item, $key) use ($params, $is_admin, $MemberModel) {


                //处理数据
                $item['is_default_text'] = $this->is_default[$item['is_default']];//是否默认
                $item['status_text']     = $this->status[$item['status']];//状态


                return $item;
            });


        if (!$is_admin && $result->isEmpty()) return false;


        return $result;
    }


    /**
     * 获取详情
     * @param $where    条件
     * @param $params   扩充参数
     * @param $is_admin 是否为后台
     * @return false|mixed
     */
    public function get_find($where = [], $params = [], $is_admin = false)
    {
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)
        $MemberModel      = new \initmodel\MemberModel();//(ps:InitModel)


        $item = $ShopAddressModel->where($where)->field($this->findField)->find();

        if (empty($item)) return false;


        //公共处理数据


        //处理数据
        $item['is_default_text'] = $this->is_default[$item['is_default']];//是否默认
        $item['status_text']     = $this->status[$item['status']];//状态


        if (!$is_admin) {
            //api处理文件


        } else {
            //admin处理文件

        }

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
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)
        $item             = $this->get_find(["id" => $params["id"]]);


        if (!empty($params["id"])) {
            //编辑
            $params["update_time"] = time();
            $result                = $ShopAddressModel->strict(false)->update($params);
            if ($result) $result = $params["id"];
        } else {
            //添加
            $params["create_time"] = time();
            $result                = $ShopAddressModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 提交(副本,无任何操作) 编辑&添加
     * @param $params
     * @return void
     */
    public function edit_post_two($params)
    {
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)
        $item             = $this->get_find(["id" => $params["id"]]);


        if (!empty($params["id"])) {
            //编辑
            $params["update_time"] = time();
            $result                = $ShopAddressModel->strict(false)->update($params);
            if ($result) $result = $params["id"];
        } else {
            //添加
            $params["create_time"] = time();
            $result                = $ShopAddressModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 删除数据 软删除
     * @param $id     传id  int或array都可以
     * @param $type   1软删除 2真实删除
     * @param $params 扩充参数
     * @return void
     */
    public function delete_post($id, $type = 1, $params = [])
    {
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)


        if ($type == 1) $result = $ShopAddressModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $ShopAddressModel->destroy($id, true);//真实删除

        return $result;
    }


    /**
     * 删除数据 版本1.0不用了
     * @param $where     where 条件
     * @param $type      1真实删除 2软删除
     * @param $params    扩充参数
     * @return void
     */
    public function delete_post_v1($where, $type = 1, $params = [])
    {
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)

        if ($type == 1) $result = $ShopAddressModel->where($where)->delete();//真实删除

        if ($type == 2) $result = $ShopAddressModel->where($where)->strict(false)->update(["delete_time" => time()]);//软删除


        return $result;
    }


    /**
     * 后台批量操作
     * @param $id
     * @param $params 修改值
     * @return void
     */
    public function batch_post($id, $params = [])
    {
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)

        $where   = [];
        $where[] = ["id", "in", $id];//$id 为数组


        $params["update_time"] = time();
        $result                = $ShopAddressModel->where($where)->strict(false)->update($params);//修改状态

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
        $ShopAddressModel = new \initmodel\ShopAddressModel(); //地址管理   (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ["id", "=", $k];
            $result  = $ShopAddressModel->where($where)->strict(false)->update(["list_order" => $v, "update_time" => time()]);//排序
        }

        return $result;
    }


}
