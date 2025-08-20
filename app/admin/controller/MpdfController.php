<?php
/**
 * Created by PhpStorm.
 * User: zhang
 * Date: 2021/11/15
 * Time: 11:35
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

class MpdfController extends AdminBaseController
{
	/**
	 * 生成pdf
	 * @param string|array $data 打印数据
	 * @param string|array $format 设置纸张大小A4-L、[127, 89]毫米
	 * @param string $font_size 设置字体大小
	 * @return array
	 * @throws MpdfException
	 */
	static function mpdf_output($data, $format, string $font_size): array
	{
		$mpdf = new Mpdf([
			'mode'              => 'utf-8',
			'tempDir'           => WEB_ROOT . 'upload/pdf',
			'default_font_size' => $font_size,//设置字体大小
			'format'            => $format,//设置纸张大小A4-L、[127, 89]毫米
			'margin_left'       => 0,//设置页面外边距
			'margin_right'      => 0,
			'margin_top'        => 0,
			'margin_bottom'     => 0,
		]);
		$mpdf->SetDisplayMode('fullpage');
		$mpdf->autoScriptToLang = true;
		$mpdf->autoLangToFont = true;
		$mpdf->shrink_tables_to_fit = 1;
		$mpdf->keep_table_proportions = true;

		if (is_array($data)){
			foreach ($data as $v) {
				$mpdf->WriteHTML($v);
			}
		}else{
			$mpdf->WriteHTML($data);
		}

		$file_name = "pdf/" . time() . ".pdf";
		$full_file_name = "upload/" . $file_name;
		$mpdf->Output($full_file_name);
		return ['code' => 1, 'msg' => '打印成功', 'data' => $full_file_name];
	}
}