<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use cmf\controller\RestBaseController;
use think\App;
use think\facade\Db;
use think\facade\Request;

header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:*');

error_reporting(0);

class AuthController extends RestBaseController
{

    public $user_info;
    public $user_id;
    public $openid;

    /**
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function initialize()
    {
        //账号到期全局拦截设置
        $app_expiration_time = cmf_config('app_expiration_time');//开发应用到期时间
        if (isset($app_expiration_time) && !empty($app_expiration_time) && strtotime($app_expiration_time) < time()) {
            //$this->error("账号已到期,请联系管理员！");
        }


        $openid = $this->request->header('openid');
        if (empty($openid)) $openid = $this->request->param('openid');

        $this->openid = $openid;
        $user_info    = $this->getUserInfoByOpenid($openid);
        if (!empty($user_info)) {
            $this->user_info = $user_info;
            $this->user_id   = $user_info['id'];
        }

        // token 验证
        $token = $this->request->header('token');
        if (empty($token)) $token = $this->request->param('token');
        if (!empty($token)) {
            $this->token = $token;
            $userToken   = Db::name("user_token")->where('token', $token)->find();
            if ($userToken) {
                $MemberInit = new \init\MemberInit();//用户管理
                $map        = [];
                $map[]      = ['id', '=', $userToken['user_id']];
                $user_info  = $MemberInit->get_find($map);
                if (!empty($user_info) && $userToken['expire_time'] > time()) {
                    $this->user_info = $user_info;
                    $this->user_id   = $user_info['id'];
                }
            }
        }
    }

    // 接口日志
    public function __construct()
    {
        parent::__construct();

        self::add_log();
    }


    /**
     * 检测用户是否登录
     * @param int $level 1是否登录
     */
    protected function checkAuth($level = 1)
    {
        if (empty($this->user_id)) $this->error('请先授权登录');
    }


    /**
     * 根据openid获取用户user_info   注:同步MemberModel
     * @param $openid
     * @param $where openid为空 条件筛选[]
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getUserInfoByOpenid($openid, $where = null)
    {
        if (empty($openid)) return false;
        $MemberInit = new \init\MemberInit();//用户管理
        $map        = [];
        $map[]      = ['openid', '=', $openid];
        $result     = $MemberInit->get_my_info($map);
        return $result;
    }


    /**
     * 根据id获取用户user_info  注:同步MemberModel
     * @param $user_id 用户id
     * @param $where   id为空 条件筛选[]
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getUserInfoById($user_id, $where = null)
    {
        if (empty($user_id)) return false;
        $MemberInit = new \init\MemberInit();//用户管理
        $map        = [];
        $map[]      = ['id', '=', $user_id];
        $result     = $MemberInit->get_my_info($map);
        return $result;
    }


    /**
     * 获取唯一单号或唯一编码
     * @param string $field_name 字段名
     * @param int    $length     长度(订单号类型，默认8位)
     * @param int    $type       生成类型：1:数子 2:数字+字母 3:纯数字 4:纯字母(大小写) 5:纯字母(大写)
     * @param string $prefix     前缀
     * @param string $table_name 表名|如为空,控制器必须和model名一致
     * @return string 唯一编号
     */
    protected function get_num_only($field_name = 'order_num', $length = 4, $type = 1, $prefix = '', $table_name = '')
    {
        static $attempts = 0;
        $attempts++;

        // 生成基础编号
        if ($type == 1) $only_num = $prefix . cmf_order_sn($length);
        if ($type == 2) $only_num = $prefix . cmf_random_string($length);
        if ($type == 3) $only_num = $prefix . $this->generatePureNumber($length);
        if ($type == 4) $only_num = $prefix . $this->generatePureLetters($length);
        if ($type == 5) $only_num = $prefix . $this->generateUppercasePureLetters($length);

        // 如果超过50次尝试，追加3位随机数字
        if ($attempts > 50) {
            $only_num .= mt_rand(100, 999); // 直接生成100-999的随机数
            $attempts = 0;
        }

        // 检查唯一性
        if ($table_name && is_string($table_name)) {
            $is = Db::name($table_name)->where($field_name, '=', $only_num)->count();
        } elseif (is_object($table_name)) {
            $is = $table_name->where($field_name, '=', $only_num)->count();
        } else {
            $model_class = "\\initmodel\\" . Request::controller() . "Model";
            $Model       = new $model_class();
            $is          = $Model->where($field_name, '=', $only_num)->count();
        }

        if ($is) {
            return $this->get_num_only($field_name, $length, $type, $prefix, $table_name);
        }

        $attempts = 0;
        return $only_num;
    }


    /**
     * 生成纯数字
     * @param $length
     * @return string
     */
    protected function generatePureNumber($length)
    {
        $digits = '0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $digits[rand(0, strlen($digits) - 1)];
        }
        return $result;
    }

    /**
     * 生成纯字母
     * @param $length
     * @return string
     */
    protected function generatePureLetters($length)
    {
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result  = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $letters[rand(0, strlen($letters) - 1)];
        }
        return $result;
    }


    /**
     * 生成纯字母 大写
     * @param $length
     * @return string
     */
    protected function generateUppercasePureLetters($length)
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result  = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $letters[rand(0, strlen($letters) - 1)];
        }
        return $result;
    }


    /**
     * 插入随机下划线
     * @param $inputString 字符串
     * @return array|string|string[]
     */
    protected function insertRandomUnderscore($inputString)
    {
        // 获取字符串长度
        $length = strlen($inputString);

        // 如果字符串长度小于等于 1，直接返回原字符串
        if ($length <= 1) return $inputString;

        // 生成一个随机位置，范围从 1 到 $length - 1，确保不在首尾
        $randomPosition = mt_rand(1, $length - 1);

        // 在随机位置插入下划线
        $resultString = substr_replace($inputString, '_', $randomPosition, 0);

        return $resultString;
    }


    /**
     * 创建文件夹
     * @param     $dir
     * @param int $mode
     * @return bool
     */
    protected function is_mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;

        if (!$this->is_mkdirs(dirname($dir), $mode)) return FALSE;

        return @mkdir($dir, $mode);
    }


    /**
     * 复制图片
     * @param $file_path 要复制图片路径
     * @param $copy_path 复制到的路径
     * @param $value     复制几份
     * @param $type      图片类型
     * @return array 返回数组格式
     */
    protected function copy_file($file_path, $copy_path, $name)
    {
        $absolute_path = $copy_path . $name;

        copy($file_path, $absolute_path);

        return $absolute_path;
    }


    /**
     * post请求
     * @param       $url
     * @param array $data
     * @return bool|string
     */
    protected function curl_post($url, $data = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
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
    protected function search_address($address)
    {
        //用于地图转经纬度,经纬度转地址,腾讯地图key
        $tencent_map_key = cmf_config('tencent_map_key');
        $url             = "https://apis.map.qq.com/ws/geocoder/v1/?key={$tencent_map_key}&address={$address}";
        $result          = file_get_contents($url);
        return json_decode($result, true);
    }


    /**
     * 坐标转换地址
     */
    protected function reverse_address($lnglat)
    {
        //用于地图转经纬度,经纬度转地址,腾讯地图key
        $tencent_map_key = cmf_config('tencent_map_key');
        $url             = "https://apis.map.qq.com/ws/geocoder/v1/?location={$lnglat}&key={$tencent_map_key}&get_poi=1";
        $result          = file_get_contents($url);
        return json_decode($result, true);
    }


    /**
     *求两个已知经纬度之间的距离,单位为米
     * @param lng1,lng2 经度
     * @param lat1,lat2 纬度
     * @return float 距离，单位米
     * @edit www.jbxue.com
     **/
    protected function getdistance($lng1, $lat1, $lng2, $lat2)
    {
        // 将角度转为狐度
        $radLat1 = deg2rad($lat1);// deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a       = $radLat1 - $radLat2;
        $b       = $radLng1 - $radLng2;
        $s       = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        return $s;
    }


    /**
     * 根据ip转地区信息
     * @return mixed|null
     */
    protected function get_ip_to_city()
    {
        //用于地图转经纬度,经纬度转地址,腾讯地图key
        $tencent_map_key = cmf_config('tencent_map_key');
        $onlineip        = get_client_ip();
        $url             = file_get_contents("https://apis.map.qq.com/ws/location/v1/ip?ip={$onlineip}&key={$tencent_map_key}");
        $res1            = json_decode($url, true);
        $data            = $res1;
        if (isset($data['result']['ad_info']) && !empty($data['result']['ad_info'])) {
            return $data['result']['ad_info']['city'];
        } else {
            return null;
        }
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


    // 写入日志
    protected function add_log()
    {
        // 屏蔽一些方法、模块（api 或 admin）控制器+方法
        $noController = [
            'wxapp/public/index',
            'wxapp/public/index1',
            'wxapp/public/find_area',
            'wxapp/public/find_navs',
            'wxapp/public/find_slide',
            'wxapp/public/upload_asset',
            'wxapp/public/find_agreement_list',
            'wxapp/public/find_setting',
        ];

        // 获取用户信息
        $user_id   = isset($this->user_id) ? $this->user_id : null;
        $user_name = $this->user_info['username'] ?? $this->user_info['nickname'];


        // 获取客户端 IP 地址
        $onlineip = get_client_ip();
        // 获取当前请求的 URL
        $menu_name  = $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        $menu_name  = str_replace("api.php?s=", "api/", $menu_name);
        $url_detail = explode('&', $menu_name, 2);
        $menu_name  = $url_detail[0];

        // 获取请求的路径信息（控制器和方法）
        $pathinfo = request()->pathinfo();

        // 如果当前请求路径在屏蔽列表中，不记录日志
        if (in_array($pathinfo, $noController)) return false;


        // 获取请求的参数
        $params = $this->request->param();
        if (isset($this->openid) && $this->openid) {
            $params['openid'] = $this->openid;
        }


        // 构建访问的完整 URL
        $visit_url = $menu_name . '?' . http_build_query($params);

        // 日志文件数据
        $array_log = [
            'admin_id'    => $user_id,
            'ip'          => $onlineip,
            'admin_name'  => $this->filterEmoji($user_name),
            'date'        => date('Y-m-d H:i:s'),
            'create_time' => time(),
            'menu_name'   => $menu_name,
            'param'       => json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'visit_url'   => $visit_url,
            'openid'      => $this->openid,
            'type'        => 2
        ];


        // 插入日志数据到数据库
        Db::name('base_admin_log')->strict(false)->insert($array_log);

        return true;
    }

    //去除昵称的表情问题
    protected function filterEmoji($str)
    {
        $str = preg_replace_callback('/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return $str;
    }

    /**
     * 获取微信昵称
     * @return void
     */
    protected function get_member_wx_nickname()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理
        $max_id      = $MemberModel->max('id');
        return '微信用户_' . ($max_id + 1);
    }

}
