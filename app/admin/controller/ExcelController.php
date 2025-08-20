<?php
/**
 * Created by PhpStorm.
 * User: zhang
 * Date: 2021/11/29
 * Time: 16:38
 */

namespace app\admin\controller;

use api\wxapp\controller\AuthController;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\facade\Db;

class ExcelController extends AuthController
{


    /**---------------------------------------   导入   ----------------------------**/
    /**
     * 用户导入
     * 上传文件
     */
    public function memberExcelImport()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理
        $heads       = ['phone', 'pass', 'identity_id'];//导入头部对应数据库字段  A-Z 按顺序

        $app_logo    = cmf_config('app_logo');
        $max_user_id = $MemberModel->max('id');

        if ($this->request->isPost()) {
            $my_file = $_FILES['my_file'];
            //获取表格的大小，限制上传表格的大小5M
            if ($my_file['size'] > 5 * 1024 * 1024) {
                $this->error('文件大小不能超过5M');
            }

            //限制上传表格类型
            $fileExtendName = substr(strrchr($my_file["name"], '.'), 1);

            if ($fileExtendName != 'xls' && $fileExtendName != 'xlsx') {
                $this->error('必须为excel表格');
            }

            if (is_uploaded_file($my_file['tmp_name'])) {
                // 有Xls和Xlsx格式两种
                if ($fileExtendName == 'xlsx') {
                    $objReader = IOFactory::createReader('Xlsx');
                } else {
                    $objReader = IOFactory::createReader('Xls');
                }

                $filename    = $my_file['tmp_name'];
                $objPHPExcel = $objReader->load($filename);    //$filename可以是上传的表格，或者是指定的表格
                $sheet       = $objPHPExcel->getSheet(0);   //excel中的第一张sheet
                $highestRow  = $sheet->getHighestRow();        // 取得总行数


                //$highestColumn = $sheet->getHighestColumn(); // 取得总列数

                $insert = [];//插入数据
                $letter = [];//字段名字
                foreach ($heads as $k => $v) {
                    $letter[$v] = $this->get_letter($k);
                }

                //循环读取excel表格，整合成数组。如果是不指定key的二维，就用$data[i][j]表示。
                for ($j = 2; $j <= $highestRow; $j++) {
                    foreach ($letter as $k => $v) {

                        $value = preg_replace("/\s+/", "", $objPHPExcel->getActiveSheet()->getCell($v . $j)->getValue());


                        //特殊处理
                        //if ($k == 'nickname') $nickname = md5($value);


                        //追加字段
                        $z                             = $j - 1;
                        $insert[$j - 2]['openid']      = $this->get_openid() . "{$z}";
                        $insert[$j - 2]['create_time'] = time();
                        $insert[$j - 2]['avatar']      = cmf_get_asset_url($app_logo);
                        $insert[$j - 2]['nickname']    = "微信用户_" . ($max_user_id + $j);

                        //定义导入字段
                        $insert[$j - 2][$k] = $value;




                        //特殊 处理数据
                        if ($k == 'phone') {
                            $is_user = $MemberModel->where('phone', $value)->count();
                            if ($is_user) unset($insert[$j - 2]);
                        }


                    }
                }



                //插入数据
                foreach ($insert as $k => $v) {
                    if ($v['phone']) $MemberModel->strict(false)->insert($v);
                }


            }

            $this->success("导入成功");
        }
    }


    /**
     * 导入,根据key获取对应字母
     * @param $key
     * @return string
     */
    public function get_letter($key)
    {
        $letter = '';
        $key    = intval($key);
        while ($key >= 0) {
            $remainder = $key % 26;
            $letter    = chr(65 + $remainder) . $letter;
            $key       = floor($key / 26) - 1;
        }
        return $letter;
    }


    /**
     * 根据key获取对应字母以及之前的字母数组
     * @param $key
     * @return array
     */
    public function get_letters_sequence_before($key)
    {
        $num   = 0;
        $chars = str_split($key);
        foreach ($chars as $char) {
            $num = $num * 26 + ord($char) - 65 + 1;
        }

        $letters = [];
        for ($i = 1; $i <= $num; $i++) {
            $letter = '';
            $n      = $i;
            while ($n > 0) {
                $remainder = ($n - 1) % 26;
                $letter    = chr(65 + $remainder) . $letter;
                $n         = intdiv($n - 1, 26);
            }
            $letters[] = $letter;
        }
        return $letters;
    }


    /**---------------------------------------   导出   ----------------------------**/


    /**
     * 导出 模板
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function test_export()
    {
        $par    = $this->request->param();
        $params = $par['excel'];

        $list = Db::name('form_test')
            ->select()
            ->each(function ($item, $key) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);

                //图片链接 可用默认浏览器打开   后面为展示链接名字
                if ($item['image']) $item['image'] = '=HYPERLINK("' . cmf_get_asset_url($item['image']) . '","图片.png")';

                return $item;
            })->toArray();


        $headArrValue = [
            ['rowName' => 'ID', 'rowVal' => 'id', 'width' => 10],
            ['rowName' => '名字', 'rowVal' => 'name', 'width' => 10],
            ['rowName' => '年龄', 'rowVal' => 'age', 'width' => 10],
            ['rowName' => '测试', 'rowVal' => 'test', 'width' => 10],
        ];

        //副标题 纵单元格
        $subtitle = [
            ['rowName' => '列1', 'acrossCells' => 2],
            ['rowName' => '列2', 'acrossCells' => 2],
        ];

        $this->excelExports($list, $headArrValue, ['fileName' => '订单导出'], $subtitle);
    }


    /**
     * 生成指定长度的随机字符串
     * @param int    $length 字符串长度
     * @param string $chars  生成字符范围
     * @return string
     */
    function get_openid($length = 50, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $result   = '';
        $char_len = strlen($chars);
        $chars    = str_shuffle($chars);//随机打乱字符
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, $char_len - 1)];
        }
        return $result;
    }




    /**
     * excel 导出
     * @param array $data      数据data
     * @param array $headerRow 首行数据data
     * @param array $conf      [fileName] string 文件名 | [format] string 文件格式后缀 xls
     * @param array $subtitle  [rowName] string 列名 | [acrossCells] string 跨越列数
     * @return void
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function excelExports($data = [], $headerRow = [], $conf = [], $subtitle = [])
    {
        // 设置文件名
        $fileName = $conf['fileName'] ?? 'Export'; // 如果配置中有文件名，则使用；否则默认 'Export'
        $fileName .= '_' . date('Ymd') . '_' . cmf_random_string(3); // 添加日期和随机字符串到文件名

        // 设置文件格式，默认为 Xlsx
        $format = $conf['format'] ?? 'Xlsx'; //'Xls'也可以作为选项
        $spreadsheet = new Spreadsheet(); // 创建一个新的excel文档
        $sheet = $spreadsheet->getActiveSheet(); // 获取当前操作sheet的对象
        $sheet->setTitle($fileName); // 设置当前sheet的标题

        $sort = 0;

        // 设置首行标题
        if (!empty($conf['fileName'])) {
            $sheet->setCellValue('A1', $fileName); // 设置文件名为首行标题
            $sheet->getStyle('A1')->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER], // 水平居中
            ]);
            $length = count($headerRow) - 1;
            $sheet->mergeCells('A1:' . $this->intToChr($length) . '1'); // 合并首行单元格
            $sort = 1;
        }

        // 设置副标题列
        if (!empty($subtitle)) {
            $subSort = 0;
            foreach ($subtitle as $key) {
                $sheet->setCellValue($this->intToChr($subSort) . "2", $key['rowName'] ?? ' '); // 设置副标题
                $endSort = $subSort + $key['acrossCells'] - 1;
                $sheet->mergeCells($this->intToChr($subSort) . "2:" . $this->intToChr($endSort) . "2"); // 合并副标题单元格
                $subSort = $endSort + 1;
            }
            $sort = 2;
        }

        // 设置字段渲染列
        $sort += 1;
        $sheetConfig = false; // 根据 $headerRow 配置数据读取方式
        $headerRowCount = count($headerRow);

        // 设置标题栏及行宽
        for ($i = 0; $i < $headerRowCount; $i++) {
            $rowLetter = $this->intToChr($i); // 获取列字母
            if (is_array($headerRow[$i])) {
                $sheetConfig = true;
                $sheet->setCellValue($rowLetter . $sort, $headerRow[$i]['rowName']); // 设置标题栏
                $sheet->getColumnDimension($rowLetter)->setWidth($headerRow[$i]['width']); // 设置列宽
            } else {
                $sheet->setCellValue($rowLetter . $sort, $headerRow[$i]); // 设置标题栏
                $sheet->getColumnDimension($rowLetter)->setWidth(30); // 设置默认列宽
            }
        }

        // 设置样式 - 水平、垂直居中
        $sheet->getStyle('A1:' . $this->intToChr($headerRowCount) . (count($data) + 2))->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER, // 水平居中
                'vertical' => Alignment::VERTICAL_CENTER, // 垂直居中
            ],
        ]);

        // 定义需要转为文本格式的长数组字段
        $longNumberFields = ['order_num', 'phone'];

        // 填充数据行
        $row_key = $sort + 1;
        foreach ($data as $rowIndex => $rowVal) {
            foreach ($headerRow as $key => $val) {
                $rowLetter = $this->intToChr($key); // 获取列字母
                $cellValue = $sheetConfig ? $rowVal[$val['rowVal']] : $rowVal[$key]; // 获取单元格值
                if (in_array($val['rowVal'], $longNumberFields)) {
                    $sheet->setCellValueExplicit($rowLetter . $row_key, $cellValue, DataType::TYPE_STRING); // 强制将值设置为文本格式
                } else {
                    $sheet->setCellValue($rowLetter . $row_key, $cellValue); // 设置单元格值
                }
                // 设置自动换行
                $sheet->getStyle($rowLetter . $row_key)->getAlignment()->setWrapText(true);
            }
            $row_key++;
        }

        // 设置文件格式和头信息
        if ($format == 'Xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // 设置Xlsx格式的Content-Type
        } elseif ($format == 'Xls') {
            header('Content-Type: application/vnd.ms-excel'); // 设置Xls格式的Content-Type
        }
        header("Content-Disposition: attachment;filename={$fileName}." . strtolower($format)); // 设置下载文件的文件名
        header('Cache-Control: max-age=0'); // 禁用缓存

        $writer = IOFactory::createWriter($spreadsheet, $format); // 创建Writer对象
        $writer->save('php://output'); // 直接输出到浏览器
        exit;
    }


    // 辅助函数：将整数转换为Excel列字符串（A, B, ..., AA, AB, ...）
    public function intToChr($index)
    {
        $index += 1;
        $chr = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $chr = chr($mod + 65) . $chr; // 将数字转换为字母
            $index = (int)(($index - $mod) / 26); // 计算下一位
        }
        return $chr;
    }



    /**
     * 下载远程图片 到指定目录
     * @param $file_url
     * @param $path
     * @return array|string|string[]
     */
    private function download($file_url, $path)
    {
        $basepath = $path;
        $dir_path = $basepath;
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0777, true);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        $file = curl_exec($ch);
        curl_close($ch);
        $filename = pathinfo($file_url, PATHINFO_BASENAME);
        $resource = fopen($basepath . '/' . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);
        return $filename;
    }


}