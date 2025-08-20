<?php

namespace app\admin\controller;

use api\wxapp\controller\WxBaseController;
use initmodel\AssetModel;
use think\App;
use think\db\Query;
use think\facade\Db;
use cmf\controller\AdminBaseController;


/**
 * @adminMenuRoot(
 *     "name"                =>"Withdrawal",
 *     "name_underline"      =>"withdrawal",
 *     "controller_name"     =>"withdrawal",
 *     "table_name"          =>"withdrawal",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"check",
 *     "remark"              =>"提现管理",
 *     "author"              =>"",
 *     "create_time"         =>"2025-01-12 18:38:03",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\WithdrawalController();
 * )
 */
class WithdrawalController extends AdminBaseController
{
    /**
     * 首页基础信息
     */
    protected function base_index()
    {
        $this->type_array    = [1 => '支付宝', 2 => '微信', 3 => '银行卡'];
        $this->status_array  = [1 => '审核中', 2 => '待确认', 3 => '已拒绝', 4 => '已转账'];
        $this->identity_type = ['member' => '用户', 'technician' => '技师'];

        $this->assign('status_list', $this->status_array);
        $this->assign('type_list', $this->type_array);
    }

    /**
     * 编辑,添加基础信息
     */
    protected function base_edit()
    {

    }

    /**
     * 提现记录查询
     */
    public function index()
    {
        $this->base_index();//处理基础文字
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $MemberInit            = new \init\MemberInit();//会员管理 (ps:InitController)

        $params = $this->request->param();


        $where   = [];
        $where[] = ['id', '>', 0];
        if (isset($params['keyword']) && $params['keyword']) $where[] = ['ali_username|ali_account', 'like', "%{$params['keyword']}%"];
        if (isset($params['type']) && $params['type']) $where[] = ['type', '=', $params['type']];
        if (isset($params['status']) && $params['status']) $where[] = ['status', '=', $params['status']];
        if ($params['user_id']) $where[] = ['user_id', '=', $params['user_id']];
        if ($params['identity_type']) $where[] = ['identity_type', '=', $params['identity_type']];
        $where[] = $this->getBetweenTime($params['beginTime'], $params['endTime'], 'create_time');


        $list = $MemberWithdrawalModel
            ->where($where)
            ->order("id desc")
            ->paginate(10)
            ->each(function ($item, $key) use ($MemberInit) {
                if ($item['create_time']) $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);

                if ($item['identity_type'] == 'member') {
                    $item['user_info'] = $MemberInit->get_find($item['user_id']);
                } else {
                    //                    $user_info           = $TechnicianInit->get_find($item['user_id']);
                    //                    $user_info['avatar'] = $user_info['avatar'][0];
                    //                    $item['user_info']   = $user_info;
                }

                $item['type_name']          = $this->type_array[$item['type']];
                $item['status_name']        = $this->status_array[$item['status']];
                $item['identity_type_name'] = $this->identity_type[$item['identity_type']];


                return $item;
            });


        $list->appends($params);

        // 获取分页显示
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('list', $list);

        return $this->fetch();
    }


    /**
     * 修改状态
     */
    public function update_withdrawal()
    {
        // 启动事务
        Db::startTrans();


        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $WxBaseController      = new WxBaseController();//微信提现,打款板块


        $admin_id_and_name     = cmf_get_current_admin_id() . '-' . session('name');//管理员信息
        $params                = $this->request->param();
        $params['update_time'] = time();


        //判断是否已处理
        $withdrawal_info = $MemberWithdrawalModel->where('id', $params['id'])->find();
        if ($withdrawal_info['status'] != 1) $this->error("已处理不能重复处理!");


        //更新
        $result = $MemberWithdrawalModel->where('id', $params['id'])
            ->strict(false)
            ->update($params);


        if ($result) {

            //申请通过   处理 微信打款板块
            if ($withdrawal_info['type'] == 2 && $params['status'] == 2) {
                $order_num       = $withdrawal_info['order_num'];
                $openid          = $withdrawal_info['openid'];
                $rmb             = $withdrawal_info['rmb'];//应打款金额,已经扣除服务费了
                $wx_username     = $withdrawal_info['wx_username'];
                $transfer_result = $WxBaseController->crteateMchPay($order_num, $openid, $rmb, $wx_username);
                if (isset($transfer_result['result_code']) && $transfer_result['result_code'] == 'SUCCESS') {
                    $out_bill_no      = $transfer_result['out_bill_no']; //商户内部单号
                    $package_info     = $transfer_result['package_info'];//前端确认收款需要使用
                    $transfer_bill_no = $transfer_result['transfer_bill_no'];//微信单号

                    //更新提现记录
                    $MemberWithdrawalModel->where('order_num', '=', $out_bill_no)->strict(false)->update([
                        'transfer_bill_no' => $transfer_bill_no,
                        'package_info'     => $package_info
                    ]);

                } else {
                    $this->error($transfer_result['err_code_des'] ?? '转账失败');
                }
            }


            //驳回
            if ($params['status'] == 3) {
                $remark = "操作人[{$admin_id_and_name}];操作说明[提现驳回:{$params['refuse']}];操作类型[管理员驳回提现申请];";//管理备注
                //技师余额提现驳回
                if ($withdrawal_info['identity_type'] == 'member') {

                    AssetModel::incAsset('用户提现驳回,退回余额 [810]', [
                        'operate_type'  => 'balance',//操作类型，balance|point ...
                        'identity_type' => $withdrawal_info['identity_type'],//身份类型，member| ...
                        'user_id'       => $withdrawal_info['user_id'],
                        'price'         => $withdrawal_info['price'],
                        'order_num'     => $withdrawal_info['order_num'],
                        'order_type'    => 810,
                        'content'       => '提现驳回:' . $params['refuse'],
                        'remark'        => $remark,
                        'order_id'      => $withdrawal_info['id'],
                    ]);

                }

                //用户佣金提现驳回
                if ($withdrawal_info['identity_type'] == 'member2') {

                    AssetModel::incAsset('用户提现驳回,退回佣金(积分) [810]', [
                        'operate_type'  => 'point',//操作类型，balance|point ...
                        'identity_type' => $withdrawal_info['identity_type'],//身份类型，member| ...
                        'user_id'       => $withdrawal_info['user_id'],
                        'price'         => $withdrawal_info['price'],
                        'order_num'     => $withdrawal_info['order_num'],
                        'order_type'    => 810,
                        'content'       => '提现驳回:' . $params['refuse'],
                        'remark'        => $remark,
                        'order_id'      => $withdrawal_info['id'],
                    ]);

                }
            }


            // 提交事务
            Db::commit();

            $this->success("处理成功!");
        } else {
            $this->error("处理失败!");
        }
    }


    /**
     * 删除提现记录
     */
    public function delete_withdrawal()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $params                = $this->request->param();
        $result                = $MemberWithdrawalModel->where('id', $params['id'])->delete();
        if ($result) {
            $this->success("删除成功!");
        } else {
            $this->error("删除失败!");
        }
    }


    public function refuse()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $id                    = $this->request->param('id');

        $result = $MemberWithdrawalModel->find($id);
        if (empty($result)) {
            $this->error("not found data");
        }
        $toArray = $result->toArray();

        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }
        return $this->fetch();
    }

}