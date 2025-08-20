<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\weipay\lib;

use Exception;
use Yansongda\Pay\Contract\ParserInterface;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Parser\ArrayParser;

class PayController
{

    /**
     * @var array
     */
    public $wx_config;

    public function __construct()
    {
        try {
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


            $config = [
                'alipay' => [
                    'default' => [
                        // 必填-支付宝分配的 app_id
                        'app_id'                  => $plugin_config['ali_app_id'],
                        // 必填-应用私钥 字符串或路径
                        'app_secret_cert'         => $plugin_config['ali_app_secret_cert'],
                        // 必填-应用公钥证书 路径
                        'app_public_cert_path'    => './upload/' . $plugin_config['ali_app_public_cert_path'],
                        // 必填-支付宝公钥证书 路径
                        'alipay_public_cert_path' => './upload/' . $plugin_config['ali_alipay_public_cert_path'],
                        // 必填-支付宝根证书 路径
                        'alipay_root_cert_path'   => './upload/' . $plugin_config['ali_alipay_root_cert_path'],
                        'return_url'              => cmf_get_domain() . $plugin_config['ali_return_url'],
                        'notify_url'              => cmf_get_domain() . $plugin_config['ali_notify_url'],
                        // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                        'service_provider_id'     => '',
                        // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                        'mode'                    => Pay::MODE_NORMAL
                    ]
                ],
                'wechat' => [
                    'default' => [
                        // 必填-商户号，服务商模式下为服务商商户号
                        'mch_id'                  => $plugin_config['wx_mch_id'],
                        // 必填-商户秘钥
                        'mch_secret_key'          => $plugin_config['wx_v3_mch_secret_key'],
                        // 必填-商户私钥 字符串或路径
                        'mch_secret_cert'         => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_secret_cert'],
                        // 必填-商户公钥证书路径
                        'mch_public_cert_path'    => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_public_cert_path'],
                        // 必填
                        'notify_url'              => cmf_get_domain() . $plugin_config['wx_notify_url'],
                        // 选填-公众号 的 app_id
                        'mp_app_id'               => $plugin_config['wx_mp_app_id'],
                        // 选填-小程序 的 app_id
                        'mini_app_id'             => $plugin_config['wx_mini_app_id'],
                        // 选填-app 的 app_id
                        'app_id'                  => $plugin_config['wx_app_id'],
                        // 选填-合单 app_id
                        'combine_app_id'          => '',
                        // 选填-合单商户号
                        'combine_mch_id'          => '',
                        // 选填-服务商模式下，子公众号 的 app_id
                        'sub_mp_app_id'           => '',
                        // 选填-服务商模式下，子 app 的 app_id
                        'sub_app_id'              => '',
                        // 选填-服务商模式下，子小程序 的 app_id
                        'sub_mini_app_id'         => '',
                        // 选填-服务商模式下，子商户id
                        'sub_mch_id'              => '',
                        // 选填-微信公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
                        'wechat_public_cert_path' => [
                            //'45F59D4DABF31918AFCEC556D5D2C6E376675D57' => __DIR__.'/Cert/wechatPublicKey.crt',
                        ],
                        // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
                        //'mode' => Pay::MODE_NORMAL,
                    ]
                ],
                'logger' => [
                    //打开日志系统需安装 composer require monolog/monolog
                    'enable'   => false,
                    'file'     => './plugins/weipay/log/log.log',
                    'level'    => 'debug', // 建议生产环境等级调整为 info，开发环境为 debug
                    'type'     => 'single', // optional, 可选 daily.
                    'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
                ],
                'http'   => [ // optional
                              'timeout'         => 5.0,
                              'connect_timeout' => 5.0,
                ],
            ];

            Pay::config($config);
            //设置参数返回类型为数组
            Pay::set(ParserInterface::class, ArrayParser::class);
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * 微信公众号支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @param $openid    | 用户openid
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_mp($order_num, float $amount, $openid): array
    {
        if (empty($openid)) return ['code' => 0, 'msg' => 'openid为空', 'data' => ''];

        $wechat = new \WeChat\Pay($this->wx_config);
        $amount = round($amount * 100);
        // 组装参数，可以参考官方商户文档
        $options = [
            'body'             => '订单支付',
            'out_trade_no'     => $order_num,
            'total_fee'        => $amount,
            'openid'           => $openid,
            'trade_type'       => 'JSAPI',
            'notify_url'       => $this->wx_config['wx_notify_url'],
            'spbill_create_ip' => '127.0.0.1',
        ];

        try {
            // 生成预支付码
            $result = $wechat->createOrder($options);
            if ($result['result_code'] != 'SUCCESS') return ['code' => 0, 'msg' => $result['err_code_des'], 'data' => $result];

            // 创建JSAPI参数签名
            $result = $wechat->createParamsForJsApi($result['prepay_id']);

            // @todo 把 $options 传到前端用js发起支付就可以了
            return ['code' => 1, 'msg' => '请求成功', 'data' => $result];

        } catch (Exception $e) {
            // 出错啦，处理下吧
            return ['code' => 0, 'msg' => $e->getMessage(), 'data' => ''];
        }
    }

    /**
     * 微信H5支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_h5($order_num, float $amount): array
    {
        $amount = round($amount * 100);
        try {
            $result = Pay::wechat()->wap([
                'out_trade_no' => $order_num,
                'description'  => '订单支付',
                'amount'       => [
                    'total' => $amount,
                ],
                'scene_info'   => [
                    'payer_client_ip' => $_SERVER['SERVER_ADDR'],
                    'h5_info'         => [
                        'type' => 'Wap',
                    ]
                ],
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信App支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_app($order_num, float $amount): array
    {
        $amount = round($amount * 100);
        try {
            $result = Pay::wechat()->app([
                'out_trade_no' => $order_num,
                'description'  => '订单支付',
                'amount'       => [
                    'total' => $amount,
                ]
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信扫码支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_scan($order_num, float $amount): array
    {
        $amount = round($amount * 100);
        try {
            $result = Pay::wechat()->scan([
                'out_trade_no' => $order_num,
                'description'  => '订单支付',
                'amount'       => [
                    'total' => $amount,
                ]
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信小程序支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @param $openid    | 用户openid
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_mini($order_num, float $amount, $openid): array
    {
        if (empty($openid)) return ['code' => 0, 'msg' => 'openid为空', 'data' => ''];

        $wechat = new \WeChat\Pay($this->wx_config);
        $amount = round($amount * 100);
        // 组装参数，可以参考官方商户文档
        $options = [
            'body'             => '订单支付',
            'out_trade_no'     => $order_num,
            'total_fee'        => $amount,
            'openid'           => $openid,
            'trade_type'       => 'JSAPI',
            'notify_url'       => $this->wx_config['wx_notify_url'],
            'spbill_create_ip' => '127.0.0.1',
        ];

        try {
            // 生成预支付码
            $result = $wechat->createOrder($options);
            if ($result['result_code'] != 'SUCCESS') return ['code' => 0, 'msg' => $result['err_code_des'], 'data' => $result];

            // 创建JSAPI参数签名
            $result = $wechat->createParamsForJsApi($result['prepay_id']);

            // @todo 把 $options 传到前端用js发起支付就可以了
            return ['code' => 1, 'msg' => '请求成功', 'data' => $result];

        } catch (Exception $e) {
            // 出错啦，处理下吧
            return ['code' => 0, 'msg' => $e->getMessage(), 'data' => ''];
        }
    }

    /**
     * 支付宝网页支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_web($order_num, float $amount): array
    {
        try {
            $res    = Pay::alipay()->web([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
            ]);
            $result = $res->getBody()->getContents();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝H5支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_wap($order_num, float $amount): array
    {
        try {
            $res    = Pay::alipay()->wap([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
            ]);
            $result = $res->getBody()->getContents();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝App支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_app($order_num, float $amount): array
    {
        try {
            $res    = Pay::alipay()->app([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
            ]);
            $result = $res->getBody()->getContents();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝小程序支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @param $buyer_id  | 小程序用户id
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_mini($order_num, float $amount, $buyer_id): array
    {
        try {
            $result = Pay::alipay()->mini([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
                'buyer_id'     => $buyer_id,
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝刷卡支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @param $auth_code | 小程序用户id
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_pos($order_num, float $amount, $auth_code): array
    {
        try {
            $result = Pay::alipay()->pos([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
                'auth_code'    => $auth_code,
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝扫码支付
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_scan($order_num, float $amount): array
    {
        try {
            $result = Pay::alipay()->scan([
                'out_trade_no' => $order_num,
                'total_amount' => $amount,
                'subject'      => '订单支付',
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信订单退款
     * @param $transaction_id | 第三方单号
     * @param $order_num      | 系统订单号
     * @param $amount         | 退款金额
     * @param $total          | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_refund($transaction_id, $order_num, float $amount, float $total = 0): array
    {
        if ($amount < 0.01) return ['code' => 0, 'msg' => '金额不能小于0.01', 'data' => ''];

        $amount = round($amount * 100);
        if (!empty($total)) {
            $total = round($total * 100);
        } else {
            $total = $amount;
        }

        //处理退款
        $options = [
            'transaction_id' => $transaction_id,
            'out_refund_no'  => $order_num,
            'total_fee'      => $amount,
            'refund_fee'     => $total,
        ];


        $wechat = new \WeChat\Pay($this->wx_config);
        $result = $wechat->createRefund($options);


        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }


    /**
     * 微信订单退款 可部分退款
     * @param $order_num | 订单号
     * @param $amount    | 退款金额
     * @param $total     | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_part_refund($order_num, float $amount, float $total = 0): array
    {
        if ($amount < 0.01) return ['code' => 0, 'msg' => '金额不能小于0.01', 'data' => ''];

        $amount = round($amount * 100);
        if (!empty($total)) {
            $total = round($total * 100);
        } else {
            $total = $amount;
        }
        try {
            $result = Pay::wechat()->refund([
                'out_trade_no'  => $order_num,
                'out_refund_no' => cmf_order_sn(),
                'amount'        => [
                    'refund'   => round($amount),
                    'total'    => round($total),
                    'currency' => 'CNY',
                ],
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }



    /**
     * 支付宝订单退款
     * @param $order_num | 订单号
     * @param $amount    | 订单金额
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_refund($order_num, float $amount): array
    {
        if ($amount < 0.1) return ['code' => 0, 'msg' => '金额不能小于0.1', 'data' => ''];
        try {
            $result = Pay::alipay()->refund([
                'out_trade_no'  => $order_num,
                'refund_amount' => $amount,
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝转账
     * @param        $amount    | 转账金额
     * @param        $identity  | 支付宝账号
     * @param        $name      | 支付宝用户姓名
     * @param string $order_num | 订单号 默认自动获取时间戳
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_transfer(float $amount, $identity, $name, string $order_num = ''): array
    {
        if (empty($order_num)) $order_num = time();
        try {
            $result = Pay::alipay()->transfer([
                'out_biz_no'   => $order_num,
                'trans_amount' => $amount,
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'biz_scene'    => 'DIRECT_TRANSFER',
                'payee_info'   => [
                    'identity'      => $identity,
                    'identity_type' => 'ALIPAY_LOGON_ID',
                    'name'          => $name
                ],
            ]);
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信企业付款到零钱
     * @param        $amount    | 付款金额
     * @param        $openid    | 转账目的用户openID
     * @param        $desc      | 付款订单描述
     * @param string $order_num | 转账订单号,不传会自动生成随机字符串
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_transfer(float $amount, $openid, $desc, string $order_num = ''): array
    {
        try {
            if (empty($order_num)) $order_num = time() . rand(10000, 99999);

            $wechat = new \WeChat\Pay($this->wx_config);

            $amount = round($amount * 100);

            $result = $wechat->createTransfers([
                'partner_trade_no' => $order_num,
                'openid'           => $openid,
                'check_name'       => 'NO_CHECK',
                'amount'           => $amount,
                'desc'             => $desc,
                'spbill_create_ip' => $_SERVER['SERVER_ADDR'],
            ]);

        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信支付回调
     * @return array 返回参数 code=1 成功
     */
    public function wx_pay_notify(): array
    {
        try {
            $result = Pay::wechat()->callback();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 支付宝支付回调
     * @return array 返回参数 code=1 成功
     */
    public function ali_pay_notify(): array
    {
        try {
            $result = Pay::alipay()->callback();
        } catch (Exception $exception) {
            return ['code' => 0, 'msg' => $exception->getMessage(), 'data' => ''];
        }
        return ['code' => 1, 'msg' => '请求成功', 'data' => $result];
    }

    /**
     * 微信支付回调成功返回参数
     * @return string 返回成功参数
     */
    public function wx_pay_success(): string
    {
        return Pay::wechat()->success()->getBody()->getContents();
    }

    /**
     * 支付宝支付回调成功返回参数
     * @return string 返回成功参数
     */
    public function ali_pay_success(): string
    {
        return Pay::alipay()->success()->getBody()->getContents();
    }




    /**
     * 关闭订单
     * @param $pay_num 支付单号
     */
    public function close_order($pay_num)
    {
        $wechat = new \WeChat\Pay($this->wx_config);

        $result = $wechat->closeOrder($pay_num);


        return $result;
    }
}