<?php

/**
 * 智能微信支付封装类
 *
 * 完整功能实现，包含所有方法和详细注释
 */
class SmartWechatPay
{
    // 微信支付配置数组
    private $config;

    // 是否使用V3接口
    private $useV3 = false;

    // 构造函数
    public function __construct(array $config)
    {
        // 合并默认配置
        $this->config = array_merge([
            'mchid'           => '',              // 微信支付商户号
            'appid'           => '',              // 应用ID
            'notify_url'      => '',         // 支付结果通知地址
            'v2_key'          => '',             // V2 API密钥(32位)
            'v3_key'          => '',             // V3 API密钥(32位)
            'serial_no'       => '',          // 商户证书序列号
            'private_key'     => '',        // 商户私钥内容或路径
            'cert_path'       => '',          // 商户证书路径（可选）
            'h5_info'         => [              // H5支付配置
                                                'type'     => 'Wap',       // 场景类型
                                                'app_name' => '',      // 应用名称
                                                'app_url'  => ''        // 网站URL
            ],
            'timeout'         => 10,            // 请求超时时间(秒)
            'cert_cache_path' => ''     // 证书缓存路径
        ], $config);

        // 检测V3接口可用性
        $this->checkV3Available();

        // 初始化私钥
        $this->initPrivateKey();
    }

    /* ==================== 公有支付方法 ==================== */

    // 微信公众号支付
    public function jsapiPay($openid, $amount, $description, $out_trade_no)
    {
        return $this->useV3
            ? $this->v3JsapiPay($openid, $amount, $description, $out_trade_no)
            : $this->v2JsapiPay($openid, $amount, $description, $out_trade_no);
    }

    // 微信小程序支付
    public function miniProgramPay($openid, $amount, $description, $out_trade_no)
    {
        return $this->jsapiPay($openid, $amount, $description, $out_trade_no);
    }

    // APP支付
    public function appPay($amount, $description, $out_trade_no)
    {
        return $this->useV3
            ? $this->v3AppPay($amount, $description, $out_trade_no)
            : $this->v2AppPay($amount, $description, $out_trade_no);
    }

    // H5支付
    public function h5Pay($amount, $description, $out_trade_no, $client_ip, $scene_type = '')
    {
        return $this->useV3
            ? $this->v3H5Pay($amount, $description, $out_trade_no, $client_ip, $scene_type)
            : $this->v2H5Pay($amount, $description, $out_trade_no, $client_ip, $scene_type);
    }

    // 扫码支付
    public function nativePay($amount, $description, $out_trade_no)
    {
        return $this->useV3
            ? $this->v3NativePay($amount, $description, $out_trade_no)
            : $this->v2NativePay($amount, $description, $out_trade_no);
    }

    /* ==================== 回调处理方法 ==================== */

    // 处理支付回调
    public function handleNotify($input = null)
    {
        if (is_null($input)) {
            $input = file_get_contents('php://input');
        }

        return $this->useV3
            ? $this->handleV3Notify($input)
            : $this->handleV2Notify($input);
    }

    // 生成成功响应
    public function getSuccessResponse($isV3 = null)
    {
        $isV3 = is_null($isV3) ? $this->useV3 : $isV3;

        if ($isV3) {
            return json_encode(['code' => 'SUCCESS', 'message' => '成功']);
        } else {
            return $this->v2ArrayToXml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
        }
    }

    // 生成失败响应
    public function getFailResponse($message, $isV3 = null)
    {
        $isV3 = is_null($isV3) ? $this->useV3 : $isV3;

        if ($isV3) {
            return json_encode(['code' => 'FAIL', 'message' => $message]);
        } else {
            return $this->v2ArrayToXml(['return_code' => 'FAIL', 'return_msg' => $message]);
        }
    }

    /* ==================== 私有工具方法 ==================== */

    // 生成随机字符串
    private function generateNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str   = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    // 检测V3接口可用性
    private function checkV3Available()
    {
        $this->useV3 = !empty($this->config['v3_key']) &&
            !empty($this->config['serial_no']) &&
            !empty($this->config['private_key']);
    }

    // 初始化私钥
    private function initPrivateKey()
    {
        if (!$this->useV3) return;

        if (is_file($this->config['private_key'])) {
            $this->config['private_key'] = file_get_contents($this->config['private_key']);
        }

        if (!preg_match('/-----BEGIN PRIVATE KEY-----/', $this->config['private_key'])) {
            throw new InvalidArgumentException('Invalid private key format');
        }
    }

    /* ==================== V3实现方法 ==================== */

    // V3 Jsapi支付
    private function v3JsapiPay($openid, $amount, $description, $out_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi';

        $data = [
            'appid'        => $this->config['appid'],
            'mchid'        => $this->config['mchid'],
            'description'  => $description,
            'out_trade_no' => $out_trade_no,
            'notify_url'   => $this->config['notify_url'],
            'amount'       => [
                'total'    => $amount,
                'currency' => 'CNY'
            ],
            'payer'        => [
                'openid' => $openid
            ]
        ];

        $result = $this->v3Request('POST', $url, $data);
        return $this->getV3JsapiPayParams($result['prepay_id']);
    }

    // V3 APP支付
    private function v3AppPay($amount, $description, $out_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/app';

        $data = [
            'appid'        => $this->config['appid'],
            'mchid'        => $this->config['mchid'],
            'description'  => $description,
            'out_trade_no' => $out_trade_no,
            'notify_url'   => $this->config['notify_url'],
            'amount'       => [
                'total'    => $amount,
                'currency' => 'CNY'
            ]
        ];

        $result = $this->v3Request('POST', $url, $data);
        return $this->getV3AppPayParams($result['prepay_id']);
    }

    // V3 H5支付
    private function v3H5Pay($amount, $description, $out_trade_no, $client_ip, $scene_type = '')
    {
        $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/h5';

        $scene_type = $scene_type ?: $this->config['h5_info']['type'];

        $data = [
            'appid'        => $this->config['appid'],
            'mchid'        => $this->config['mchid'],
            'description'  => $description,
            'out_trade_no' => $out_trade_no,
            'notify_url'   => $this->config['notify_url'],
            'amount'       => [
                'total'    => $amount,
                'currency' => 'CNY'
            ],
            'scene_info'   => [
                'payer_client_ip' => $client_ip,
                'h5_info'         => [
                    'type'     => $scene_type,
                    'app_name' => $this->config['h5_info']['app_name'],
                    'app_url'  => $this->config['h5_info']['app_url']
                ]
            ]
        ];

        return $this->v3Request('POST', $url, $data);
    }

    // V3 Native支付
    private function v3NativePay($amount, $description, $out_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/v3/pay/transactions/native';

        $data = [
            'appid'        => $this->config['appid'],
            'mchid'        => $this->config['mchid'],
            'description'  => $description,
            'out_trade_no' => $out_trade_no,
            'notify_url'   => $this->config['notify_url'],
            'amount'       => [
                'total'    => $amount,
                'currency' => 'CNY'
            ]
        ];

        return $this->v3Request('POST', $url, $data);
    }

    // V3 请求方法
    private function v3Request($method, $url, $data = [])
    {
        $body = $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : '';
        $auth = $this->v3BuildAuthHeader($method, $url, $body);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: WechatPay/1.0',
            'Authorization: ' . $auth
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);

        if (!empty($this->config['cert_path'])) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->config['cert_path']);
        }

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            error_log('CURL Error: ' . $error); // 记录详细错误日志
            throw new Exception('CURL Error: ' . $error);
        }

        $result = json_decode($response, true);
        if ($httpCode >= 400) {
            $message = $result['message'] ?? 'Unknown error';
            $code    = $result['code'] ?? 'UNKNOWN_ERROR';
            error_log("WeChatPay V3 Error [$code]: $message"); // 记录详细错误日志
            throw new Exception("WeChatPay V3 Error [$code]: $message", $httpCode);
        }

        return $result;
    }

    // V3 构建认证头
    private function v3BuildAuthHeader($method, $url, $body = '')
    {
        $timestamp = time();
        $nonce     = $this->generateNonceStr();
        $sign      = $this->v3GenerateSignature($method, $url, $timestamp, $nonce, $body);

        return sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",serial_no="%s",nonce_str="%s",timestamp="%d",signature="%s"',
            $this->config['mchid'],
            $this->config['serial_no'],
            $nonce,
            $timestamp,
            $sign
        );
    }

    // V3 生成签名
    private function v3GenerateSignature($method, $url, $timestamp, $nonce, $body)
    {
        $urlParts     = parse_url($url);
        $canonicalUrl = ($urlParts['path'] ?? '') . (isset($urlParts['query']) ? '?' . $urlParts['query'] : '');

        $message = $method . "\n" .
            $canonicalUrl . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";

        openssl_sign($message, $raw_sign, $this->config['private_key'], 'sha256WithRSAEncryption');
        return base64_encode($raw_sign);
    }

    // V3 Jsapi支付参数
    private function getV3JsapiPayParams($prepay_id)
    {
        $timestamp = time();
        $nonce     = $this->generateNonceStr();
        $package   = 'prepay_id=' . $prepay_id;

        $message = $this->config['appid'] . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $package . "\n";

        openssl_sign($message, $sign, $this->config['private_key'], 'sha256WithRSAEncryption');
        $paySign = base64_encode($sign);

        return [
            'appId'     => $this->config['appid'],
            'timeStamp' => (string)$timestamp,
            'nonceStr'  => $nonce,
            'package'   => $package,
            'signType'  => 'RSA',
            'paySign'   => $paySign
        ];
    }

    // V3 APP支付参数
    private function getV3AppPayParams($prepay_id)
    {
        $timestamp = time();
        $nonce     = $this->generateNonceStr();
        $package   = 'Sign=WXPay';

        $message = $this->config['appid'] . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $prepay_id . "\n";

        openssl_sign($message, $sign, $this->config['private_key'], 'sha256WithRSAEncryption');
        $paySign = base64_encode($sign);

        return [
            'appid'     => $this->config['appid'],
            'partnerid' => $this->config['mchid'],
            'prepayid'  => $prepay_id,
            'package'   => $package,
            'noncestr'  => $nonce,
            'timestamp' => (string)$timestamp,
            'sign'      => $paySign
        ];
    }


    // V3 处理回调
    private function handleV3Notify($input)
    {
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data');
        }

        $this->verifyV3NotifySignature();

        // 支付成功后的业务逻辑可以在这里处理
        // 例如更新订单状态、记录日志等
        $this->processPaymentSuccess($data);

        return $data;
    }

    // 验证V3通知签名
    private function verifyV3NotifySignature()
    {
        $headers = $this->getAllHeaders();

        $requiredHeaders = [
            'wechatpay-serial',
            'wechatpay-signature',
            'wechatpay-timestamp',
            'wechatpay-nonce'
        ];

        foreach ($requiredHeaders as $header) {
            if (empty($headers[$header])) {
                throw new Exception("Missing required header: $header");
            }
        }

        $body    = file_get_contents('php://input');
        $message = $headers['wechatpay-timestamp'] . "\n" .
            $headers['wechatpay-nonce'] . "\n" .
            $body . "\n";

        $signature = base64_decode($headers['wechatpay-signature']);
        $publicKey = $this->getPublicKey($headers['wechatpay-serial']);

        $result = openssl_verify(
            $message,
            $signature,
            $publicKey,
            'sha256WithRSAEncryption'
        );

        if ($result !== 1) {
            throw new Exception('Invalid signature');
        }

        if (abs(time() - $headers['wechatpay-timestamp']) > 300) {
            throw new Exception('Timestamp expired');
        }
    }

    /* ==================== V2实现方法 ==================== */

    private function v2JsapiPay($openid, $amount, $description, $out_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

        $data = [
            'appid'            => $this->config['appid'],
            'mch_id'           => $this->config['mchid'],
            'nonce_str'        => $this->generateNonceStr(),
            'body'             => $description,
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => $amount,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url'       => $this->config['notify_url'],
            'trade_type'       => 'JSAPI',
            'openid'           => $openid
        ];

        $data['sign'] = $this->v2MakeSign($data);
        $result       = $this->v2Request($url, $data);
        return $this->getV2JsapiPayParams($result['prepay_id']);
    }

    private function v2AppPay($amount, $description, $out_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

        $data = [
            'appid'            => $this->config['appid'],
            'mch_id'           => $this->config['mchid'],
            'nonce_str'        => $this->generateNonceStr(),
            'body'             => $description,
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => $amount,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url'       => $this->config['notify_url'],
            'trade_type'       => 'APP'
        ];

        $data['sign'] = $this->v2MakeSign($data);
        $result       = $this->v2Request($url, $data);
        return $this->getV2AppPayParams($result['prepay_id']);
    }

    private function v2H5Pay($amount, $description, $out_trade_no, $client_ip, $scene_type = '')
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

        $scene_type = $scene_type ?: $this->config['h5_info']['type'];

        $scene_info = json_encode([
            'h5_info' => [
                'type'     => $scene_type,
                'app_name' => $this->config['h5_info']['app_name'],
                'app_url'  => $this->config['h5_info']['app_url']
            ]
        ], JSON_UNESCAPED_UNICODE);

        $data = [
            'appid'            => $this->config['appid'],
            'mch_id'           => $this->config['mchid'],
            'nonce_str'        => $this->generateNonceStr(),
            'body'             => $description,
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => $amount,
            'spbill_create_ip' => $client_ip,
            'notify_url'       => $this->config['notify_url'],
            'trade_type'       => 'MWEB',
            'scene_info'       => $scene_info
        ];

        $data['sign'] = $this->v2MakeSign($data);
        return $this->v2Request($url, $data);
    }

    private function v2NativePay($amount, $description, $out_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

        $data = [
            'appid'            => $this->config['appid'],
            'mch_id'           => $this->config['mchid'],
            'nonce_str'        => $this->generateNonceStr(),
            'body'             => $description,
            'out_trade_no'     => $out_trade_no,
            'total_fee'        => $amount,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url'       => $this->config['notify_url'],
            'trade_type'       => 'NATIVE'
        ];

        $data['sign'] = $this->v2MakeSign($data);
        return $this->v2Request($url, $data);
    }

    private function v2Request($url, $data)
    {
        $xml = $this->v2ArrayToXml($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('CURL Error: ' . $error); // 记录详细错误日志
            throw new Exception('CURL Error: ' . $error);
        }

        return $this->v2XmlToArray($response);
    }

    private function v2MakeSign($data)
    {
        $data = array_filter($data);
        ksort($data);

        $string = '';
        foreach ($data as $k => $v) {
            $string .= $k . '=' . $v . '&';
        }

        $string .= 'key=' . $this->config['v2_key'];
        return strtoupper(md5($string));
    }

    private function getV2JsapiPayParams($prepay_id)
    {
        $data = [
            'appId'     => $this->config['appid'],
            'timeStamp' => (string)time(),
            'nonceStr'  => $this->generateNonceStr(),
            'package'   => 'prepay_id=' . $prepay_id,
            'signType'  => 'MD5'
        ];

        $data['paySign'] = $this->v2MakeSign($data);
        return $data;
    }

    private function getV2AppPayParams($prepay_id)
    {
        $data = [
            'appid'     => $this->config['appid'],
            'partnerid' => $this->config['mchid'],
            'prepayid'  => $prepay_id,
            'package'   => 'Sign=WXPay',
            'noncestr'  => $this->generateNonceStr(),
            'timestamp' => (string)time()
        ];

        $data['sign'] = $this->v2MakeSign($data);
        return $data;
    }

    private function handleV2Notify($input)
    {
        $data = $this->v2XmlToArray($input);

        if (!$this->verifyV2Notify($data)) {
            throw new Exception('Invalid V2 signature');
        }

        // 支付成功后的业务逻辑可以在这里处理
        // 例如更新订单状态、记录日志等
        $this->processPaymentSuccess($data);

        return $data;
    }

    private function verifyV2Notify($data)
    {
        $originalSign = $data['sign'];
        unset($data['sign']);
        $newSign = $this->v2MakeSign($data);
        return $originalSign === $newSign;
    }

    private function v2ArrayToXml($data)
    {
        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= "<$key>$val</$key>";
        }
        $xml .= '</xml>';
        return $xml;
    }

    private function v2XmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

    /* ==================== 辅助方法 ==================== */

    private function getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return array_change_key_case(getallheaders(), CASE_LOWER);
        }

        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))] = $value;
            }
        }
        return $headers;
    }

    private function getPublicKey($serialNo)
    {
        $cacheFile = $this->getCertCacheFile($serialNo);
        if (file_exists($cacheFile)) {
            $certInfo = json_decode(file_get_contents($cacheFile), true);
            if ($certInfo['expire_time'] > time()) {
                return openssl_x509_read($certInfo['public_key']);
            }
        }

        $certUrl  = 'https://api.mch.weixin.qq.com/v3/certificates';
        $response = $this->v3Request('GET', $certUrl);

        foreach ($response['data'] as $cert) {
            if ($cert['serial_no'] === $serialNo) {
                $publicKey  = $cert['encrypt_certificate']['ciphertext'];
                $expireTime = strtotime($cert['expire_time']);

                $cacheData = [
                    'public_key'  => $publicKey,
                    'expire_time' => $expireTime
                ];
                file_put_contents($cacheFile, json_encode($cacheData));

                return openssl_x509_read($publicKey);
            }
        }

        throw new Exception("Public key not found for serial: $serialNo");
    }

    private function getCertCacheFile($serialNo)
    {
        $cacheDir = $this->config['cert_cache_path'] ?: sys_get_temp_dir();
        return rtrim($cacheDir, '/') . '/wxpay_cert_' . md5($serialNo) . '.json';
    }

    // 处理支付成功的业务逻辑（可根据需求扩展）
    private function processPaymentSuccess($data)
    {
        // 这里添加你的业务逻辑，例如：
        // 1. 更新订单状态
        // 2. 记录支付日志
        // 3. 发送通知
        // 4. 处理业务逻辑等

        // 示例：记录支付日志
        $logData = [
            'timestamp'      => date('Y-m-d H:i:s'),
            'transaction_id' => $data['transaction_id'] ?? '',
            'out_trade_no'   => $data['out_trade_no'] ?? '',
            'total_fee'      => $data['amount']['total'] ?? $data['total_fee'] ?? 0,
            'status'         => 'success'
        ];

        $this->logPayment($logData);

        // 你可以添加更多的业务逻辑处理
    }

    // 记录支付日志（可根据需求扩展）
    private function logPayment($data)
    {
        // 示例：将支付信息记录到日志文件
        $logFile    = __DIR__ . '/payment_logs/' . date('Y-m-d') . '.log';
        $logMessage = json_encode($data) . PHP_EOL;

        // 确保日志目录存在
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }


}