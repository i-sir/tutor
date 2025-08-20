<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------

namespace plugins\form\validate;

use mindplay\demo\Form;
use plugins\form\model\FormModel;
use think\Validate;

class FormValidate extends Validate
{
    protected $rule = [
        'table_name' => 'require|isModel',
        'model_name' => 'require|isModel',
        'controllers'=> 'require',
        'menu'=> 'require',
        'menu_parent'=> 'require',

    ];

    protected $message = [
        'table_name.require' => '表不能空',
        'model_name.require' => '模型名不能空',
        'controllers.require' => '控制器不能为空',
        'menu.require'   => '子标签不能为空',
        'menu_parent.require'   => '父标签不能为空',

    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];
	
	/**
	 * @param $value
	 * @param $rules
	 * @param $data
	 * @param $field
	 * @return bool|string
	 */
    protected function isModel($value , $rules , $data , $field)
    {
	    $tableName = config('database.connections.mysql.prefix').$value;
	
	    $isTable=FormModel::query(sprintf("SHOW TABLES LIKE '%s'",$tableName));
        if($isTable === false)
        {
            return $value.'表不在';
        }
        return true;

    }


}
