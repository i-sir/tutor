<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use cmf\lib\Storage;
use init\AesUtilInit;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\Response;
use think\exception\HttpResponseException;
use WeChat\Contracts\BasicWeChat;
use WeChat\Media;

class WxBaseController extends AuthController
{
    protected const QR_ACTION_NAME     = 'QR_STR_SCENE';//二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
    protected const QR_LIMIT_STR_SCENE = 'QR_LIMIT_STR_SCENE';//二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
    protected const QR_EXPIRE_SECONDS  = 60;//该二维码有效时间，以秒为单位。 最大不超过2592000（即30天），此字段如果不填，则默认有效期为60秒。


    protected $transfer_notify_url;//微信支付回调地址
    protected $payment;//微信转账配置信息
    protected $aesKey = '';


    // 初始化信息
    public function initialize()
    {
        //获取初始化信息
        $plugin_config             = cmf_get_option('weipay');
        $this->transfer_notify_url = cmf_get_domain() . $plugin_config['transfer_notify_url'];//微信支付回调地址
        if ($plugin_config['wx_system_type'] == 'wx_mini') {//wx_mini:小程序
            $appid     = $plugin_config['wx_mini_app_id'];
            $appsecret = $plugin_config['wx_mini_app_secret'];
        } else {//wx_mp:公众号
            $appid     = $plugin_config['wx_mp_app_id'];
            $appsecret = $plugin_config['wx_mp_app_secret'];
        }

        //微信转账配置信息
        $this->payment = [
            'app_id'  => $appid,
            //'key'     => $plugin_config['wx_v3_key'],
            'payment' => [
                'wx_certificates' => $plugin_config['wx_certificates'],//证书序列号
                'cert_path'       => './upload/' . $plugin_config['wx_mch_public_cert_path'],
                'key_path'        => './upload/' . $plugin_config['wx_mch_secret_cert'],
                'merchant_id'     => $plugin_config['wx_mch_id']
            ],
        ];

        //秘钥
        $this->aesKey = $plugin_config['wx_v3_key'];

        //微信基本信息
        $this->wx_config = [
            //微信基本信息
            'token'             => $plugin_config['wx_token'],
            'wx_mini_appid'     => $plugin_config['wx_mini_app_id'],//小程序 appid
            'wx_mini_appsecret' => $plugin_config['wx_mini_app_secret'],//小程序 secret
            'wx_mp_appid'       => $plugin_config['wx_mp_app_id'],//公众号 appid
            'wx_mp_appsecret'   => $plugin_config['wx_mp_app_secret'],//公众号 secret
            'appid'             => $appid,
            'appsecret'         => $appsecret,
            'encodingaeskey'    => $plugin_config['wx_encodingaeskey'],
            // 配置商户支付参数
            'mch_id'            => $plugin_config['wx_mch_id'],
            'mch_key'           => $plugin_config['wx_v2_mch_secret_key'],
            // 配置商户支付双向证书目录 （p12 | key,cert 二选一，两者都配置时p12优先）
            //	'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.p12',
            'ssl_key'           => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_secret_cert'],
            'ssl_cer'           => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_public_cert_path'],
            // 配置缓存目录，需要拥有写权限
            'cache_path'        => './wx_cache_path',
            'wx_system_type'    => $plugin_config['wx_system_type'],//wx_mini:小程序 wx_mp:公众号
        ];


    }



    /*******************************    测试板块     ******************************************/


    /**
     * 微信订单退款 测试
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/wx_refund_test
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/wx_refund_test
     *   api: /wxapp/wx_base/wx_refund_test
     *
     */
    public function wx_refund_test()
    {
        $params = $this->request->param();
        //给用户退款
        $WxBaseController = new WxBaseController();//微信基础类
        $OrderPayModel    = new \initmodel\OrderPayModel();//支付记录表

        $map           = [];
        $map[]         = ['order_num', '=', $params['order_num']];//实际订单号
        $map[]         = ['status', '=', 2];//已支付
        $pay_info      = $OrderPayModel->where($map)->find();//支付记录表
        $amount        = $pay_info['amount'];//支付金额&全部退款
        $refund_amount = $pay_info['amount'];//支付金额&全部退款
        $pay_num       = $pay_info['pay_num'];//支付单号


        $pay_num       = '5550250512336893783811';
        $refund_amount = '0.02';//退款金额
        $amount        = '0.09';//总金额

        $refund_result = $WxBaseController->wx_refund($pay_num, $refund_amount, $amount);//退款测试&输入单号直接退
        if ($refund_result['code'] == 0) $this->error($refund_result['msg']);


        $this->success('请求成功', $refund_result['data']);
    }


    /**
     * 测试发送模板消息
     * @param string $openid
     * @param string $template_id
     * @param array  $send_data
     * @param string $pagepath
     * @param int    $type
     * @param string $urls
     * @param string $color
     *
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/send_temp_msg_test
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/send_temp_msg_test
     *   api: /wxapp/wx_base/send_temp_msg_test
     *
     */
    public function send_temp_msg_test($openid = '', $template_id = '', $send_data = [], $pagepath = 'pages/index/index', $type = 1, $urls = '', $color = '#173177')
    {
        $WxBaseController = new WxBaseController();
        $openid           = '**********';
        $template_id      = '*******';

        $send_data = [
            'first'    => ['value' => $WxBaseController->processString('您好，您有一个订单')],
            'keyword1' => ['value' => cmf_order_sn()],
            'keyword2' => ['value' => date('Y-m-d H:i:s')],
            'keyword3' => ['value' => '张三'],
            'keyword4' => ['value' => $WxBaseController->processString('测试地址')],
        ];


        $result = $WxBaseController->sendTempMsg($openid, $template_id, $send_data, 'pages/index/index');


        $this->success('通知成功', $result);

    }


    /**
     * 发货测试用
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/send_shipping
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/send_shipping
     *   api: /wxapp/wx_base/send_shipping
     *   remark_name: 发货测试用
     *
     *
     */
    public function send_shipping()
    {
        $WxBaseController = new WxBaseController();
        $order_sn         = '31024012125932297865052';
        $openid           = 'o9MqX61dNfV7Q05cPnoc2eVLVunc';
        $item_desc        = '商品发货';
        $logistics_type   = 3;
        $result           = $WxBaseController->uploadShippingInfo($order_sn, $openid, $item_desc, $logistics_type);
        $this->success('成功', $result);
    }


    /**
     *  文字违规检测 测试
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/msg_sec_check_test
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/msg_sec_check_test
     *   api: /wxapp/wx_base/msg_sec_check_test
     *
     */
    public function msg_sec_check_test()
    {
        $WxBaseController = new WxBaseController();
        $check_result     = $WxBaseController->msgSecCheck('滚');
        if ($check_result['errcode'] != 0) $this->error('存在违规内容!');

        $this->success('验证通过');
    }


    /**
     *  微信转账实例
     *  调用示例 https://xcxkf159.aubye.com/api/wxapp/wx_base/example
     */
    public function example()
    {
        $WxBaseController = new WxBaseController();

        $openid    = 'oa6L07EVGl3IzgpVtdWAwoUFPuPU';
        $money     = 0.1;
        $user_name = '刘**';
        $order_num = 'Z' . cmf_order_sn();

        //dump($payment, $openid, $money, $user_name);exit();

        $res = $WxBaseController->crteateMchPay($order_num, $openid, $money, $user_name);
        if (isset($res['result_code']) && $res['result_code'] == 'SUCCESS') {

            $out_bill_no      = $res['out_bill_no']; //商户内部单号
            $package_info     = $res['package_info'];//前端确认收款需要使用
            $transfer_bill_no = $res['transfer_bill_no'];//微信单号

            $this->success('转账成功，请等待用户确认收款');
        } else {
            $this->error($res['err_code_des'] ?? '转账失败');
        }

    }




    /**
     * 测试生成小程序(太阳)码
     * 调用示例  https://xcxkf159.aubye.com/api/wxapp/wx_base/get_mini_qr_test
     */
    public function get_mini_qr_test()
    {
        $WxBaseController = new WxBaseController();
        $qr_result        = $WxBaseController->getMiniQrCode('777', 'pages/index/index','release');

        dump($qr_result);
        exit();

    }


    /**
     * 测试生成小程序(方形)码
     * 调用示例  https://xcxkf159.aubye.com/api/wxapp/wx_base/get_mini_square_qr_test
     */
    public function get_mini_square_qr_test()
    {
        $WxBaseController = new WxBaseController();
        $qr_result        = $WxBaseController->getMiniSquareQrCode('pages/index/index?id=777');

        dump($qr_result);
        exit();
    }

    /*******************************    小程序,转账,退款等模块     ******************************************/

    /**
     * 微信支付申请退款
     * @param string $out_trade_no  商户订单号（本系统生成的订单号）
     * @param float  $refund_fee    退款金额（单位：元）
     * @param float  $total_fee     订单总金额（单位：元，可选。默认等于退款金额）
     * @param string $out_refund_no 退款单号（可选。不传则自动生成）
     * @return array 返回结果，包含 code（1成功/0失败）、msg（提示信息）、data（微信返回数据）
     */
    public function wx_refund($out_trade_no, $refund_fee, $total_fee = null, $out_refund_no = null)
    {
        // 1. 校验退款金额是否合法
        if (empty($refund_fee) || $refund_fee <= 0) {
            return ['code' => 0, 'msg' => '退款金额必须大于0'];
        }

        // 2. 获取微信支付配置
        $appid     = $this->wx_config['appid'];      // APPID
        $mch_id    = $this->wx_config['mch_id'];     // 微信商户号
        $key       = $this->wx_config['mch_key'];    // 微信商户API密钥
        $nonce_str = uniqid();                       // 生成随机字符串，用于防重放

        // 3. 处理金额和退款单号
        $refund_fee    = round($refund_fee * 100);       // 元转分（微信以分为单位）
        $out_refund_no = $out_refund_no ?? cmf_order_sn(); // 生成唯一退款单号
        if (empty($total_fee) || $total_fee <= 0) {
            $total_fee = $refund_fee;// 订单总金额为空，则默认退款金额
        } else {
            $total_fee = round($total_fee * 100);
        }

        // 4. 构造签名参数（按字典序排序）
        $params = [
            'appid'         => $appid,                // 公众号ID
            'mch_id'        => $mch_id,               // 商户号
            'nonce_str'     => $nonce_str,            // 随机字符串
            'out_refund_no' => $out_refund_no,        // 退款单号
            'out_trade_no'  => $out_trade_no,         // 商户订单号（微信支持二选一：transaction_id 或 out_trade_no）
            'refund_fee'    => $refund_fee,           // 退款金额（分）
            'total_fee'     => $total_fee,            // 订单总金额（分）
        ];


        // 5. 生成签名（MD5加密并转大写）
        ksort($params); // 按ASCII字典序排序参数
        $stringA        = urldecode(http_build_query($params)); // 拼接成 key=value&key=value 格式
        $stringSignTemp = $stringA . "&key=" . $key;    // 拼接API密钥
        $sign           = strtoupper(md5($stringSignTemp));       // MD5加密并转大写

        // 6. 构造XML请求数据
        $data = '<xml>';
        foreach ($params as $key => $value) {
            $data .= "<{$key}>{$value}</{$key}>";      // 循环添加所有参数
        }
        $data .= "<sign>{$sign}</sign>";              // 添加签名
        $data .= '</xml>';

        // 7. 调用微信退款接口
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund"; // 微信退款API地址
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);                     // 设置请求URL
        curl_setopt($ch, CURLOPT_POST, true);                    // POST请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);             // 提交XML数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);         // 返回结果不直接输出
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);        // 不验证SSL证书（生产环境建议改为true）
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);        // 不验证SSL HOST
        curl_setopt($ch, CURLOPT_SSLCERT, $this->wx_config['ssl_cer']); // 设置证书路径（PEM格式）
        curl_setopt($ch, CURLOPT_SSLKEY, $this->wx_config['ssl_key']);  // 设置密钥路径（PEM格式）
        $response = curl_exec($ch); // 执行请求
        curl_close($ch);            // 关闭CURL会话

        // 8. 解析微信返回结果
        $result = simplexml_load_string($response); // 将XML转换为对象
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
            return ['code' => 1, 'msg' => '退款成功', 'data' => json_decode(json_encode($result), true)]; // 退款成功
        } else {
            $errorMsg = isset($result->err_code_des) ? $result->err_code_des : $result->return_msg;
            return ['code' => 0, 'msg' => '退款失败: ' . $errorMsg]; // 退款失败
        }

    }

    /**
     * 新版商家付款到零钱
     * @param        $payment   直连商户的appid
     * @param        $openid    收款用户的openid
     * @param        $money     转账金额
     * @param        $user_name 收款用户姓名
     * @param        $transfer_scene_report_infos
     * @return mixed
     */
    public function crteateMchPay($order_num, $openid, $money, $user_name = '', $transfer_scene_report_infos = [])
    {
        //接口文档
        //https://pay.weixin.qq.com/doc/v3/merchant/4012716434
        $url = 'https://api.mch.weixin.qq.com/v3/fund-app/mch-transfer/transfer-bills';//新版地址

        $post_data                = [];
        $post_data['appid']       = $this->payment['app_id'];//直连商户的appid
        $post_data['out_bill_no'] = $order_num;//【商户单号】 商户系统内部的商家单号，要求此参数只能由数字、大小写字母组成，在商户系统内部唯一
        //【转账场景ID】 https://pay.weixin.qq.com/doc/v3/merchant/4013774588 参考文档
        $post_data['transfer_scene_id'] = '1005';//【转账场景ID】 该笔转账使用的转账场景，可前往“商户平台-产品中心-商家转账”中申请。如：1001-现金营销
        $post_data['openid']            = $openid;//【收款用户OpenID】 商户AppID下，某用户的OpenID
        $post_data['transfer_amount']   = round($money * 100);//【转账金额】 转账金额单位为“分”。
        $post_data['notify_url']        = $this->transfer_notify_url;//回调地址


        $Wechatpay = 0;
        //大于等于2000必须要姓名
        if ($post_data['transfer_amount'] >= 200000 && !empty($user_name)) {
            $post_data['user_name'] = $this->getEncrypt($user_name, $this->payment);//【收款用户姓名】 收款方真实姓名。需要加密传入，支持标准RSA算法和国密算法，公钥由微信侧提供。转账金额 >= 2,000元时，该笔明细必须填写若商户传入收款用户姓名，微信支付会校验收款用户与输入姓名是否一致，并提供电子回单
            $Wechatpay              = 1;
        }

        $post_data['transfer_remark'] = '商家转账';//【转账备注】 转账备注，用户收款时可见该备注信息，UTF8编码，最多允许32个字符


        if (empty($transfer_scene_report_infos)) {
            $transfer_scene_report_infos = [
                [
                    'info_type'    => '岗位类型',
                    'info_content' => '技师',
                ], [
                    'info_type'    => '报酬说明',
                    'info_content' => '高温补贴',
                ],
            ];//转账明细列表
        }
        //        if (empty($transfer_scene_report_infos)) {
        //            $transfer_scene_report_infos = [
        //                [
        //                    'info_type'    => '回收商品名称',
        //                    'info_content' => '耳机',
        //                ],
        //            ];//转账明细列表
        //        }

        $post_data['transfer_scene_report_infos'] = $transfer_scene_report_infos;

        $token  = $this->getToken($post_data, $this->payment);
        $res    = $this->http_post($url, json_encode($post_data), $token, $this->payment, $Wechatpay);//发送请求
        $resArr = json_decode($res, true);

        if (!empty($resArr['code'])) {
            $resArr['result_code']  = $resArr['return_code'] = 'FAIL';
            $resArr['err_code_des'] = $resArr['message'];
        } else {
            $resArr['result_code'] = $resArr['return_code'] = 'SUCCESS';
            //state
            /*ACCEPTED: 转账已受理
            PROCESSING: 转账处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够
            WAIT_USER_CONFIRM: 待收款用户确认，可拉起微信收款确认页面进行收款确认
            TRANSFERING: 转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款
            SUCCESS: 转账成功
            FAIL: 转账失败
            CANCELING: 商户撤销请求受理成功，该笔转账正在撤销中
            CANCELLED: 转账撤销完成*/
        }
        //        Log::write($post_data, '转账发送信息');
        //        Log::write($resArr, '转账返回信息');

        return $resArr;
        //成功返回
    }


    /**
     * 小程序->订单发货
     * @param        $order_sn         : 订单编号，我们自己生成的订单编号
     * @param        $openid           : 用户openID
     * @param        $item_desc        : 物品信息 必填
     * @param int    $logistics_type   : 物流模式 1、实体物流配送采用快递公司进行实体物流配送形式 2、同城配送 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式 4、用户自提
     * @param string $express_company  : 物流公司编码  配合腾讯的物流公司编码数据表
     * @param string $tracking_no      : 物流单号
     * @param string $receiver_contact : 顺丰是需要收货人手机号  15236182399
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function uploadShippingInfo($order_sn, $openid, $item_desc, int $logistics_type = 1, string $express_company = '', string $tracking_no = '', string $receiver_contact = '')
    {
        try {
            $shipping_list = [
                'tracking_no'     => $tracking_no,              //物流单号，物流快递发货时必填
                'express_company' => $express_company,      //物流公司编码，快递公司ID，参见「查询物流公司编码列表」，物流快递发货时必填
                'item_desc'       => $item_desc,                  //商品信息，例如：微信红包抱枕*1个  必填
            ];
            if ($express_company == 'SF') {
                if (empty($receiver_contact)) return ['code' => 0, 'msg' => '顺丰快递需填写收件人手机号'];
                $shipping_list['contact']['receiver_contact'] = substr_replace($receiver_contact, '****', -8, 4);
                //联系方式，当发货的物流公司为顺丰时，联系方式为必填，收件人或寄件人联系方式二选一
                // 'receiver_contact' 收件人联系方式，收件人联系方式为，采用掩码传输，最后4位数字不能打掩码 示例值: `189****1234, 021-****1234, ****1234, 0**2-***1234, 0**2-******23-10, ****123-8008` 值限制: 0 ≤ value ≤ 1024 字段加密: 使用APIv3定义的方式加密
            }
            $param = [
                'order_key'      => [
                    'order_number_type' => 1,
                    'mchid'             => $this->wx_config['mch_id'],//商户号
                    'out_trade_no'      => $order_sn,
                ],
                'logistics_type' => $logistics_type,
                'delivery_mode'  => 1,
                'shipping_list'  => [$shipping_list],
                'upload_time'    => date("c", time()),
                'payer'          => [
                    'openid' => $openid
                ]
            ];

            $BasicWeChat  = new BasicWeChat($this->wx_config);
            $access_token = $BasicWeChat->getAccessToken();
            $url          = 'https://api.weixin.qq.com/wxa/sec/order/upload_shipping_info?access_token=' . $access_token;
            $return       = $BasicWeChat->callPostApi($url, $param);
            if (!empty($return) && $return['errcode'] == 0) {
                return ['code' => 1, 'msg' => '调用成功', 'data' => ''];
            } else {
                return ['code' => 0, 'msg' => $return['errmsg'], 'data' => ''];
            }
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage(), 'data' => ''];
        }
    }


    /**
     * 微信转账回调 (官方回调)
     * 调用示例 https://xcxkf159.aubye.com/api/wxapp/wx_base/notify
     */
    public function notify()
    {
        $params = $this->request->param();
        Log::write('回调通知');
        Log::write($params);

        $AesUtil  = new AesUtilInit($this->aesKey);
        $resource = $params['resource'];
        $result   = $AesUtil->decryptToString($resource['associated_data'], $resource['nonce'], $resource['ciphertext']);
        $result   = json_decode($result, true);

        Log::write('回调解密后内容');
        Log::write($result);

        if ($result['state'] != 'SUCCESS') {
            Log::write("提现收款失败:
            【单据状态】商家转账订单状态
            ACCEPTED：单据已受理
            PROCESSING：单据处理中，转账结果尚未明确，如一直处于此状态，建议检查账户余额是否足够
            WAIT_USER_CONFIRM：待收款用户确认，可拉起微信收款确认页面进行收款确认
            TRANSFERING：转账中，转账结果尚未明确，可拉起微信收款确认页面再次重试确认收款
            SUCCESS： 转账成功
            FAIL： 转账失败
            CANCELING： 撤销中
            CANCELLED： 已撤销
            ");
            Log::write($result['state']);

            //修改提现状态,并将余额退回账户,如24小时未点击,将钱退回余额

            return false;
        }

        //修改提现状态
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $map                   = [];
        $map[]                 = ['order_num', '=', $result['out_bill_no']];
        $MemberWithdrawalModel->where($map)->strict(false)->update([
            'status'      => 4,
            'pass_time'   => time(),
            'update_time' => time(),
        ]);


        //回复内容
        $header   = [];
        $result   = [
            'code'    => 200,
            'message' => $result['state'],
        ];
        $type     = $this->getResponseType();
        $response = Response::create($result, $type)->header($header);


        throw new HttpResponseException($response);
    }



    /**
     * 生成小程序(太阳)码
     * @param string $scene       最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
     * @param string $page        跳转页面地址
     * @param string $env_version 要打开的小程序版本。正式版为 "release"，体验版为 "trial"，开发版为 "develop"。默认是正式版。
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getMiniQrCode($scene = '', $page = 'pages/index/index', $env_version = 'release')
    {
        //获取插件格式,(注意公众号,和小程序的appid)
        $BasicWeChat  = new BasicWeChat($this->wx_config);
        $access_token = $BasicWeChat->getAccessToken();
        $url          = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";
        $post_data    = [
            'scene'       => $scene,
            'page'        => $page,
            'env_version' => $env_version,
            'is_hyaline'  => false,//默认是false，是否需要透明底色，为 true 时，生成透明底色的小程序
        ];
        $qr_result    = $this->http_request($url, json_encode($post_data));


        //检测是否报错
        $result       = json_decode($qr_result, true);
        $errcode_list = [
            40001 => 'access_token已过期',
            40129 => '最大32个字符',
            41030 => '页面不存在',
            40097 => '参数错误',
            40169 => 'scene 不合法',
        ];
        if ($errcode_list[$result['errcode']]) $this->error($errcode_list[$result['errcode']]);


        //使用存储库
        $storage      = cmf_get_option('storage');
        $pluginClass  = cmf_get_plugin_class($storage['type']);
        $this->plugin = new $pluginClass();
        $this->config = $this->plugin->getConfig();//获取仓库信息
        $dz           = $this->config['dir'];
        $date         = date('Ymd');
        $qrcode       = 'wxqrcode';
        $image_name   = md5(md5(uniqid() . uniqid())) . '.png';//图片名字


        //生成路径
        $path     = "upload/{$dz}/{$qrcode}/{$date}/{$image_name}";
        $rel_path = "{$dz}/{$qrcode}/{$date}/{$image_name}";
        $this->is_mkdirs("upload/{$dz}/{$qrcode}/{$date}/");


        //将图片保存到阿里云
        $url = cmf_get_asset_url($rel_path);
        file_put_contents($path, $qr_result);
        // 上传Oss储存
        $storage = cmf_get_option('storage');
        $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
        $storage->upload($rel_path, $path);
        //删除单个文件方法
        unlink(app()->getRootPath() . "public/{$path}");


        return $url;
    }


    /**
     * 生成小程序(方形)码
     * @param string $path 扫码进入的小程序页面路径，最大长度 128 个字符，不能为空；对于小游戏，可以只传入 query 部分，来实现传参效果，如：传入 "?foo=bar"，即可在 wx.getLaunchOptionsSync 接口中的 query 参数获取到 {foo:"bar"}。scancode_time为系统保留参数，不允许配置
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getMiniSquareQrCode($path = 'pages/index/index?aaa=c111')
    {
        //获取插件格式,(注意公众号,和小程序的appid)
        $BasicWeChat  = new BasicWeChat($this->wx_config);
        $access_token = $BasicWeChat->getAccessToken();
        $url          = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token={$access_token}";
        $post_data    = [
            'path' => $path,
        ];
        $qr_result    = $this->http_request($url, json_encode($post_data));

        //检测是否报错
        $result       = json_decode($qr_result, true);
        $errcode_list = [
            40001 => 'access_token已过期',
            45029 => '生成码个数总和到达最大个数限制',
            40159 => 'path 不能为空，且长度不能大于 128 字节',
        ];
        if ($errcode_list[$result['errcode']]) $this->error($errcode_list[$result['errcode']]);


        //使用存储库
        $storage      = cmf_get_option('storage');
        $pluginClass  = cmf_get_plugin_class($storage['type']);
        $this->plugin = new $pluginClass();
        $this->config = $this->plugin->getConfig();//获取仓库信息
        $dz           = $this->config['dir'];
        $date         = date('Ymd');
        $qrcode       = 'wxqrcode';
        $image_name   = md5(md5(uniqid() . uniqid())) . '.png';//图片名字

        //生成路径
        $path     = "upload/{$dz}/{$qrcode}/{$date}/{$image_name}";
        $rel_path = "{$dz}/{$qrcode}/{$date}/{$image_name}";
        $this->is_mkdirs("upload/{$dz}/{$qrcode}/{$date}/");


        //将图片保存到阿里云
        $url = cmf_get_asset_url($rel_path);
        file_put_contents($path, $qr_result);
        // 上传Oss储存
        $storage = cmf_get_option('storage');
        $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
        $storage->upload($rel_path, $path);
        //删除单个文件方法
        unlink(app()->getRootPath() . "public/{$path}");


        return $url;
    }

    /*******************************    公众号,模板消息,菜单等模块     ******************************************/


    /**
     * 检测文本内容
     * @param $content
     * @return mixed
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     *
     *  参考地址:https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/sec-center/sec-check/msgSecCheck.html
     *  命中标签枚举值，100 正常；10001 广告；20001 时政；20002 色情；20003 辱骂；20006 违法犯罪；20008 欺诈；20012 低俗；20013 版权；21000 其他
     *
     */
    function msgSecCheck($content)
    {
        $BasicWeChat  = new BasicWeChat($this->wx_config);
        $access_token = $BasicWeChat->getAccessToken();

        $url  = "https://api.weixin.qq.com/wxa/msg_sec_check?access_token=" . $access_token;
        $data = json_encode(['content' => $content], JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);


        //errcode:0 验证通过
        return json_decode($response, true);
    }


    /**
     * 将公众号的official_openid存入member表中   可以在用户授权登录成功后操作
     * 调用示例 https://xcxkf159.aubye.com/api/wxapp/wx_base/update_official_openid
     */
    public function update_official_openid()
    {
        $gzh_list = Db::name('member_gzh')->select();
        foreach ($gzh_list as $k => $v) {
            Db::name('member')->where('unionid', '=', $v['unionid'])
                ->update(['official_openid' => $v['openid'], 'update_time' => time()]);
        }
    }


    /**
     * 公众号配置下 给微信配置域名  关注或取消关注执行
     * 公众号操作,获取用户信息,获取unionid   公众号自动回复等功能
     * @return void
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \think\db\exception\DbException
     *
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/find_official
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/find_official
     *   api: /wxapp/wx_base/find_official
     *
     * 相关文档:https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Passive_user_reply_message.html
     */
    public function find_official()
    {
        $signature = $_GET["signature"];//微信 返回
        $timestamp = $_GET["timestamp"];//微信 返回
        $nonce     = $_GET["nonce"];//微信 返回
        $token     = $this->wx_config['token'];//配置
        $tmpArr    = array($token, $timestamp, $nonce);


        //处理数据格式
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        Log::write('get_params');
        Log::write($_GET);
        Log::write('tmpStr');
        Log::write($tmpStr);


        $WeChat      = new \WeChat\Contracts\BasicPushEvent($this->wx_config);
        $getReceive  = $WeChat->getReceive();//获取公众号推送对象
        $openid      = $WeChat->getOpenid();//获取当前用户openid
        $getToOpenid = $WeChat->getToOpenid();//获取当前推送公众号
        $getMsgType  = $WeChat->getMsgType();//获取当前推送消息类型

        Log::write('getReceive');
        Log::write($getReceive);
        Log::write('openid');
        Log::write($openid);
        Log::write('getToOpenid');
        Log::write($getToOpenid);
        Log::write('getMsgType');
        Log::write($getMsgType);


        //拿到access_token
        $access_token = $this->get_stable_access_token();
        //        Log::write('access_token');
        //        Log::write($access_token);


        //关注或取消关注操作
        if ($getMsgType == 'event') {
            if ($access_token && $openid) {
                //获取用户信息 拿到unionid
                $user_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$openid&lang=zh_CN";
                Log::write('user_url');
                Log::write($user_url);
                $result = $this->getSslPage($user_url);
                $result = json_decode($result, true);
                Log::write('userinfo');
                Log::write($result);

                if (empty($result['unionid']) || empty($result['openid'])) {
                    Log::write('userinfo:error');
                    Log::write($result);
                }


                if ($result['unionid']) {
                    //插入公众号用户表
                    Db::name('member_gzh')->where(['openid' => $result['openid']])->delete();
                    Db::name('member_gzh')->insert([
                        'ext'         => $result,
                        'openid'      => $result['openid'],
                        'unionid'     => $result['unionid'],
                        'create_time' => time(),
                    ]);


                    //更新用户表&这里的unionid是请求接口拿到的,所以会慢必须判断存在了在执行下面操作
                    $this->update_official_openid();
                }

                $send_data = [
                    'ToUserName'   => $openid,
                    'FromUserName' => $getToOpenid,
                    'CreateTime'   => time(),
                    'MsgType'      => 'text',
                    'Content'      => '欢迎~',
                ];
                $send      = $WeChat->reply($send_data, true);
                //想要回复信息,必须执行一下
                exit($send);

            }
        }


        //主动发信息
        if ($getMsgType == 'text') {
            Log::write('Content');
            Log::write($getReceive['Content']);

            if ($getReceive['Content'] == '登录') {
                //用户发送登录文字,回复指定关键字
            }
            $send_data = [
                'ToUserName'   => $openid,
                'FromUserName' => $getToOpenid,
                'CreateTime'   => time(),
                'MsgType'      => 'text',
                'Content'      => '测试',
            ];
            $send      = $WeChat->reply($send_data, true);

            //想要回复信息,必须执行一下
            //exit($send);
        }


        //点击事件,发送文字消息
        if ($getMsgType == 'event') {
            Log::write('Content');
            Log::write($getReceive['Content']);

            //key值
            if ($getReceive['EventKey'] == 'PlatformIntroduction') $content = '敬请期待';
            if ($getReceive['EventKey'] == 'ContactService') $content = "联系电话:022-58099879\n工作时间:周一至周五 10:00-17:00";
            $send_data = [
                'ToUserName'   => $openid,
                'FromUserName' => $getToOpenid,
                'CreateTime'   => time(),
                'MsgType'      => 'text',
                'Content'      => $content,
            ];
            $send      = $WeChat->reply($send_data, true);

            //想要回复信息,必须执行一下
            if ($content) exit($send);
        }

        //用于验证设置的token,关联公众号后台,给公众号返回的结果
        if ($tmpStr == $signature) {
            ob_clean();
            echo $_GET['echostr'];
            Log::write('find_official_token_echostr');
            Log::write($_GET);
            exit();
        } else {
            echo '失败';
            exit;
        }
    }


    /**
     * 发送模板消息
     * 参考文档 https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Template_Message_Interface.html
     * @param string  $openid
     * @param integer $template_id 模板ID
     * @param array   $data        模板数据
     * @param array   $pagepath    小程序路径
     * @param int     $type        消息模板类型 1:模板消息 2:订阅消息
     * @param string  $url         消息模板跳转url，可不填
     * @param string  $color       主题字体颜色
     * @return  string
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/sendTempMsg
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/sendTempMsg
     *   api: /wxapp/wx_base/sendTempMsg
     *
     */
    public function sendTempMsg($openid = '', $template_id = '', $send_data = [], $pagepath = 'pages/index/index', $type = 1, $urls = '', $color = '#173177')
    {
        //  $template_id = '*******';

        //        $send_data = [
        //            'first'    => ['value' => '您好，您有一个订单'],
        //            'keyword1' => ['value' => cmf_get_order_sn()],
        //            'keyword2' => ['value' => date('Y-m-d H:i:s')],
        //            'keyword3' => ['value' => '张三'],
        //            'keyword4' => ['value' => '测试地址'],
        //        ];


        $miniprogram  = ['appid' => $this->wx_config['wx_mini_appid'], 'pagepath' => $pagepath];
        $access_token = $this->get_stable_access_token();//获取token

        //模板消息
        $template_data = $this->_sendTempMsg($openid, $template_id, $urls, $color, $send_data, []);

        //模板消息类型
        $type   = ($type == 1) ? 'send' : 'subscribe';
        $url    = "https://api.weixin.qq.com/cgi-bin/message/template/{$type}?access_token={$access_token}";
        $result = $this->http_request($url, $template_data);
        $result = json_decode($result, true);


        //        Log::write("wxSendTempMsg");
        //        Log::write("发送公众号提醒:{$template_id}");
        //        Log::write("通知人:{$openid}");
        //        Log::write("通知内容:");
        //        Log::write($send_data);
        //
        //
        //        Log::write("通知结果:");
        //        Log::write($result);
        return $result;


        //        if ($result['errcode'] == 0) {
        //            return true;
        //        } else {
        //            //失败返回错误信息
        //            return $result;
        //        }


        //        if ($result['errcode'] == 0) {
        //            return '发送成功';
        //        } else {
        //            //失败返回错误信息
        //            return $result;
        //        }
    }


    /**
     * 创建公众号 临时二维码
     * 可带参数
     * 用户扫码后,进入公众号会携带参数以及ticket
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/qrcode_create
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/qrcode_create
     *   api: /wxapp/wx_base/qrcode_create
     *
     * 相关文档:
     * https://developers.weixin.qq.com/doc/offiaccount/Account_Management/Generating_a_Parametric_QR_Code.html
     */
    public function qrcode_create()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$this->get_stable_access_token()}";


        //$params['expire_seconds'] = self::QR_EXPIRE_SECONDS;//(如果永久类型,此字段不传)该二维码有效时间，以秒为单位。 最大不超过2592000（即30天），此字段如果不填，则默认有效期为60秒。
        $params['action_name'] = self::QR_LIMIT_STR_SCENE;//二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
        $params['action_info'] = ['scene' => ['scene_str' => 'lalalalal' . cmf_order_sn()]];

        $result = $this->http_request($url, json_encode($params));
        $result = json_decode($result, true);


        $get_image = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$result['ticket']}";

        $result1 = $this->getSslPage($get_image);

        //$result['ticket'] 和 scene_str存入数据库,如有人关注公众号扫码,或扫码操作

        $this->success('请求链接,获取临时二维码', $get_image);
        //        header("Location: $get_image");
        //        exit();
    }


    /**
     * 增加菜单   用浏览器直接访问下 然后就可以直接更新了
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/addWeixinMenu
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/addWeixinMenu
     *   api: /wxapp/wx_base/addWeixinMenu
     *
     */
    public function addWeixinMenu()
    {
        $access_token = $this->get_stable_access_token();//获取token
        $jsonmenu     = '{
                 "button":[
                 {	
                      "type":"view",
                      "name":"拓客文章",
                      "url":"https://dz264.aulod.com/h5/#/"
                  },
                  {	
                      "type":"view",
                      "name":"我的房源",
                      "url":"https://dz264.aulod.com/h5/#/pages/house/index"
                  },
                  {
                           "name":"关于我们",
                           "sub_button":[{
                               "type":"click",
                               "name":"平台介绍",
                               "key":"PlatformIntroduction"
                            },
                            {
                               "type":"click",
                               "name":"联系客服",
                               "key":"ContactService"
                            }]
                  },
				  {
					 "type":"miniprogram",
					 "name":"快汇收",
					 "url":"http://mp.weixin.qq.com",
					 "appid":"wxaf99fcb7d5c489df",
					 "pagepath":"pages/index/index"
				 }]
             }';
        $url          = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $access_token;
        $result       = $this->http_request($url, $jsonmenu);
        dump($result);
    }



    /*******************************    公共方法    ******************************************/

    /**
     * 获取微信资源信息,图片,视频,音频
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/batch_get_material
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/batch_get_material
     *   api: /wxapp/wx_base/batch_get_material
     *
     */
    public function batch_get_material()
    {
        $Media = new Media($this->wx_config);
        dump($Media->batchGetMaterial('video'));
        exit();
    }


    /**
     * 处理发送模板消息
     * @param $openid
     * @param $template_id
     * @param $url
     * @param $color
     * @param $data
     * @param $miniprogram
     * @return false|string
     */
    private function _sendTempMsg($openid, $template_id, $url, $color, $data, $miniprogram)
    {
        //模板消息
        $template_data = [
            'touser'      => $openid, //用户openid
            'template_id' => $template_id, //在公众号下配置的模板id
            'miniprogram' => $miniprogram,
            'url'         => $url, //点击模板消息会跳转的链接
            'color'       => $color,//消息字体颜色，不填默认为黑色
            'data'        => $data,
        ];
        $template_data = json_encode($template_data);
        return $template_data;
    }


    /**
     * 公众号,发送模板消息稳定获取token
     * 请求:https://api.weixin.qq.com/cgi-bin/stable_token
     * 获取公众号全局后台接口调用凭据，有效期最长为7200s，开发者需要进行妥善保存；
     * 有两种调用模式: 1. 普通模式，access_token 有效期内重复调用该接口不会更新 access_token，绝大部分场景下使用该模式；2. 强制刷新模式，会导致上次获取的 access_token 失效，并返回新的 access_token；
     * 该接口调用频率限制为 1万次 每分钟，每天限制调用 50w 次；
     * 与获取Access token获取的调用凭证完全隔离，互不影响。该接口仅支持 POST JSON 形式的调用；
     *
     *   test_environment: http://tutor.ikun:9090/api/wxapp/wx_base/get_stable_access_token
     *   official_environment: https://xcxkf159.aubye.com/api/wxapp/wx_base/get_stable_access_token
     *   api: /wxapp/wx_base/get_stable_access_token
     *
     *  只针对公众号
     */
    public function get_stable_access_token()
    {
        //获取插件格式,(注意公众号,和小程序的appid)
        //$BasicWeChat  = new BasicWeChat($this->wx_config);
        //$access_token = $BasicWeChat->getAccessToken();

        $token = Cache::get('get_stable_access_token');
        if (!$token) {
            $appid  = $this->wx_config['wx_mp_appid'];
            $secret = $this->wx_config['wx_mp_appsecret'];
            $url2   = 'https://api.weixin.qq.com/cgi-bin/stable_token';
            //小程序信息获取token
            $param['grant_type'] = 'client_credential';
            $param['appid']      = $appid;
            $param['secret']     = $secret;
            $result              = $this->http_request($url2, json_encode($param));
            $result              = json_decode($result, true);
            $token               = $result['access_token'];
            Cache::set('get_stable_access_token', $token, 7000);
        }

        return $token;
    }


    /**
     * 根据平台证书加密
     * @param $str
     * @param $payment
     * @功能说明:根据平台证书加密
     */
    public function getEncrypt($str, $payment)
    {
        //$str是待加密字符串
        $public_key_path = $payment['payment']['wx_certificates'];
        if (empty($public_key_path)) {
            return '';
        }

        $public_key = file_get_contents($public_key_path);
        $encrypted  = '';
        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new Exception('encrypt failed');
        }
        return $sign;
    }


    /**
     * @功能说明:查询转账记录
     */
    public function getMchPayRecord($order_code, $payment)
    {
        $url = 'https://api.mch.weixin.qq.com/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/' . $order_code;
        //请求方式
        $token  = $this->getToken([], $payment, 'GET', $url);
        $res    = $this->https_request($url, [], $token);//发送请求
        $resArr = json_decode($res, true);

        return $resArr;
    }


    /**
     * @功能说明:https请求
     * @param $url
     * @param $data
     * @param $token
     * @return bool|string
     */
    public function https_request($url, $data, $token)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, (string)$url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //添加请求头
        $headers = [
            'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $token,
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
        ];
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


    /**
     * 获取证书序列号
     * @param $payment
     * @功能说明:获取证书序列号
     */
    public function get_Certificates($payment)
    {
        $platformCertificateFilePath = $payment['payment']['wx_certificates'];

        if (empty($platformCertificateFilePath)) {
            return '';
        }

        $a = openssl_x509_parse(file_get_contents($platformCertificateFilePath));

        return !empty($a['serialNumberHex']) ? $a['serialNumberHex'] : '';
    }


    /**
     * POST 请求
     */
    private function http_post($url, $data, $token, $payment, $Wechatpay = 0)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, (string)$url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //添加请求头
        $headers = [

            'Authorization:WECHATPAY2-SHA256-RSA2048 ' . $token,
            'Accept: application/json',
            'Content-Type: application/json; charset=utf-8',
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
        ];
        //需要加密
        if ($Wechatpay == 1) {

            $certificates = $this->get_Certificates($payment);

            $headers[] = 'Wechatpay-Serial:' . $certificates;
        }
        if (!empty($headers)) {

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        $output = curl_exec($curl);
        curl_close($curl);

        return $output;
    }


    /**
     * @param $post_data
     * @param $payment
     * @功能说明:获取token
     */
    public function getToken($post_data, $payment, $http_method = 'POST', $usrl_data = '')
    {
        $url = !empty($usrl_data) ? $usrl_data : 'https://api.mch.weixin.qq.com/v3/fund-app/mch-transfer/transfer-bills';
        // $http_method = 'POST';//请求方法（GET,POST,PUT）
        $timestamp   = time();//请求时间戳
        $url_parts   = parse_url($url);//获取请求的绝对URL
        $nonce       = $timestamp . rand(10000, 99999);//请求随机串
        $body        = !empty($post_data) ? json_encode((object)$post_data) : '';//请求报文主体
        $stream_opts = [
            "ssl" => [
                "verify_peer"      => false,
                "verify_peer_name" => false,
            ]
        ];


        $apiclient_cert_path = $payment['payment']['cert_path'];
        $apiclient_key_path  = $payment['payment']['key_path'];
        $apiclient_cert_arr  = openssl_x509_parse(file_get_contents($apiclient_cert_path, false, stream_context_create($stream_opts)));
        $serial_no           = $apiclient_cert_arr['serialNumberHex'];//证书序列号
        $mch_private_key     = file_get_contents($apiclient_key_path, false, stream_context_create($stream_opts));//密钥
        $merchant_id         = $payment['payment']['merchant_id'];//商户id
        $canonical_url       = ($url_parts['path'] . (!empty($url_parts['query']) ? "?${url_parts['query']}" : ""));
        $message             = $http_method . "\n" .
            $canonical_url . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";
        openssl_sign($message, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');

        $sign   = base64_encode($raw_sign);//签名
        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $token  = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $merchant_id, $nonce, $timestamp, $serial_no, $sign);//微信返回token
        return $token;

    }


    /**
     * post请求
     * @param      $url
     * @param null $data
     * @return bool|string
     */
    private function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


    /**
     * get请求
     * @param $url
     * @return bool|string
     */
    function getSslPage($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


    /**
     * 字符串处理 大于n个字符显示省略号
     * @param $str   字符
     * @param $limit 长度
     * @return string
     */
    public function processString($str = '', $limit = 18)
    {
        if (mb_strlen($str) > $limit) {
            return mb_substr($str, 0, $limit) . '..';
        }
        return $str;
    }


}
