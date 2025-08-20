<?php
// +----------------------------------------------------------------------
// | 提现相关接口
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;


use initmodel\AssetModel;
use think\facade\Db;

error_reporting(0);


/**
 * @ApiController(
 *     "name"                    =>"Withdrawal",
 *     "name_underline"          =>"withdrawal",
 *     "controller_name"         =>"WithdrawalController",
 *     "table_name"              =>"withdrawal",
 *     "remark"                  =>"提现管理"
 *     "api_url"                 =>"/api/wxapp/withdrawal/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2025-01-12 17:34:25",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\WithdrawalController();
 *     "test_environment"        =>" http://lscs.ikun:9090/api/wxapp/withdrawal/index",
 *     "official_environment"    =>"https://lscs001.jscxkf.net/api/wxapp/withdrawal/index",
 * )
 */


/**
 * 1.数据库
 * 2.创建model
 * 3.后台管理
 * 4.配置管理提现金额等    提现管理最低金额   cmf_config('withdraw_amount')   提现扣除金额    cmf_config('withdraw_charges')
 * 5.用户表创建对应字段
 * ali_username:支付宝姓名  ali_account:支付宝账号  wx_image:微信收款码图片  bank_username:银行卡户名  bank_account:银行卡账号  opening_bank:开户行
 */
class WithdrawalController extends AuthController
{
    public function initialize()
    {
        parent::initialize();//初始化方法

        $this->type_array   = [1 => '支付宝', 2 => '微信'];
        $this->status_array = [1 => '审核中', 2 => '待确认', 3 => '已拒绝', 4 => '已转账'];
    }

    /**
     * 提现记录查询  (微信提现 stauts==2 收款按钮显示)    (余额记录也会出现提现记录)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"小程序端提现模块"},
     *     path="/wxapp/withdrawal/find_withdrawal_list",
     *     @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/withdrawal/find_withdrawal_list
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/withdrawal/find_withdrawal_list
     *   api: /wxapp/withdrawal/find_withdrawal_list
     *   remark_name: 提现记录查询
     *
     */
    public function find_withdrawal_list()
    {
        $this->checkAuth();
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理


        $params = $this->request->param();


        $map   = [];
        $map[] = ['user_id', '=', $this->user_id];
        $map[] = ['identity_type', '=', $this->user_info['identity_type'] ?? 'member'];

        //微信配置信息
        $plugin_config = cmf_get_option('weipay');


        $result = $MemberWithdrawalModel
            ->where($map)
            ->order('id desc')
            ->paginate($params['page_size'])
            ->each(function ($item, $key) use ($plugin_config) {

                $item['status_name'] = $this->status_array[$item['status']];
                $item['type_name']   = $this->type_array[$item['type']];
                $item['wx_mch_id']   = $plugin_config['wx_mch_id'];

                return $item;
            });

        $this->success('请求成功！', $result);
    }


    /**
     * 提交提现申请
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"小程序端提现模块"},
     *     path="/wxapp/withdrawal/add_withdrawal",
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
     * 	   @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="1支付宝 2微信 3银行卡 (不传默认为1)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="price",
     *         in="query",
     *         description="提现金额",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *   @OA\Parameter(
     *         name="opening_bank",
     *         in="query",
     *         description="开户行 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *   @OA\Parameter(
     *         name="bank_username",
     *         in="query",
     *         description="银行卡户名 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *   @OA\Parameter(
     *         name="bank_account",
     *         in="query",
     *         description="银行卡账号 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *   @OA\Parameter(
     *         name="ali_username",
     *         in="query",
     *         description="支付宝姓名 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="ali_account",
     *         in="query",
     *         description="支付宝账号 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="wx_image",
     *         in="query",
     *         description="微信收款码图片 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Parameter(
     *         name="wx_username",
     *         in="query",
     *         description="微信收款 个人姓名 (选)",
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/withdrawal/add_withdrawal
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/withdrawal/add_withdrawal
     *   api: /wxapp/withdrawal/add_withdrawal
     *   remark_name: 提交提现申请
     *
     */
    public function add_withdrawal()
    {
        $this->checkAuth();

        // 启动事务
        Db::startTrans();


        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理


        $params = $this->request->param();

        //获取身份类型
        $this->user_info['identity_type'] = $this->user_info['identity_type'] ?? 'member';

        if (empty($params['type'])) $params['type'] = 1;
        if (empty($params['price'])) $this->error('请填写正确的金额!');


        //先扣除指定金额
        if ($params['price'] > $this->user_info['balance']) $this->error('提现金额超出了可提现金额！');


        //计算手续费
        $withdraw_amount = cmf_config('withdraw_amount');
        if ($withdraw_amount > $params['price']) $this->error('提现金额不能低于' . $withdraw_amount . '元！');


        $charges = 0;//手续费
        // $withdraw_charges = cmf_config('withdraw_charges') / 100;
        // if ($withdraw_charges) {
        //     $charges = $params['price'] * $withdraw_charges;
        //     $charges = round($charges, 2);
        // }

        //需打款金额
        $rmb = round($params['price'] - $charges, 2);


        $order_num = $this->get_num_only('order_num', 8, 1, '', $MemberWithdrawalModel);
        //插入数据
        $recharge = [
            'type'          => $params['type'],
            'price'         => $params['price'],
            'user_id'       => $this->user_id,
            'identity_type' => $this->user_info['identity_type'],
            'openid'        => $this->openid,
            'wx_openid'     => $this->openid,
            'charges'       => $charges,
            'rmb'           => $rmb,
            'create_time'   => time(),
            'ali_username'  => $params['ali_username'],
            'ali_account'   => $params['ali_account'],
            'opening_bank'  => $params['opening_bank'],
            'wx_image'      => $params['wx_image'],
            'bank_username' => $params['bank_username'],
            'bank_account'  => $params['bank_account'],
            'wx_username'   => $params['wx_username'],
            'order_num'     => $order_num,
            'status'        => 1,
        ];

        //插入提现记录
        $result = $MemberWithdrawalModel->strict(false)->insert($recharge, true);


        if ($result) {
            $remark = "操作人[{$this->user_id}-{$this->user_info['nickname']}];操作说明[申请提现:{$params['price']}];操作类型[用户申请提现];";//管理备注
            AssetModel::decAsset('用户提现,扣除余额  [800]', [
                'operate_type'  => 'balance',//操作类型，balance|point ...
                'identity_type' => $this->user_info['identity_type'],//身份类型，member| ...
                'user_id'       => $this->user_id,
                'price'         => $recharge['price'],
                'order_num'     => $recharge['order_num'],
                'order_type'    => 800,
                'content'       => '用户提现',
                'remark'        => $remark,
                'order_id'      => 0,
            ]);


            // 提交事务
            Db::commit();

            $this->success('提交成功!');
        } else {
            $this->error('提交失败，请稍后再试！');
        }
    }


    /**
     * 微信转账回调 (接口回调,h5端)
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws Exception
     * @OA\Post(
     *     tags={"小程序端提现模块"},
     *     path="/wxapp/withdrawal/api_notify",
     *
     *
     *     @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号",
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
     *   test_environment: http://lscs.ikun:9090/api/wxapp/withdrawal/api_notify
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/withdrawal/api_notify
     *   api: /wxapp/withdrawal/api_notify
     *   remark_name: 微信转账回调 (接口回调,h5端)
     *
     */
    public function api_notify()
    {
        $params = $this->request->param();


        //修改提现状态
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理

        $map    = [];
        $map[]  = ['order_num', '=', $params['order_num']];
        $result = $MemberWithdrawalModel->where($map)->strict(false)->update([
            'status'      => 4,
            'pass_time'   => time(),
            'update_time' => time(),
        ]);


        if (empty($result)) $this->error('更新失败!');

        $this->success('操作成功');
    }


    /**
     * 获取总提现金额
     */
    public function withdrawal_total()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理

        //获取提现总金额
        $map2                  = [];
        $map2[]                = ['user_id', '=', $this->user_id];
        $map2[]                = ['status', 'in', [1, 2]];
        $withdrawal_pass_total = $MemberWithdrawalModel->where($map2)->sum('price');

        $map3                    = [];
        $map3[]                  = ['user_id', '=', $this->user_id];
        $map3[]                  = ['status', '=', 3];
        $withdrawal_refuse_total = $MemberWithdrawalModel->where($map3)->sum('price');

        $user['withdrawal_total'] = round($withdrawal_pass_total - $withdrawal_refuse_total, 2);
    }

}
