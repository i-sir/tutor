<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------

namespace plugins\form\controller;

//Demo插件英文名，改成你的插件英文就行了

use app\admin\model\AdminMenuModel;
use app\admin\model\AuthRuleModel;
use cmf\controller\PluginAdminBaseController;
use plugins\form\model\FormModel;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\Model;
use tree\Tree;
use function DI\string;

/**
 * Class AdminIndexController.
 */
class AdminIndexController extends PluginAdminBaseController
{


    /**
     * @var Model
     */
    public $model;


    protected function initialize()
    {
        $this->model = new FormModel();
    }

    // 数据库信息
    public function db_info()
    {
        $params = $this->request->param();

        //不用验证的字段
        $notValidate = ['status', 'list_order', 'is_recommend'];

        $rule = [
            'null'                          => '无',
            'require'                       => '确保 * 字段不能为空!',
            'require|mobile'                => '确保 * 字段存有效的手机号!',
            'email'                         => '确保 * 字段为有效的邮箱地址',
            'number|between:1,120'          => '确保 * 字段为数字且在 1 到 120 之间',
            'require|min:6|confirm'         => '确保 * 字段存在、长度至少为 6 且与 password_confirm 字段值相同',
            'image|fileSize:2M'             => '确保 * 字段为图片文件且文件大小不超过 2MB',
            'require|array'                 => '确保 * 字段存在且为非空数组',
            'integer'                       => '确保 * 字段为整数',
            'in:male,female'                => '确保 * 字段值为 male 或 female',
            'notIn:deleted,banned'          => '确保 * 字段值不为 deleted 或 banned',
            'url'                           => '确保 * 字段为有效的 URL',
            'date'                          => '确保 * 字段为有效的日期',
            'dateFormat:Y-m-d'              => '确保 * 字段符合 YYYY-MM-DD 格式',
            'alpha'                         => '确保 * 字段值只能包含字母',
            'alphaNum'                      => '确保 * 字段值只能包含字母和数字',
            'alphaDash'                     => '确保 * 字段值只能包含字母、数字、下划线和破折号',
            'unique:users,email'            => '确保 * 字段值在 users 表的 email 列中唯一',
            'different:old_password'        => '确保 * 字段值与 old_password 字段值不同',
            'after:2023-01-01'              => '确保 * 字段值为 2023-01-01 之后的日期',
            'before:2023-12-31'             => '确保 * 字段值为 2023-12-31 之前的日期',
            'expire:2023-01-01,2023-12-31'  => '确保 * 字段值在 2023-01-01 到 2023-12-31 之间',
            'ip'                            => '确保 * 字段为有效的 IP 地址',
            'file'                          => '确保 * 字段为文件',
            'fileExt:jpg,png,gif'           => '确保 * 字段的文件扩展名为 jpg、png 或 gif',
            'fileMime:image/jpeg,image/png' => '确保 * 字段的 MIME 类型为 image/jpeg 或 image/png',
            'fileSize:2M'                   => '确保 * 字段的文件大小不超过 2MB',
        ];

        $tableName = config('database.connections.mysql.prefix') . $params['table_name'];
        $db        = config('database.connections.mysql.database');
        $sql       = sprintf(
            "select COLUMN_NAME,COLUMN_COMMENT,DATA_TYPE from information_schema.COLUMNS
	where table_name ='%s' and table_schema ='%s'", $tableName, $db);


        $table_result = Db::query($sql);
        $result       = [];
        $html         = '';
        $validateHtml = '';
        foreach ($table_result as $k => $v) {
            $COLUMN_NAME    = $v['COLUMN_NAME'];
            $COLUMN_COMMENT = $v['COLUMN_COMMENT'];

            if (empty($COLUMN_COMMENT)) $COLUMN_COMMENT = $COLUMN_NAME;
            $result[$k]['key']   = $COLUMN_NAME;
            $result[$k]['value'] = $COLUMN_COMMENT;


            // 生成验证规则，排除 is_ 开头和 _time 结尾的字段
            if (!in_array($COLUMN_NAME, $notValidate) && !preg_match('/^is_/', $COLUMN_NAME) && !preg_match('/_time$/', $COLUMN_NAME)) {
                // 搜索关键字
                $html .= '<input style="margin-left: 10px;" type="checkbox" class="form-control" lay-skin="tag" name="keyword[' . $COLUMN_NAME . ']" title="' . $COLUMN_COMMENT . '" >';

                // 生成验证规则
                $validateHtml .= <<<HTML
        <div class="col-md-12" style="">
            <div class="layui-inline">
                <input style="margin-left: 10px;" class="form-control" id="{$COLUMN_NAME}_key" onclick="copyKey('{$COLUMN_NAME}_key')" value="{$COLUMN_COMMENT}"> 
            </div>
            <div class="layui-inline" style="width: 500px;">
                <select name="validate[{$COLUMN_NAME}]" id="{$COLUMN_NAME}_key_select" class="form-control">
HTML;
                // 循环 $rule 生成下拉选项
                foreach ($rule as $key => $value) {
                    $validateHtml .= <<<HTML
            <option value="{$key}">{$value}</option>
HTML;
                }
                $validateHtml .= <<<HTML
                </select>
            </div>
             <div class="layui-inline">
               <input style="margin-left: 10px;width: 400px;" id="{$COLUMN_NAME}_key_value" type="text" class="form-control"  name="validate_text[{$COLUMN_NAME}]">
            </div>
        </div>
HTML;
            }
        }


        $result['html']         = $html;
        $result['validateHtml'] = $validateHtml;


        $sql               = 'SELECT * FROM ';
        $sql               .= 'INFORMATION_SCHEMA.TABLES ';
        $sql               .= 'WHERE ';
        $sql               .= "table_name = '{$tableName}'  AND table_schema = '{$db}'";
        $table_result      = Db::query($sql);
        $table_result_name = $table_result[0]['TABLE_COMMENT'];
        $result['tags']    = $table_result_name;


        $this->success('list', '', $result);
    }


    /**
     * 获取数据库表的列表
     */
    public function get_table_name_list()
    {
        //获取当前数据库列表
        $sql     = "show tables";
        $re      = Db::query($sql);
        $db      = config('database.connections.mysql.database');
        $db_list = [];
        $this->i = 0;
        foreach ($re as $k => $v) {
            $this->i++;
            $db_item                         = array_values($v);
            $table_name                      = substr($db_item[0], 4);//去掉前缀
            $db_list[$this->i]['table_name'] = $table_name;
            $tableName                       = $db_item[0];
            $sql                             = 'SELECT * FROM ';
            $sql                             .= 'INFORMATION_SCHEMA.TABLES ';
            $sql                             .= 'WHERE ';
            $sql                             .= "table_name = '{$tableName}'  AND table_schema = '{$db}'";
            $table_result                    = Db::query($sql);
            $TABLE_COMMENT                   = $table_result[0]['TABLE_COMMENT'];
            if (empty($TABLE_COMMENT)) $TABLE_COMMENT = $table_name;
            $db_list[$this->i]['table_remark'] = $TABLE_COMMENT;//获取注释
        }

        $this->success('list', '', $db_list);
    }

    /**
     * 列表
     */
    public function index()
    {
        $tree     = new Tree();
        $parentId = $this->request->param("parent_id", 0, 'intval');
        $result   = AdminMenuModel::order(["list_order" => "ASC"])->select()->toArray();
        $array    = [];
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $parentId ? 'selected' : '';
            $array[]       = $r;
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $selectCategory = $tree->getTree(0, $str);


        $this->assign("select_category", $selectCategory);

        return $this->fetch('/setting');
    }


    /**
     * 设置
     */
    public function setting()
    {
        //获取当前数据库列表
        $sql     = "show tables";
        $re      = Db::query($sql);
        $db      = config('database.connections.mysql.database');
        $db_list = [];
        $this->i = 0;
        foreach ($re as $k => $v) {
            $this->i++;
            $db_item                         = array_values($v);
            $table_name                      = substr($db_item[0], 4);//去掉前缀
            $db_list[$this->i]['table_name'] = $table_name;
            $tableName                       = $db_item[0];
            $sql                             = 'SELECT * FROM ';
            $sql                             .= 'INFORMATION_SCHEMA.TABLES ';
            $sql                             .= 'WHERE ';
            $sql                             .= "table_name = '{$tableName}'  AND table_schema = '{$db}'";
            $table_result                    = Db::query($sql);
            $TABLE_COMMENT                   = $table_result[0]['TABLE_COMMENT'];
            if (empty($TABLE_COMMENT)) $TABLE_COMMENT = $table_name;
            $db_list[$this->i]['table_remark'] = $TABLE_COMMENT;//获取注释
        }


        //获取左侧菜单栏
        $tree     = new Tree();
        $parentId = $this->request->param("parent_id", 0, 'intval');
        $result   = AdminMenuModel::order(["list_order" => "ASC"])->select()->toArray();
        $array    = [];
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $parentId ? 'selected' : '';
            $array[]       = $r;
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $selectCategory = $tree->getTree(0, $str);
        $this->assign("select_category", $selectCategory);
        $this->assign("db_list", $db_list);

        return $this->fetch('/setting');
    }


    /**
     * 提交数据
     * @return void
     * @throws \Exception
     */
    public function settingPost()
    {
        $parm = $this->request->param();

        //读取域名
        $domain_name_url   = cmf_config('domain_name') . '/plugin/form/AdminIndex/settingPost';//线上域名
        $local_domain_name = cmf_config('local_domain_name') . '/plugin/form/AdminIndex/settingPost';//本地域名
        $http_type         = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $domain_url        = $http_type . $_SERVER['SERVER_NAME'];

        //回调url地址
        if ($domain_url == cmf_config('local_domain_name')) {
            $visit_url = $domain_name_url;//本地访问线上
        } else {
            $visit_url = $local_domain_name;//线上访问本地
        }

        //存入数据库
        if (isset($parm['model_name']) && $parm['model_name']) Db::name('base_form_model')->insert(['json_params' => serialize($parm), 'type' => 1]);
        $json_params = Db::name('base_form_model')->where('type', '=', 1)->order('id desc')->find();
        $parm_new    = unserialize($json_params['json_params']);
        unset($parm);
        $parm = $parm_new;


        if (!$parm['model_name']) $this->error('请输入 controller/model 名字');


        $keyword = '';//关键字搜索
        if (!empty($parm['keyword']) && $parm['keyword']) {
            foreach ($parm['keyword'] as $k => $v) {
                $keyword .= $k . '|';
            }
            $keyword = rtrim($keyword, '|');
        }

        $AdminMenuParams               = $this->request->param();
        $AdminMenuParams['controller'] = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $parm['model_name']));


        //生成菜单
        if (!empty($AdminMenuParams['name']) && !empty($AdminMenuParams['app']) && isset($AdminMenuParams['admin_menu']) && !empty($AdminMenuParams['admin_menu'])) {
            $app        = $this->request->param("app");
            $controller = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $parm['model_name']));
            $menuName   = $this->request->param("name");
            $parent_id  = AdminMenuModel::strict(false)->field(true)->insert($AdminMenuParams, true);

            //处理index权限
            $action            = $this->request->param("action");
            $param             = $this->request->param("param");
            $authRuleName      = "$app/$controller/$action";
            $findAuthRuleCount = AuthRuleModel::where([
                'app'  => $app,
                'name' => $authRuleName,
                'type' => 'admin_url',
            ])->count();
            if (empty($findAuthRuleCount)) {
                AuthRuleModel::insert([
                    "name"  => $authRuleName,
                    "app"   => $app,
                    "type"  => "admin_url", //type 1-admin rule;2-user rule
                    "title" => $menuName,
                    'param' => $param,
                ]);
            }


            $menu['parent_id']  = $parent_id;
            $menu['app']        = $AdminMenuParams['app'];
            $menu['controller'] = $AdminMenuParams['controller'];
            $menu['action']     = 'edit';
            $menu['name']       = '编辑';
            if (!empty($AdminMenuParams['index_edit'])) {
                $AdminMenuCount = AdminMenuModel::where([
                    'app'        => $AdminMenuParams['app'],
                    'controller' => $AdminMenuParams['controller'],
                    'action'     => $menu['action'],
                ])->count();
                if (empty($AdminMenuCount)) {
                    AdminMenuModel::strict(false)->insert($menu);
                }


                $authRuleName      = "$app/$controller/edit";
                $findAuthRuleCount = AuthRuleModel::where([
                    'app'  => $app,
                    'name' => $authRuleName,
                    'type' => 'admin_url',
                ])->count();
                if (empty($findAuthRuleCount)) {
                    AuthRuleModel::insert([
                        "name"  => $authRuleName,
                        "app"   => $app,
                        "type"  => "admin_url", //type 1-admin rule;2-user rule
                        "title" => $menuName . '-' . $menu['name'],
                    ]);
                }
            }


            $menu['action'] = 'add';
            $menu['name']   = '添加';
            if (!empty($AdminMenuParams['index_add'])) {
                $AdminMenuCount = AdminMenuModel::where([
                    'app'        => $AdminMenuParams['app'],
                    'controller' => $AdminMenuParams['controller'],
                    'action'     => $menu['action'],
                ])->count();
                if (empty($AdminMenuCount)) {
                    AdminMenuModel::strict(false)->insert($menu);
                }


                $authRuleName      = "$app/$controller/add";
                $findAuthRuleCount = AuthRuleModel::where([
                    'app'  => $app,
                    'name' => $authRuleName,
                    'type' => 'admin_url',
                ])->count();
                if (empty($findAuthRuleCount)) {
                    AuthRuleModel::insert([
                        "name"  => $authRuleName,
                        "app"   => $app,
                        "type"  => "admin_url", //type 1-admin rule;2-user rule
                        "title" => $menuName . '-' . $menu['name'],
                    ]);
                }
            }


            $menu['action'] = 'find';
            $menu['name']   = '查看';
            if (!empty($AdminMenuParams['index_find'])) {

                $AdminMenuCount = AdminMenuModel::where([
                    'app'        => $AdminMenuParams['app'],
                    'controller' => $AdminMenuParams['controller'],
                    'action'     => $menu['action'],
                ])->count();
                if (empty($AdminMenuCount)) {
                    AdminMenuModel::strict(false)->insert($menu);
                }


                $authRuleName      = "$app/$controller/find";
                $findAuthRuleCount = AuthRuleModel::where([
                    'app'  => $app,
                    'name' => $authRuleName,
                    'type' => 'admin_url',
                ])->count();
                if (empty($findAuthRuleCount)) {
                    AuthRuleModel::insert([
                        "name"  => $authRuleName,
                        "app"   => $app,
                        "type"  => "admin_url", //type 1-admin rule;2-user rule
                        "title" => $menuName . '-' . $menu['name'],
                    ]);
                }
            }


            $menu['action'] = 'delete';
            $menu['name']   = '删除';
            if (!empty($AdminMenuParams['index_delete']) || !empty($AdminMenuParams['index_delete_s'])) {
                $AdminMenuCount = AdminMenuModel::where([
                    'app'        => $AdminMenuParams['app'],
                    'controller' => $AdminMenuParams['controller'],
                    'action'     => $menu['action'],
                ])->count();
                if (empty($AdminMenuCount)) {
                    AdminMenuModel::strict(false)->insert($menu);
                }


                $authRuleName      = "$app/$controller/delete";
                $findAuthRuleCount = AuthRuleModel::where([
                    'app'  => $app,
                    'name' => $authRuleName,
                    'type' => 'admin_url',
                ])->count();
                if (empty($findAuthRuleCount)) {
                    AuthRuleModel::insert([
                        "name"  => $authRuleName,
                        "app"   => $app,
                        "type"  => "admin_url", //type 1-admin rule;2-user rule
                        "title" => $menuName . '-' . $menu['name'],
                    ]);
                }
            }


            $menu['action'] = 'recommend_post';
            $menu['name']   = '推荐';
            if (!empty($AdminMenuParams['index_recommend'])) {
                $AdminMenuCount = AdminMenuModel::where([
                    'app'        => $AdminMenuParams['app'],
                    'controller' => $AdminMenuParams['controller'],
                    'action'     => $menu['action'],
                ])->count();
                if (empty($AdminMenuCount)) {
                    AdminMenuModel::strict(false)->insert($menu);
                }

                $authRuleName      = "$app/$controller/recommend_post";
                $findAuthRuleCount = AuthRuleModel::where([
                    'app'  => $app,
                    'name' => $authRuleName,
                    'type' => 'admin_url',
                ])->count();
                if (empty($findAuthRuleCount)) {
                    AuthRuleModel::insert([
                        "name"  => $authRuleName,
                        "app"   => $app,
                        "type"  => "admin_url", //type 1-admin rule;2-user rule
                        "title" => $menuName . '-' . $menu['name'],
                    ]);
                }
            }

            $menu['action'] = 'list_order_post';
            $menu['name']   = '排序';
            if (!empty($AdminMenuParams['index_list_order'])) {
                $AdminMenuCount = AdminMenuModel::where([
                    'app'        => $AdminMenuParams['app'],
                    'controller' => $AdminMenuParams['controller'],
                    'action'     => $menu['action'],
                ])->count();
                if (empty($AdminMenuCount)) {
                    AdminMenuModel::strict(false)->insert($menu);
                }


                $authRuleName      = "$app/$controller/list_order_post";
                $findAuthRuleCount = AuthRuleModel::where([
                    'app'  => $app,
                    'name' => $authRuleName,
                    'type' => 'admin_url',
                ])->count();
                if (empty($findAuthRuleCount)) {
                    AuthRuleModel::insert([
                        "name"  => $authRuleName,
                        "app"   => $app,
                        "type"  => "admin_url", //type 1-admin rule;2-user rule
                        "title" => $menuName . '-' . $menu['name'],
                    ]);
                }
            }


            $this->_exportAppMenuDefaultLang();
        }


        //规整一个input框中
        $parm['controllers']    = $parm['model_name'];
        $parm['root_menu_name'] = $parm['model_name'];
        $parm['menu']           = $parm['model_name'];
        $parm['menu_parent']    = 'default';


        //查询表备注名字
        $table_name        = env('DATABASE_PREFIX') . $parm['table_name'];
        $database          = env('DATABASE.DATABASE');
        $sql               = 'SELECT * FROM ';
        $sql               .= 'INFORMATION_SCHEMA.TABLES ';
        $sql               .= 'WHERE ';
        $sql               .= "table_name = '{$table_name}'  AND table_schema = '{$database}'";
        $table_result      = Db::query($sql);
        $table_result_name = $table_result[0]['TABLE_COMMENT'];

        if (!empty($parm['tags'])) $table_result_name = $parm['tags'];


        $controllers = $parm['controllers'];

        // 得到表中的所有字段
        $fields = $this->model->getField($parm['table_name']);
        if (count($fields) < 2) $this->error("获取表中的字段少于2");


        $parm['model_name']    = $parm['model_name'] . 'Model';//model
        $parm['validate_name'] = $parm['model_name'] . 'Validate';//validate


        //首页开关 对应js
        $index_checkbox_js = '';
        $is_show           = false;
        $is_index          = false;
        $is_class_id       = false;
        $is_status         = false;
        $is_type           = false;


        foreach ($fields as $key => &$val) {
            $field        = '{$vo.' . $val["COLUMN_NAME"] . '}';
            $val['field'] = $field;
            if (empty($val['COLUMN_COMMENT'])) {
                $val['annotation_field'] = '<!--' . $this->translate($val['COLUMN_NAME']) . '-->';//注释
            } else {
                $val['annotation_field'] = '<!--' . $val["COLUMN_COMMENT"] . '-->';//注释
            }


            $val['type'] = $this->get_label($val);//声明类型
            if (!in_array($val['type'], ['user_id', 'image', 'file', 'video', 'is'])) {
                $val['annotation_field_index'] = $val['annotation_field'];
            }

            //特殊处理类型
            if ($val['COLUMN_NAME'] == 'type') {
                $is_type     = true;
                $val['type'] = 'type';
            }


            //默认备注信息
            $val['comment_name'] = $val['COLUMN_COMMENT'];
            //Api参数名称
            $val['ApiParameterName'] = $val['COLUMN_COMMENT'];


            //备注信息,后面生成信息使用
            $val['comment_name_remark'] = $val['COLUMN_COMMENT'];
            if ($val['DATA_TYPE'] == 'tinyint') {
                $comment_name_remark        = $this->getParams($val['COLUMN_COMMENT'], ':')[0];
                $val['COLUMN_COMMENT']      = $comment_name_remark;
                $val['comment_name_remark'] = $comment_name_remark;
            }


            //default
            $val['value'] = "{\${$val['COLUMN_NAME']}|default=''}";

            //编辑页面
            $val['edit_html'] = '';
            if ($val['COLUMN_NAME'] == 'type') $val['edit_html'] = $this->get_edit_html('type', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'image') $val['edit_html'] = $this->get_edit_html('image', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'images') $val['edit_html'] = $this->get_edit_html('images', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'video') $val['edit_html'] = $this->get_edit_html('video', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'file') $val['edit_html'] = $this->get_edit_html('file', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'class') $val['edit_html'] = $this->get_edit_html('class', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'content') {
                $content_html     = $this->get_edit_html('content', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
                $val['html']      = $content_html;
                $val['edit_html'] = $content_html;
            }


            //index页面
            $val['index_html'] = '';
            //其他通用类型
            if ($val['type'] != 'file' && $val['type'] != 'video' && $val['type'] != 'image') {
                $val['index_html'] = $this->get_index_html('rests', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            }
            if ($val['type'] == 'image') $val['index_html'] = $this->get_index_html('image', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'video') $val['index_html'] = $this->get_index_html('video', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'file') $val['index_html'] = $this->get_index_html('file', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'user_id') $val['index_html'] = $this->get_index_html('user_id', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'is') $val['index_html'] = $this->get_index_html('is', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'is') $index_checkbox_js .= $this->checkbox_js($val['COLUMN_NAME'], $val['COLUMN_COMMENT']);


            //find详情页面
            $val['find_html'] = '';
            //其他通用类型
            if ($val['type'] != 'file' && $val['type'] != 'video' && $val['type'] != 'image') {
                $val['find_html'] = $this->get_find_html('rests', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            }
            if ($val['type'] == 'image') $val['find_html'] = $this->get_find_html('image', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'images') $val['find_html'] = $this->get_find_html('images', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'video') $val['find_html'] = $this->get_find_html('video', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'file') $val['find_html'] = $this->get_find_html('file', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);
            if ($val['type'] == 'user_id') $val['find_html'] = $this->get_find_html('user_id', $val, "{\${$val['COLUMN_NAME']}}", $key, $val['COLUMN_NAME']);


            //处理时间字段
            //if ($val['COLUMN_NAME'] == 'create_time') $val['index_html'] = "{\$vo.{$val['COLUMN_NAME']}|date='Y-m-d H:i:s'}";

            // {:isset($vo.receive_time) && $vo.receive_time > 10000 ? date('Y-m-d H:i:s', $vo.receive_time) : ''}
            if (strpos($val['COLUMN_NAME'], '_time') !== false) {
                //index
                if (in_array($val['COLUMN_NAME'], ['start_time', 'begin_time', 'end_time', 'stop_time', 'closing_time'])) {
                    $str_date = "{:isset(\$vo.{$val['COLUMN_NAME']}) && \$vo.{$val['COLUMN_NAME']} > 10000 ? date('Y-m-d', \$vo.{$val['COLUMN_NAME']}) : ''}";
                } else {
                    $str_date = "{:isset(\$vo.{$val['COLUMN_NAME']}) && \$vo.{$val['COLUMN_NAME']} > 10000 ? date('Y-m-d H:i:s', \$vo.{$val['COLUMN_NAME']}) : ''}";
                }
                $val['index_html'] = $str_date;

                //find
                $columnName = $val['COLUMN_NAME'];  // 获取字段名（如 "create_time"）
                if (in_array($val['COLUMN_NAME'], ['start_time', 'begin_time', 'end_time', 'stop_time', 'closing_time'])) {
                    $str_date2 = "{:isset(\$$columnName) && \$$columnName > 10000 ? date('Y-m-d', \$$columnName) : ''}";
                } else {
                    $str_date2 = "{:isset(\$$columnName) && \$$columnName > 10000 ? date('Y-m-d H:i:s', \$$columnName) : ''}";
                }
                $val['find_html'] = $str_date2;

                //edit
                if (in_array($val['COLUMN_NAME'], ['start_time', 'begin_time', 'end_time', 'stop_time', 'closing_time'])) {
                    $str_date3 = "{:isset(\$$columnName) && \$$columnName > 10000 ? date('Y-m-d', \$$columnName) : ''}";
                } else {
                    $str_date3 = "{:isset(\$$columnName) && \$$columnName > 10000 ? date('Y-m-d H:i', \$$columnName) : ''}";
                }

                if (in_array($val['COLUMN_NAME'], ['start_time', 'begin_time', 'end_time', 'stop_time', 'closing_time'])) {
                    $val['edit_html'] = <<<HTML
                    <input class="form-control post-params item-left js-date" name="{$columnName}" required
                           type="text"
                           value="{$str_date3}">
 
HTML;
                } else {
                    $val['edit_html'] = <<<HTML
                    <input class="form-control post-params item-left js-datetime" name="{$columnName}" required
                           type="text"
                           value="{$str_date3}">
 
HTML;
                }
                $val['type'] = 'date';
            }

            //特殊处理
            if (in_array($val['COLUMN_NAME'], ['id', 'user_id', 'create_time', 'status'])) {
                $val['COLUMN_COMMENT'] = $val['COLUMN_NAME'];//字段名为注释
                if ($val['COLUMN_NAME'] == 'id') {
                    $val['COLUMN_COMMENT']   = 'ID';
                    $val['ApiParameterName'] = 'ID';
                }
                if ($val['COLUMN_NAME'] == 'user_id') {
                    $val['COLUMN_COMMENT']   = '用户信息';
                    $val['ApiParameterName'] = '用户信息';
                }
                if ($val['COLUMN_NAME'] == 'create_time') {
                    $val['COLUMN_COMMENT']   = '创建时间';
                    $val['ApiParameterName'] = '创建时间';
                }
                if ($val['COLUMN_NAME'] == 'status') {
                    $val['COLUMN_COMMENT'] = '状态';
                }
            }

            //如果字段备注为空
            if (empty($val['COLUMN_COMMENT'])) {
                if ($val['COLUMN_NAME'] == 'name') {
                    $val['COLUMN_COMMENT']   = '名称';
                    $val['ApiParameterName'] = '名称';
                } else {
                    $val['COLUMN_COMMENT']   = $this->translate($val['COLUMN_NAME']);
                    $val['ApiParameterName'] = $val['COLUMN_COMMENT'];
                }
            }

            if ($val['COLUMN_NAME'] == 'is_show') $is_show = true;
            if ($val['COLUMN_NAME'] == 'is_index') $is_index = true;
            if ($val['COLUMN_NAME'] == 'class_id') $is_class_id = true;
            if ($val['COLUMN_NAME'] == 'status') $is_status = true;
        }
        //foreach 循环字段结束
        //dump($fields);exit();


        //如果需要数字转文字,  处理   字段名_name文字格式
        $init_field_value        = $this->get_init_field_name($fields);
        $parm['init_field']      = $init_field_value['init_field'];
        $parm['init_field_name'] = $init_field_value['init_field_name'];


        //api文件
        if (isset($parm['api_controller']) && $parm['api_controller']) {
            $controller_content = $this->ApiControllerViews($parm, $controllers, $fields, $table_result_name, $keyword, $is_show, $is_index, $is_class_id, $is_status, $is_type);//api控制器
            $this->model->template_build(CMF_ROOT . "/api/wxapp/controller/" . ucfirst($parm['controllers']) . 'Controller.php', $controller_content);
        }


        //api模型 && 先关掉
        //        if (isset($parm['api_model']) && $parm['api_model']) {
        //            $model_content = $this->ApiModelViews($parm, $controllers, $fields, $table_result_name);//api模型
        //            $this->model->template_build(CMF_ROOT . "/api/wxapp/model/" . ucfirst($parm['model_name']) . '.php', $model_content);
        //        }


        //后台控制器
        if (isset($parm['admin_controller']) && $parm['admin_controller']) {
            $controller_content = $this->AdminControllerViews($parm, $controllers, $fields, $keyword, $table_result_name, $is_class_id, $is_type);
            $this->model->template_build(APP_PATH . "/admin/controller/" . ucfirst($parm['controllers']) . 'Controller.php', $controller_content);
        }


        //后台模型 && 先关掉
        //        if (isset($parm['admin_model']) && $parm['admin_model']) {
        //            $model_content = $this->AdminModelViews($parm, $controllers, $fields, $table_result_name);
        //            $this->model->template_build(APP_PATH . "/admin/model/" . ucfirst($parm['model_name']) . '.php', $model_content);
        //        }


        //validate 验证器 前端api
        $initmodel_content = $this->ValidateViews($parm, $table_result_name);
        $this->model->template_build(CMF_ROOT . "/api/wxapp/validate/" . ucfirst($parm['menu'] . 'Validate') . '.php', $initmodel_content);

        //validate 验证器 后端admin
        $initmodel_content = $this->AdminValidateViews($parm, $table_result_name);
        $this->model->template_build(CMF_ROOT . "/app/admin/validate/" . ucfirst($parm['menu'] . 'Validate') . '.php', $initmodel_content);


        //init 公共模型Model
        $initmodel_content = $this->InitModelViews($parm, $controllers, $fields, $table_result_name, $is_class_id);
        $this->model->template_build(CMF_ROOT . "/extend/initmodel/" . ucfirst($parm['model_name']) . '.php', $initmodel_content);


        //init  公共 Controller
        if (isset($controllers) && $controllers) {
            $controller_content = $this->InitViews($controllers, $parm, $table_result_name, $fields, $is_class_id, $is_type);
            $this->model->template_build(CMF_ROOT . "/extend/init/" . ucfirst($parm['controllers']) . 'Init.php', $controller_content);
        }


        //后台视图
        if (isset($parm['admin_view']) && $parm['admin_view']) {
            $this->model->template_View($parm,
                $this->indexViews($parm, $AdminMenuParams['controller'], $fields, $table_result_name, $index_checkbox_js),
                $this->addViews($parm, $AdminMenuParams['controller'], $fields, $table_result_name),
                $this->editViews($parm, $AdminMenuParams['controller'], $fields, $table_result_name),
                $this->formViews($parm, $controllers, $fields),
                $this->findViews($parm, $controllers, $fields));
        }


        $remark = "表名:{$parm['table_name']}  -------  控制器(tags):{$parm['tags']}";
        Db::name('base_form_model')->insert(['json_params' => $visit_url, 'type' => 2, 'remark' => $remark]);

        $this->error("操作成功数据保存");
    }


    /**
     * 翻译
     * @param string $value 转入翻译内容
     * @return mixed
     *                      https://kf104.aulod.com/plugin/form/AdminIndex/translate?value=你好
     */
    public function translate($value = 'undefined')
    {
        $translate = new \init\TranslateInit();
        $result    = $translate->translate($value, 'en', 'zh');

        if (isset($result['trans_result']) && !empty($result['trans_result'])) return $result['trans_result'][0]['dst'];
        if (empty($result['trans_result'])) return $value;
    }


    //获取url
    public function get_url()
    {
        $res = Db::name('base_form_model')->where('type', '=', 2)->order('id desc')->find();
        $this->success("请求成功", '', $res);
    }


    /**
     * 导出后台菜单语言包
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _exportAppMenuDefaultLang()
    {
        $menus         = AdminMenuModel::order(["app" => "ASC", "controller" => "ASC", "action" => "ASC"])->select();
        $langDir       = cmf_current_lang();
        $adminMenuLang = CMF_DATA . "lang/" . $langDir . "/admin_menu.php";

        if (!empty($adminMenuLang) && !file_exists_case($adminMenuLang)) {
            mkdir(dirname($adminMenuLang), 0777, true);
        }

        $lang = [];

        foreach ($menus as $menu) {
            $lang_key        = strtoupper($menu['app'] . '_' . $menu['controller'] . '_' . $menu['action']);
            $lang[$lang_key] = $menu['name'];
        }

        $langStr = var_export($lang, true);
        $langStr = preg_replace("/\s+\d+\s=>\s(\n|\r)/", "\n", $langStr);

        if (!empty($adminMenuLang)) {
            file_put_contents($adminMenuLang, "<?php\nreturn $langStr;");
        }
    }


    /**
     * 获取字段 数字对应文字  1=未支付
     * @param $fields
     */
    public function get_init_field_name($fields)
    {
        $init_field      = '';
        $init_field_name = "";
        foreach ($fields as $key => $val) {
            if ($val['DATA_TYPE'] == 'tinyint') {
                $comment_name = $this->getParams($val['comment_name'], ':');
                if (!isset($comment_name[1]) || empty($comment_name[1])) break; // 退出循环
                $comment_name_remark = $this->getParams($comment_name[1]);
                //处理数组值
                $temporary_value = '';
                foreach ($comment_name_remark as $k => $v) {
                    $v_key           = (int)$v;
                    $v_value         = preg_replace('/[0-9]/', '', $v);
                    $temporary_value .= "$v_key=>'$v_value',";
                }
                $temporary_value = rtrim($temporary_value, ",");
                $init_field      .= "public $" . "{$val['COLUMN_NAME']} =[{$temporary_value}];//$comment_name[0] \n";
                $init_field_name .= '$item' . "['{$val['COLUMN_NAME']}_name']=" . '$this->' . "{$val['COLUMN_NAME']}" . '[$item' . "['{$val['COLUMN_NAME']}']];//$comment_name[0] \n";
            }
        }


        return ['init_field' => $init_field, 'init_field_name' => $init_field_name];
    }

    /**
     * 生成代码 form
     * @param $type         类型 images
     * @param $field        字段信息
     * @param $fields_value 模板展示文件
     * @param $i            下标
     * @param $f            名字去除{}
     * @return void
     */
    public function get_edit_html($type, $field, $fields_value, $i, $f)
    {
        $result = '';


        if ($type == 'images') {
            $i      = "0000000{$i}";
            $f_i    = '$' . $f;
            $result .= '<div class="help-block text-danger">长按图片可上下拖动,调整图片顺序</div>' . "\n";
            $result .= "<if condition=\"!empty($f_i)\">" . "\n";
            $result .= '<ul id="imagesId' . $i . '" class="pic-list list-unstyled form-inline">' . "\n";
            $result .= '<foreach name="$' . $f . '" item="vo">' . "\n";
            $result .= '<li id="savedImages-' . $i . '-{$key}" style="margin-bottom: 5px;">' . "\n";
            $result .= '<input id="photoImages-' . $i . '-{$key}" class="form-control post-params item-left" type="hidden" name="' . $f . '[{$key}]" value="{$vo}">' . "\n";
            $result .= '<img id="photoImages-' . $i . '-{$key}-preview" src="{:cmf_get_image_preview_url($vo)}" height="50" onclick="parent.imagePreviewDialog(this.src);">' . "\n";
            $result .= '<a href="javascript:uploadOneImage(\'图片上传\',\'#photoImages-' . $i . '-{$key}\');">替换</a>' . "\n";
            $result .= '<a href="javascript:(function(){$(\'#savedImages-' . $i . '-{$key}\').remove();})();">移除</a>' . "\n";
            $result .= '</li>' . "\n";
            $result .= '</foreach>' . "\n";
            $result .= '</ul>' . "\n";
            $result .= '<else/>' . "\n";
            $result .= '<ul id="imagesId' . $i . '" class="pic-list list-unstyled form-inline">' . "\n";
            $result .= '<li id="savedImages-' . $i . '" style="margin-bottom: 5px;">' . "\n";
            $result .= '</li>' . "\n";
            $result .= '</ul>' . "\n";
            $result .= '</if>' . "\n";
            $result .= '<a href="javascript:uploadMultiImage(\'图片上传\',\'#imagesId' . $i . '\',\'itemImages-' . $i . '\');" class="btn btn-sm btn-default">选择图片</a>' . "\n";
            $result .= '<script type="text/html" id="itemImages-' . $i . '">' . "\n";
            $result .= '<li id="savedImages-' . $i . '-{id}" style="margin-bottom: 5px;">' . "\n";
            $result .= '<input id="photoImages-' . $i . '-{id}" class="form-control post-params item-left" type="hidden" name="' . $f . '[{id}]" value="{filepath}">' . "\n";
            $result .= '<img id="photoImages-' . $i . '-{id}-preview" src="{url}" height="50" onclick="imagePreviewDialog(this.src);">' . "\n";
            $result .= '<a href="javascript:uploadOneImage(\'图片上传\',\'#photoImages-' . $i . '-{id}\');">替换</a>' . "\n";
            $result .= '<a href="javascript:(function(){$(\'#savedImages-' . $i . '-{id}\').remove();})();">移除</a>' . "\n";
            $result .= '</li>' . "\n";
            $result .= '</script>' . "\n";
            $result .= '<script>' . "\n";
            $result .= '// 多图上传支持排序' . "\n";
            $result .= 'new Sortable(imagesId' . $i . ', {' . "\n";
            $result .= 'animation: 150,' . "\n";
            $result .= 'ghostClass: \'blue-background-class\'' . "\n";
            $result .= '});' . "\n";
            $result .= '</script>' . "\n";
        }


        if ($type == 'video') {
            $f_i    = '$' . $f;
            $result .= '<input type="text" class="form-control post-params item-left"  name="' . $f . '"  id="video-000000' . $i . '"  
                         readonly="readonly"  value="{' . $f_i . '}">' . "\n";
            $result .= "<if condition='!empty($f_i)'>" . "\n";
            $result .= '<a id="video-000000' . $i . '-preview" href="{:cmf_get_file_download_url(' . $f_i . ')}"
										target="_blank">下载</a>' . "\n";
            $result .= '</if>' . "\n";
            $result .= '<a href="javascript:uploadOne(' . "'视频上传'" . ',' . "'#video-000000$i'" . ',' . "'video'" . ');">上传</a>' . "\n";
            $result .= '<a onclick="' . "$('#video-000000$i').val('');return false;" . '">取消</a>' . "\n";
        }

        if ($type == 'file') {
            $f_i    = '$' . $f;
            $result .= '<input type="text" class="form-control post-params item-left" name="' . $f . '"  id="file-000000' . $i . '"  
                          readonly="readonly" value="{' . $f_i . '}">' . "\n";
            $result .= "<if condition='!empty($f_i)'>" . "\n";
            $result .= '<a id="file-000000' . $i . '-preview" href="{:cmf_get_file_download_url(' . $f_i . ')}"
										target="_blank">下载</a>' . "\n";
            $result .= '</if>' . "\n";
            $result .= '<a href="javascript:uploadOne(' . "'文件上传'" . ',' . "'#file-000000$i'" . ',' . "'file'" . ');">上传</a>' . "\n";
            $result .= '<a onclick="' . "$('#file-000000$i').val('');return false;" . '">取消</a>' . "\n";
        }


        if ($type == 'image') {
            $f_i    = '$' . $f;
            $f_i_a  = "'{:cmf_get_asset_url($f_i)}'";//a链接打开图片
            $result .= '<input type="hidden" class="form-control post-params item-left" name="' . $f . '"  id="photo-000000' . $i . '"  
                           value="{' . $f_i . '}">' . "\n";
            $result .= "<if condition='!empty($f_i)'>" . "\n";
            $result .= '<a href="javascript:imagePreviewDialog(' . $f_i_a . ');">' . "\n";
            $result .= "<img src='{:cmf_get_image_preview_url($f_i)}' id='photo-000000$i-preview' style='width: 85px;'>" . "\n";
            $result .= '</a>' . "\n";
            $result .= '<else/>' . "\n";
            $result .= "<img src='/static/images/default.png' id='photo-000000$i-preview' style='width: 85px;'>" . "\n";
            $result .= '</if>' . "\n";
            $result .= '<a href="javascript:uploadOneImage(' . "'图片上传'" . ',' . "'#photo-000000$i'" . ',' . "'image'" . ');">上传</a>' . "\n";
            $result .= '<a onclick="' . "$('#photo-000000$i-preview').attr('src','__TMPL__/public/assets/images/default-thumbnail.png');$('#photo-000000$i').val('');return false;" . '">取消图片</a>' . "\n";
        }


        if ($type == 'class') {
            $result .= "<if condition=\"!empty(\$" . $f . ")\">\n";
            $result .= '    <select class="form-control post-params" lay-search lay-filter="' . $f . '" name="' . $f . '">' . "\n";
            $result .= '        <foreach item="vo" key="key" name="class_list">' . "\n";
            $result .= '            <option value="{$vo.id}"' . "\n";
            $result .= '            <if condition="$' . $f . ' eq $vo.id ">selected</if>' . "\n";
            $result .= '            >{$vo.name}</option>' . "\n";
            $result .= '        </foreach>' . "\n";
            $result .= '    </select>' . "\n";
            $result .= '    <else/>' . "\n";
            $result .= '    <select class="form-control post-params" lay-search lay-filter="' . $f . '" name="' . $f . '">' . "\n";
            $result .= '        <foreach item="vo" key="key" name="class_list">' . "\n";
            $result .= '            <option value="{$vo.id}">{$vo.name}</option>' . "\n";
            $result .= '        </foreach>' . "\n";
            $result .= '    </select>' . "\n";
            $result .= '</if>' . "\n";
        }

        if ($type == 'type') {
            $result .= "<if condition=\"!empty(\$" . $f . ")\">\n";
            $result .= '    <select class="form-control post-params" lay-search lay-filter="' . $f . '" name="' . $f . '">' . "\n";
            $result .= '        <foreach item="vo" key="key" name="' . $f . '_list">' . "\n";
            $result .= '            <option value="{$key}"' . "\n";
            $result .= '            <if condition="$' . $f . ' eq $key ">selected</if>' . "\n";
            $result .= '            >{$vo}</option>' . "\n";
            $result .= '        </foreach>' . "\n";
            $result .= '    </select>' . "\n";
            $result .= '    <else/>' . "\n";
            $result .= '    <select class="form-control post-params" lay-search lay-filter="' . $f . '" name="' . $f . '">' . "\n";
            $result .= '        <foreach item="vo" key="key" name="' . $f . '_list">' . "\n";
            $result .= '            <option value="{$key}">{$vo}</option>' . "\n";
            $result .= '        </foreach>' . "\n";
            $result .= '    </select>' . "\n";
            $result .= '</if>' . "\n";
        }

        if ($type == 'content') {
            $f_i    = '$' . $f;
            $result .= '{:cmf_replace_content_file_url(htmlspecialchars_decode(' . $f_i . '))}';
        }


        return $result;
    }


    /**
     * 生成代码 index
     * @param $type         类型 images
     * @param $field        字段信息
     * @param $fields_value 模板展示文件
     * @param $i            下标
     * @param $f            名字去除{}
     * @return void
     */
    public function get_index_html($type, $field, $fields_value, $i, $f)
    {
        $result = '';
        $f_i    = '$vo.' . $f;

        if ($type == 'is') {
            $result .= "\n" . "<!--{$field['COLUMN_COMMENT']}-->";
            $result .= "<div class='layui-form'>" . "\n";
            $result .= "<input type='checkbox' lay-skin='switch' lay-text='是|否' lay-filter='$f' name='$f' value='{\$vo.id}'" . "\n";
            $result .= "<if condition='$f_i == 1'>checked</if>" . "\n";
            $result .= ">" . "\n";
            $result .= "</div>" . "\n";
        }


        if ($type == 'video') {
            $result .= "\n" . "<!--{$field['COLUMN_COMMENT']}-->";
            $result .= "<if condition='!empty($f_i)'>" . "\n";
            $result .= '<a href="{:cmf_get_file_download_url(' . $f_i . ')}"
										target="_blank">下载</a>' . "\n";
            $result .= '</a>' . "\n";
            $result .= '<else/>' . "\n";
            $result .= '暂无上传' . "\n";
            $result .= '</if>' . "\n";
        }

        if ($type == 'file') {
            $result .= "\n" . "<!--{$field['COLUMN_COMMENT']}-->";
            $result .= "<if condition='!empty($f_i)'>" . "\n";
            $result .= '<a href="{:cmf_get_file_download_url(' . $f_i . ')}"
										target="_blank">下载</a>' . "\n";
            $result .= '</a>' . "\n";
            $result .= '<else/>' . "\n";
            $result .= '暂无上传' . "\n";
            $result .= '</if>' . "\n";
        }

        if ($type == 'image') {
            $result    .= "\n" . "<!--{$field['COLUMN_COMMENT']}-->";
            $get_image = "'{:cmf_get_asset_url($f_i)}'";
            $result    .= "<if condition='!empty($f_i)'>" . "\n";
            $result    .= '<a href="javascript:imagePreviewDialog(' . $get_image . ');">' . "\n";
            $result    .= "<img src='{:cmf_get_asset_url($f_i)}' style='height:40px;'>" . "\n";
            $result    .= '</a>' . "\n";
            $result    .= '<else/>' . "\n";
            $result    .= '暂无上传' . "\n";
            $result    .= '</if>' . "\n";
        }


        if ($type == 'rests') {
            //如果字段类型为 tinyint那么会有一个  字段名_name 文本展示字段 替换一下
            if ($field['DATA_TYPE'] == 'tinyint') $f_i = $f_i . '_name';
            if ($field['COLUMN_NAME'] == 'class_id') $f_i = "\$vo.class_name";
            $result = '{' . $f_i . "|default=''" . '}';
        }


        if ($type == 'user_id') {
            $result       .= "\n" . "<!--{$field['COLUMN_COMMENT']}-->";
            $avatar_a     = "'" . '{:cmf_get_asset_url($vo.user_info.avatar)}' . "'";
            $avatar       = '$vo.user_info.avatar';
            $openid       = "'" . '{$vo.user_info.openid}' . "'";
            $nickname     = '{$vo.user_info.nickname}';
            $is_user_info = '$vo.user_info';
            $result       .= "<if condition='!empty($is_user_info)'>" . "\n";
            $result       .= '<div style="display: flex;align-items: center;">' . "\n";
            $result       .= '<a href="javascript:imagePreviewDialog(' . $avatar_a . ');">' . "\n";
            $result       .= "<img class='img-rounded' src='{:cmf_get_asset_url($avatar)}' style='height:40px;'>" . "\n";
            $result       .= '</a>' . "\n";
            $result       .= '<div style="margin-left: 6px;">' . "\n";
            $result       .= '<p ondblclick="copyContent(' . $openid . ')">' . $nickname . '({$vo.user_info.id})' . '</p>' . "\n";
            $result       .= '<p>' . '{$vo.user_info.phone}' . '</p>' . "\n";
            $result       .= '</div>' . "\n";
            $result       .= '</div>' . "\n";  // 这里闭合主div
            $result       .= '<else/>' . "\n";
            $result       .= '已删除' . "\n";
            $result       .= '</if>' . "\n";
        }


        return $result;
    }


    /**
     * 生成开关 js
     * @param $f            名字去除{}
     * @return void
     */
    public function checkbox_js($f, $field_name)
    {
        $result = '';
        $result .= "//{$field_name}" . "\n";
        $result .= "form.on('switch($f)', function (data) {" . "\n";
        $result .= "    batchPost(data.value, '$f', this.checked ? 1 : 2)" . "\n";
        $result .= "});" . "\n" . "\n" . "\n";

        return $result;
    }


    /**
     * 生成代码 find详情页
     * @param $type         类型 images
     * @param $field        字段信息
     * @param $fields_value 模板展示文件
     * @param $i            下标
     * @param $f            名字去除{}
     * @return void
     */
    public function get_find_html($type, $field, $fields_value, $i, $f)
    {
        $result = '';
        $f_i    = "$" . $f;

        if ($type == 'video') {
            $result .= "<if condition='!empty($f_i)'>" . "\n";
            $result .= '<a href="{:cmf_get_file_download_url(' . $f_i . ')}"
										target="_blank">下载</a>' . "\n";
            $result .= '</a>' . "\n";
            $result .= '<else/>' . "\n";
            $result .= '暂无上传' . "\n";
            $result .= '</if>' . "\n";
        }

        if ($type == 'file') {
            $result .= "<if condition='!empty($f_i)'>" . "\n";
            $result .= '<a href="{:cmf_get_file_download_url(' . $f_i . ')}"
										target="_blank">下载</a>' . "\n";
            $result .= '</a>' . "\n";
            $result .= '<else/>' . "\n";
            $result .= '暂无上传' . "\n";
            $result .= '</if>' . "\n";
        }

        if ($type == 'image') {
            $get_image = "'{:cmf_get_asset_url($f_i)}'";
            $result    .= "<if condition='!empty($f_i)'>" . "\n";
            $result    .= '<a href="javascript:imagePreviewDialog(' . $get_image . ');">' . "\n";
            $result    .= "<img src='{:cmf_get_asset_url($f_i)}' style='height:40px;'>" . "\n";
            $result    .= '</a>' . "\n";
            $result    .= '<else/>' . "\n";
            $result    .= '暂无上传' . "\n";
            $result    .= '</if>' . "\n";
        }


        if ($type == 'images') {
            $get_image = "'{:cmf_get_image_preview_url(\$value)}'";
            $result    .= "<if condition='!empty($f_i)'>" . "\n";
            $result    .= "<foreach name='$f_i' item='value'>" . "\n";
            $result    .= '<a href="javascript:imagePreviewDialog(' . $get_image . ');">' . "\n";
            $result    .= "<img src='{:cmf_get_image_preview_url(\$value)}' style='height:40px;'>" . "\n";
            $result    .= '</a>' . "\n";
            $result    .= '</foreach>' . "\n";
            $result    .= '</if>' . "\n";
        }

        if ($type == 'rests') {
            //如果字段类型为 tinyint那么会有一个  字段名_name 文本展示字段 替换一下
            if ($field['DATA_TYPE'] == 'tinyint') $f_i = $f_i . '_name';
            if ($field['COLUMN_NAME'] == 'class_id') $f_i = "\$class_name";
            $result = '{' . $f_i . "|default=''" . '}';
        }


        if ($type == 'user_id') {
            $avatar_a     = "'" . '{:cmf_get_asset_url($user_info.avatar)}' . "'";
            $avatar       = '$user_info.avatar';
            $openid       = "'" . '{$user_info.openid}' . "'";
            $nickname     = '{$user_info.nickname}';
            $is_user_info = '$user_info';
            $result       .= "<if condition='!empty($is_user_info)'>" . "\n";
            $result       .= '<div style="display: flex;align-items: center;">' . "\n";
            $result       .= '<a href="javascript:imagePreviewDialog(' . $avatar_a . ');">' . "\n";
            $result       .= "<img class='img-rounded' src='{:cmf_get_asset_url($avatar)}' style='height:40px;'>" . "\n";
            $result       .= '</a>' . "\n";
            $result       .= '<div style="margin-left: 6px;">' . "\n";
            $result       .= '<p ondblclick="copyContent(' . $openid . ')">' . $nickname . '({$user_info.id})' . '</p>' . "\n";
            $result       .= '<p>' . '{$user_info.phone}' . '</p>' . "\n";
            $result       .= '</div>' . "\n";
            $result       .= '</div>' . "\n";  // 这里闭合主div
            $result       .= '<else/>' . "\n";
            $result       .= '已删除' . "\n";
            $result       .= '</if>' . "\n";
        }

        return $result;
    }


    /**
     * 获取 对应标签
     * @param $fields
     * @return void
     */
    public function get_label($field)
    {
        $template = [
            'user_id',
            'password',
            'image',
            'images',
            'video',
            'file',
            'ids',
            'id',
            'is',
            'textarea',
            'date',
            'ext',
            'lnglat',
            'number',
            'class',
        ];


        if ($field['COLUMN_NAME'] == 'id') return 'number';


        foreach ($template as $k => $v) {
            if ($field['COLUMN_NAME'] == $v) {
                return $v;
            } elseif (str_contains($field['COLUMN_NAME'], 'is_')) {
                return 'is';
            } else {
                $name     = explode("_", $field['COLUMN_NAME']);
                $count    = count($name);
                $new_name = $name[$count - 1];
                if ($new_name == 'content' && $field['DATA_TYPE'] == 'text') return 'content';

                if ($v == $new_name) return $v;
            }

            //文本域
            if ($field['COLUMN_NAME'] == 'abstract' || $field['COLUMN_NAME'] == 'introduce') {
                return 'textarea';
            }

            //价格,数字格式
            if ($field['DATA_TYPE'] == 'decimal') return 'number';

            //分类
            if ($field['COLUMN_NAME'] == 'class_id') return 'class';
        }

        if (!isset($template_id) || empty($template_id)) {
            return 'text';
        }

    }


    /**
     * 验证器渲染代码
     * @param $parm
     * @param $table_result_name
     */
    public function ValidateViews($parm, $table_result_name)
    {
        $validate      = $parm['validate'];
        $validate_text = $parm['validate_text'];


        $rule    = 'protected $rule = [';//规则
        $message = 'protected $message = [';//提示信息
        foreach ($validate as $k => $v) {
            if ($v != null && $v != 'null' && $v != '' && $v) {
                $rule    .= "'{$k}'=>'{$v}',\n";
                $message .= "'{$k}.{$v}'=>'{$validate_text[$k]}',\n";
            }
        }
        $rule    .= "];\n\n\n";
        $message .= "];\n\n\n";

        $content = file_get_contents(CMF_ROOT . '/public/plugins/form/view/template/model/Validate.html');

        $root_menu_name = '';
        $date           = date('Y-m-d H:i:s');
        $Controller     = '$this->validate($params, ' . $parm['root_menu_name'] . ');';
        $name_underline = $this->toUnderScore($parm['root_menu_name']);//驼峰转下划线


        if ($parm['root_menu_name']) {
            $root_menu_name = <<<EOF
/**
    * @AdminModel(
    *     "name"             =>"{$parm['root_menu_name']}",
    *     "name_underline"   =>"{$name_underline}",
    *     "table_name"       =>"{$parm['table_name']}",
    *     "validate_name"    =>"{$parm['root_menu_name']}Validate",
    *     "remark"           =>"$table_result_name",
    *     "author"           =>"",
    *     "create_time"      =>"$date",
    *     "version"          =>"1.0",
    *     "use"              =>   $Controller
    * )
    */
EOF;
        }


        $content = str_replace(
            ['%ValidateName%', '%rule%', '%message%', '%root_menu_name%'],
            [$parm['root_menu_name'] . 'Validate', $rule, $message, $root_menu_name],
            $content
        );
        return $content;
    }


    /**
     * 验证器渲染代码
     * @param $parm
     * @param $table_result_name
     */
    public function AdminValidateViews($parm, $table_result_name)
    {
        $validate      = $parm['validate'];
        $validate_text = $parm['validate_text'];


        $rule    = 'protected $rule = [';//规则
        $message = 'protected $message = [';//提示信息
        foreach ($validate as $k => $v) {
            if ($v != null && $v != 'null' && $v != '' && $v) {
                $rule    .= "'{$k}'=>'{$v}',\n";
                $message .= "'{$k}.{$v}'=>'{$validate_text[$k]}',\n";
            }
        }
        $rule    .= "];\n\n\n";
        $message .= "];\n\n\n";

        $content = file_get_contents(CMF_ROOT . '/public/plugins/form/view/template/model/AdminValidate.html');

        $root_menu_name = '';
        $date           = date('Y-m-d H:i:s');
        $Controller     = '$this->validate($params, ' . $parm['root_menu_name'] . ');';
        $name_underline = $this->toUnderScore($parm['root_menu_name']);//驼峰转下划线


        if ($parm['root_menu_name']) {
            $root_menu_name = <<<EOF
/**
    * @AdminModel(
    *     "name"             =>"{$parm['root_menu_name']}",
    *     "name_underline"   =>"{$name_underline}",
    *     "table_name"       =>"{$parm['table_name']}",
    *     "validate_name"    =>"{$parm['root_menu_name']}Validate",
    *     "remark"           =>"$table_result_name",
    *     "author"           =>"",
    *     "create_time"      =>"$date",
    *     "version"          =>"1.0",
    *     "use"              =>   $Controller
    * )
    */
EOF;
        }


        $content = str_replace(
            ['%ValidateName%', '%rule%', '%message%', '%root_menu_name%'],
            [$parm['root_menu_name'] . 'Validate', $rule, $message, $root_menu_name],
            $content
        );
        return $content;
    }


    /**
     * 后台控制 渲染代码
     * @param $parm
     * @param $controllers
     * @param $fields
     * @param $keyword            搜索关键字
     * @param $table_result_name  表备注
     * @return array|false|string|string[]
     */
    public function AdminControllerViews($parm, $controllers, $fields, $keyword = 'id', $table_result_name, $is_class_id = false, $is_type = false)
    {
        $content      = file_get_contents(CMF_ROOT . 'public/plugins/form/view/template/controller/FooController.html');
        $InitName     = "{$controllers}Init";
        $InitModel    = "{$controllers}Model";
        $ValidateName = "'{$controllers}'";


        $root_menu_name = '';
        $base_edit      = '';//分类
        $type_model     = '';//类型
        $date           = date('Y-m-d H:i:s');
        $Controller     = "new \app\admin\controller\\" . ucfirst($parm['controllers']) . 'Controller();';
        $name_underline = $this->toUnderScore($parm['root_menu_name']);//驼峰转下划线


        //分类版块
        $controllers_name = ucfirst($controllers);
        if ($is_class_id) {
            $base_edit .= "\${$controllers_name}ClassInit=new \init\\{$controllers_name}ClassInit();//分类管理     (ps:InitController)\n ";
            $base_edit .= "\$class_map   = []; \n \$class_map[] = ['id','<>',0]; \n \$this->assign('class_list', \${$controllers_name}ClassInit->get_list(\$class_map));";
        }

        //类型
        if ($is_type) {
            $type_model .= "\${$controllers_name}Init=new \init\\{$controllers_name}Init();//{$parm['table_name']}     (ps:InitController)\n ";
            $type_model .= "\$this->assign('type_list', \${$controllers_name}Init->type);";
        }

        if ($parm['root_menu_name']) {
            $root_menu_name = <<<EOF
/**
    * @adminMenuRoot(
    *     "name"                =>"{$parm['root_menu_name']}",
    *     "name_underline"      =>"$name_underline",
    *     "controller_name"     =>"{$parm['controllers']}",
    *     "table_name"          =>"{$parm['table_name']}",
    *     "action"              =>"default",
    *     "parent"              =>"",
    *     "display"             => true,
    *     "order"               => 10000,
    *     "icon"                =>"none",
    *     "remark"              =>"$table_result_name",
    *     "author"              =>"",
    *     "create_time"         =>"$date",
    *     "version"             =>"1.0",
    *     "use"                 => $Controller
    * )
    */
EOF;
        }

        $get_list = '';

        //admin 是否开启分页
        if (isset($parm['admin_paginate']) && !empty($parm['admin_paginate'])) $get_list = 'get_list_paginate';
        if (!isset($parm['admin_paginate']) && empty($parm['admin_paginate'])) $get_list = 'get_list';

        $content = str_replace(
            ['%type_model%', '%base_edit%', '%InitName%', '%name_underline%', '%InitModel%', '%root_menu_name%', '%menu%', '%menu_parent%', '%controller_name%', '%model_name%', '%keyword%', '%table_result_name%', '%get_list%', '%ValidateName%'],
            [$type_model, $base_edit, $InitName, $name_underline, $InitModel, $root_menu_name, $parm['menu'], $parm['menu_parent'], $parm['controllers'], $parm['model_name'], $keyword, $table_result_name, $get_list, $ValidateName],
            $content
        );


        return $content;
    }


    /**
     * 后台模型 渲染代码
     * @param $parm
     * @param $controllers
     * @param $fields
     * @param $table_result_name 表备注
     * @return array|false|string|string[]
     */
    public function AdminModelViews($parm, $controllers, $fields, $table_result_name)
    {
        $content = file_get_contents(CMF_ROOT . '/public/plugins/form/view/template/model/Foo.html');


        $paginate = '';


        $root_menu_name = '';
        $date           = date('Y-m-d H:i:s');
        $Controller     = "new \app\admin\model\\" . ucfirst($parm['controllers']) . 'Model();';
        if ($parm['root_menu_name']) {
            $root_menu_name = <<<EOF
/**
    * @AdminModel(
    *     "name"             =>"{$parm['root_menu_name']}",
    *     "table_name"       =>"{$parm['table_name']}",
    *     "model_name"       =>"{$parm['model_name']}",
    *     "remark"           =>"$table_result_name",
    *     "author"           =>"",
    *     "create_time"      =>"$date",
    *     "version"          =>"1.0",
    *     "use"              => $Controller
    * )
    */
EOF;
        }


        $this->assign('model_name', $parm['model_name']);
        $this->assign('table_name', $parm['table_name']);


        $content = str_replace(
            ['%model_name%', '%table_name%', '%table_result_name%', '%paginate%', '%root_menu_name%'],
            [$parm['model_name'], $parm['table_name'], $table_result_name, $paginate, $root_menu_name],
            $content
        );
        return $content;
    }


    /**
     * 处理初始化文件
     * @param $controllers       控制器名称
     * @param $parm
     * @param $table_result_name 表名
     * @param $fields
     * @param $is_class_id       是否有分类
     * @param $is_type           是否有类型
     * @return array|false|string|string[]
     */
    public function InitViews($controllers, $parm, $table_result_name, $fields, $is_class_id = false, $is_type = false)
    {
        $content = file_get_contents(CMF_ROOT . '/public/plugins/form/view/template/model/Init.html');

        $class_model    = '';
        $class_name     = '';
        $date           = date('Y-m-d H:i:s');
        $InitController = "new \init\\" . ucfirst($parm['controllers']) . 'Init();';
        $InitModel      = "new \initmodel\\" . ucfirst($parm['controllers']) . 'Model();';

        //用于变量名字
        $InitName       = ucfirst($parm['controllers']) . 'Init';
        $ModelName      = ucfirst($parm['controllers']) . 'Model';
        $name_underline = $this->toUnderScore($parm['root_menu_name']);//驼峰转下划线

        //分类版块
        $controllers_name = ucfirst($controllers);
        if ($is_class_id) {
            $class_model = "\${$controllers_name}ClassModel=new \initmodel\\{$controllers_name}ClassModel();//分类管理   (ps:InitModel) \n";
            $class_name  = "//分类名称\n \$item['class_name'] = \${$controllers_name}ClassModel->where('id','=',\$item['class_id'])->value('name');\n";
        }


        $root_menu_name = '';
        if ($parm['root_menu_name']) {
            $root_menu_name = <<<EOF
/**
    * @Init(
    *     "name"            =>"{$parm['controllers']}",
    *     "name_underline"  =>"{$name_underline}",
    *     "table_name"      =>"{$parm['table_name']}",
    *     "model_name"      =>"{$parm['model_name']}",
    *     "remark"          =>"$table_result_name",
    *     "author"          =>"",
    *     "create_time"     =>"$date",
    *     "version"         =>"1.0",
    *     "use"             => $InitController
    * )
    */
EOF;
        }


        //处理数据源-ApiModel
        $find_images_list  = '';
        $find_images       = '';
        $find_image_list   = '';
        $find_image        = '';
        $find_content      = '';
        $edit_images       = '';
        $edit_time         = '';
        $find_video        = '';
        $find_file         = '';
        $member_model      = '';//会员model
        $user_info         = '';//用户信息
        $user_info_list    = '';
        $find_admin_images = '';//后台处理图片
        $order_field       = 'id desc';//排序字段

        $use = "each(function (\$item, \$key) use(\$params)";

        //过滤字段
        $filtration_array = ['id', 'create_time', 'update_time', 'delete_time'];


        foreach ($fields as $k => $v) {
            if (!in_array($v['COLUMN_NAME'], $filtration_array)) {
                $name = $v['COLUMN_NAME'];
                if ($name == 'list_order') $order_field = 'list_order,id desc';

                if ($v['type'] == 'images') {
                    //列表图片处理
                    $find_admin_images .= "if(\$item['{$name}']) \$item['{$name}']=\$this->getParams(\$item['{$name}']);//{$v['COLUMN_COMMENT']}\n";
                    $find_images       .= "if(\$item['{$name}']) \$item['{$name}']=\$this->getImagesUrl(\$item['{$name}']);//{$v['COLUMN_COMMENT']}\n";
                    $find_images_list  .= "if(\$item['{$name}']) \$item['{$name}']=\$this->getImagesUrl(\$item['{$name}']);//{$v['COLUMN_COMMENT']}\n";
                    $edit_images       .= "if(\$params['{$name}']) \$params['{$name}']=\$this->setParams(\$params['{$name}']);//{$v['COLUMN_COMMENT']}\n";
                }


                if ($v['type'] == 'image') {
                    //详情图片处理
                    $find_image      .= "if(\$item['{$name}']) \$item['{$name}']=cmf_get_asset_url(\$item['{$name}']);//{$v['COLUMN_COMMENT']}\n";
                    $find_image_list .= "if(\$item['{$name}']) \$item['{$name}']=cmf_get_asset_url(\$item['{$name}']);//{$v['COLUMN_COMMENT']}\n";
                }


                if ($v['type'] == 'video') $find_video .= "if(\$item['{$name}']) \$item['{$name}']=cmf_get_asset_url(\$item['{$name}']);//{$v['COLUMN_COMMENT']}\n";


                if ($v['type'] == 'file') $find_file .= "if(\$item['{$name}']) \$item['{$name}']=cmf_get_asset_url(\$item['{$name}']);//{$v['COLUMN_COMMENT']}\n";


                //富文本
                if ($v['type'] == 'content') $find_content .= "if(\$item['{$name}']) \$item['{$name}']=htmlspecialchars_decode(cmf_replace_content_file_url(\$item['{$name}']));//{$v['COLUMN_COMMENT']}\n";

                //时间
                if ($v['type'] == 'date') {
                    if (in_array($name, ['start_time', 'begin_time'])) {
                        $edit_time .= "if(\$params['{$name}'] && is_string(\$params['{$name}'])) \$params['{$name}']=(strlen(\$params['{$name}'])<=10)?strtotime(\$params['{$name}'].' 00:00:00'):strtotime(\$params['{$name}']);//{$v['COLUMN_COMMENT']}\n";
                    } elseif (in_array($name, ['end_time', 'stop_time', 'closing_time'])) {
                        $edit_time .= "if(\$params['{$name}'] && is_string(\$params['{$name}'])) \$params['{$name}']=(strlen(\$params['{$name}'])<=10)?strtotime(\$params['{$name}'].' 23:59:59'):strtotime(\$params['{$name}']);//{$v['COLUMN_COMMENT']}\n";
                    } else {
                        $edit_time .= "if(\$params['{$name}'] && is_string(\$params['{$name}'])) \$params['{$name}']=strtotime(\$params['{$name}']);//{$v['COLUMN_COMMENT']}\n";
                    }
                }


                if ($v['type'] == 'user_id') {
                    //用户信息
                    $member_model   = "\$MemberInit= new \init\MemberInit();//会员管理 (ps:InitController)";
                    $user_info      = "//查询用户信息\n \$user_info=\$MemberInit->get_find(['id'=>\$item['user_id']]);\n \$item['user_info']=\$user_info;";
                    $user_info_list = "//查询用户信息\n \$user_info=\$MemberInit->get_find(['id'=>\$item['user_id']]);\n \$item['user_info']=\$user_info;";
                }
            }
        }

        if ($edit_time) $edit_time = "//处理时间格式\n" . $edit_time;

        // 处理数据源-InitModel
        $replacePairs = [
            '%OrderField%'        => $order_field,
            '%InitName%'          => $InitName,
            '%use%'               => $use,
            '%member_model%'      => $member_model,
            '%user_info%'         => $user_info,
            '%user_info_list%'    => $user_info_list,
            '%InitModel%'         => $InitModel,
            '%root_menu_name%'    => $root_menu_name,
            '%find_images_list%'  => $find_images_list,
            '%find_image_list%'   => $find_image_list,
            '%find_image%'        => $find_image,
            '%find_video%'        => $find_video,
            '%find_file%'         => $find_file,
            '%find_content%'      => $find_content,
            '%edit_images%'       => $edit_images,
            '%find_images%'       => $find_images,
            '%find_admin_images%' => $find_admin_images,
            '%table_result_name%' => $table_result_name,
            '%init_field%'        => $parm['init_field'],
            '%init_field_name%'   => $parm['init_field_name'],
            '%ModelName%'         => $ModelName,
            '%edit_time%'         => $edit_time,
            '%class_model%'       => $class_model,
            '%class_name%'        => $class_name,
        ];


        $content = str_replace(
            array_keys($replacePairs),
            array_values($replacePairs),
            $content
        );

        return $content;
    }


    /**
     * Init公共模型 渲染代码
     * @param $parm
     * @param $controllers
     * @param $fields
     * @param $table_result_name 表备注
     * @param $is_class_id       是否有分类
     * @return array|false|string|string[]
     */
    public function InitModelViews($parm, $controllers, $fields, $table_result_name, $is_class_id = false)
    {
        $content = file_get_contents(CMF_ROOT . '/public/plugins/form/view/template/model/InitModel.html');

        $root_menu_name = '';
        $date           = date('Y-m-d H:i:s');
        $Controller     = "new \initmodel\\" . ucfirst($parm['controllers']) . 'Model();';
        $name_underline = $this->toUnderScore($parm['root_menu_name']);//驼峰转下划线


        if ($parm['root_menu_name']) {
            $root_menu_name = <<<EOF
/**
    * @AdminModel(
    *     "name"             =>"{$parm['root_menu_name']}",
    *     "name_underline"   =>"{$name_underline}",
    *     "table_name"       =>"{$parm['table_name']}",
    *     "model_name"       =>"{$parm['model_name']}",
    *     "remark"           =>"$table_result_name",
    *     "author"           =>"",
    *     "create_time"      =>"$date",
    *     "version"          =>"1.0",
    *     "use"              => $Controller
    * )
    */
EOF;
        }


        $this->assign('model_name', $parm['model_name']);
        $this->assign('table_name', $parm['table_name']);


        $content = str_replace(
            ['%model_name%', '%table_name%', '%table_result_name%', '%root_menu_name%'],
            [$parm['model_name'], $parm['table_name'], $table_result_name, $root_menu_name],
            $content
        );
        return $content;
    }


    /**
     * 后台index页面渲染
     * @param $parm
     * @param $controllers
     * @param $fields
     * @param $table_result_name  表名
     * @param $index_checkbox_js  开关js代码
     * @return mixed|string
     * @throws \Exception
     */
    public function indexViews($parm, $controllers, $fields, $table_result_name, $index_checkbox_js = '')
    {
        //提交数据渲染到
        foreach ($parm as $k => $v) {
            $this->assign($k, $v);
        }


        //处理隐藏字段
        $unset_fields      = ['images', 'password', 'content', 'textarea', 'ext'];//类型
        $unset_COLUMN_NAME = ['update_time', 'delete_time', 'updatetime', 'deletetime', 'list_order'];//字段名字
        foreach ($fields as $k => $v) {
            if (in_array($v['type'], $unset_fields) || in_array($v['COLUMN_NAME'], $unset_COLUMN_NAME)) {
                unset($fields[$k]);
            }
        }

        $fields = array_merge($fields);

        //渲染
        $this->assign('index_checkbox_js', $index_checkbox_js);
        $this->assign('batch_post', "{:url('batch_post')}");
        $this->assign('head', ' <include file="public@header"/>');
        $this->assign('foreach', ' <foreach name="list" item="vo">');
        $this->assign('foreachEnd', '</foreach>');
        $this->assign('id', '{$vo.id}');
        $this->assign('pid', '{$pid}');
        $this->assign('list_name', $table_result_name);
        $this->assign('pagination', '<div class="pagination">{$pagination|default=\'\'}</div>');
        //字段
        $this->assign('fields', $fields);
        $this->assign("url_list", "{:url('index',['pid'=>\$pid,'params'=>\$params])}");
        $this->assign("url_add", "{:url('add',['pid'=>\$pid,'params'=>\$params])}");
        $this->assign("url_edit", "{:url('edit',['id'=>\$vo.id,'pid'=>\$pid,'page'=>\$page,'params'=>\$params])}");
        $this->assign("url_edit_id", "{:url('edit',['id'=>\$id,'pid'=>\$pid,'page'=>\$page,'params'=>\$params])}");//编辑id
        $this->assign("url_delete_all", "{:url('delete',['pid'=>\$pid,'page'=>\$page,'params'=>\$params])}");


        $delete_id = "{:url('delete',['id'=>\$vo.id,'pid'=>\$pid,'page'=>\$page,'params'=>\$params])}";
        $find_id   = "{:url('find',['id'=>\$vo.id,'pid'=>\$pid,'page'=>\$page,'params'=>\$params])}";
        $edit_id   = "{:url('edit',['id'=>\$vo.id,'pid'=>\$pid,'page'=>\$page,'params'=>\$params])}";
        $find_name = "{\$vo.name}";


        $url_delete = '<a class="layui-btn layui-btn-primary layui-border-red layui-btn-xs js-ajax-delete" href="' . $delete_id . '">删除</a>' . "\n";


        $this->assign("url_delete", $url_delete);
        $this->assign("find_id", $find_id);
        $this->assign("edit_id", $edit_id);
        $this->assign("find_name", $find_name);


        $this->assign("recommend_post", "{:url('batch_post',['is_recommend'=>2,'pid'=>\$pid,'page'=>\$page,'params'=>\$params])}");
        $this->assign("no_recommend_post", "{:url('batch_post',['is_recommend'=>1,'pid'=>\$pid,'page'=>\$page,'params'=>\$params])}");
        $this->assign("list_order_post", "{:url('list_order_post',['page'=>\$page,'pid'=>\$pid,'params'=>\$params])}");

        $this->assign("name_list_order", 'list_order[{$vo.id}]');//排序条件
        $this->assign("value_list_order", '{$vo.list_order}');//排序条件

        $this->assign("import", "{:url('test_import')}");
        $this->assign("export", "{:url('excel/test_export',['page'=>\$page,'excel'=>\$excel])}");

        $this->assign("name", "{:input('request.keyword/s','')}");//搜索条件
        $this->assign("admin_js", "__STATIC__/js/admin.js");//引入admin.js文件


        //权限管理
        $authRuleName = "'" . "admin/{$controllers}/";
        $this->assign("menu_add", '<if condition="cmf_auth_check(cmf_get_current_admin_id(),' . $authRuleName . 'add' . "'" . ')">' . "\n");
        $this->assign("menu_edit", '<if condition="cmf_auth_check(cmf_get_current_admin_id(),' . $authRuleName . 'edit' . "'" . ')">' . "\n");
        $this->assign("menu_find", '<if condition="cmf_auth_check(cmf_get_current_admin_id(),' . $authRuleName . 'find' . "'" . ')">' . "\n");
        $this->assign("menu_delete", '<if condition="cmf_auth_check(cmf_get_current_admin_id(),' . $authRuleName . 'delete' . "'" . ')">' . "\n");
        $this->assign("menu_recommend", '<if condition="cmf_auth_check(cmf_get_current_admin_id(),' . $authRuleName . 'batch_post' . "'" . ')">' . "\n");
        $this->assign("menu_list_order", '<if condition="cmf_auth_check(cmf_get_current_admin_id(),' . $authRuleName . 'list_order_post' . "'" . ')">' . "\n");
        $this->assign("end_if", "</if>" . "\n");


        $this->assign('fields', array_filter($fields, static function ($item) {

            $not_show = ['delete_time'];
            if (in_array(strtolower($item['COLUMN_NAME']), $not_show)) {
                return false;
            }
            return true;
        }));


        return $this->fetch("/template/view/index");
    }


    /**
     * 后台add页面渲染
     * @param $parm
     * @param $controllers
     * @param $fields
     * @return mixed|string
     * @throws \Exception
     */
    protected function addViews($parm, $controllers, $fields, $table_result_name)
    {
        $this->assign('controllers_name', $this->model->uncamelize($controllers));//控制器
        $this->assign('head', '<include file="public@header"/>');
        $this->assign('list_name', $table_result_name);

        $this->assign("url_list", "{:url('index',['page'=>\$page,'pid'=>\$pid,'params'=>\$params])}");
        $this->assign("url_add", "{:url('add',['page'=>\$page,'pid'=>\$pid,'params'=>\$params])}");
        $this->assign("url_add_post", "{:url('add_post',['page'=>\$page,'pid'=>\$pid,'params'=>\$params])}");
        $this->assign("url_edit_id", "{:url('edit',['id'=>\$id,'page'=>\$page,'pid'=>\$pid,'params'=>\$params])}");//编辑id
        $this->assign("admin_js", "__STATIC__/js/admin.js");//引入admin.js文件

        return $this->fetch("/template/view/add");
    }


    /**
     * 后台edit页面渲染
     * @param $parm
     * @param $controllers
     * @param $fields
     * @return mixed|string
     * @throws \Exception
     */
    protected function editViews($parm, $controllers, $fields, $table_result_name)
    {
        //权限管理
        $authRuleName = "admin/{$controllers}/";

        $menu_add = <<<EOT
         <if condition="cmf_auth_check(cmf_get_current_admin_id(), '{$authRuleName}add')">
        EOT;
        $this->assign("menu_add", "{$menu_add}\n");

        $menu_edit = <<<EOT
         <if condition="cmf_auth_check(cmf_get_current_admin_id(), '{$authRuleName}edit')">
        EOT;
        $this->assign("menu_edit", "{$menu_edit}\n");
        $this->assign("end_if", "</if>\n");


        $this->assign('head', ' <include file="public@header"/>');
        $this->assign('id', '{$id}');
        $this->assign('page', '{$page}');
        $this->assign('list_name', $table_result_name);
        $this->assign('controllers_name', $this->model->uncamelize($controllers));//控制器

        $this->assign("url_list", "{:url('index',['page'=>\$page,'pid'=>\$pid,'params'=>\$params])}");
        $this->assign("url_add", "{:url('add',['page'=>\$page,'pid'=>\$pid,'params'=>\$params])}");
        $this->assign("url_edit_id", "{:url('edit',['id'=>\$id,'page'=>\$page,'pid'=>\$pid,'params'=>\$params])}");//编辑id
        $this->assign("url_edit_post", "{:url('edit_post',['id'=>\$id,'page'=>\$page,'pid'=>\$pid,'params'=>\$params])}");
        $this->assign("admin_js", "__STATIC__/js/admin.js");//引入admin.js文件

        return $this->fetch("/template/view/edit");
    }


    /**
     * 后台find页面渲染
     * @param $parm
     * @param $controllers
     * @param $fields
     * @return mixed|string
     * @throws \Exception
     */
    protected function findViews($parm, $controllers, $fields)
    {
        $this->assign('head', ' <include file="public@header"/>');
        $this->assign('id', '{$id}');
        $this->assign('controllers_name', $this->model->uncamelize($controllers));//控制器
        $this->assign("admin_js", "__STATIC__/js/admin.js");//引入admin.js文件
        $this->assign("url_find_id", "{:url('find',['id'=>\$id,'pid'=>\$pid,'params'=>\$params])}");//编辑id


        $this->assign('fields', array_filter($fields, static function ($item) {

            $not_show = ['id', 'create_time', 'update_time', 'delete_time'];
            if (in_array(strtolower($item['COLUMN_NAME']), $not_show)) {
                return false;
            }
            return true;
        }));


        return $this->fetch("/template/view/find");
    }


    /**
     * 后台form页面渲染
     * @param $parm
     * @param $controllers
     * @param $fields
     * @return mixed|string
     * @throws \Exception
     */
    protected function formViews($parm, $controllers, $fields)
    {
        $this->assign('fields', array_filter($fields, static function ($item) {
            //隐藏字段
            $not_show    = ['id', 'create_time', 'update_time', 'delete_time', 'list_order'];
            $column_name = strtolower($item['COLUMN_NAME']);

            // 隐藏指定字段或以is_开头的字段
            if (in_array($column_name, $not_show) || strpos($column_name, 'is_') === 0) {
                return false;
            }
            return true;
        }));

        return $this->fetch("/template/view/form");
    }


    /**
     * Api控制 渲染代码
     * @param $parm
     * @param $controllers
     * @param $fields
     * @param $table_result_name
     * @param $keyword      = 'id' 关键字搜索
     * @param $is_show      是否隐藏显示
     * @param $is_index     是否首页推荐
     * @param $is_class_id  分类id
     * @param $is_status    状态
     * @param $is_type      类型
     * @return array|false|string|string[]
     */
    public function ApiControllerViews($parm, $controllers, $fields, $table_result_name, $keyword = 'id', $is_show = false, $is_index = false, $is_class_id = false, $is_status = false, $is_type = false)
    {
        $content = file_get_contents(CMF_ROOT . 'public/plugins/form/view/template/controller/ApiFooController.html');

        $paginate = '';

        //api 是否开启分页
        if (isset($parm['api_paginate']) && !empty($parm['api_paginate'])) $get_list = 'get_list_paginate';
        if (!isset($parm['api_paginate']) && empty($parm['api_paginate'])) $get_list = 'get_list';


        //驼峰转下划线
        $api_function_name = $this->toUnderScore($parm['controllers']);

        //api 登录访问
        $api_checkAuth = '';
        $user_id_where = "\$where[]=['id','>',0];" . "\n";
        $is_where      = "";//搜索条件
        if ($is_show) $user_id_where .= "\$where[]=['is_show','=',1];";
        if ($is_index) $is_where .= "if(\$params['is_index']) \$where[]=['is_index','=',1];" . "\n";
        if ($is_class_id) $is_where .= "if(\$params['class_id']) \$where[]=['class_id','=',\$params['class_id']];" . "\n";
        if ($is_type) $is_where .= "if(\$params['type']) \$where[]=['type','=',\$params['type']];" . "\n";

        if (isset($parm['api_checkAuth']) && !empty($parm['api_checkAuth'])) {
            $api_checkAuth .= "\$this->checkAuth();\n";
            $user_id_where .= "\$where[]=['user_id','=',\$this->user_id];";
        }


        $api_url = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $parm['root_menu_name']));

        $domain_name          = cmf_config('domain_name');//线上域名
        $official_environment = "{$domain_name}/api/wxapp/{$api_url}/index";

        $http_type        = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $domain_name_api  = $http_type . $_SERVER['HTTP_HOST'];
        $test_environment = "{$domain_name_api}/api/wxapp/{$api_url}/index";


        $InitName     = "{$controllers}Init";
        $InitModel    = "{$controllers}Model";
        $ValidateName = "'{$controllers}'";


        $root_menu_name = '';
        $date           = date('Y-m-d H:i:s');
        $Controller     = "new \api\wxapp\controller\\" . ucfirst($parm['controllers']) . "Controller();";
        $name_underline = $this->toUnderScore($parm['root_menu_name']);//驼峰转下划线


        if ($parm['root_menu_name']) {
            $root_menu_name = <<<EOF
/**
    * @ApiController(
    *     "name"                    =>"{$parm['root_menu_name']}",
    *     "name_underline"          =>"$name_underline",
    *     "controller_name"         =>"{$parm['controllers']}",
    *     "table_name"              =>"{$parm['table_name']}",
    *     "remark"                  =>"{$table_result_name}"
    *     "api_url"                 =>"/api/wxapp/{$api_url}/index",
    *     "author"                  =>"",
    *     "create_time"             =>"{$date}",
    *     "version"                 =>"1.0",
    *     "use"                     => $Controller
    *     "test_environment"        =>"{$test_environment}",
    *     "official_environment"    =>"{$official_environment}",
    * )
    */
EOF;
        }


        //默认接口
        $success_url = "/api/wxapp/{$api_url}/index\n";
        $success_url .= cmf_config('domain_name') . "/api/wxapp/{$api_url}/index\n";
        $success     = "'{$table_result_name}-接口请求成功'";

        //引入
        $use_auth_controllers = 'use think\facade\Db;' . "\n";
        $use_auth_controllers .= 'use think\facade\Log;' . "\n";
        $use_auth_controllers .= 'use think\facade\Cache;' . "\n";

        //api接口名字
        $table_name = $parm['table_name'];
        $api_openid = $parm['api_openid'];

        /**
         * api接口生成
         * api_index:查询列表 find_.$table_name._list
         * api_add:添加操作  $table_name.add
         * api_edit:编辑操作  $table_name.edit
         * api_delete:删除操作   $table_name.delete
         */


        //获取tags标签备注
        if ($table_result_name) {
            $tags_i = $table_result_name;
            $tags   = '{"' . $table_result_name . '"}';
        } else {
            $tags_i = $parm['tags'];
            $tags   = '{"' . $parm['tags'] . '"}';
        }
        //控制器转下划线
        $controllers_i = $this->toUnderScore($controllers);


        //声明备注内容
        $api_index        = '';
        $api_index_field  = '';
        $api_index_opneid = '';
        $api_index_id     = '';

        //图片+富文本处理
        $find_images      = '';
        $find_image       = '';
        $find_images_list = '';
        $find_image_list  = '';
        $find_content     = '';
        $edit_images      = '';


        //过滤字段
        $filtration_array = ['id', 'create_time', 'update_time', 'delete_time', 'user_id'];
        foreach ($fields as $k => $v) {

            if ($v['COLUMN_NAME'] == 'status') $status_name = $v['ApiParameterName'];
            if ($v['COLUMN_NAME'] == 'type') $type_name = $v['ApiParameterName'];

            if (strpos($v['COLUMN_NAME'], '_images') !== false) $v['ApiParameterName'] = $v['COLUMN_COMMENT'] . "     (数组格式)";
            if (strpos($v['COLUMN_NAME'], 'images') !== false) $v['ApiParameterName'] = $v['COLUMN_COMMENT'] . "     (数组格式)";


            if (!in_array($v['COLUMN_NAME'], $filtration_array)) {
                $api_index_field .= <<<EOF
\n         *
         *
         *    @OA\Parameter(
         *         name="{$v['COLUMN_NAME']}",
         *         in="query",
         *         description="{$v['ApiParameterName']}",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ), 
         *
EOF;


                $name = $v['COLUMN_NAME'];

                if ($v['type'] == 'images') {
                    //列表图片处理
                    $find_image .= "if(\$result['$name']) \$result['$name']=\$this->getImagesUrl(\$result['$name']);" . "\n";
                    $find_image .= "if(\$item['$name']) \$item['$name']=\$this->getImagesUrl(\$item['$name']);" . "\n";
                    $find_image .= "if(\$params['$name']) \$params['$name']=\$this->setParams(\$params['$name']);" . "\n";
                }


                if ($v['type'] == 'image') {
                    //详情图片处理
                    $find_image .= "if(\$result['$name']) \$result['$name']=cmf_get_asset_url(\$result['$name']);" . "\n";
                    $find_image .= "if(\$item['$name']) \$item['$name']=cmf_get_asset_url(\$item['$name']);" . "\n";
                }

                if ($v['type'] == 'video') {
                    $find_image .= "if(\$result['$name']) \$result['$name']=cmf_get_asset_url(\$result['$name']);" . "\n";
                }


                if ($v['type'] == 'file') {
                    $find_image .= "if(\$result['$name']) \$result['$name']=cmf_get_asset_url(\$result['$name']);" . "\n";
                }

                if ($v['type'] == 'content') {
                    //富文本
                    $find_content .= "if(\$result['$name']) \$result['$name']=htmlspecialchars_decode(cmf_replace_content_file_url(\$result['$name']);\n";
                }
            }
        }


        //id
        $api_index_id = <<<EOF
\n         *
         *
         *    @OA\Parameter(
         *         name="id",
         *         in="query",
         *         description="id",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ), 
         *
         *
EOF;

        //edit_id
        $api_edit_id = <<<EOF
\n         *
         *
         *    @OA\Parameter(
         *         name="id",
         *         in="query",
         *         description="id空添加,存在编辑",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ), 
         *
         *
EOF;

        //openid
        $api_index_opneid = <<<EOF
\n         *
         *
         *    @OA\Parameter(
         *         name="{$api_openid}",
         *         in="query",
         *         description="{$api_openid}",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ),  
         *
         * 
EOF;

        //分类
        $api_is_index = '';
        if ($is_index) $api_is_index = <<<EOF
\n         *
         *
         *    @OA\Parameter(
         *         name="is_index",
         *         in="query",
         *         description="true 首页推荐",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ), 
         *
EOF;

        //分类
        $api_class_id = '';
        if ($is_class_id) $api_class_id = <<<EOF
\n         *
         *
         *    @OA\Parameter(
         *         name="class_id",
         *         in="query",
         *         description="分类ID",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ), 
         *
EOF;

        //状态
        $api_status = '';
        if ($is_status) $api_status = <<<EOF
\n         *
         *
         *    @OA\Parameter(
         *         name="status",
         *         in="query",
         *         description="{$status_name}",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ), 
         *
EOF;

        //类型
        $api_type = '';
        if ($is_type) $api_type = <<<EOF
\n         *
         *
         *    @OA\Parameter(
         *         name="type",
         *         in="query",
         *         description="{$type_name}",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ), 
         *
EOF;

        //index
        $api_index = <<<EOF
        /**
         * $tags_i 列表
         * @OA\Post(
         *     tags={$tags},
         *     path="/wxapp/{$controllers_i}/find_{$controllers_i}_list",
         *     
         *     {$api_index_opneid}
         *
         *     {$api_class_id}
         *
         *     {$api_is_index}
         *
         *     {$api_status}
         *
         *     {$api_type}
         *
         *     @OA\Parameter(
         *         name="keyword",
         *         in="query",
         *         description="(选填)关键字搜索",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ), 
         *
         *
         *     @OA\Parameter(
         *         name="is_paginate",
         *         in="query",
         *         description="false=分页(不传默认分页),true=不分页",
         *         required=false,
         *         @OA\Schema(
         *             type="string",
         *         )
         *     ), 
         *
         *
         *
         *
         *     @OA\Response(response="200", description="An example resource"),
         *     @OA\Response(response="default", description="An example resource")
         * )
         *
         *
         *   test_environment: $domain_name_api/api/wxapp/{$controllers_i}/find_{$controllers_i}_list
         *   official_environment: $domain_name/api/wxapp/{$controllers_i}/find_{$controllers_i}_list
         *   api:  /wxapp/{$controllers_i}/find_{$controllers_i}_list
         *   remark_name: $tags_i 列表
         *
         */
EOF;


        //编辑
        $api_edit = <<<EOF
        /**
         * $tags_i 编辑&添加
         * @OA\Post(
         *     tags={$tags},
         *     path="/wxapp/{$controllers_i}/edit_{$controllers_i}",
         *     {$api_index_opneid}
         *     {$api_index_field}
         *     {$api_edit_id}
         *
         *     @OA\Response(response="200", description="An example resource"),
         *     @OA\Response(response="default", description="An example resource")
         * )
         *
         *   test_environment: $domain_name_api/api/wxapp/{$controllers_i}/edit_{$controllers_i}
         *   official_environment: $domain_name/api/wxapp/{$controllers_i}/edit_{$controllers_i}
         *   api:  /wxapp/{$controllers_i}/edit_{$controllers_i}
         *   remark_name: $tags_i 编辑&添加
         *
         */
EOF;

        //delete
        $api_delete = <<<EOF
        /**
         * $tags_i 删除
         * @OA\Post(
         *     tags={$tags},
         *     path="/wxapp/{$controllers_i}/delete_{$controllers_i}",
         *     {$api_index_opneid}
         *     {$api_index_id}
         *
         *     @OA\Response(response="200", description="An example resource"),
         *     @OA\Response(response="default", description="An example resource")
         * )
         *  
         *   test_environment: $domain_name_api/api/wxapp/{$controllers_i}/delete_{$controllers_i}
         *   official_environment: $domain_name/api/wxapp/{$controllers_i}/delete_{$controllers_i}
         *   api:  /wxapp/{$controllers_i}/delete_{$controllers_i}
         *   remark_name: $tags_i 删除
         *
         */
EOF;

        //find
        $api_find = <<<EOF
        /**
         * $tags_i 详情
         * @OA\Post(
         *     tags={$tags},
         *     path="/wxapp/{$controllers_i}/find_{$controllers_i}",
         *     {$api_index_id}
         *
         *     @OA\Response(response="200", description="An example resource"),
         *     @OA\Response(response="default", description="An example resource")
         * )
         *
         *   test_environment: $domain_name_api/api/wxapp/{$controllers_i}/find_{$controllers_i}
         *   official_environment: $domain_name/api/wxapp/{$controllers_i}/find_{$controllers_i}
         *   api:  /wxapp/{$controllers_i}/find_{$controllers_i}
         *   remark_name: $tags_i 详情
         *
         */
EOF;


        $content = str_replace(
            [
                '%InitName%', '%InitModel%', '%root_menu_name%', '%menu%', '%menu_parent%', '%controller_name%',
                '%model_name%', '%use_auth_controllers%', '%table_name%', '%api_index%',
                '%api_edit%', '%api_delete%', '%api_find%', '%find_images%', '%find_image%', '%find_images_list%',
                '%find_image_list%', '%find_content%', '%edit_images%', '%paginate%', '%api_checkAuth%', '%user_id_where%', '%success_url%',
                '%success%', '%table_result_name%', '%api_function_name%', '%get_list%', '%keyword%', '%ValidateName%', '%is_where%'],
            [
                $InitName, $InitModel, $root_menu_name, $parm['menu'], $parm['menu_parent'], $parm['controllers'],
                $parm['model_name'], $use_auth_controllers, $table_name, $api_index,
                $api_edit, $api_delete, $api_find, $find_images, $find_image, $find_images_list, $find_image_list,
                $find_content, $edit_images, $paginate, $api_checkAuth, $user_id_where, $success_url, $success, $table_result_name,
                $api_function_name, $get_list, $keyword, $ValidateName, $is_where],
            $content
        );

        return $content;
    }


    /**
     * Api模型 渲染代码
     * @param $parm
     * @param $controllers
     * @param $fields
     * @param $table_result_name 表备注信息
     * @return array|false|string|string[]
     */
    public function ApiModelViews($parm, $controllers, $fields, $table_result_name)
    {
        $content = file_get_contents(CMF_ROOT . '/public/plugins/form/view/template/model/ApiFoo.html');


        $root_menu_name = '';
        $date           = date('Y-m-d H:i:s');
        $Controller     = "new \api\wxapp\model\\" . ucfirst($parm['controllers']) . 'Model();';
        if ($parm['root_menu_name']) {
            $root_menu_name = <<<EOF
/**
    * @ApiModel(
    *     "name"            =>"{$parm['root_menu_name']}",
    *     "table_name"      =>"{$parm['table_name']}",
    *     "model_name"      =>"{$parm['model_name']}",
    *     "remark"          =>"$table_result_name",
    *     "author"          =>"",
    *     "create_time"     =>"$date",
    *     "version"         =>"1.0",
    *     "use"             => $Controller
    * )
    */
EOF;
        }


        //api 是否开启分页
        if (isset($parm['api_paginate']) && !empty($parm['api_paginate'])) $paginate = 'paginate(20)';
        if (!isset($parm['api_paginate']) && empty($parm['api_paginate'])) $paginate = 'select()';


        $this->assign('model_name', $parm['model_name']);
        $this->assign('table_name', $parm['table_name']);


        $use_auth_controllers = 'use think\facade\Db;' . "\n";
        $use_auth_controllers .= 'use think\Model;' . "\n";


        $content = str_replace(
            ['%model_name%', '%table_name%', '%use_auth_controllers%', '%table_result_name%', '%paginate%', '%root_menu_name%'],
            [$parm['model_name'], $parm['table_name'], $use_auth_controllers, $table_result_name, $paginate, $root_menu_name],
            $content
        );
        return $content;
    }


    /**
     * 驼峰转下划线
     * @param $str
     * @return string
     */
    function toUnderScore($str)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $str));
    }


}
