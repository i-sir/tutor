<?php

namespace init;


/**
 * @Init(
 *     "name"            =>"Teacher",
 *     "name_underline"  =>"teacher",
 *     "table_name"      =>"teacher",
 *     "model_name"      =>"TeacherModel",
 *     "remark"          =>"教师管理",
 *     "author"          =>"",
 *     "create_time"     =>"2025-08-25 15:19:53",
 *     "version"         =>"1.0",
 *     "use"             => new \init\TeacherInit();
 * )
 */

use think\facade\Db;
use app\admin\controller\ExcelController;


class TeacherInit extends Base
{

    public $status      = [1 => '审核中', 2 => '已通过', 3 => '已驳回'];//状态
    public $work_status = [1 => '接单中', 2 => '已下线'];//工作状态
    public $type        = [1 => '线上', 2 => '线下'];//上课方式


    protected $Field         = "*";//过滤字段,默认全部
    protected $Limit         = 100000;//如不分页,展示条数
    protected $PageSize      = 15;//分页每页,数据条数
    protected $Order         = "list_order,id desc";//排序
    protected $InterfaceType = "api";//接口类型:admin=后台,api=前端
    protected $DataFormat    = "find";//数据格式,find详情,list列表

    //本init和model
    public function _init()
    {
        $TeacherInit  = new \init\TeacherInit();//教师管理   (ps:InitController)
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)
    }

    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $TeacherClassModel = new \initmodel\TeacherClassModel(); //授课类型   (ps:InitModel)
        $MemberInit        = new \init\MemberInit();//会员管理 (ps:InitController)
        $TeacherOrderModel = new \initmodel\TeacherOrderModel();


        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        //数据格式
        if ($params['DataFormat']) $this->DataFormat = $params['DataFormat'];


        /** 数据格式(公共部分),find详情&&list列表 共存数据 **/

        //检测是否对这个老师已经下单过,下单过不让下单了
        $item['is_package'] = false;
        $map100             = [];
        $map100[]           = ['teacher_id', '=', $item['id']];
        $map100[]           = ['is_package', '=', 1];
        $map100[]           = ['user_id', '=', $params['user_id']];
        $map100[]           = ['status', 'in', [2, 4, 8]];
        $is_package_order   = $TeacherOrderModel->where($map100)->find();
        if ($is_package_order) $item['is_package'] = true;


        /** 处理文字描述 **/
        $item['status_name']      = $this->status[$item['status']];//状态
        $item['work_status_name'] = $this->work_status[$item['work_status']];//工作状态

        //回显,展示
        $item['distance_km'] = '0km';
        if ($item['distance']) $item['distance_km'] = round($item['distance'] / 1000, 2) . 'km';


        if ($item['type']) {
            $type_list  = $this->getParams($item['type']);
            $type_list2 = [];
            foreach ($type_list as $key => $value) {
                $type_list2[$key]  = $this->type[$value];
                $item['type_name'] .= $this->type[$value] . '/';
            }
            $item['type_name'] = substr($item['type_name'], 0, -1);
            $item['type_list'] = $type_list2;
            $item['type']      = $this->getParams($item['type']);
        }


        //查询用户信息
        $user_info         = $MemberInit->get_find(['id' => $item['user_id']]);
        $item['user_info'] = $user_info;


        //年级信息
        if ($item['grade_ids']) {
            $grade_list         = $TeacherClassModel->where('id', 'in', $this->getParams($item['grade_ids']))->column('name');
            $grade_name         = $this->setParams($grade_list);
            $item['grade_list'] = $grade_list;
            $item['grade_name'] = $grade_name;
            $item['grade_ids']  = $this->getParams($item['grade_ids']);
        }

        //科目信息
        if ($item['course_ids']) {
            $course_list         = $TeacherClassModel->where('id', 'in', $this->getParams($item['course_ids']))->column('name');
            $course_name         = $this->setParams($course_list);
            $item['course_list'] = $course_list;
            $item['course_name'] = $course_name;
            $item['course_ids']  = $this->getParams($item['course_ids']);
        }


        /** 处理数据 **/
        if ($this->InterfaceType == 'api') {
            /** api处理文件 **/
            if ($item['qualification_images']) $item['qualification_images'] = $this->getImagesUrl($item['qualification_images']);//资质证书
            if ($item['images']) $item['images'] = $this->getImagesUrl($item['images']);//个人照片
            if ($item['video']) $item['video'] = cmf_get_asset_url($item['video']);//个人视频


            /** 处理富文本 **/


            if ($this->DataFormat == 'find') {
                /** find详情数据格式 **/


            } else {
                /** list列表数据格式 **/

            }


        } else {
            /** admin处理文件 **/
            if ($item['qualification_images']) $item['qualification_images'] = $this->getParams($item['qualification_images']);//资质证书
            if ($item['images']) $item['images'] = $this->getParams($item['images']);//个人照片


            if ($this->DataFormat == 'find') {
                /** find详情数据格式 **/


                /** 处理富文本 **/


            } else {
                /** list列表数据格式 **/

            }

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
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)


        /** 查询数据 **/
        if ($params['is_recommend']) {
            $result = $TeacherModel
                ->where($where)
                ->orderRaw('rand()')
                ->field($params['field'] ?? $this->Field)
                ->limit($params["limit"] ?? $this->Limit)
                ->select()
                ->each(function ($item, $key) use ($params) {

                    /** 处理公共数据 **/
                    $item = $this->common_item($item, $params);

                    return $item;
                });
        } else {
            $result = $TeacherModel
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
        }

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
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)


        /** 查询数据 **/
        if ($params['is_recommend']) {
            $result = $TeacherModel
                ->where($where)
                ->orderRaw('rand()')
                ->field($params['field'] ?? $this->Field)
                ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
                ->each(function ($item, $key) use ($params) {

                    /** 处理公共数据 **/
                    $item = $this->common_item($item, $params);

                    return $item;
                });
        } else {
            $result = $TeacherModel
                ->where($where)
                ->order($params['order'] ?? $this->Order)
                ->field($params['field'] ?? $this->Field)
                ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
                ->each(function ($item, $key) use ($params) {

                    /** 处理公共数据 **/
                    $item = $this->common_item($item, $params);

                    return $item;
                });
        }
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
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)

        /** 查询数据 **/
        $result = $TeacherModel
            ->alias('a')
            ->join('member b', 'a.user_id = b.id')
            ->where($where)
            ->order('a.id desc')
            ->field('a.*')
            ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
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
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)

        /** 可直接传id,或者where条件 **/
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];
        if (empty($where)) return false;

        /** 查询数据 **/
        $item = $TeacherModel
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
        if ($params['qualification_images']) $params['qualification_images'] = $this->setParams($params['qualification_images']);//资质证书
        if ($params['images']) $params['images'] = $this->setParams($params['images']);//个人照片
        if ($params['grade_ids']) $params['grade_ids'] = $this->setParams($params['grade_ids']);//个人照片
        if ($params['course_ids']) $params['course_ids'] = $this->setParams($params['course_ids']);//个人照片
        if ($params['tag']) $params['tag'] = $this->setParams($params['tag']);//个人照片
        if ($params['type']) $params['type'] = $this->setParams($params['type']);//个人照片


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
        if ($params['qualification_images']) $params['qualification_images'] = $this->setParams($params['qualification_images']);//资质证书
        if ($params['images']) $params['images'] = $this->setParams($params['images']);//个人照片
        if ($params['grade_ids']) $params['grade_ids'] = $this->setParams($params['grade_ids']);//个人照片
        if ($params['course_ids']) $params['course_ids'] = $this->setParams($params['course_ids']);//个人照片
        if ($params['tag']) $params['tag'] = $this->setParams($params['tag']);//个人照片
        if ($params['type']) $params['type'] = $this->setParams($params['type']);//个人照片


        $result = $this->edit_post($params, $where);//admin提交

        return $result;
    }


    /**
     * 提交 编辑&添加
     * @param $params
     * @param $where where条件(或传id)
     * @return void
     */
    public function edit_post($params, $where = [])
    {
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)


        /** 查询详情数据 && 需要再打开 **/
        //if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]],["DataFormat"=>"list"]);
        //if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where,["DataFormat"=>"list"]);

        /** 可直接传id,或者where条件 **/
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];


        /** 公共提交,处理数据 **/


        //处理时间格式
        if ($params['pass_time'] && is_string($params['pass_time'])) $params['pass_time'] = strtotime($params['pass_time']);//通过时间
        if ($params['refuse_time'] && is_string($params['refuse_time'])) $params['refuse_time'] = strtotime($params['refuse_time']);//拒绝时间


        if (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $TeacherModel->where($where)->strict(false)->update($params);
            //if ($result) $result = $item["id"];
        } elseif (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $TeacherModel->where("id", "=", $params["id"])->strict(false)->update($params);
            //if($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $TeacherModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 提交(副本,无任何操作,不查询详情,不返回id) 编辑&添加
     * @param $params
     * @param $where where 条件(或传id)
     * @return void
     */
    public function edit_post_two($params, $where = [])
    {
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)


        /** 可直接传id,或者where条件 **/
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];


        /** 公共提交,处理数据 **/


        if (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $TeacherModel->where($where)->strict(false)->update($params);
        } elseif (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $TeacherModel->where("id", "=", $params["id"])->strict(false)->update($params);
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $TeacherModel->strict(false)->insert($params);
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
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)


        if ($type == 1) $result = $TeacherModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $TeacherModel->destroy($id, true);//真实删除

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
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)

        $where   = [];
        $where[] = ["id", "in", $id];//$id 为数组


        $params["update_time"] = time();
        $result                = $TeacherModel->where($where)->strict(false)->update($params);//修改状态

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
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理   (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ["id", "=", $k];
            $result  = $TeacherModel->where($where)->strict(false)->update(["list_order" => $v, "update_time" => time()]);//排序
        }

        return $result;
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $TeacherInit  = new \init\TeacherInit();//教师管理   (ps:InitController)
        $TeacherModel = new \initmodel\TeacherModel(); //教师管理  (ps:InitModel)

        $result = $TeacherInit->get_list($where, $params);

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
        $Excel->excelExports($result, $headArrValue, ["fileName" => "教师管理"]);
    }

}
