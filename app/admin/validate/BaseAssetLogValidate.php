<?php

namespace app\admin\validate;

use think\Validate;


/**
    * @AdminModel(
    *     "name"             =>"BaseAssetLog",
    *     "name_underline"   =>"base_asset_log",
    *     "table_name"       =>"base_asset_log",
    *     "validate_name"    =>"BaseAssetLogValidate",
    *     "remark"           =>"用户资产变动记录",
    *     "author"           =>"",
    *     "create_time"      =>"2025-06-01 11:13:24",
    *     "version"          =>"1.0",
    *     "use"              =>   $this->validate($params, BaseAssetLog);
    * )
    */

class BaseAssetLogValidate extends Validate
{

protected $rule = [];




protected $message = [];




//软删除(delete_time,0)  'action'     => 'require|unique:AdminMenu,app^controller^action,delete_time,0',

//    protected $scene = [
//        'add'  => ['name', 'app', 'controller', 'action', 'parent_id'],
//        'edit' => ['name', 'app', 'controller', 'action', 'id', 'parent_id'],
//    ];


}
