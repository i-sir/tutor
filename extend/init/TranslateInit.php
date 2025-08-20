<?php

namespace init;

class TranslateInit
{
    /***************************************************************************
     * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
     *
     **************************************************************************/

    /**
     * 初始化
     * @return void
     */
    public function _init()
    {
        $this->CURL_TIMEOUT = 10;
        $this->URL          = "http://api.fanyi.baidu.com/api/trans/vip/translate";
        $this->APP_ID       = 20221110001446116;//替换为您的APPID
        $this->SEC_KEY      = 'WruFanRRSSqyx6PS3Jhc';//替换为您的密钥
    }

    /**
     * @file   baidu_transapi.php
     * @author mouyantao(mouyantao@baidu.com)
     * @date   2015/06/23 14:32:18
     * @brief
     **/


    /**
     * @param $query 请求翻译query
     * @param $from  翻译源语言 auto
     * @param $to    翻译目标语言 en
     * @return mixed
     */
    function translate($query, $from = 'auto', $to = 'en')
    {
        $this->_init();

        $args         = array(
            'q'     => $query,
            'appid' => $this->APP_ID,
            'salt'  => rand(10000, 99999),
            'from'  => $from,
            'to'    => $to,

        );
        $args['sign'] = $this->buildSign($query, $this->APP_ID, $args['salt'], $this->SEC_KEY);
        $ret          = $this->call($this->URL, $args);
        $ret          = json_decode($ret, true);
        return $ret;
    }


    /**
     * @param $query 请求翻译query
     * @return mixed
     */
    function translate_youdao($query)
    {
        $this->_init();
        $url = 'http://fanyi.youdao.com/translate?&doctype=json&type=AUTO&i=' . $query;
        $ret = cmf_curl_get($url);
        $ret = json_decode($ret, true);
        return $ret['translateResult'][0][0]['tgt'];
    }


    //加密
    function buildSign($query, $appID, $salt, $secKey)
    {
        $str = $appID . $query . $salt . $secKey;
        $ret = md5($str);
        return $ret;
    }

    //发起网络请求
    function call($url, $args = null, $method = "post", $testflag = 0, $headers = array())
    {
        $this->_init();

        $ret = false;
        $i   = 0;
        while ($ret === false) {
            if ($i > 1)
                break;
            if ($i > 0) {
                sleep(1);
            }
            $ret = $this->callOnce($url, $args, $method, false, $this->CURL_TIMEOUT, $headers);
            $i++;
        }
        return $ret;
    }


    function callOnce($url, $args = null, $method = "post", $withCookie = false, $headers = array())
    {
        $this->_init();

        $ch = curl_init();
        if ($method == "post") {
            $data = $this->convert($args);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            $data = $this->convert($args);
            if ($data) {
                if (stripos($url, "?") > 0) {
                    $url .= "&$data";
                } else {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->CURL_TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $headers = [];
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($withCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    function convert(&$args)
    {
        $data = '';
        if (is_array($args)) {
            foreach ($args as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $data .= $key . '[' . $k . ']=' . rawurlencode($v) . '&';
                    }
                } else {
                    $data .= "$key=" . rawurlencode($val) . "&";
                }
            }
            return trim($data, "&");
        }
        return $args;
    }
}