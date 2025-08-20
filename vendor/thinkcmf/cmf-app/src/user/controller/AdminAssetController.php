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
namespace app\user\controller;

use app\user\model\AssetModel;
use cmf\controller\AdminBaseController;
use think\facade\Db;

class AdminAssetController extends AdminBaseController
{
    /**
     * 资源管理列表
     * @adminMenu(
     *     'name'   => '资源管理',
     *     'parent' => '',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => 'file',
     *     'remark' => '资源管理列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $content = hook_one('user_admin_asset_index_view');

        if (!empty($content)) {
            return $content;
        }

        $result = AssetModel::field('a.*,u.user_login,u.user_email,u.user_nickname')
            ->alias('a')->join('user u', 'a.user_id = u.id')
            ->order('create_time', 'DESC')
            ->paginate(10);
        $this->assign('assets', $result->items());
        $this->assign('page', $result->render());
        return $this->fetch();
    }

    public function listimage()
    {
        $page   = $this->request->param('page');
        $join   = [
            ['__USER__ u', 'a.user_id = u.id']
        ];
        $total = Db::name('asset')->field('a.id,a.file_path,a.filename,a.create_time')
            ->alias('a')
            ->where('a.suffix','in',['bmp','jpg','jpeg','png','gif','tiff'])
            ->count();
        $result = Db::name('asset')->field('a.id,a.file_path,a.filename,a.create_time')
            ->alias('a')
            ->where('a.suffix','in',['bmp','jpg','jpeg','png','gif','tiff'])
            ->order('a.create_time', 'DESC')
            ->paginate(10)->each(function($item,$key){
                $item['preview_url'] = cmf_get_image_preview_url($item['file_path']);
                return $item;
            });
        $result = array(
            "state" => "SUCCESS",
            "list" => $result->items(),
            "total" => $total
        );
        die(json_encode($result));
    }


    /**
     * 删除文件
     * @adminMenu(
     *     'name'   => '删除文件',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '删除文件',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        if ($this->request->isPost()) {
            $id            = $this->request->param('id');
            $file_filePath = AssetModel::where('id', $id)->value('file_path');
            $file          = 'upload/' . $file_filePath;
            $res           = true;
            if (file_exists($file)) {
                $res = unlink($file);
            }
            if ($res) {
                AssetModel::where('id', $id)->delete();
                $this->success(lang('DELETE_SUCCESS'));
            } else {
                $this->error(lang('DELETE_FAILED'));
            }
        }
    }

}
 