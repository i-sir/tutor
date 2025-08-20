<?php
/**
 * Created by PhpStorm.
 * User: zhang
 * data: 2021/12/17
 * Time: 11:08
 */

namespace api\wxapp\controller;

use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;

class NotifyController extends AuthController
{


    public function initialize()
    {
        parent::initialize();//初始化方法

        //获取初始化信息
        $plugin_config        = cmf_get_option('weipay');
        $this->wx_system_type = $plugin_config['wx_system_type'];//默认 读配置可手动修改
        if ($this->wx_system_type == 'wx_mini') {//wx_mini:小程序
            $appid     = $plugin_config['wx_mini_app_id'];
            $appsecret = $plugin_config['wx_mini_app_secret'];
        } else {//wx_mp:公众号
            $appid     = $plugin_config['wx_mp_app_id'];
            $appsecret = $plugin_config['wx_mp_app_secret'];
        }
        $this->wx_config = [
            //微信基本信息
            'token'             => $plugin_config['wx_token'],
            'wx_mini_appid'     => $plugin_config['wx_mini_app_id'],//小程序 appid
            'wx_mini_appsecret' => $plugin_config['wx_mini_app_secret'],//小程序 secret
            'wx_mp_appid'       => $plugin_config['wx_mp_app_id'],//公众号 appid
            'wx_mp_appsecret'   => $plugin_config['wx_mp_app_secret'],//公众号 secret
            'appid'             => $appid,//读取默认 appid
            'appsecret'         => $appsecret,//读取默认 secret
            'encodingaeskey'    => $plugin_config['wx_encodingaeskey'],
            // 配置商户支付参数
            'mch_id'            => $plugin_config['wx_mch_id'],
            'mch_key'           => $plugin_config['wx_v2_mch_secret_key'],
            // 配置商户支付双向证书目录 （p12 | key,cert 二选一，两者都配置时p12优先）
            //	'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.p12',
            'ssl_key'           => './upload/' . $plugin_config['wx_mch_secret_cert'],
            'ssl_cer'           => './upload/' . $plugin_config['wx_mch_public_cert_path'],
            // 配置缓存目录，需要拥有写权限
            'cache_path'        => './wx_cache_path',
            'wx_system_type'    => $this->wx_system_type,//wx_mini:小程序 wx_mp:公众号
            'wx_notify_url'     => cmf_get_domain() . $plugin_config['wx_notify_url'],//微信支付回调地址
        ];
    }


    /**
     * 微信支付回调-微信回调用
     * api: /wxapp/notify/wxPayNotify
     */
    public function wxPayNotify()
    {
        $OrderPayModel = new \initmodel\OrderPayModel();//支付记录表

        $wechat = new \WeChat\Pay($this->wx_config);
        // 4. 获取通知参数
        $result = $wechat->getNotify();
        Log::write($result, '微信回调用-wx_pay_notify');


        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            Log::write("微信回调用-wx_pay_notify:支付成功,修改订单状态;支付单号[{$result['out_trade_no']}]");
            //Log::write($result);

            $pay_num        = $result['out_trade_no'];
            $pay_amount     = round($result['total_fee'] / 100, 2);//支付金额(元)
            $transaction_id = $result['transaction_id'];

            //查询出支付信息,如果已支付,则不再处理
            $pay_info = $OrderPayModel->where('pay_num', $pay_num)->find();
            if ($pay_info['status'] == 2) return false;


            /** 更改支付记录,状态 */
            $result['time']          = time();
            $pay_update['pay_time']  = time();
            $pay_update['trade_num'] = $transaction_id ?? '6666' . cmf_order_sn(6);
            $pay_update['status']    = 2;
            $pay_update['notify']    = serialize($result);
            $OrderPayModel->where('pay_num', '=', $pay_num)->strict(false)->update($pay_update);


            /** 处理订单状态等操作  最后处理,防止时间差导致订单状态异常 **/
            $this->processOrder($pay_num);//微信官方支付回调


            // 返回接收成功的回复
            ob_clean();
            echo $wechat->getNotifySuccessReply();

        } else {
            Log::write('event_type:' . $result);
        }
    }


    /**
     * 支付宝回调
     * /api/wxapp/notify/aliPayNotify
     */
    public function aliPayNotify()
    {
        $Pay           = new PayController();
        $OrderPayModel = new \initmodel\OrderPayModel();//支付记录表

        $pay_data = $Pay->ali_pay_notify();
        $result   = $pay_data['data'];//支付参数
        Log::write('aliPayNotify:result');
        Log::write($result);


        if (in_array($result['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            $pay_num        = $result['out_trade_no'];
            $transaction_id = $result['trade_no'];


            //查询出支付信息,如果已支付,则不再处理
            $pay_info = $OrderPayModel->where('pay_num', $pay_num)->find();
            if ($pay_info['status'] == 2) return false;


            /** 更改支付记录,状态 */
            $result['time']          = time();
            $pay_update['pay_time']  = time();
            $pay_update['trade_num'] = $transaction_id ?? '7777' . cmf_order_sn(6);
            $pay_update['status']    = 2;
            $pay_update['notify']    = serialize($result);
            $OrderPayModel->where('pay_num', '=', $pay_num)->strict(false)->update($pay_update);


            /** 处理订单状态等操作  最后处理,防止时间差导致订单状态异常**/
            $this->processOrder($pay_num);//支付宝回调

            ob_clean();
            return $Pay->ali_pay_success();

        } else {
            Log::write('trade_status:' . $result['trade_status']);
        }
    }


    /**
     * 余额支付回调 (扣除余额之后调用)
     * @param int $pay_num 支付单号
     * @throws \WeChat\Exceptions\InvalidResponseException
     *                     api: /wxapp/notify/balancePayNotify
     */
    public function balancePayNotify($pay_num = 0)
    {
        $OrderPayModel = new \initmodel\OrderPayModel();//支付记录表


        //查询出支付信息,如果已支付,则不再处理
        $pay_info = $OrderPayModel->where('pay_num', $pay_num)->find();
        if ($pay_info['status'] == 2) return 'false';  //查询出支付信息,如果已支付,则不再处理


        /** 更改支付记录,状态 */
        $result['time']          = time();
        $pay_update['pay_time']  = time();
        $pay_update['trade_num'] = $transaction_id ?? '8888' . cmf_order_sn(6);
        $pay_update['status']    = 2;
        $pay_update['notify']    = serialize($result);
        $OrderPayModel->where('pay_num', '=', $pay_num)->strict(false)->update($pay_update);


        /** 处理订单状态等操作  最后处理,防止时间差导致订单状态异常**/
        $this->processOrder($pay_num);//余额支付回调


        return 'true';
    }


    /**
     * 积分支付回调 (扣除积分之后调用)
     * @param int $pay_num 支付单号
     * @throws \WeChat\Exceptions\InvalidResponseException
     *                     api: /wxapp/notify/pointPayNotify
     */
    public function pointPayNotify($pay_num = 0)
    {
        $OrderPayModel = new \initmodel\OrderPayModel();//支付记录表


        //查询出支付信息,如果已支付,则不再处理
        $pay_info = $OrderPayModel->where('pay_num', $pay_num)->find();
        if ($pay_info['status'] == 2) return 'false';


        /** 更改支付记录,状态 */
        $result['time']          = time();
        $pay_update['pay_time']  = time();
        $pay_update['trade_num'] = $transaction_id ?? '9999' . cmf_order_sn(6);
        $pay_update['status']    = 2;
        $pay_update['notify']    = serialize($result);
        $OrderPayModel->where('pay_num', '=', $pay_num)->strict(false)->update($pay_update);


        /** 处理订单状态等操作  最后处理,防止时间差导致订单状态异常**/
        $this->processOrder($pay_num);//积分支付回调


        return 'true';
    }


    /**
     * 免费支付 (下单之后调用)
     * @param int $pay_num 支付单号
     * @throws \WeChat\Exceptions\InvalidResponseException
     *                     api: /wxapp/notify/balancePayNotify
     */
    public function freePayNotify($pay_num = 0)
    {
        $OrderPayModel = new \initmodel\OrderPayModel();//支付记录表


        //查询出支付信息,如果已支付,则不再处理
        $pay_info = $OrderPayModel->where('pay_num', $pay_num)->find();
        if ($pay_info['status'] == 2) return 'false';  //查询出支付信息,如果已支付,则不再处理


        /** 更改支付记录,状态 */
        $result['time']          = time();
        $pay_update['pay_time']  = time();
        $pay_update['trade_num'] = $transaction_id ?? '5555' . cmf_order_sn(6);
        $pay_update['status']    = 2;
        $pay_update['notify']    = serialize($result);
        $OrderPayModel->where('pay_num', '=', $pay_num)->strict(false)->update($pay_update);


        /** 处理订单状态等操作  最后处理,防止时间差导致订单状态异常**/
        $this->processOrder($pay_num);//余额支付回调


        return 'true';
    }




    /**
     *
     * 微信支付回调 测试
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/notify/wx_pay_notify_test?pay_num=1000
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/notify/wx_pay_notify_test?pay_num=1000
     *   api:   /wxapp/notify/wx_pay_notify_test?pay_num=1000
     */
    public function wx_pay_notify_test()
    {
        $OrderPayModel = new \initmodel\OrderPayModel();//支付记录表

        $params  = $this->request->param();
        $pay_num = $params['pay_num'];


        //查询出支付信息,以及关联的订单号
        $pay_info = $OrderPayModel->where('pay_num', $pay_num)->find();


        /**  查询订单条件  */
        $order_num = $pay_info['order_num'];
        $map       = [];
        $map[]     = ['order_num', '=', $order_num];


        /**
         * 更新订单 默认字段
         */
        $update['update_time'] = time();
        $update['pay_time']    = time();
        $update['status']      = 2;


        Log::write('wxPayNotifyTest:pay_info');
        Log::write($pay_info);


        /** 处理订单状态 **/
        $pay_result = $this->processOrder($pay_num);//本地测试支付回调


        //更改支付记录,状态
        $data['time']            = time();
        $pay_update['pay_time']  = time();
        $pay_update['trade_num'] = $transaction_id ?? '9880' . cmf_order_sn(6);
        $pay_update['status']    = 2;
        $pay_update['notify']    = serialize($data);
        $OrderPayModel->where('pay_num', '=', $pay_num)->strict(false)->update($pay_update);


        $this->success('操作成功', $pay_result['order_info']);
    }


    /**
     * 支付成功回调
     * @param $pay_num  支付单号
     */
    public function processOrder($pay_num)
    {
        $OrderPayModel  = new \initmodel\OrderPayModel();//支付记录表
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)


        /** 查询出支付信息,以及关联的订单号 */
        $pay_info = $OrderPayModel->where('pay_num', $pay_num)->find();
        //Log::write('processOrder:pay_info');
        //Log::write($pay_info);

        /**  查询订单条件  */
        $order_num = $pay_info['order_num'];
        $map       = [];
        $map[]     = ['order_num', '=', $order_num];


        /** 更新订单 默认字段  */
        $update['update_time'] = time();
        $update['pay_time']    = time();
        $update['pay_num']     = $pay_num;
        $update['status']      = 2;
        $update['is_pay']      = 1;

        //商城 & 类型注意
        if ($pay_info['order_type'] == 10) {
            $order_info = $ShopOrderModel->where($map)->find();//查询订单信息
            if ($order_info['status'] != 1) {
                Log::write("订单状态异常[processOrder],订单号[{$order_num}]");
                return false;//订单状态异常
            }
            $result = $ShopOrderModel->where($map)->strict(false)->update($update);//更新订单信息
        }


        //Log::write('processOrder:order_info');
        //Log::write($order_info);

        return ['result' => $result, 'order_info' => $order_info];
    }
}