<?php

namespace app\admin\controller;

/**
 * @adminMenuRoot(
 *     "name"                =>"Member",
 *     "name_underline"      =>"member",
 *     "controller_name"     =>"member",
 *     "table_name"          =>"member",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"用户管理",
 *     "author"              =>"",
 *     "create_time"         =>"2024-12-17 11:34:36",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\MemberController();
 * )
 */


use initmodel\AssetModel;
use think\facade\Db;
use cmf\controller\AdminBaseController;


class MemberController extends AdminBaseController
{
    //    public function initialize()
    //    {
    //        parent::initialize();
    //    }


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
     * 展示
     * @adminMenu(
     *     'name'   => 'Member',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '会员管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $this->base_index();
        $params      = $this->request->param();
        $MemberInit  = new \init\MemberInit();//会员管理
        $MemberModel = new \initmodel\MemberModel();//用户管理

        $this->assign("excel", $params);//导出使用


        $where = [];
        if ($params["keyword"]) $where[] = ["nickname|phone", "like", "%{$params["keyword"]}%"];


        //导出数据
        if ($params["is_export"]) $this->export_excel($where, $params);


        $params['InterfaceType'] = 'admin';//身份类型,后台
        $params['field']         = '*';//所有字段
        $result                  = $MemberInit->get_list_paginate($where, $params);

        $this->assign("list", $result);
        $this->assign('pagination', $result->render());//单独提取分页出来
        $this->assign("page", $result->currentPage());


        return $this->fetch();
    }


    //编辑详情
    public function edit()
    {
        $this->base_edit();
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理

        $params['InterfaceType'] = 'admin';//身份类型,后台
        $params['field']         = '*';//所有字段

        $where   = [];
        $where[] = ['id', '=', $params['id']];


        $result = $MemberInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //提交编辑-副本
    public function edit_post_two()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理

        $result = $MemberInit->edit_post_two($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功");
    }


    //提交编辑
    public function edit_post()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理

        $result = $MemberInit->edit_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", "index{$this->params_url}");

    }

    //编辑详情
    public function refuse()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理

        $params['InterfaceType'] = 'admin';//身份类型,后台
        $params['field']         = '*';//所有字段

        $where   = [];
        $where[] = ['id', '=', $params['id']];


        $result = $MemberInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //驳回,更改状态
    public function audit_post()
    {
        $MemberInit = new \init\MemberInit();//会员管理
        $params     = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $item                    = $MemberInit->get_find($where);
        if (empty($item)) $this->error("暂无数据");

        //通过&拒绝时间
        if ($params['status'] == 2) $params['pass_time'] = time();
        if ($params['status'] == 3) $params['refuse_time'] = time();

        //提交数据
        $result = $MemberInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("操作成功", "index{$this->params_url}");
    }


    //添加
    public function add()
    {
        $this->base_edit();

        return $this->fetch();
    }


    //添加提交
    public function add_post()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理


        $result = $MemberInit->edit_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", "index{$this->params_url}");
    }


    //查看详情
    public function find()
    {
        $this->base_edit();


        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理

        $params['InterfaceType'] = 'admin';//身份类型,后台
        $params['field']         = '*';//所有字段

        $where   = [];
        $where[] = ['id', '=', $params['id']];


        $result = $MemberInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }


        return $this->fetch();
    }


    //导入
    public function member_import()
    {
        return $this->fetch();
    }


    //删除
    public function delete()
    {
        $id         = $this->request->param('id/a');
        $MemberInit = new \init\MemberInit();//会员管理

        if (empty($id)) $id = $this->request->param('ids/a');


        $result = $MemberInit->delete_post($id);
        if (empty($result)) $this->error('失败请重试');


        $this->success("删除成功");
    }


    //更新排序
    public function list_order_post()
    {
        $params     = $this->request->param('list_order/a');
        $MemberInit = new \init\MemberInit();//会员管理


        $result = $MemberInit->list_order_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功");
    }

    //更改状态
    public function batch_post()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理


        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        $result = $MemberInit->batch_post($id, $params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", "index{$this->params_url}");
    }

    /******************************************   余额操作 & 积分操作  ********************************************************/

    //查询下级列表
    public function children()
    {
        $params     = $this->request->param();
        $MemberInit = new \init\MemberInit();//会员管理

        $where = [];
        if ($params['pid']) {
            $where[] = ['pid', '=', $params['pid']];
            $this->assign("pid", $params['pid']);
        }

        $params['InterfaceType'] = 'admin';//身份类型,后台
        $result                  = $MemberInit->get_list_paginate($where, $params);


        $this->assign("list", $result);
        $this->assign('page', $result->render());//单独提取分页出来


        return $this->fetch();
    }




    //会员关系图
    public function children_tree()
    {
        return $this->fetch();
    }


    //会员关系图 用户数据
    public function get_user_list()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理
        $params      = $this->request->param();

        //条件
        $map = [];
        if (empty($params['nickname']) && empty($params['phone'])) $map[] = ['pid', '=', $params['pid'] ?? 0];
        if (isset($params['nickname']) && $params['nickname']) $map[] = ['nickname', 'like', "%{$params['nickname']}%"];
        if (isset($params['phone']) && $params['phone']) $map[] = ['phone', '=', $params['phone']];

        $result = $MemberModel->where($map)
            ->field('id,nickname,avatar,phone,create_time')
            ->order('id')
            ->select()
            ->each(function ($item, $key) use ($MemberModel) {


                //判断是否有子级
                $item['isLeaf'] = true;
                if ($MemberModel->where('pid', $item['id'])->count()) $item['isLeaf'] = false;

                return $item;
            });


        $this->success("请求成功", '', $result);
    }




    //关系树状图 (废弃)
    public function children_tree999()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理


        $rootMembers = $MemberModel::where('pid', 0)
            ->field('id,nickname,pid,phone,vip_id,performance')
            ->select()
            ->each(function ($item, $key) {

                //处理公共数据


                return $item;
            });


        $result = [];
        foreach ($rootMembers as $rootMember) {
            $rootMember['title']        = "({$rootMember['id']}) {$rootMember['nickname']} " . "<span style='color:#b89602'>{$rootMember['phone']}</span>" . "<span style='color:#0058fa;margin-left: 6px;'>{$rootMember['vip_name']}</span>" . "<span style='margin-left: 6px;'>  </span>";
            $subMembers                 = self::getSubMembers($rootMember->id);
            $rootMemberData             = $rootMember->toArray();
            $rootMemberData['children'] = $subMembers;
            $result[]                   = $rootMemberData;
        }

        $this->assign("member_list", json_encode($result));


        return $this->fetch();
    }

    // 递归获取子成员的方法 (废弃)
    protected static function getSubMembers($parentId)
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理

        $subMembers = $MemberModel::where('pid', $parentId)
            ->field('id,nickname,pid,phone,vip_id,performance')
            ->select()
            ->each(function ($item, $key) {

                //处理公共数据

                return $item;
            });


        $children = [];
        foreach ($subMembers as $subMember) {
            $subMember['title'] = "({$subMember['id']}) {$subMember['nickname']} " . "<span style='color:#b89602'>{$subMember['phone']}</span>" . "<span style='color:#0058fa;margin-left: 6px;'>{$subMember['vip_name']}</span>" . "<span style='margin-left: 6px;'> </span>";
            $subMemberData      = $subMember->toArray();
            $subChildren        = self::getSubMembers($subMember->id);
            if ($subChildren) {
                $subMemberData['children'] = $subChildren;
            }
            $children[] = $subMemberData;
        }
        return $children;
    }


    /**
     * 导出数据--用户导出
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $MemberInit = new \init\MemberInit();//会员管理

        $params['InterfaceType'] = 'admin';//身份类型,后台
        $result                  = $MemberInit->get_list($where, $params);

        $result = $result->toArray();
        foreach ($result as $k => &$item) {
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);

            //订单号过长问题
            if ($item["identity_number"]) $item["identity_number"] = $item["identity_number"] . "\t";
            if ($item["number_bank"]) $item["number_bank"] = $item["number_bank"] . "\t";
            if ($item["order_num"]) $item["order_num"] = $item["order_num"] . "\t";


            //图片链接 可用默认浏览器打开   后面为展示链接名字 --单独,多图特殊处理一下
            if ($item["image"]) $item["image"] = '=HYPERLINK("' . cmf_get_asset_url($item['image']) . '","图片.png")';


            //用户信息

            $item['userInfo'] = "(ID:{$item['id']}) {$item['nickname']}";
        }

        $headArrValue = [
            ["rowName" => "ID", "rowVal" => "id", "width" => 10],
            ["rowName" => "用户信息", "rowVal" => "userInfo", "width" => 30],
            ["rowName" => "手机号", "rowVal" => "phone", "width" => 20],
            ["rowName" => "身份类型", "rowVal" => "identity_name", "width" => 20],
            ["rowName" => "身份证号", "rowVal" => "identity_number", "width" => 30],
            ["rowName" => "积分", "rowVal" => "balance", "width" => 20],
            ["rowName" => "性别", "rowVal" => "gender", "width" => 20],
            ["rowName" => "年龄", "rowVal" => "age", "width" => 20],
            ["rowName" => "学历", "rowVal" => "educational", "width" => 30],
            ["rowName" => "开户行", "rowVal" => "opening_bank", "width" => 30],
            ["rowName" => "银行卡号", "rowVal" => "number_bank", "width" => 30],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];

        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => count($headArrValue)/2],
        //            ["rowName" => "列2", "acrossCells" => count($headArrValue)/2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "用户管理"]);
    }


}
