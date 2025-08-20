<?php

namespace plugins\configs\controller;

use cmf\controller\PluginAdminBaseController;
use think\facade\Db;

error_reporting(E_ERROR | E_PARSE);

class AdminIndexController extends PluginAdminBaseController
{
    protected $group_list;

    protected function initialize()
    {
        //parent::initialize();
        $CUSTODIAN_SIR = cookie('CUSTODIAN_SIR');

        /*根据自身需求配置参数分组*/
        $list        = Db::name('base_config')->where('is_menu', 1)->order('list_order,id desc')->select()->toArray();
        $group_array = [];
        foreach ($list as $value) {
            $group_array[$value['key']] = $value['menu_name'];
        }
        $this->group_list = $group_array;

        //本地测试展示内容
        if ($CUSTODIAN_SIR) {
            $list = Db::name('base_config')->where('is_menu', 'in', [1, 3])->order('key')->select()->toArray();
            foreach ($list as $value) {
                $group_array[$value['key']] = $value['menu_name'];
            }
            $this->group_list = $group_array;
        }


        $this->assign('group_list', $this->group_list);
        $this->assign("CUSTODIAN_SIR", $CUSTODIAN_SIR);//管理员
    }


    //翻译
    public function translate()
    {
        $value = $this->request->param('value');

        $translate = new \init\TranslateInit();
        $result    = $translate->translate($value);

        if (isset($result) && $result) {
            $this->success('翻译结果', '', $result['trans_result'][0]['dst']);
        }
    }


    /**
     * 系统参数设置
     * @adminMenu(
     *     'name'   => '系统参数设置',
     *     'parent' => 'admin/setting/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '系统参数设置',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $CUSTODIAN_SIR = cookie('CUSTODIAN_SIR');


        $group_id      = input('group_id', 100, 'intval');
        $post_group_id = input('post_group_id', 100, 'intval');
        $config        = self::getConfig($group_id);

        if (request()->isPost()) {
            self::saveConfig($group_id);


            //更新
            $params = $this->request->param();
            cmf_set_option('set_config', $params);

            $this->success("保存成功", cmf_plugin_url('Configs://AdminIndex/index', ['group_id' => input('group_id')]));
        }


        //处理显示数据
        $this_page_params = '';//本页参数复制用
        $result           = [];//结果
        foreach ($config as $k => $v) {
            $result[$k] = $v;
            if ($v['name'] == 'app_expiration_time' && $CUSTODIAN_SIR) {
                unset($result[$k]);
            }
            $this_page_params .= $v['name'] . ' : ' . $v['label'] . "\n";
        }


        $this->assign('menuid', 1);
        $this->assign('config', array_merge($result));
        $this->assign('group_id', $group_id);
        $this->assign('post_group_id', $post_group_id);


        //本页参数
        if ($group_id != 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_setting   查询系统配置信息';
        if ($group_id == 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_agreement_list   查询协议列表';
        $this->assign('this_page_params', $thisPageParams);


        return $this->fetch('/admin_index');
    }


    /**
     * 保存参数设置
     * @param $group_id
     * @author: lampzww
     * @Date  : 11:25  2019/8/10
     */
    private function saveConfig($group_id)
    {
        $params = $this->request->param();

        $config = Db::name('base_config')->where(['group_id' => $group_id])->column("name,type");
        if ($config) {
            //修改,内容值信息   将全部改为隐藏,后面勾选那个开启显示
            foreach ($config as $key => $item) {
                $val = input($item['name'], "", "trim");

                //处理显示|隐藏
                if ($params['is_show']) Db::name('base_config')->where(['name' => $item['name']])->update(['value' => serialize($val), 'is_show' => 0]);

                //处理编辑|禁止编辑
                if ($params['is_edit']) Db::name('base_config')->where(['name' => $item['name']])->update(['is_edit' => 0]);


                //处理组件格式|非组件格式
                if ($params['is_label']) Db::name('base_config')->where(['name' => $item['name']])->update(['is_label' => 0]);


                //隐藏了数据  也需要提交过来保存一下
                if (!isset($params['is_show']) || empty($params['is_show'])) Db::name('base_config')->where(['name' => $item['name']])->update(['value' => serialize($val)]);
            }
        }


        //处理排序
        $list_order = $this->request->param('list_order');
        if ($list_order) {
            foreach ($list_order as $k => $v) {
                Db::name('base_config')->where(['id' => $k])->update(['list_order' => $v]);
            }
        }


        //处理备注
        $about = $this->request->param('about');
        if ($about) {
            foreach ($about as $k => $v) {
                Db::name('base_config')->where(['id' => $k])->update(['about' => $v]);
            }
        }


        //处理名称
        $label = $this->request->param('label');
        if ($label) {
            foreach ($label as $k => $v) {
                Db::name('base_config')->where(['id' => $k])->update(['label' => $v]);
            }
        }


        //处理英文
        $name = $this->request->param('name');
        if ($name) {
            foreach ($name as $k => $v) {
                Db::name('base_config')->where(['id' => $k])->update(['name' => $v]);
            }
        }


        //处理显示隐藏
        if ($params['showList']) {
            $show_list = $this->request->param('showList');
            if ($show_list) {
                foreach ($show_list as $k => $v) {
                    Db::name('base_config')->where(['id' => $k])->update(['is_show' => 1]);
                }
            }
        }


        //处理编辑
        if ($params['editList']) {
            $edit_list = $this->request->param('editList');
            if ($edit_list) {
                foreach ($edit_list as $k => $v) {
                    Db::name('base_config')->where(['id' => $k])->update(['is_edit' => 1]);
                }
            }
        }

        //处理组件格式
        if ($params['labelList']) {
            $label_list = $this->request->param('labelList');
            if ($label_list) {
                foreach ($label_list as $k => $v) {
                    Db::name('base_config')->where(['id' => $k])->update(['is_label' => 1]);
                }
            }
        }


        cache("DB_CONFIG_DATA", null); //清除缓存
    }


    /**
     * 添加/修改参数
     * @author: lampzww
     * @Date  : 11:00  2019/8/10
     */
    public function add()
    {
        $params = $this->request->param();

        $group_id   = input('group_id', 1, "intval");
        $name       = input("name", "", "trim");
        $label      = input("label", "", "trim");
        $type       = input("type", "", "trim");
        $data       = input("data", "", "trim");
        $about      = input("about", "", "trim");
        $uridata    = input('uridata', "", 'trim');
        $scatter    = input('scatter', "", 'trim');
        $list_order = input("list_order", 0, "intval");
        $id         = input("id", "", "trim");


        if (request()->isPost()) {
            $result = $this->validate(input(), "Configs");
            if ($result !== true) $this->error($result);


            //排序
            if ($list_order == 0) $list_order = (int)(Db::name('base_config')->where("group_id = " . $group_id)->max("list_order") / 100 + 1) * 100;


            $is_label = 0;
            //组件数据格式:0否,1是
            if (isset($params['is_label']) && $params['is_label']) $is_label = 1;

            $is_edit = 0;
            //是否可编辑数据:0否,1是
            if (isset($params['is_edit']) && $params['is_edit']) $is_edit = 1;

            $is_show = 0;
            //是否显示:0否,1是
            if (isset($params['is_show']) && $params['is_show']) $is_show = 1;


            $is_menu   = 2;
            $key       = 0;
            $menu_name = null;
            //是否菜单1:线上显示,本地都显示,2所有不显示,3本地显示,线上不显示
            if (isset($params['group_id']) && $params['group_id'] == 'site') {
                $is_menu   = 1;
                $menu_name = $params['label'];


                //当为空时,生成key
                if (empty($params['id'])) {
                    //生成key
                    $map        = [];
                    $map[]      = ['is_menu', 'in', [1, 3]];
                    $map[]      = ['id', '<>', 2];
                    $key        = Db::name('base_config')->where($map)->order('id desc')->value('key');
                    $key        = ($key + 100);
                    $list_order = $key;
                } else {
                    $map         = [];
                    $map[]       = ['id', '=', $params['id']];
                    $config_info = Db::name('base_config')->where($map)->find();
                    $key         = $config_info['key'];
                    $list_order  = $config_info['list_order'];
                }
            }


            //数据值
            if (in_array($type, ["select", "checkbox", "radio"])) {
                if ($data == "") {
                    $this->error("请填写可选参数!");
                } else {
                    $data = explode("\n", $data);
                    $data = array_map("trim", $data);
                    empty($data) && $this->error("请填写可选参数!");
                    $option_data = [];
                    foreach ($data as $val) {
                        $_data = explode("=", $val);
                        if (count($_data) == 2)
                            $option_data[] = ['value' => $_data[0], 'name' => $_data[1]];
                    }
                    empty($option_data) && $this->error("请填写可选参数!");
                    $data = $option_data;
                }
            } else {
                $data = "";
            }


            if ($id) {
                //编辑,检测是否重复,存在
                $where   = [];
                $where[] = ['id', '=', $id];
                $config  = Db::name('base_config')->where($where)->find();
                if ($config['name'] != $name) {
                    $where2    = [];
                    $where2[]  = ['name', '=', $name];
                    $is_config = Db::name('base_config')->where($where2)->find();
                    if ($is_config) $this->error("参数名[{$name}]已存在");
                }


                //编辑数据
                $config_data = [
                    'scatter'    => $scatter,
                    'name'       => $name,
                    'label'      => $label,
                    'is_label'   => $is_label,
                    'is_edit'    => $is_edit,
                    'is_show'    => $is_show,
                    'is_menu'    => $is_menu,
                    'menu_name'  => $menu_name,
                    'key'        => $key,
                    'type'       => $type,
                    'data'       => serialize($data),
                    'group_id'   => $group_id,
                    'about'      => $about,
                    'list_order' => $list_order,
                    'uridata'    => $uridata,
                ];
                $result      = Db::name('base_config')->where(['id' => $id])->update($config_data);
            } else {
                //添加数据


                //检测是否存在
                $where     = [];
                $where[]   = ['name', '=', $name];
                $is_config = Db::name('base_config')->where($where)->find();
                if ($is_config) $this->error("参数名[{$name}]已存在");


                $config_data = [
                    'scatter'    => $scatter,
                    'name'       => $name,
                    'label'      => $label,
                    'is_label'   => $is_label,
                    'is_edit'    => $is_edit,
                    'is_show'    => $is_show,
                    'is_menu'    => $is_menu,
                    'menu_name'  => $menu_name,
                    'key'        => $key,
                    'type'       => $type,
                    'data'       => serialize($data),
                    'group_id'   => $group_id,
                    'about'      => $about,
                    'list_order' => $list_order,
                ];
                $result      = Db::name('base_config')->strict(false)->insert($config_data);
            }
            $result === false && $this->error("操作失败");
            if ($params['post_group_id'] == 'site' || $params['group_id'] == 'site') $this->success("操作成功", cmf_plugin_url('Configs://AdminIndex/site', ['group_id' => $params['post_group_id']]));
            $this->success("操作成功", cmf_plugin_url('Configs://AdminIndex/index', ['group_id' => $params['post_group_id']]));
        }


        $config = [];
        if ($id) {
            $config = $this->getConfigIdInfo($id);
            $this->assign('nav_title', '修改参数');
            $this->assign('id', $id);

        } else {
            $this->assign('nav_title', '添加参数');
        }
        $this->assign(input());
        $this->assign('group_id', input('group_id', '', 'intval'));
        $this->assign($config);


        return $this->fetch('/add');
    }


    /**
     * 删除参数
     * @author: lampzww
     * @Date  : 14:15  2019/8/10
     */
    public function delete()
    {
        $params = $this->request->param();
        $map    = [];
        $map[]  = ['id', '=', $params['id']];
        $result = Db::name('base_config')->where($map)->delete();
        $result === false && $this->error('操作失败!');
        if ($params['group_id'] == 'site') $this->success('操作成功!', cmf_plugin_url('Configs://AdminIndex/site', ['group_id' => $params['group_id']]));

        $this->success('操作成功!', cmf_plugin_url('Configs://AdminIndex/index', ['group_id' => $params['group_id']]));
    }


    /**
     * @param $name
     * @param $group_id
     * @author: lampzww
     * @Date  : 16:40  2019/8/10
     */
    protected function getConfigInfo($name, $group_id)
    {
        if (!$name || !$group_id) return false;

        $data = Db::name('base_config')->where(['group_id' => $group_id, 'name' => $name])->find();
        if ($data) {
            unset($data['value']);
            $data['data'] = unserialize($data['data']);
            $_data        = "";
            if (is_array($data['data'])) {
                foreach ($data['data'] as $val) {
                    $_data .= "{$val['value']}={$val['name']}\n";
                }
            }
            $data['data'] = trim($_data);
        }
        return $data;
    }


    /**
     * 根据id获取参数值
     * @param $id
     * @return array|Db|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getConfigIdInfo($id)
    {
        $data = Db::name('base_config')->where(['id' => $id])->find();
        if ($data) {
            unset($data['value']);
            $data['data'] = unserialize($data['data']);
            $_data        = "";
            if (is_array($data['data'])) {
                foreach ($data['data'] as $val) {
                    $_data .= "{$val['value']}={$val['name']}\n";
                }
            }
            $data['data'] = trim($_data);
        }
        return $data;
    }


    /**
     * 获取配置信息
     * @param int $group_id
     * @return array
     * @author: lampzww
     * @Date  : 17:41  2019/8/8
     */
    private function getConfig($group_id = 1)
    {
        $config = Db::name('base_config')->where(['group_id' => $group_id])->order("list_order")->select()->toArray();
        if ($config) {
            foreach ($config as &$item) {
                $item['value'] = unserialize($item['value']);
                $item['data']  = unserialize($item['data']);

                //处理备注
                $copy_value = '';
                if ($item['about']) $copy_value = $item['about'];
                if (empty($copy_value) && $item['label']) $copy_value = $item['label'];
                if (empty($copy_value)) $copy_value = $item['name'];

                $item['copy'] = "//{$copy_value}\n" . '$' . "{$item['name']}=cmf_config('" . $item['name'] . "');";
            }
        }
        return $config;
    }


    /**
     * 设置菜单栏是否显示
     */
    public function site()
    {
        $map    = [];
        $map[]  = ['is_menu', 'in', [1, 3]];
        $result = Db::name('base_config')->where($map)->order('list_order,id desc')->select();

        $this->assign('list', $result);

        return $this->fetch('/site');
    }


    /**
     * 保存,菜单栏
     */
    public function site_post()
    {
        $params = $this->request->param();

        $map   = [];
        $map[] = ['is_menu', 'in', [1, 3]];
        $list  = Db::name('base_config')->where($map)->select();
        $ids   = array_keys($params['ids']);//处理id值
        foreach ($list as $key => $value) {
            //默认线上可显示
            $is_menu = 1;

            //如果没有选择,状态改为本地可见
            if (!in_array($value['id'], $ids)) $is_menu = 3;

            Db::name('base_config')->where('id', '=', $value['id'])->update([
                'is_menu' => $is_menu
            ]);
        }


        //处理排序
        $list_order = $this->request->param('list_order');
        if ($list_order) {
            foreach ($list_order as $k => $v) {
                Db::name('base_config')->where(['id' => $k])->update(['list_order' => $v]);
            }
        }


        $this->success('保存成功!');
    }



    /****************************************   可以设置详情页的   ******************************************/
    /**
     * 查看某个分类详情
     * @adminMenu(
     *     'name'   => '系统参数设置',
     *     'parent' => 'plugin/configs/admin_index/details',
     *     '应用' => 'plugin/configs',
     *     '控制器' => 'admin_index',
     *     '方法' => 'details',
     *     '参数' => '?group_id=500',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '系统参数设置',
     *     'param'  => ''
     * )
     */
    public function details()
    {
        $CUSTODIAN_SIR = cookie('CUSTODIAN_SIR');


        $group_id      = input('group_id', 100, 'intval');
        $post_group_id = input('post_group_id', 100, 'intval');
        $config        = self::getConfig($group_id);


        if (request()->isPost()) {
            self::saveConfig($group_id);
            //更新
            $params = $this->request->param();
            cmf_set_option('set_config', $params);

            $this->success("保存成功", cmf_plugin_url('Configs://AdminIndex/details', ['group_id' => input('group_id')]));
        }


        //处理显示数据
        $this_page_params = '';//本页参数复制用
        $result           = [];//结果
        foreach ($config as $k => $v) {
            $result[$k] = $v;
            if ($v['name'] == 'app_expiration_time' && $CUSTODIAN_SIR) {
                unset($result[$k]);
            }
            $this_page_params .= $v['name'] . ' : ' . $v['label'] . "\n";
        }


        $this->assign('menuid', 1);
        $this->assign('config', array_merge($result));
        $this->assign('group_id', $group_id);
        $this->assign('post_group_id', $post_group_id);


        //本页参数
        if ($group_id != 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_setting   查询系统配置信息';
        if ($group_id == 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_agreement_list   查询协议列表';
        $this->assign('this_page_params', $thisPageParams);

        /*获取菜单栏(tab),即便是配置信息隐藏,这里也要展示*/
        $list        = Db::name('base_config')->where('is_menu', 'in', [1, 2, 3])->select()->toArray();
        $group_array = [];
        foreach ($list as $value) {
            $group_array[$value['key']] = $value['menu_name'];
        }
        $this->assign('group_list', $group_array);


        return $this->fetch('/details');
    }


    /**
     * 查看某个分类详情2
     * @adminMenu(
     *     'name'   => '系统参数设置',
     *     'parent' => 'plugin/configs/admin_index/details',
     *     '应用' => 'plugin/configs',
     *     '控制器' => 'admin_index',
     *     '方法' => 'details2',
     *     '参数' => '?group_id=500',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '系统参数设置',
     *     'param'  => ''
     * )
     */
    public function details2()
    {
        $CUSTODIAN_SIR = cookie('CUSTODIAN_SIR');


        $group_id      = input('group_id', 100, 'intval');
        $post_group_id = input('post_group_id', 100, 'intval');
        $config        = self::getConfig($group_id);


        if (request()->isPost()) {
            self::saveConfig($group_id);


            //更新
            $params = $this->request->param();
            cmf_set_option('set_config', $params);

            $this->success("保存成功", cmf_plugin_url('Configs://AdminIndex/details2', ['group_id' => input('group_id')]));
        }


        //处理显示数据
        $this_page_params = '';//本页参数复制用
        $result           = [];//结果
        foreach ($config as $k => $v) {
            $result[$k] = $v;
            if ($v['name'] == 'app_expiration_time' && $CUSTODIAN_SIR) {
                unset($result[$k]);
            }
            $this_page_params .= $v['name'] . ' : ' . $v['label'] . "\n";
        }


        $this->assign('menuid', 1);
        $this->assign('config', array_merge($result));
        $this->assign('group_id', $group_id);
        $this->assign('post_group_id', $post_group_id);


        //本页参数
        if ($group_id != 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_setting   查询系统配置信息';
        if ($group_id == 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_agreement_list   查询协议列表';
        $this->assign('this_page_params', $thisPageParams);

        /*获取菜单栏(tab),即便是配置信息隐藏,这里也要展示*/
        $list        = Db::name('base_config')->where('is_menu', 'in', [1, 2, 3])->select()->toArray();
        $group_array = [];
        foreach ($list as $value) {
            $group_array[$value['key']] = $value['menu_name'];
        }
        $this->assign('group_list', $group_array);

        return $this->fetch('/details2');
    }


    /**
     * 查看某个分类详情3
     * @adminMenu(
     *     'name'   => '系统参数设置',
     *     'parent' => 'plugin/configs/admin_index/details',
     *     '应用' => 'plugin/configs',
     *     '控制器' => 'admin_index',
     *     '方法' => 'details3',
     *     '参数' => '?group_id=500',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '系统参数设置',
     *     'param'  => ''
     * )
     */
    public function details3()
    {
        $CUSTODIAN_SIR = cookie('CUSTODIAN_SIR');


        $group_id      = input('group_id', 100, 'intval');
        $post_group_id = input('post_group_id', 100, 'intval');
        $config        = self::getConfig($group_id);


        if (request()->isPost()) {
            self::saveConfig($group_id);


            //更新
            $params = $this->request->param();
            cmf_set_option('set_config', $params);

            $this->success("保存成功", cmf_plugin_url('Configs://AdminIndex/details3', ['group_id' => input('group_id')]));
        }


        //处理显示数据
        $this_page_params = '';//本页参数复制用
        $result           = [];//结果
        foreach ($config as $k => $v) {
            $result[$k] = $v;
            if ($v['name'] == 'app_expiration_time' && $CUSTODIAN_SIR) {
                unset($result[$k]);
            }
            $this_page_params .= $v['name'] . ' : ' . $v['label'] . "\n";
        }


        $this->assign('menuid', 1);
        $this->assign('config', array_merge($result));
        $this->assign('group_id', $group_id);
        $this->assign('post_group_id', $post_group_id);


        //本页参数
        if ($group_id != 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_setting   查询系统配置信息';
        if ($group_id == 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_agreement_list   查询协议列表';
        $this->assign('this_page_params', $thisPageParams);

        /*获取菜单栏(tab),即便是配置信息隐藏,这里也要展示*/
        $list        = Db::name('base_config')->where('is_menu', 'in', [1, 2, 3])->select()->toArray();
        $group_array = [];
        foreach ($list as $value) {
            $group_array[$value['key']] = $value['menu_name'];
        }
        $this->assign('group_list', $group_array);

        return $this->fetch('/details3');
    }


    /**
     * 查看某个分类详情4
     * @adminMenu(
     *     'name'   => '系统参数设置',
     *     'parent' => 'plugin/configs/admin_index/details',
     *     '应用' => 'plugin/configs',
     *     '控制器' => 'admin_index',
     *     '方法' => 'details4',
     *     '参数' => '?group_id=500',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '系统参数设置',
     *     'param'  => ''
     * )
     */
    public function details4()
    {
        $CUSTODIAN_SIR = cookie('CUSTODIAN_SIR');


        $group_id      = input('group_id', 100, 'intval');
        $post_group_id = input('post_group_id', 100, 'intval');
        $config        = self::getConfig($group_id);


        if (request()->isPost()) {
            self::saveConfig($group_id);


            //更新
            $params = $this->request->param();
            cmf_set_option('set_config', $params);

            $this->success("保存成功", cmf_plugin_url('Configs://AdminIndex/details4', ['group_id' => input('group_id')]));
        }


        //处理显示数据
        $this_page_params = '';//本页参数复制用
        $result           = [];//结果
        foreach ($config as $k => $v) {
            $result[$k] = $v;
            if ($v['name'] == 'app_expiration_time' && $CUSTODIAN_SIR) {
                unset($result[$k]);
            }
            $this_page_params .= $v['name'] . ' : ' . $v['label'] . "\n";
        }


        $this->assign('menuid', 1);
        $this->assign('config', array_merge($result));
        $this->assign('group_id', $group_id);
        $this->assign('post_group_id', $post_group_id);


        //本页参数
        if ($group_id != 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_setting   查询系统配置信息';
        if ($group_id == 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_agreement_list   查询协议列表';
        $this->assign('this_page_params', $thisPageParams);

        /*获取菜单栏(tab),即便是配置信息隐藏,这里也要展示*/
        $list        = Db::name('base_config')->where('is_menu', 'in', [1, 2, 3])->select()->toArray();
        $group_array = [];
        foreach ($list as $value) {
            $group_array[$value['key']] = $value['menu_name'];
        }
        $this->assign('group_list', $group_array);

        return $this->fetch('/details4');
    }


    /**
     * 查看某个分类详情5
     * @adminMenu(
     *     'name'   => '系统参数设置',
     *     'parent' => 'plugin/configs/admin_index/details',
     *     '应用' => 'plugin/configs',
     *     '控制器' => 'admin_index',
     *     '方法' => 'details4',
     *     '参数' => '?group_id=500',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '系统参数设置',
     *     'param'  => ''
     * )
     */
    public function details5()
    {
        $CUSTODIAN_SIR = cookie('CUSTODIAN_SIR');


        $group_id      = input('group_id', 100, 'intval');
        $post_group_id = input('post_group_id', 100, 'intval');
        $config        = self::getConfig($group_id);


        if (request()->isPost()) {
            self::saveConfig($group_id);


            //更新
            $params = $this->request->param();
            cmf_set_option('set_config', $params);

            $this->success("保存成功", cmf_plugin_url('Configs://AdminIndex/details5', ['group_id' => input('group_id')]));
        }


        //处理显示数据
        $this_page_params = '';//本页参数复制用
        $result           = [];//结果
        foreach ($config as $k => $v) {
            $result[$k] = $v;
            if ($v['name'] == 'app_expiration_time' && $CUSTODIAN_SIR) {
                unset($result[$k]);
            }
            $this_page_params .= $v['name'] . ' : ' . $v['label'] . "\n";
        }


        $this->assign('menuid', 1);
        $this->assign('config', array_merge($result));
        $this->assign('group_id', $group_id);
        $this->assign('post_group_id', $post_group_id);


        //本页参数
        if ($group_id != 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_setting   查询系统配置信息';
        if ($group_id == 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_agreement_list   查询协议列表';
        $this->assign('this_page_params', $thisPageParams);


        /*获取菜单栏(tab),即便是配置信息隐藏,这里也要展示*/
        $list        = Db::name('base_config')->where('is_menu', 'in', [1, 2, 3])->select()->toArray();
        $group_array = [];
        foreach ($list as $value) {
            $group_array[$value['key']] = $value['menu_name'];
        }
        $this->assign('group_list', $group_array);


        return $this->fetch('/details5');
    }


    /**
     * 查看某个分类详情6
     * @adminMenu(
     *     'name'   => '系统参数设置',
     *     'parent' => 'plugin/configs/admin_index/details',
     *     '应用' => 'plugin/configs',
     *     '控制器' => 'admin_index',
     *     '方法' => 'details6',
     *     '参数' => '?group_id=500',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '系统参数设置',
     *     'param'  => ''
     * )
     */
    public function details6()
    {
        $CUSTODIAN_SIR = cookie('CUSTODIAN_SIR');


        $group_id      = input('group_id', 100, 'intval');
        $post_group_id = input('post_group_id', 100, 'intval');
        $config        = self::getConfig($group_id);


        if (request()->isPost()) {
            self::saveConfig($group_id);


            //更新
            $params = $this->request->param();
            cmf_set_option('set_config', $params);

            $this->success("保存成功", cmf_plugin_url('Configs://AdminIndex/details6', ['group_id' => input('group_id')]));
        }


        //处理显示数据
        $this_page_params = '';//本页参数复制用
        $result           = [];//结果
        foreach ($config as $k => $v) {
            $result[$k] = $v;
            if ($v['name'] == 'app_expiration_time' && $CUSTODIAN_SIR) {
                unset($result[$k]);
            }
            $this_page_params .= $v['name'] . ' : ' . $v['label'] . "\n";
        }


        $this->assign('menuid', 1);
        $this->assign('config', array_merge($result));
        $this->assign('group_id', $group_id);
        $this->assign('post_group_id', $post_group_id);


        //本页参数
        if ($group_id != 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_setting   查询系统配置信息';
        if ($group_id == 3) $thisPageParams = $this_page_params . "\n" . "\n" . '/wxapp/public/find_agreement_list   查询协议列表';
        $this->assign('this_page_params', $thisPageParams);


        /*获取菜单栏(tab),即便是配置信息隐藏,这里也要展示*/
        $list        = Db::name('base_config')->where('is_menu', 'in', [1, 2, 3])->select()->toArray();
        $group_array = [];
        foreach ($list as $value) {
            $group_array[$value['key']] = $value['menu_name'];
        }
        $this->assign('group_list', $group_array);


        return $this->fetch('/details6');
    }


}
