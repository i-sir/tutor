<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-present http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\validate;

use app\admin\model\AdminMenuModel;
use think\facade\Db;
use think\Validate;

error_reporting(0);


class AdminMenuValidate extends Validate
{
    protected $rule = [
        'name'       => 'require',
        'app'        => 'require',
        'controller' => 'require',
        'parent_id'  => 'checkParentId',
        'action'     => 'require|checkUniqueAction',
    ];

    protected $message = [
        'name.require'             => '名称不能为空',
        'app.require'              => '应用不能为空',
        'parent_id'                => '超过了4级',
        'controller.require'       => '名称不能为空',
        'action.require'           => '名称不能为空',
        'action.checkUniqueAction' => '同样的记录已经存在!',
    ];

    protected $scene = [
        'add'  => ['name', 'app', 'controller', 'action', 'parent_id'],
        'edit' => ['name', 'app', 'controller', 'action', 'id', 'parent_id'],
    ];


    /**
     * 自定义验证规则：检查 action 是否唯一（排除软删除记录）
     * @param       $value
     * @param       $rule
     * @param array $data
     * @return bool
     */
    protected function checkUniqueAction($value, $rule, array $data = [])
    {
        $map = [];
        if ($data['id']) $map[] = ['id', '<>', $data['id'] ?? 0];
        if ($data['app']) $map[] = ['app', '=', $data['app']];
        if ($data['controller']) $map[] = ['controller', '=', $data['controller']];
        if ($value) $map[] = ['action', '=', $value];
        $map[] = ['delete_time', '=', 0]; // 排除软删除的记录

        $count = Db::name('admin_menu')->where($map)->count();

        return $count === 0;
    }


    // 自定义验证规则
    protected function checkParentId($value)
    {
        $find = AdminMenuModel::where("id", $value)->value('parent_id');

        if ($find) {
            $find2 = AdminMenuModel::where("id", $find)->value('parent_id');
            if ($find2) {
                $find3 = AdminMenuModel::where("id", $find2)->value('parent_id');
                if ($find3) {
                    return false;
                }
            }
        }
        return true;
    }
}
