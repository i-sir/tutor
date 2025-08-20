<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------

namespace plugins\form\model;

use think\Model;


class FormModel extends Model
{
    protected $name = 'base_form_model';

    private function template_check(string $check_dir, string $check_file)
    {
        if (!is_dir($check_dir)) {
            if (!mkdir($check_dir, 0777, true) && !is_dir($check_dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $check_dir));
            }
        }
        if (file_exists($check_file)) {
            return false;
        }
        return true;

    }

    public function template_build(string $to_file, string $content)
    {
        if (empty($content)) {
            throw new \RuntimeException("controller content is empty");
        }

        if ($this->template_check(dirname($to_file), $to_file)) {
            $res = file_put_contents($to_file, $content);
            if (!$res) {
                return false;
            }
        }
        return true;
    }

    public function template_View(array $parm, string $index, string $add, string $edit, string $form,string $find)
    {

        //驼峰命名转下划线命名
        $catalog = $this->uncamelize($parm['controllers']);

        //生成视图 获取当前模板

        //        $viewPath = "../public/themes/admin_simpleboot3/admin/$catalog/";
        $viewPath = "../public/themes/admin_fengiy/admin/$catalog/";

        $res1 = $this->template_build($viewPath . 'index.html', $index);
        $res2 = $this->template_build($viewPath . 'add.html', $add);
        $res3 = $this->template_build($viewPath . 'edit.html', $edit);
        $res4 = $this->template_build($viewPath . 'form.html', $form);
        $res5 = $this->template_build($viewPath . 'find.html', $find);

        return $res1 && $res2 && $res3 && $res4 & $res5;
    }


    /**
     * 　　* 驼峰命名转下划线命名
     * 　　* 思路:
     * 　　* 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
     */
    public function uncamelize($camelCaps, $separator = '_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    /**
     * @param string $model_name
     * @return mixed   返回格式 [['COLUMN_NAME'=>xxx,'COLUMN_COMMENT'=>xxx,'DATA_TYPE'=>xxx],...]
     */
    public function getField(string $model_name)
    {

        $tableName = config('database.connections.mysql.prefix') . $model_name;
        $db        = config('database.connections.mysql.database');
        $sql       = sprintf(
            "select COLUMN_NAME,COLUMN_COMMENT,DATA_TYPE from information_schema.COLUMNS
	where table_name ='%s' and table_schema ='%s'", $tableName, $db);
        return self::query($sql);

    }


    static function query($sql)
    {

        try {
            $pdo = new \PDO(
                sprintf(
                    "mysql:dbname=%s;host=%s;port=%s;charset=UTF8", config("database.connections.mysql.database"), config("database.connections.mysql.hostname"), config("database.connections.mysql.hostport"),), config("database.connections.mysql.username"), config("database.connections.mysql.password"),);
            $res = $pdo->query($sql);
            if ($res === false) {
                throw new \RuntimeException(json_encode($pdo->errorInfo()));
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $res->fetchAll(\PDO::FETCH_ASSOC);
    }


}
