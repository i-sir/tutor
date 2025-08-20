<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"WechatMenu",
 *     "name_underline"      =>"wechat_menu",
 *     "controller_name"     =>"WechatMenu",
 *     "table_name"          =>"wechat_menu",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"微信公众号菜单栏",
 *     "author"              =>"",
 *     "create_time"         =>"2025-04-06 11:46:22",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\WechatMenuController();
 * )
 */


use api\wxapp\controller\WxBaseController;
use think\facade\Cache;
use think\facade\Db;
use cmf\controller\AdminBaseController;


class WechatMenuController extends AdminBaseController
{

    //    public function initialize()
    //    {
    //        //微信公众号菜单栏
    //        parent::initialize();
    //    }


    /**
     * 首页基础信息
     */
    protected function base_index()
    {

    }

    /**
     * 编辑,添加基础信息
     */
    protected function base_edit()
    {
        $WechatMenuInit = new \init\WechatMenuInit();//wechat_menu    (ps:InitController)
        $this->assign('type_list', $WechatMenuInit->type);
    }


    /**
     * 首页列表数据
     * @adminMenu(
     *     'name'             => 'WechatMenu',
     *     'name_underline'   => 'wechat_menu',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => 'wechat_menu',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $this->base_index();//处理基础信息


        $WechatMenuInit  = new \init\WechatMenuInit();//wechat_menu    (ps:InitController)
        $WechatMenuModel = new \initmodel\WechatMenuModel(); //wechat_menu   (ps:InitModel)
        $params          = $this->request->param();


        /** 查询条件 **/
        $where   = [];
        $where[] = ['status', 'in', [1, 2]];
        $where[] = empty($params['pid']) ? ['pid', '=', 0] : ['pid', '=', $params['pid']];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表
        $params["field"]         = "*";//过滤字段


        /** 查询数据 **/
        $result = $WechatMenuInit->get_list_paginate($where, $params);


        /** 数据渲染 **/
        $this->assign("list", $result);
        $this->assign("pagination", $result->render());//单独提取分页出来
        $this->assign("page", $result->currentPage());//当前页码


        return $this->fetch();
    }


    //更新菜单
    public function pushMenu()
    {
        $WechatMenuInit  = new \init\WechatMenuInit();//wechat_menu    (ps:InitController)
        $WechatMenuModel = new \initmodel\WechatMenuModel(); //wechat_menu   (ps:InitModel)


        $map   = [];
        $map[] = ['status', '=', 1];
        $map[] = ['pid', '=', 0];
        $list  = $WechatMenuInit->get_list($map);

        $menu = [];

        foreach ($list as $item) {
            $map2   = [];
            $map2[] = ['pid', '=', $item['id']];
            $map2[] = ['status', '=', 1];
            $child  = $WechatMenuModel->where($map2)->order('list_order,id')->select();
            if (!$child->isEmpty()) {
                $sub_button = [];
                foreach ($child as $child_item) {
                    $sub_button[] = self::getButton($child_item);
                }
                $menu[] = [
                    'name'       => $item['name'],
                    'sub_button' => $sub_button
                ];
            } else {
                $menu[] = self::getButton($item);
            }
        }


        $access_token = self::getWxAccessToken();
        $menu         = [
            'button' => $menu
        ];
        $url          = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $access_token;
        $data         = urldecode(json_encode($menu, JSON_UNESCAPED_UNICODE));
        $result       = json_decode(self::curl_post($url, $data), true);

        if ($result['errcode'] == 0) {
            $this->success("创建成功！");
        } else {
            $this->error("推送失败！" . $result['errmsg']);
        }
    }

    //添加
    public function add()
    {
        $this->base_edit();//处理基础信息

        return $this->fetch();
    }


    //添加提交
    public function add_post()
    {
        $WechatMenuInit  = new \init\WechatMenuInit();//wechat_menu   (ps:InitController)
        $WechatMenuModel = new \initmodel\WechatMenuModel(); //wechat_menu   (ps:InitModel)
        $params          = $this->request->param();


        /** 检测参数信息 **/
        $validateResult = $this->validate($params, 'WechatMenu');
        if ($validateResult !== true) $this->error($validateResult);


        if (!empty($params['pid'])) {
            $map         = [];
            $map[]       = ['pid', '=', $params['pid']];
            $total_child = $WechatMenuModel->where($map)->count();
            if ($total_child >= 5) {
                $this->error('最多添加5个子菜单');
            }
        }


        if (!empty($params['thumb'])) {
            $params['media_id'] = self::upload_media($params['thumb']);
        }


        if (empty($params['pid'])) $params['pid'] = 0;


        /** 插入数据 **/
        $result = $WechatMenuInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //查看详情
    public function find()
    {
        $this->base_edit();//处理基础信息

        $WechatMenuInit  = new \init\WechatMenuInit();//wechat_menu    (ps:InitController)
        $WechatMenuModel = new \initmodel\WechatMenuModel(); //wechat_menu   (ps:InitModel)
        $params          = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "find";//数据格式,find详情,list列表
        $result                  = $WechatMenuInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        /** 数据格式转数组 **/
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //编辑详情
    public function edit()
    {
        $this->base_edit();//处理基础信息

        $WechatMenuInit  = new \init\WechatMenuInit();//wechat_menu  (ps:InitController)
        $WechatMenuModel = new \initmodel\WechatMenuModel(); //wechat_menu   (ps:InitModel)
        $params          = $this->request->param();

        /** 查询条件 **/
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        /** 查询数据 **/
        $params["InterfaceType"] = "admin";//接口类型
        $params["DataFormat"]    = "list";//数据格式,find详情,list列表

        $result = $WechatMenuInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        /** 数据格式转数组 **/
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //提交编辑
    public function edit_post()
    {
        $WechatMenuInit  = new \init\WechatMenuInit();//wechat_menu   (ps:InitController)
        $WechatMenuModel = new \initmodel\WechatMenuModel(); //wechat_menu   (ps:InitModel)
        $params          = $this->request->param();


        /** 检测参数信息 **/
        $validateResult = $this->validate($params, 'WechatMenu');
        if ($validateResult !== true) $this->error($validateResult);


        /** 更改数据条件 && 或$params中存在id本字段可以忽略 **/
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        /** 提交数据 **/
        $result = $WechatMenuInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //批量操作
    public function batch_post()
    {
        $WechatMenuInit  = new \init\WechatMenuInit();//wechat_menu   (ps:InitController)
        $WechatMenuModel = new \initmodel\WechatMenuModel(); //wechat_menu   (ps:InitModel)
        $params          = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $WechatMenuInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功");//   , "index{$this->params_url}"
    }


    //删除
    public function delete()
    {
        $WechatMenuInit  = new \init\WechatMenuInit();//wechat_menu   (ps:InitController)
        $WechatMenuModel = new \initmodel\WechatMenuModel(); //wechat_menu   (ps:InitModel)
        $params          = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        /** 删除数据 **/
        $result = $WechatMenuInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功");//   , "index{$this->params_url}"
    }


    //更新排序
    public function list_order_post()
    {
        $WechatMenuInit  = new \init\WechatMenuInit();//wechat_menu   (ps:InitController)
        $WechatMenuModel = new \initmodel\WechatMenuModel(); //wechat_menu   (ps:InitModel)
        $params          = $this->request->param("list_order/a");

        //提交更新
        $result = $WechatMenuInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功"); //   , "index{$this->params_url}"
    }


    /********************************** 相关方法 ***************************************/


    public static function getButton($data)
    {
        $return = [
            'type' => $data['type'],
            'name' => $data['name']
        ];
        if ($data['type'] == 'click') {
            $return['key'] = $data['key'];
        } elseif ($data['type'] == 'view') {
            $return['url'] = $data['url'];
        } elseif ($data['type'] == 'miniprogram') {
            $return['appid']    = $data['appid'];
            $return['pagepath'] = $data['pagepath'];
        } elseif ($data['type'] == 'media_id') {
            $return['media_id'] = $data['media_id'];
        }
        return $return;
    }

    public static function upload_media($image)
    {
        $content = file_get_contents(cmf_get_asset_url($image));
        $path    = 'upload/default/' . time() . '.png';
        file_put_contents($path, $content);

        $upload_url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=" . self::getWxAccessToken() . "&type=image";

        $json = self::curl_post($upload_url, [], [], $path);
        // json转为数组
        $arr = json_decode($json, true);

        if (file_exists($path)) {
            unlink($path);
        }


        return $arr['media_id'] ?? '';
    }


    public static function getButtonType($type = -1)
    {
        //按钮类型 ，view，media_id，article_id，miniprogram
        $type_name = [
            'click'       => '文本',
            'view'        => '网页',
            'miniprogram' => '小程序',
            //  'article_id'=>'图文消息',
            //  'media_id'=>'图片'
        ];
        return $type == -1 ? $type_name : ($type_name[$type] ?? '无');
    }


    public static function curl_post($url, $data = null, $headers = [], $file = '')
    {

        if (!empty($file)) {
            $data['media'] = new \CURLFile($file);
        }
        $curl = curl_init();
        if (count($headers) >= 1) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        //判断有无出错
        if (curl_errno($curl) > 0) {
            $output = json_encode([curl_error($curl)]);
        }
        curl_close($curl);
        return $output;
    }


    public static function getWxAccessToken()
    {
        return (new WxBaseController())->get_stable_access_token();
    }


}
