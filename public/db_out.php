<?php

$file = dirname(dirname(__FILE__));

class Env
{
    const ENV_PREFIX = 'PHP_';

    /**
     * 加载配置文件
     * @access public
     * @param string $filePath 配置文件路径（php7+以上需加上 string 类型标注）
     * @return void（php7+才支持）
     */
    public static function loadFile($filePath)//:void
    {
        if (!file_exists($filePath)) throw new \Exception('配置文件' . $filePath . '不存在');
        // 返回二位数组
        $env = parse_ini_file($filePath, true);
        foreach ($env as $key => $val) {
            $prefix = static::ENV_PREFIX . strtoupper($key);
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $item = $prefix . '_' . strtoupper($k);
                    putenv("$item=$v");
                }
            } else {
                putenv("$prefix=$val");
            }
        }
    }

    /**
     * 获取环境变量值
     * @access public
     * @param string $name    环境变量名（支持二级. 号分割）
     * @param string $default 默认值
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        $result = getenv(static::ENV_PREFIX . strtoupper(str_replace('.', '_', $name)));

        if (false !== $result) {
            if ('false' === $result) {
                $result = false;
            } elseif ('true' === $result) {
                $result = true;
            }
            return $result;
        }
        return $default;
    }
}

Env::loadFile("../.env"); /*调用配置文件*/

$DB_HOST = Env::get('database.hostname');
$DB_USER = Env::get('database.username');
$DB_PASS = Env::get('database.password');
$DB_NAME = Env::get('database.database');

$con = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);


// 表结构单独导出的数组
$tablesForStructureOnly = ['cmf_base_admin_log', 'cmf_asset', 'cmf_base_test', 'cmf_admin_log', 'cmf_test'];
// 注释：这个数组包含需要单独导出表结构而不导出数据的表名。

// 不导出的表名数组
$tablesToExclude = ['cmf_region', 'cmf_area'];
// 注释：这个数组包含不需要导出的表名。

$tables = array();

$result = mysqli_query($con, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

$return = '';

foreach ($tables as $table) {
    if (in_array($table, $tablesForStructureOnly)) {
        $row2   = mysqli_fetch_row(mysqli_query($con, 'SHOW CREATE TABLE ' . $table));
        $return .= $row2[1] . ";\n\n";
    } elseif (!in_array($table, $tablesToExclude)) {
        $result     = mysqli_query($con, "SELECT * FROM " . $table);
        $num_fields = mysqli_num_fields($result);

        $return .= 'DROP TABLE ' . $table . ';';
        $row2   = mysqli_fetch_row(mysqli_query($con, 'SHOW CREATE TABLE ' . $table));
        $return .= "\n\n" . $row2[1] . ";\n\n";

        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = mysqli_fetch_row($result)) {
                $return .= 'INSERT INTO ' . $table . ' VALUES(';
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < $num_fields - 1) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
}

mkdirs('../db/system/' . date('Y-m'));

// sql 文件
$file_sql = date('Y-m') . '/' . date('Ymd') . '-' . md5(createRandomStr() . time()) . '.sql';
$sql      = '../db/system/' . $file_sql;

// 缓存文件
$cache_file_sql = $file . '/db/system/' . $file_sql;
$cache_file     = $file . '/db/db.txt';

// 导出数据
$handle = fopen($sql, 'w+');
fwrite($handle, $return);

/**
 * 处理压缩包
 */

// 存放路径和文件名
$filename = $file . '/db/system/' . date('Y-m') . '/' . date('Ymd') . '-' . createRandomStr(25) . '.zip'; //随便起个名
// 实例化类
$zip = new \ZipArchive();
// 打开压缩包
$zip->open($filename, \ZipArchive::CREATE);
// 向压缩包中添加文件
$zip->addFile($cache_file_sql, basename($cache_file_sql));
// 关闭压缩包
$zip->close();

// 运行缓存
$cache = fopen($cache_file, 'w+');
fwrite($cache, $filename);

fclose($handle);
fclose($cache);

unlink($cache_file_sql);//删除 sql 保存 zip

echo "\n" . "数据库备份成功" . date('Y-m-d H:i:s') . "\n" . "\n" . "\n";

/**
 * 检测文件夹是否存在
 * @param $dir  文件地址
 * @param $mode 权限
 * @return bool
 */
function mkdirs($dir, $mode = 0777)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;

    if (!mkdirs(dirname($dir), $mode)) return FALSE;

    return @mkdir($dir, $mode);
}

// 生成随机数
function createRandomStr($length = 8)
{
    $str    = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';//62 个字符
    $strlen = 62;
    while ($length > $strlen) {
        $str    .= $str;
        $strlen += 62;
    }
    $str = str_shuffle($str);
    return substr($str, 0, $length);
}

?>