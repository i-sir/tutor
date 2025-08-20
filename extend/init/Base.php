<?php

namespace init;

use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Request;
use think\Response;

class Base
{
    /**
     * 获取当前的response 输出类型
     * @access protected
     * @return string
     */
    protected function getResponseType()
    {
        return 'json';
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param mixed $msg    提示信息
     * @param mixed $data   返回的数据
     * @param array $header 发送的Header信息
     * @return void
     */
    protected function success($msg = '', $data = '', array $header = [])
    {
        $code   = 1;
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];

        $type                                   = $this->getResponseType();
        $header['Access-Control-Allow-Origin']  = '*';
        $header['Access-Control-Allow-Headers'] = 'X-Requested-With,Content-Type,XX-Device-Type,XX-Token,Authorization,XX-Api-Version,XX-Wxapp-AppId';
        $header['Access-Control-Allow-Methods'] = 'GET,POST,PATCH,PUT,DELETE,OPTIONS';
        $response                               = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param mixed $msg    提示信息,若要指定错误码,可以传数组,格式为['code'=>您的错误码,'msg'=>'您的错误消息']
     * @param mixed $data   返回的数据
     * @param array $header 发送的Header信息
     * @return void
     */
    protected function error($msg = '', $data = '', array $header = [])
    {
        $code = 0;
        if (is_array($msg)) {
            $code = $msg['code'];
            $msg  = $msg['msg'];
        }
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];

        $type                                   = $this->getResponseType();
        $header['Access-Control-Allow-Origin']  = '*';
        $header['Access-Control-Allow-Headers'] = 'X-Requested-With,Content-Type,XX-Device-Type,XX-Token,Authorization,XX-Api-Version,XX-Wxapp-AppId';
        $header['Access-Control-Allow-Methods'] = 'GET,POST,PATCH,PUT,DELETE,OPTIONS';
        $response                               = Response::create($result, $type)->header($header);
        throw new HttpResponseException($response);
    }


    /**
     * 处理上传数组图片
     * @param $images
     * @return void
     */
    protected function setImages($images = [])
    {
        $result = implode(',', $images);
        if (empty($result)) return null;
        return $result;
    }


    /**
     * 获取图片 数组 短路径
     * @param $images 图片
     * @param $field  返回字段
     * @return array
     */
    protected function getImages($images = '', $field = 'images')
    {
        $images = explode(",", $images);
        if (empty($result)) return null;
        return $images;
    }


    /**
     * 获取图片 数组 全路径
     * @param $images 图片
     * @param $field  返回字段
     * @return array
     */
    protected function getImagesUrl($images = '', $field = 'images')
    {
        $images = explode(",", $images);

        if (is_array($images)) {
            for ($i = 0; $i < count($images); $i++) {
                $url[$i] = cmf_get_asset_url($images[$i]);
            }
            return $url;
        }

        return null;
    }

    /**
     * 处理上传内容数组打散字符串
     * @param $params
     * @return void
     */
    protected function setParams($params = [], $separator = ',')
    {
        $result = implode($separator, $params);
        if (empty($result)) return null;
        return $result;
    }


    /**
     * 处理上传内容数组打散字符串
     * @param $params
     * @return void
     */
    protected function getParams($params = '', $separator = ',')
    {
        $result = explode($separator, $params);
        if (empty($result)) return null;
        return $result;
    }

    /**
     * 地址转换为坐标
     */
    protected function search_address($param)
    {
        $url    = "https://apis.map.qq.com/ws/geocoder/v1/?key=GGVBZ-DUV6J-N33FQ-FX3AE-HTJ56-N6FDP&address=" . $param;
        $result = file_get_contents($url);
        return json_decode($result, true);
    }


    /**
     * 坐标转换地址
     */
    protected function reverse_address($params)
    {
        $url    = "https://apis.map.qq.com/ws/geocoder/v1/?location=" . $params . "&key=GGVBZ-DUV6J-N33FQ-FX3AE-HTJ56-N6FDP&get_poi=1";
        $result = file_get_contents($url);
        return json_decode($result, true);
    }


    /**
     * 获取时间区间值
     * @param $begin      开始时间 2022-11-1 15:53:55
     * @param $end        结束时间 2022-11-1 15:53:55
     * @param $Field      筛选时间字段名
     * @return array   [$beginField, 'between', [$beginTime, $endTime]];
     */
    protected function getBetweenTime($begin = '', $end = '', $Field = 'create_time')
    {
        $where[] = [$Field, 'between', [0, 999999999999]];

        if (!empty($begin)) {
            unset($where);
            $beginTime = strtotime($begin);//默认 00:00:00
            $where[]   = [$Field, 'between', [$beginTime, 999999999999]];
        }

        if (!empty($end)) {
            unset($where);
            $strlen = strlen($end);
            if ($strlen > 10) $endTime = strtotime($end);//传入 年月日,时分秒不用转换
            if ($strlen <= 10) $endTime = strtotime($end . '23:59:59');//传入 年月日,年月  拼接时分秒
            $where[] = [$Field, 'between', [0, $endTime]];
        }

        if (!empty($begin) && !empty($end)) {
            unset($where);
            $beginTime = strtotime($begin);
            $strlen    = strlen($end);
            if ($strlen > 10) $endTime = strtotime($end);
            if ($strlen <= 10) $endTime = strtotime($end . '23:59:59');//传入 年月日,年月  拼接时分秒
            $where = [$Field, 'between', [$beginTime, $endTime]];
        }
        return $where;
    }


}