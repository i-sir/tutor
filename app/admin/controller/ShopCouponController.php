<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"ShopCoupon",
 *     "name_underline"      =>"shop_coupon",
 *     "controller_name"     =>"ShopCoupon",
 *     "table_name"          =>"shop_coupon",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"优惠券",
 *     "author"              =>"",
 *     "create_time"         =>"2025-02-21 15:10:22",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\ShopCouponController();
 * )
 */


use api\wxapp\controller\PublicController;
use init\QrInit;
use think\facade\Db;
use cmf\controller\AdminBaseController;


class ShopCouponController extends AdminBaseController
{

    //    public function initialize()
    //    {
    //        parent::initialize();
    //    }


    /**
     * 首页列表数据
     * @adminMenu(
     *     'name'             => 'ShopCoupon',
     *     'name_underline'   => 'shop_coupon',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '优惠券',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券    (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();

        /** 查询条件 **/
        $where = [];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params["keyword"]}%"];
        if ($params["test"]) $where[] = ["test", "=", $params["test"]];
        //if($params["status"]) $where[]=["status","=", $params["status"]];
        //$where[]=["type","=", 1];


        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表


        /** 导出数据 **/
        if ($params["is_export"]) $this->export_excel($where, $params);


        /** 查询数据 **/
        $result = $ShopCouponInit->get_list_paginate($where, $params);


        /** 数据渲染 **/
        $this->assign("list", $result);
        $this->assign("page", $result->render());//单独提取分页出来

        return $this->fetch();
    }

    //编辑详情
    public function edit()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券  (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表

        $result = $ShopCouponInit->get_find($where, $params);
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
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();


        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        /** 提交数据 **/
        $result = $ShopCouponInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //提交(副本,无任何操作) 编辑&添加
    public function edit_post_two()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();

        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        /** 提交数据 **/
        $result = $ShopCouponInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //驳回
    public function refuse()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券  (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];


        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $ShopCouponInit->get_find($where, $params);
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
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();

        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $item                    = $ShopCouponInit->get_find($where);
        if (empty($item)) $this->error("暂无数据");

        /** 通过&拒绝时间 **/
        if ($params['status'] == 2) $params['pass_time'] = time();
        if ($params['status'] == 3) $params['refuse_time'] = time();

        /** 提交数据 **/
        $result = $ShopCouponInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("操作成功");
    }


    //添加
    public function add()
    {
        return $this->fetch();
    }


    //添加提交
    public function add_post()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();

        /** 插入数据 **/
        $result = $ShopCouponInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //查看详情
    public function find()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券    (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $ShopCouponInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        /** 数据格式转数组 **/
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //删除
    public function delete()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        /** 删除数据 **/
        $result = $ShopCouponInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功", "index{$this->params_url}");
    }


    //批量操作
    public function batch_post()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $ShopCouponInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //更新排序
    public function list_order_post()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $params          = $this->request->param("list_order/a");

        //提交更新
        $result = $ShopCouponInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)


        $result = $ShopCouponInit->get_list($where, $params);

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


    //发放优惠券,用户列表
    public function send()
    {
        $MemberInit = new \init\MemberInit();//会员管理
        $params     = $this->request->param();


        /** 查询数据 **/
        $map       = [];
        $map[]     = ['nickname|phone|id', 'like', "%{$params['keyword']}%"];
        $user_list = $MemberInit->get_list($map);
        if (empty($user_list)) $this->error("暂无数据");

        $this->assign("user_list", $user_list);


        return $this->fetch();
    }


    public function send_post()
    {
        $params = $this->request->param();

        $ShopCouponModel     = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)
        $PublicController    = new PublicController();
        $QrInit              = new QrInit();


        //优惠券信息
        $coupon_info = $ShopCouponModel->where(['id' => $params['coupon_id']])->find();
        if (empty($coupon_info)) $this->error("优惠券不存在!");


        //处理优惠券到期时间 && 按天计算
        if ($coupon_info['type'] == 2) $coupon_info['end_time'] = time() + (86400 * $coupon_info['day']);


        foreach ($params['ids'] as $user_id) {
            //生成优惠券码
            $code     = $this->get_num_only('code', 10, 4,'',$ShopCouponUserModel);

            $qr_image = $QrInit->get_qr($code);//二维码

            $ShopCouponUserModel->strict(false)->insert([
                'user_id'     => $user_id,
                'coupon_id'   => $params['coupon_id'],
                'name'        => $coupon_info['name'],
                'cav_type'    => $coupon_info['cav_type'],
                'full_amount' => $coupon_info['full_amount'],
                'amount'      => $coupon_info['amount'],
                'type'        => $coupon_info['type'],
                'coupon_type' => $coupon_info['coupon_type'],
                'end_time'    => $coupon_info['end_time'],
                'point'       => $coupon_info['point'],
                'code'        => $code,
                'qr_image'    => $qr_image,
                'is_show'     => 1,
                'start_time'  => time(),
                'create_time' => time(),
            ]);

        }


        $this->success('发放成功!');
    }


}
