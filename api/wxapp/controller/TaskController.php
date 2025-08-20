<?php

namespace api\wxapp\controller;

/**
 * @ApiMenuRoot(
 *     'name'   =>'Task',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'cogs',
 *     'remark' =>'定时任务'
 * )
 */

use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class TaskController
{

    /**
     * 执行定时任务
     *
     *   test_environment: http://lscs.ikun:9090/api/wxapp/task/index
     *   official_environment: https://lscs001.jscxkf.net/api/wxapp/task/index
     *   api: /wxapp/task/index
     *   remark_name: 执行定时任务
     *
     */
    public function index()
    {
        $task = new \init\TaskInit();
        $task->operation_vip();//处理vip
        $task->operation_cancel_order();//自动取消订单
        $task->operation_accomplish_order();//自动完成订单


        //将公众号的official_openid存入member表中  可以在用户授权登录后操作
        //$task->update_official_openid();

        echo("定时任务,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n\n\n");

        return json("定时任务已执行完毕-------" . date('Y-m-d H:i:s'));
    }


}