<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

class AppInfoController extends AdminBaseController
{
    public function index()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $res = cmf_set_option('set_config', $data);
            if ($res) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        } else {
            $info = cmf_get_option('set_config');
            if (!empty($info['user_agreement'])) $info['user_agreement'] = cmf_replace_content_file_url(htmlspecialchars_decode($info['user_agreement']));
            if (!empty($info['privacy_agreement'])) $info['privacy_agreement'] = cmf_replace_content_file_url(htmlspecialchars_decode($info['privacy_agreement']));
            $this->assign('info', $info);
            return $this->fetch();
        }
    }



}