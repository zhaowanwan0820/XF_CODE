<?php

/**
 * 导出
 * Class OfflineExport.
 */
class Offline
{
   public function export($title, $data, $columns, $name = '导出')
   {
       Yii::$enableIncludePath = false;
       Yii::import('application.extensions.phpexcel.PHPExcel', 1);
       $objPHPExcel = new PHPExcel();
       $objPHPExcel->setActiveSheetIndex(0);
       $objPHPExcel->getActiveSheet()->setTitle('第一页');

       $num = count($title);
       for ($i = 0; $i < $num; $i++) {
           $pCoordinate = PHPExcel_Cell::stringFromColumnIndex($i).''.(1);
           $len         = strlen(iconv('utf-8', 'gb2312', $title[$i]));
           $objPHPExcel->getActiveSheet()->setCellValue($pCoordinate, $title[$i]);
           $objPHPExcel->getActiveSheet()->getColumnDimension(substr($pCoordinate, 0, -1))->setWidth($len + 2);
       }
       foreach ($data as $key => $val) {
           foreach ($columns as $k => $column) {
               if ($k < $num) {
                   if (in_array($column, ['value_date', 'repayment_time', 'rg_time', 'end_repay_time', 'time', 'real_time']) && !empty($val[$column])) {
                       $val[$column] = date('Y-m-d H:i:s', $val[$column]);
                   }
                   $pCoordinate = PHPExcel_Cell::stringFromColumnIndex($k).''.($key + 2);
                   if (is_numeric($val[$column]) && strlen($val[$column]) > 10) {
                       $val[$column] = $val[$column] . "'";
                   }
                   $objPHPExcel->getActiveSheet()->setCellValue($pCoordinate, $val[$column]);
               }
           }
       }

       $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
       $name      = $name . date("Y年m月d日 H时i分s秒", time());

       header("Pragma: public");
       header("Expires: 0");
       header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
       header("Content-Type:application/force-download");
       header("Content-Type:application/vnd.ms-execl");
       header("Content-Type:application/octet-stream");
       header("Content-Type:application/download");;
       header('Content-Disposition:attachment;filename="'.$name.'.xls"');
       header("Content-Transfer-Encoding:binary");

       $objWriter->save('php://output');
       exit;

   }

    /**
     * 匹配中文
     *
     * @param $str
     *
     * @return string
     */
    public function checkString($str)
    {
        preg_match_all('/[\x{4e00}-\x{9fff}]+/u', $str, $matches);

        return join('', $matches[0]);
    }

    /**
     * string去掉数组两边空格
     *
     * @param $array
     *
     * @return array
     */
    public function trimArray($array)
    {
        array_walk_recursive($array, create_function('&$item', '$item = trim($item);'));
        return $array;
    }

    /**
     * 获取毫秒级时间戳
     *
     * @return int 毫秒级时间戳
     */
    public function getMillisecond()
    {
        list($usec, $sec) = explode(' ', microtime());
        $msec = round($usec * 1000);

        return $sec.$msec;
    }

    public function FetchRepeatMemberInArray($array)
    {
        // 获取去掉重复数据的数组
        $unique_arr = array_unique($array);
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc($array, $unique_arr);
        return $repeat_arr;
    }

    /**
     * 去除默认0
     *
     * @param $array
     *
     * @return array
     */

    public function formatArray($array)
    {
        array_walk_recursive($array, create_function('&$item', '$item=="0" && is_string($item) ?$item="":$item=$item;'));
        return $array;
    }

}
