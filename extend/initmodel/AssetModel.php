<?php

namespace initmodel;

use think\facade\Db;
use think\Model;

/**
 * @AdminModel(
 *     "name"             =>"Asset",
 *     "table_name"       =>"无",
 *     "model_name"       =>"AssetModel",
 *     "remark"           =>"资产管理",
 *     "author"           =>"",
 *     "create_time"      =>"2025年1月15日11:20:27",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\AssetModel();
 * )
 */
class AssetModel extends Model
{
    protected $name = 'base_asset_log';

    //字段说明
    public $change_type = [1 => '收入', 2 => '支出'];


    //操作字段类型 && 如删除,后台自动不显示
    public $operate_type = [
        'balance' => '余额',
        'point'   => '积分',
    ];

    //操作类型,对应key (管理员专属)
    public $operate_order_admin = [
        'balance' => [1 => '1100', 2 => '1110'],//余额 (后面操作类型 1为增加 2为减少)
        'point'   => [1 => '1200', 2 => '1210'],//积分 (后面操作类型 1为增加 2为减少)
    ];


    //操作记录列表   && 如删除,后台列表自动不显示
    public $operate_type_log = [
        'balance' => '余额',
        'point'   => '积分',
    ];


    //身份类型
    public $identity_type = [
        'member' => '用户',
        'rider'  => '骑手',
        'shop'   => '店铺',
    ];

    //订单类型
    public $order_type = [
        //余额板块
        100  => '支付订单(余额)',
        110  => '订单退款增加(余额)',
        120  => '邀请佣金增加(余额)',

        //积分板块
        200  => '支付订单(积分)',
        210  => '签到(积分)',
        220  => '兑换优惠券扣除(积分)',
        290  => '兑换优惠券扣除(积分)',

        //提现板块
        800  => '提现申请',
        810  => '提现驳回',


        //管理员操作板块(余额)
        1100 => '管理员操作余额(增加)',
        1110 => '管理员操作余额(减少)',

        //管理员操作板块(积分)
        1200 => '管理员操作积分(增加)',
        1210 => '管理员操作积分(减少)',
    ];

    /**
     * 增加资产
     * @param string $describe 请求描述,注释. 方便查找操作
     * @param array  $params   参数数组，包含以下键值：
     * @param string $params   [identity_type]     身份类型，member
     * @param int    $params   [user_id]           用户ID
     * @param float  $params   [price]             操作的金额，必须大于 0
     * @param string $params   [operate_type]      操作类型，balance | point
     * @param string $params   [order_num]         订单号
     * @param int    $params   [order_type]        订单类型，100后台操作|500邀请佣金(余额)|800提现申请
     * @param string $params   [content]           说明
     * @param string $params   [remark]            备注
     * @param int    $params   [order_id]          订单ID
     * @param int    $params   [child_id]          子级ID
     *
     */
    public static function incAsset($describe = '', $params = [])
    {
        if ($params['identity_type'] == 'member') $Model = new \initmodel\MemberModel();//用户管理


        //查找对应个人信息
        $info = $Model->where('id', '=', $params['user_id'])->field("{$params['operate_type']},id")->find();
        if ($params['price'] <= 0) return false;//金额必须大于0

        //变动前金额
        $before = $info[$params['operate_type']] ?? 0;
        //变动后金额
        $after = $before + $params['price'] ?? 0;

        $log = [
            'user_id'       => $params['user_id'],//用户id
            'admin_id'      => $params['admin_id'] ?? 0,//管理员id
            'admin_name'    => $params['admin_name'] ?? '',//管理员名字
            'order_num'     => $params['order_num'] ?? cmf_order_sn(),//订单号
            'operate_type'  => $params['operate_type'] ?? 'balance',//操作类型:balance=余额,point=钱包
            'identity_type' => $params['identity_type'] ?? 'member',//身份类型:member=用户
            'change_type'   => 1,//变动类型:1=收入,2=支出
            'order_type'    => $params['order_type'] ?? 0,//订单类型
            'price'         => $params['price'] ?? 0,//金额
            'before'        => $before ?? 0,//变动前金额
            'after'         => $after ?? 0,//变动后金额
            'content'       => $params['content'] ?? '扣除',//说明
            'remark'        => $params['remark'] ?? '无',//管理员备注
            'order_id'      => $params['order_id'] ?? 0,//订单id
            'child_id'      => $params['child_id'] ?? 0,//子级id
            'describe'      => $describe ?? '',//操作描述
            'create_time'   => time(),//创建时间
        ];
        //写入明细
        Db::name('base_asset_log')->strict(false)->insert($log);
        //更新当前金额
        $Model->where('id', '=', $params['user_id'])->inc($params['operate_type'], $params['price'] ?? 0)->update();
    }


    /**
     * 减少资产
     * @param string $describe 请求描述,注释. 方便查找操作
     * @param array  $params   参数数组，包含以下键值：
     * @param string $params   [identity_type]     身份类型，member
     * @param int    $params   [user_id]           用户ID
     * @param float  $params   [price]             操作的金额，必须大于 0
     * @param string $params   [operate_type]      操作类型，balance | point
     * @param string $params   [order_num]         订单号
     * @param int    $params   [order_type]        订单类型，100后台操作
     * @param string $params   [content]           说明
     * @param string $params   [remark]            备注
     * @param int    $params   [order_id]          订单ID
     * @param int    $params   [child_id]          子级ID
     *
     */
    public static function decAsset($describe = '', $params = [])
    {
        if ($params['identity_type'] == 'member') $Model = new \initmodel\MemberModel();//用户管理


        //查找对应个人信息
        $info = $Model->where('id', '=', $params['user_id'])->field("{$params['operate_type']},id")->find();
        if ($params['price'] <= 0) return false;//金额必须大于0

        //变动前金额
        $before = $info[$params['operate_type']] ?? 0;
        //变动后金额
        $after = $before - $params['price'] ?? 0;

        $log = [
            'user_id'       => $params['user_id'],//用户id
            'admin_id'      => $params['admin_id'] ?? 0,//管理员id
            'admin_name'    => $params['admin_name'] ?? '',//管理员名字
            'order_num'     => $params['order_num'] ?? cmf_order_sn(),//订单号
            'operate_type'  => $params['operate_type'] ?? 'balance',//操作类型:balance=余额,point=钱包
            'identity_type' => $params['identity_type'] ?? 'member',//身份类型:member=用户
            'change_type'   => 2,//变动类型:1=收入,2=支出
            'order_type'    => $params['order_type'] ?? 0,//订单类型
            'price'         => $params['price'] ?? 0,//金额
            'before'        => $before ?? 0,//变动前金额
            'after'         => $after ?? 0,//变动后金额
            'content'       => $params['content'] ?? '扣除',//说明
            'remark'        => $params['remark'] ?? '无',//管理员备注
            'order_id'      => $params['order_id'] ?? 0,//订单id
            'child_id'      => $params['child_id'] ?? 0,//子级id
            'describe'      => $describe ?? '',//操作描述
            'create_time'   => time(),//创建时间
        ];
        //写入明细
        Db::name('base_asset_log')->strict(false)->insert($log);
        //更新当前金额
        $Model->where('id', '=', $params['user_id'])->dec($params['operate_type'], $params['price'] ?? 0)->update();
    }

}