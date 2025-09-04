<?php

namespace api\wxapp\controller;

use initmodel\AssetModel;
use initmodel\MemberModel;

/**
 * @ApiController(
 *     "name"                    =>"Init",
 *     "name_underline"          =>"init",
 *     "controller_name"         =>"Init",
 *     "table_name"              =>"无",
 *     "remark"                  =>"基础接口,封装的接口"
 *     "api_url"                 =>"/api/wxapp/init/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-24 17:16:22",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\InitController();
 *     "test_environment"        =>"http://tutor.ikun:9090/api/wxapp/init/index",
 *     "official_environment"    =>"https://xcxkf159.aubye.com/api/wxapp/init/index",
 * )
 */
class InitController
{
    /**
     * 本模块,用于封装常用方法,复用方法
     */


    /**
     * 给上级发放佣金
     * @param $p_user_id 上级id
     * @param $child_id  子级id
     *                   https://xcxkf159.aubye.com/api/wxapp/init/send_invitation_commission?p_user_id=1
     */
    public function sendInvitationCommission($p_user_id = 0, $child_id = 0)
    {
        //邀请佣金
        $price  = cmf_config('invitation_rewards');
        $remark = "操作人[邀请奖励];操作说明[邀请好友得佣金];操作类型[佣金奖励];";//管理备注

        AssetModel::incAsset('邀请注册奖励,给上级发放佣金 [120]', [
            'operate_type'  => 'balance',//操作类型，balance|point ...
            'identity_type' => 'member',//身份类型，member| ...
            'user_id'       => $p_user_id,
            'price'         => $price,
            'order_num'     => cmf_order_sn(),
            'order_type'    => 120,
            'content'       => '邀请奖励',
            'remark'        => $remark,
            'order_id'      => 0,
            'child_id'      => $child_id
        ]);

        return "true";
    }


    /**
     * 订单完成,给教师放发佣金
     * @param $order_num
     */
    public function sendTeacherOrderAccomplish($order_num)
    {
        $TeacherOrderModel = new \initmodel\TeacherOrderModel(); //订单管理   (ps:InitModel)
        $TeacherModel      = new \initmodel\TeacherModel(); //教师管理   (ps:InitModel)


        $map   = [];
        $map[] = ['order_num', '=', $order_num];


        $order_info = $TeacherOrderModel->where($map)->find();
        if (empty($order_info)) return false;
        //if ($order_info['status'] != 4) return false;


        //发佣金
        $platform_commission = cmf_config('platform_commission');        //教师订单,平台抽取(%)
        $commission          = $order_info['amount'] - $order_info['amount'] * ($platform_commission / 100);

        $remark = "操作人[授课完成,给老师发佣金];操作说明[授课完成,给老师发佣金];操作类型[授课完成,给老师发佣金];";//管理备注
        AssetModel::incAsset('授课完成,给老师发佣金 [500]', [
            'operate_type'  => 'commission',//操作类型，balance|point ...
            'identity_type' => 'teacher',//身份类型，member| ...
            'user_id'       => $order_info['teacher_id'],
            'price'         => $commission,
            'order_num'     => $order_num,
            'order_type'    => 500,
            'content'       => '授课完成',
            'remark'        => $remark,
            'order_id'      => $order_info['id'],
        ]);


        //老师课时增加一下
        $total_duration = (float)$order_info['duration'];
        if ($order_info['is_package'] == 1) $total_duration = cmf_config('package_duration');//套餐时长,小时
        $TeacherModel->where('id', '=', $order_info['teacher_id'])->inc('total_duration', $total_duration)->update();


        return true;
    }

}