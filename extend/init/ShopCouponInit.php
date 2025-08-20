<?php

namespace init;


/**
 * @Init(
 *     "name"            =>"ShopCoupon",
 *     "name_underline"  =>"shop_coupon",
 *     "table_name"      =>"shop_coupon",
 *     "model_name"      =>"ShopCouponModel",
 *     "remark"          =>"优惠券",
 *     "author"          =>"",
 *     "create_time"     =>"2025-02-21 15:10:22",
 *     "version"         =>"1.0",
 *     "use"             => new \init\ShopCouponInit();
 * )
 */

use think\facade\Db;


class ShopCouponInit extends Base
{
    public $type        = [1 => '时间段', 2 => '按天'];//时间类型
    public $coupon_type = [1 => '满减券', 2 => '折扣券'];//优惠券类型
    public $status      = [1 => '显示', 2 => '隐藏'];//状态


    protected $Field         = "*";//过滤字段,默认全部
    protected $Limit         = 100000;//如不分页,展示条数
    protected $PageSize      = 15;//分页每页,数据条数
    protected $Order         = "list_order,id desc";//排序
    protected $InterfaceType = "api";//接口类型:admin=后台,api=前端
    protected $DataFormat    = "find";//数据格式,find详情,list列表

    //本init和model
    public function _init()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)
    }

    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)


        /** 处理文字描述 **/
        $item['type_name']        = $this->type[$item['type']];//时间类型
        $item['status_name']      = $this->status[$item['status']];//状态
        $item['coupon_type_name'] = $this->coupon_type[$item['coupon_type']];//优惠券类型


        //是否已经领取
        $item['is_receive'] = false;
        if ($params['user_id']) {
            $map         = [];
            $map[]       = ['user_id', '=', $params['user_id']];
            $map[]       = ['coupon_id', '=', $item['id']];
            $coupon_info = $ShopCouponUserModel->where($map)->count();
            if ($coupon_info) $item['is_receive'] = true;
        }

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        //数据格式
        if ($params['DataFormat']) $this->DataFormat = $params['DataFormat'];


        /** 数据格式(公共部分),find详情,list列表 **/


        /** 数据格式,find详情,list列表 **/
        if ($this->DataFormat == 'find') {
            /** find详情数据格式 **/


            /** 处理富文本 **/
            if ($item['content']) $item['content'] = htmlspecialchars_decode(cmf_replace_content_file_url($item['content']));

        } else {
            /** list列表数据格式 **/

        }


        /** 处理数据 **/
        if ($this->InterfaceType == 'api') {
            /** api处理文件 **/


        } else {
            /** admin处理文件 **/


        }


        /** 导出数据处理 **/
        if (isset($params["is_export"]) && $params["is_export"]) {
            $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);
        }

        return $item;
    }


    /**
     * 获取列表
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_list($where = [], $params = [])
    {
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)


        /** 查询数据 **/
        $result = $ShopCouponModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use ($params) {

                /** 处理公共数据 **/
                $item = $this->common_item($item, $params);

                return $item;
            });

        /** 根据接口类型,返回不同数据类型 **/
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && empty(count($result))) return false;

        return $result;
    }


    /**
     * 分页查询
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 page_size=每页条数  InterfaceType=admin|api后端,前端
     * @return mixed
     */
    public function get_list_paginate($where = [], $params = [])
    {
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)


        /** 查询数据 **/
        $result = $ShopCouponModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
            ->each(function ($item, $key) use ($params) {

                /** 处理公共数据 **/
                $item = $this->common_item($item, $params);

                return $item;
            });

        /** 根据接口类型,返回不同数据类型 **/
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && $result->isEmpty()) return false;


        return $result;
    }

    /**
     * 获取列表
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_join_list($where = [], $params = [])
    {
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)

        /** 查询数据 **/
        $result = $ShopCouponModel
            ->alias('a')
            ->join('member b', 'a.user_id = b.id')
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use ($params) {

                /** 处理公共数据 **/
                $item = $this->common_item($item, $params);


                return $item;
            });

        /** 根据接口类型,返回不同数据类型 **/
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && empty(count($result))) return false;

        return $result;
    }


    /**
     * 获取详情
     * @param $where     条件 或 id值
     * @param $params    扩充参数 field=过滤字段  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_find($where = [], $params = [])
    {
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)

        /** 可直接传id,或者where条件 **/
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];
        if (empty($where)) return false;

        /** 查询数据 **/
        $item = $ShopCouponModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->find();


        if (empty($item)) return false;


        /** 处理公共数据 **/
        $item = $this->common_item($item, $params);


        return $item;
    }


    /**
     * 前端  编辑&添加
     * @param $params 参数
     * @param $where  where条件
     * @return void
     */
    public function api_edit_post($params = [], $where = [])
    {
        $result = false;

        /** 接口提交,处理数据 **/


        $result = $this->edit_post($params, $where);//api提交

        return $result;
    }


    /**
     * 后台  编辑&添加
     * @param $model  类
     * @param $params 参数
     * @param $where  更新提交(编辑数据使用)
     * @return void
     */
    public function admin_edit_post($params = [], $where = [])
    {
        $result = false;

        /** 后台提交,处理数据 **/


        $result = $this->edit_post($params, $where);//admin提交

        return $result;
    }


    /**
     * 提交 编辑&添加
     * @param $params
     * @param $where where条件
     * @return void
     */
    public function edit_post($params, $where = [])
    {
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)


        /** 查询详情数据 **/
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]], ["DataFormat" => "list"]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where, ["DataFormat" => "list"]);


        /** 公共提交,处理数据 **/
        if ($params['start_time']) $params['start_time'] = strtotime($params['start_time']);
        if($params['end_time']) $params['end_time'] = strtotime($params['end_time']." 23:59:59");


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $ShopCouponModel->where("id", "=", $params["id"])->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $ShopCouponModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $ShopCouponModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 提交(副本,无任何操作) 编辑&添加
     * @param $params
     * @param $where where 条件
     * @return void
     */
    public function edit_post_two($params, $where = [])
    {
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)


        /** 查询详情数据 **/
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        /** 公共提交,处理数据 **/


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $ShopCouponModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $ShopCouponModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $ShopCouponModel->strict(false)->insert($params, true);
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
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)


        if ($type == 1) $result = $ShopCouponModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $ShopCouponModel->destroy($id, true);//真实删除

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
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)

        $where   = [];
        $where[] = ["id", "in", $id];//$id 为数组


        $params["update_time"] = time();
        $result                = $ShopCouponModel->where($where)->strict(false)->update($params);//修改状态

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
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ["id", "=", $k];
            $result  = $ShopCouponModel->where($where)->strict(false)->update(["list_order" => $v, "update_time" => time()]);//排序
        }

        return $result;
    }


}
