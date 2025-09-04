<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\facade\Db;
use think\facade\Request;


error_reporting(0);


class BaseController extends AdminBaseController
{

    /**
     * @var \app\common\model\Base
     */
    protected $model      = null;
    protected $where      = [];
    public    $admin_info = null;


    public function initialize()
    {
        parent::initialize();
        //管理员信息
        $this->admin_info = $this->get_admin_info(cmf_get_current_admin_id());
    }

    /**
     * 获取用户信息
     * @param $user_id 用户id
     * @return mixed
     */
    public function get_user_info($user_id)
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理

        $item = $MemberModel->where('id', '=', $user_id)->find();
        if ($item) $item['avatar'] = cmf_get_asset_url($item['avatar']);
        return $item;
    }


    /**
     * 获取管理员信息
     * @param $user_id 用户id
     * @return mixed
     */
    public function get_admin_info($user_id)
    {
        $AdminUserInit = new \init\AdminUserInit();//管理员    (ps:InitController)
        $item          = $AdminUserInit->get_find($user_id);
        return $item;
    }



}
