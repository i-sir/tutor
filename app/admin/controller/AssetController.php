<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"Asset",
 *     "name_underline"      =>"asset",
 *     "controller_name"     =>"asset",
 *     "table_name"          =>"asset",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"用户资产变动记录",
 *     "author"              =>"",
 *     "create_time"         =>"2025-06-01 11:13:24",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\AssetController();
 * )
 */


use initmodel\AssetModel;
use think\facade\Db;
use cmf\controller\AdminBaseController;


class AssetController extends AdminBaseController
{

    // public function initialize(){
    //	//用户资产变动记录
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


    //操作 积分 或余额
    public function operate()
    {
        $AssetModel = new \initmodel\AssetModel();


        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }


        $this->assign('operate_type_list', $AssetModel->operate_type);//操作字段类型
        $this->assign('identity_type_list', $AssetModel->identity_type);//身份类型


        return $this->fetch();
    }

    //提交
    public function operate_post()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理
        $AssetModel  = new \initmodel\AssetModel();

        $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息


        $params                  = $this->request->param();
        $params['identity_type'] = $params['identity_type'] ?? 'member';

        //用户
        if ($params['identity_type'] == 'member') $info = $MemberModel->where('id', '=', $params['id'])->find();
        //店铺
        if ($params['identity_type'] == 'shop') $info = [];

        //找出对应订单类型
        $operate_order = $AssetModel->operate_order_admin[$params['operate_type']];//操作类型
        $order_type    = $operate_order[$params['change_type']];//订单类型


        //增加
        if ($params['change_type'] == 1) {
            if (empty($params['content'])) $params['content'] = '管理员添加';

            //操作说明,备注
            if ($params['operate_type'] == 'balance') $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[增加用户余额];";//管理备注
            if ($params['operate_type'] == 'point') $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[增加用户积分];";//管理备注


            AssetModel::incAsset('管理员添加记录 [1000]', [
                'operate_type'  => $params['operate_type'],//操作类型，balance|point ...
                'identity_type' => $params['identity_type'],//身份类型，member| ...
                'user_id'       => $params['id'],
                'price'         => $params['price'],
                'order_num'     => cmf_order_sn(),
                'order_type'    => $order_type,
                'content'       => $params['content'],
                'remark'        => $remark,
                'order_id'      => 0,
                'admin_id'      => session('ADMIN_ID'),
                'admin_name'    => $this->admin_info['user_login'],
            ]);
        }

        //扣除
        if ($params['change_type'] == 2) {
            if (empty($params['content'])) $params['content'] = '管理员扣除';
            if ($info[$params['operate_type']] < $params['price']) $this->error('请输入正确金额');


            //操作说明,备注
            if ($params['operate_type'] == 'balance') $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[扣除用户余额];";//管理备注
            if ($params['operate_type'] == 'point') $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[扣除用户积分];";//管理备注


            AssetModel::decAsset('管理员扣除记录 [1000]', [
                'operate_type'  => $params['operate_type'],//操作类型，balance|point ...
                'identity_type' => $params['identity_type'],//身份类型，member| ...
                'user_id'       => $params['id'],
                'price'         => $params['price'],
                'order_num'     => cmf_order_sn(),
                'order_type'    => $order_type,
                'content'       => $params['content'],
                'remark'        => $remark,
                'order_id'      => 0,
                'admin_id'      => session('ADMIN_ID'),
                'admin_name'    => $this->admin_info['user_login'],
            ]);
        }


        $this->success('操作成功');
    }


    //个人日志
    public function log()
    {
        $AssetModel = new \initmodel\AssetModel();


        //数据类型
        $operate_type_list     = $AssetModel->operate_type;//操作字段类型
        $operate_type_log_list = $AssetModel->operate_type_log;//菜单栏 类型列表
        $change_type_list      = $AssetModel->change_type;//变动类型
        $order_type_list       = $AssetModel->order_type;//订单类型
        $identity_type         = $AssetModel->identity_type;//身份类型

        $this->assign('operate_type_list', $operate_type_list);//操作字段类型
        $this->assign('operate_type_log_list', $operate_type_log_list);//菜单栏 类型列表
        $this->assign('identity_type_list', $identity_type);//身份类型


        $params = $this->request->param();

        //默认获取第一个,操作字段类型
        if (empty($params['operate_type'])) $params['operate_type'] = array_keys($operate_type_log_list)[0];
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }


        //筛选条件
        $map   = [];
        $map[] = ["user_id", "=", $params["user_id"]];
        $map[] = ["identity_type", "=", $params["identity_type"] ?? 'member'];
        $map[] = ["operate_type", "=", $params["operate_type"] ?? 'balance'];
        $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);
        if ($params['keyword']) $map[] = ["content", "like", "%{$params['keyword']}%"];


        //导出数据
        if ($params["is_export"]) $this->export_excel($map, $params);


        $result = $AssetModel->where($map)
            ->order('id desc')
            ->paginate(['list_rows' => 15, 'query' => $params])
            ->each(function ($item, $key) use ($change_type_list, $order_type_list, $operate_type_list) {

                $item['change_type_name']  = $change_type_list[$item['change_type']];
                $item['order_type_name']   = $order_type_list[$item['order_type']];
                $item['operate_type_name'] = $operate_type_list[$item['operate_type']];


                return $item;
            });


        $this->assign("list", $result);
        $this->assign('page', $result->render());//单独提取分页出来

        return $this->fetch();
    }


    //所有人信息
    public function log_all()
    {
        $AssetModel  = new \initmodel\AssetModel();
        $MemberModel = new \initmodel\MemberModel();


        //数据类型
        $operate_type_list     = $AssetModel->operate_type;//操作字段类型
        $operate_type_log_list = $AssetModel->operate_type_log;//菜单栏 类型列表
        $change_type_list      = $AssetModel->change_type;//变动类型
        $order_type_list       = $AssetModel->order_type;//订单类型
        $identity_type         = $AssetModel->identity_type;//身份类型
        $this->assign('operate_type_list', $operate_type_list);//操作字段类型
        $this->assign('operate_type_log_list', $operate_type_log_list);//菜单栏 类型列表
        $this->assign('identity_type_list', $identity_type);//身份类型

        $params = $this->request->param();

        //默认获取第一个,操作字段类型
        if (empty($params['operate_type'])) $params['operate_type'] = array_keys($operate_type_log_list)[0];
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }


        //普通查询
        $map   = [];
        $map[] = ["identity_type", "=", $params["identity_type"] ?? 'member'];
        $map[] = ["operate_type", "=", $params["operate_type"] ?? 'balance'];
        $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);
        if ($params["user_id"]) $map[] = ["user_id", "=", $params["user_id"]];
        if ($params['keyword']) $map[] = ["content", "like", "%{$params['keyword']}%"];


        //拼表查询
        if ($params['user_keyword']) {
            $map   = [];
            $map[] = ["l.identity_type", "=", $params["identity_type"] ?? 'member'];
            $map[] = ["l.operate_type", "=", $params["operate_type"] ?? 'balance'];
            $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime'], 'l.create_time');
            if ($params["user_id"]) $map[] = ["l.user_id", "=", $params["user_id"]];
            if ($params['keyword']) $map[] = ["l.content", "like", "%{$params['keyword']}%"];
            $map[] = ["m.nickname|m.phone", "like", "%{$params['user_keyword']}%"];
        }


        //导出数据
        if ($params["is_export"]) $this->export_excel($map, $params);


        //拼表查询
        if ($params['user_keyword']) {
            $result = $AssetModel->alias('l')
                ->join('member m', 'l.user_id = m.id')
                ->where($map)
                ->order('l.id desc')
                ->field('l.*,m.nickname as user_nickname,m.phone as user_phone,m.avatar as user_avatar,m.openid as user_openid')
                ->paginate(['list_rows' => 15, 'query' => $params])
                ->each(function ($item, $key) use ($change_type_list, $order_type_list, $operate_type_list, $MemberModel) {
                    $item['change_type_name']  = $change_type_list[$item['change_type']];
                    $item['order_type_name']   = $order_type_list[$item['order_type']];
                    $item['operate_type_name'] = $operate_type_list[$item['operate_type']];


                    //子级
                    $child_user_info         = $MemberModel->where('id', $item['child_id'])->find();
                    $item['child_user_info'] = $child_user_info;

                    //个人信息
                    $item['user_info'] = [
                        'nickname' => $item['user_nickname'],
                        'phone'    => $item['user_phone'],
                        'avatar'   => cmf_get_asset_url($item['user_avatar']),
                        'openid'   => $item['user_openid'],
                        'id'       => $item['user_id'],
                    ];


                    return $item;
                });
        } else {
            //普通查询
            $result = $AssetModel->where($map)
                ->order('id desc')
                ->paginate(['list_rows' => 15, 'query' => $params])
                ->each(function ($item, $key) use ($change_type_list, $order_type_list, $operate_type_list, $MemberModel) {
                    $item['change_type_name']  = $change_type_list[$item['change_type']];
                    $item['order_type_name']   = $order_type_list[$item['order_type']];
                    $item['operate_type_name'] = $operate_type_list[$item['operate_type']];


                    //子级
                    $child_user_info         = $MemberModel->where('id', $item['child_id'])->find();
                    $item['child_user_info'] = $child_user_info;

                    //个人信息
                    $user_info         = $MemberModel->where('id', $item['user_id'])->find();
                    $item['user_info'] = $user_info;


                    return $item;
                });
        }


        $this->assign("list", $result);
        $this->assign('page', $result->render());//单独提取分页出来

        return $this->fetch();
    }


    /**
     * 导出数据 export_excel_use ,积分-余额导出
     * @param array $where 条件
     */
    public function export_excel($map = [], $params = [])
    {
        $AssetModel  = new \initmodel\AssetModel();
        $MemberModel = new \initmodel\MemberModel();


        //数据类型
        $operate_type_list = $AssetModel->operate_type;
        $change_type_list  = $AssetModel->change_type;
        $order_type_list   = $AssetModel->order_type;


        //拼表查询
        if ($params['user_keyword']) {
            $result = $AssetModel->alias('l')
                ->join('member m', 'l.user_id = m.id')
                ->where($map)
                ->order('l.id desc')
                ->field('l.*,m.nickname as user_nickname,m.phone as user_phone,m.avatar as user_avatar,m.openid as user_openid')
                ->select()
                ->each(function ($item, $key) use ($change_type_list, $order_type_list, $operate_type_list, $MemberModel) {
                    $item['change_type_name']  = $change_type_list[$item['change_type']];
                    $item['order_type_name']   = $order_type_list[$item['order_type']];
                    $item['operate_type_name'] = $operate_type_list[$item['operate_type']];


                    //子级
                    $child_user_info         = $MemberModel->where('id', $item['child_id'])->find();
                    $item['child_user_info'] = $child_user_info;

                    //个人信息
                    $item['user_info'] = [
                        'nickname' => $item['user_nickname'],
                        'phone'    => $item['user_phone'],
                        'avatar'   => cmf_get_asset_url($item['user_avatar']),
                        'openid'   => $item['user_openid'],
                        'id'       => $item['user_id'],
                    ];


                    return $item;
                });
        } else {
            //普通查询
            $result = $AssetModel->where($map)
                ->order('id desc')
                ->select()
                ->each(function ($item, $key) use ($change_type_list, $order_type_list, $operate_type_list, $MemberModel) {
                    $item['change_type_name']  = $change_type_list[$item['change_type']];
                    $item['order_type_name']   = $order_type_list[$item['order_type']];
                    $item['operate_type_name'] = $operate_type_list[$item['operate_type']];


                    //子级
                    $child_user_info         = $MemberModel->where('id', $item['child_id'])->find();
                    $item['child_user_info'] = $child_user_info;

                    //个人信息
                    $user_info         = $MemberModel->where('id', $item['user_id'])->find();
                    $item['user_info'] = $user_info;


                    return $item;
                });
        }


        $result = $result->toArray();

        foreach ($result as $k => &$item) {

            //导出基本信息
            $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);

            $item['change_type_name']  = $change_type_list[$item['change_type']];
            $item['order_type_name']   = $order_type_list[$item['order_type']];
            $item['operate_type_name'] = $operate_type_list[$item['operate_type']];

            //用户信息
            $user_info        = $item['user_info'];
            $item['userInfo'] = "(ID:{$user_info['id']}) {$user_info['nickname']}  {$user_info['phone']}";
        }

        $headArrValue = [
            ["rowName" => "ID", "rowVal" => "id", "width" => 10],
            ["rowName" => "用户信息", "rowVal" => "userInfo", "width" => 30],
            ["rowName" => "类型", "rowVal" => "change_type_name", "width" => 15],
            ["rowName" => "说明", "rowVal" => "content", "width" => 30],
            ["rowName" => "变得值", "rowVal" => "price", "width" => 20],
            ["rowName" => "变动前", "rowVal" => "before", "width" => 20],
            ["rowName" => "变动后", "rowVal" => "after", "width" => 20],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];


        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => 2],
        //            ["rowName" => "列2", "acrossCells" => 2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "操作记录"]);
    }


}
