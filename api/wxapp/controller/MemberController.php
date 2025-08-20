<?php
// +----------------------------------------------------------------------
// | 会员中心
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use think\facade\Db;

header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:*');


error_reporting(0);


class MemberController extends AuthController
{
    //    public function initialize()
    //    {
    //        parent::initialize();//初始化方法
    //    }

    /**
     * 测试用
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/member/index
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/member/index
     *   api: /wxapp/member/index
     *   remark_name: 测试用
     *
     */
    public function index()
    {
        $MemberInit  = new \init\MemberInit();//用户管理
        $MemberModel = new \initmodel\MemberModel();//用户管理


        $map    = [];
        $map[]  = ['openid', '=', $openid ?? 1];
        $result = $MemberInit->get_my_info($map);

        $this->success('请求成功', $result);
    }


    /**
     * 查询会员信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_member",
     *
     *
     *
     *     @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/member/find_member
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/member/find_member
     *   api: /wxapp/member/find_member
     *   remark_name: 查询会员信息
     *
     */
    public function find_member()
    {
        $this->checkAuth();

        $MemberModel = new \initmodel\MemberModel();//用户管理
        $MemberInit  = new \init\MemberInit();//用户管理


        $map    = [];
        $map[]  = ['openid', '=', $this->openid];
        $result = $MemberInit->get_my_info($map);

        $this->success("请求成功!", $result);
    }


    /**
     * 更新会员信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/update_member",
     *
     *
     *     @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="nickname",
     *         in="query",
     *         description="昵称",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="手机号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="avatar",
     *         in="query",
     *         description="头像",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *      @OA\Parameter(
     *         name="used_pass",
     *         in="query",
     *         description="旧密码,如需要传,不需要请勿传",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="pass",
     *         in="query",
     *         description="更改密码,如需要传,不需要请勿传",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/member/update_member
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/member/update_member
     *   api: /wxapp/member/update_member
     *   remark_name: 更新会员信息
     *
     */
    public function update_member()
    {
        $this->checkAuth();

        $MemberModel = new \initmodel\MemberModel();//用户管理


        $params                = $this->request->param();
        $params['update_time'] = time();
        $member                = $this->user_info;


        //        $result = $this->validate($params, 'Member');
        //        if ($result !== true) $this->error($result);


        if (empty($member)) $this->error("该会员不存在!");
        if ($member['pid']) unset($params['pid']);


        //修改密码
        if ($params['pass']) {
            if (!cmf_compare_password($params['used_pass'], $member['pass'])) $this->error('旧密码错误');
            $params['pass'] = cmf_password($params['pass']);
        }

        $result = $MemberModel->where('id', $member['id'])->strict(false)->update($params);
        if ($result) {
            $result = $this->getUserInfoByOpenid($this->openid);
            $this->success("保存成功!", $result);
        } else {
            $this->error("保存失败!");
        }
    }


    /**
     * 账户资产变动明细
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_asset_list",
     *
     *
     *     @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="operate_type",
     *         in="query",
     *         description="操作字段类型:balance余额,point积分",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="change_type",
     *         in="query",
     *         description="类型:1=收入,2=支出 (选填)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="begin_time",
     *         in="query",
     *         description="2025-01-15",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="2025-01-15",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/member/find_asset_list
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/member/find_asset_list
     *   api: /wxapp/member/find_asset_list
     *   remark_name: 账户资产变动明细
     *
     */
    public function find_asset_list()
    {
        $this->checkAuth();
        $AssetModel = new \initmodel\AssetModel();

        $params = $this->request->param();


        //数据类型
        $operate_type_list = $AssetModel->operate_type;
        if (empty($params['operate_type'])) $params['operate_type'] = array_keys($operate_type_list)[0];


        $where   = [];
        $where[] = ['user_id', '=', $this->user_id];
        $where[] = ['identity_type', '=', $this->user_info['identity_type'] ?? 'member'];
        $where[] = ['operate_type', '=', $params['operate_type'] ?? 'balance'];
        $where[] = $this->getBetweenTime($params['begin_time'], $params['end_time']);
        if ($params['change_type']) $where[] = ['change_type', '=', $params['change_type'] ?? 1];

        $result = $AssetModel->where($where)
            ->field("id,user_id,order_num,operate_type,identity_type,order_type,price,content,change_type,create_time")
            ->order("id desc")
            ->paginate($params['page_size'])
            ->each(function ($item, $key) use ($operate_type_list) {

                if ($item['change_type'] == 2) {
                    $item['price'] = -$item['price'];
                } else {
                    $item['price'] = '+' . $item['price'];
                }


                return $item;
            });

        $this->success("请求成功！", $result);
    }


    /**
     * 团队列表查询
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_team_list",
     *
     *
     *
     *    @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="1 直接团队成员列表 2间接团队成员列表",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/member/find_team_list
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/member/find_team_list
     *   api: /wxapp/member/find_team_list
     *   remark_name: 团队列表查询
     *
     */
    public function find_team_list()
    {
        $this->checkAuth();
        $MemberModel = new \initmodel\MemberModel();//用户管理


        $params  = $this->request->param();
        $user_id = $this->user_id;
        if ($params['user_id']) {
            $user_id = $params['user_id'];
        }

        if ($params['type'] == 2) {
            $result = $MemberModel
                ->where("spid", $user_id)
                ->field('*')
                ->order("id desc")
                ->paginate(10)
                ->each(function ($item, $key) use ($MemberModel) {
                    $item['avatar']           = cmf_get_asset_url($item['avatar']);
                    $item['child_total_fans'] = $MemberModel->where('pid', $item['id'])->count(); //直接下级数
                    //$item['second_total_fans'] = $MemberModel->where('spid', $item['id'])->count(); //间接下级数

                    return $item;
                });
        } else {
            $result = $MemberModel
                ->where("pid", $user_id)
                ->field('*')
                ->order("id desc")
                ->paginate(10)
                ->each(function ($item, $key) use ($MemberModel) {
                    $item['avatar']           = cmf_get_asset_url($item['avatar']);
                    $item['child_total_fans'] = $MemberModel->where('pid', $item['id'])->count(); //直接下级数
                    //$item['second_total_fans'] = $MemberModel->where('spid', $item['id'])->count(); //间接下级数

                    return $item;
                });
        }
        $this->success("请求成功！", $result);
    }


    /**
     * 获客海报&分享&推广二维码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/poster",
     *
     *
     *     @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/member/poster
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/member/poster
     *   api: /wxapp/member/poster
     *   remark_name: 获客海报&分享&推广二维码
     *
     */
    public function poster()
    {
        $this->checkAuth();

        $Qr               = new \init\QrInit();
        $PublicController = new PublicController();
        $MemberModel      = new \initmodel\MemberModel();//用户管理

        //分销+二维码图
        $image = $this->user_info['invite_image'];
        if (empty($image)) {
            $image_url = $PublicController->wx_qrcode($this->user_info['invite_code'], 'pages/index/index', 2);
            $image     = $Qr->applet_share($image_url);
            //$MemberModel->where('id', '=', $this->user_id)->update(['invite_image' => $image]);
        }


        //带头像昵称图
        $wx       = cmf_get_asset_url($this->user_info['wx_image']);
        $avatar   = cmf_get_asset_url($this->user_info['avatar']);
        $username = $this->user_info['username'];
        $image    = $Qr->drawing($wx, $username, $avatar);

        $this->success('请求成功', cmf_get_asset_url($image));
    }
}