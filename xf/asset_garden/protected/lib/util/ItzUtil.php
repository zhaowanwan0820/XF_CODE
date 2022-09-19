<?php

/**
 * ItzUtil
 *
 * 专用的util
 *
 */
class ItzUtil
{

    //获取上传图片的缩略图地址，可能没有缩略图
    public function getThumbImg($img_url)
    {
        $position = strripos($img_url, "/");
        if ($position != false) {
            return $img_url = substr($img_url, 0, $position + 1) . "thumb_" . substr($img_url, $position + 1);
        } else {
            return $img_url;
        }
    }

    //生成唯一的guid
    function create_guid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);// "}"
        return $uuid;
    }

    /**
     * 模拟低版本array_column函数
     * @param $input
     * @param $columnKey
     * @param null $indexKey
     * @return array
     */
    static public function array_column($input, $columnKey, $indexKey = NULL)
    {
        if (!function_exists("array_column")) {
            $columnKeyIsNumber = (is_numeric($columnKey)) ? TRUE : FALSE;
            $indexKeyIsNull = (is_null($indexKey)) ? TRUE : FALSE;
            $indexKeyIsNumber = (is_numeric($indexKey)) ? TRUE : FALSE;
            $result = array();

            foreach ((array)$input AS $key => $row) {
                if ($columnKeyIsNumber) {
                    $tmp = array_slice($row, $columnKey, 1);
                    $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : NULL;
                } else {
                    $tmp = isset($row[$columnKey]) ? $row[$columnKey] : NULL;
                }
                if (!$indexKeyIsNull) {
                    if ($indexKeyIsNumber) {
                        $key = array_slice($row, $indexKey, 1);
                        $key = (is_array($key) && !empty($key)) ? current($key) : NULL;
                        $key = is_null($key) ? 0 : $key;
                    } else {
                        $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                    }
                }
                $result[$key] = $tmp;
            }
            return $result;
        } else {
            return array_column($input, $columnKey, $indexKey);
        }
    }

    /**
     * 两个，号分割字符串求交集
     * @param $bstr 1,2,3,4
     * @param $estr 2,3,4,5
     * @param $return 返回交集 默认不返回
     * @return true有交集 false无交集
     */
    static public function intersec($bstr, $estr, $return = false)
    {
        $bstr = is_null($bstr) ? "" : $bstr;
        $estr = is_null($estr) ? "" : $estr;
        if (empty($bstr) || empty($estr)) {
            return false;
        }
        //如果没有，号分割只是数字或者字符串类型
        $arr = array();
        if (is_numeric($bstr) || empty($bstr) || is_string($bstr)) {
            $arr['one'] = array($bstr);
        }
        if (is_numeric($estr) || empty($bstr) || is_string($estr)) {
            $arr['two'] = array($estr);
        }
        //如果有，号分割
        if (strpos($bstr, ',') !== false) {
            $arr['one'] = explode(",", $bstr);
        }
        if (strpos($estr, ',') !== false) {
            $arr['two'] = explode(",", $estr);
        }
        $diffarr = array_intersect($arr['one'], $arr['two']);
        if ($return) {
            return $diffarr;
        }

        return !empty($diffarr) ? true : false;
    }

    /**
     * 验证json格式
     * @param  $string
     * @return void
     */
    static public function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * 验证json格式内存在数据
     * @param  $string
     * @param  $filed
     * @return void
     */
    static public function checkJson($string, $filed)
    {
        if (!self::is_json($string)) {
            return false;
        }
        $jsonArr = json_decode($string, true);
        foreach ($jsonArr as $k => $v) {
            if (empty($v[$filed]) || !isset($v[$filed])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 验证整数或小数二位的正则
     */
    static public function checkMoney($money)
    {
        if (preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $money)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加文件锁
     * @param $exchange_id
     * @param $fnLock_pre 临时文件绝对路径
     * @param $is_lock 1:加锁 2:查询
     * @return bool|null|resource
     */
    static public function enterTenderIdFnLock($exchange_id, $fnLock_pre, $is_lock = 1)
    {
        $exchange_id = (int)$exchange_id;
        if ($exchange_id <= 0) {
            return false;
        }
        if (!in_array($is_lock, [1, 2])) {
            return false;
        }
        $fnLock_tenderid = $fnLock_pre . $exchange_id . '.pid';
        if (empty($fnLock_tenderid)) {
            return false;
        }
        if ($is_lock == 1) {
            $fpLock = fopen($fnLock_tenderid, 'w+');
            if ($fpLock) {
                if (flock($fpLock, LOCK_EX | LOCK_NB)) {
                    return ['fnLock' => $fnLock_tenderid, 'fpLock' => $fpLock];
                }
            }
        } elseif ($is_lock == 2) {
            if (is_file($fnLock_tenderid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 释放文件锁
     * @param $fpLock 句柄
     * @param $fnLock 临时文件绝对路径
     * @return bool
     */
    static public function releaseLock($fpLock, $fnLock)
    {
        if (!$fpLock) {
            return false;
        }
        if (is_file($fnLock)) {
            flock($fpLock, LOCK_UN);
            fclose($fpLock);
            unlink($fnLock);
            return true;
        }
        return false;
    }

    /**
     *  获取批量插入语句
     */
    static public function get_all_insert_sql($tbl_name, $keys, $values)
    {
        if (is_array($values) && !empty($values)) {
            foreach ($values as $key => $val) {
                $tempArray[] = "'" . implode("','", $val) . "'";
            }
            $s_fields = "(" . implode(",", $keys) . ")";
            $s_values = "(" . implode("),(", $tempArray) . ")";
            $sql = "INSERT INTO
                       $tbl_name
                       $s_fields
                   VALUES
                       $s_values";
            return $sql;
        } else {
            return false;
        }
    }

    /**
     * 获取插入语句
     * @param $tbl_name
     * @param $info
     * @return bool|string
     */
    static public function get_insert_db_sql($tbl_name, $info)
    {
        if (is_array($info) && !empty($info)) {
            $i = 0;
            foreach ($info as $key => $val) {
                $fields[$i] = $key;
                $values[$i] = $val;
                $i++;
            }
            $s_fields = "(" . implode(",", $fields) . ")";
            $s_values = "('" . implode("','", $values) . "')";
            $sql = "INSERT INTO
                       $tbl_name
                       $s_fields
                   VALUES
                       $s_values";
            return $sql;
        } else {
            return false;
        }
    }

    /**
     * 获取更新SQL语句
     * @param $tbl_name
     * @param $info
     * @param $condition
     * @return bool|string
     */
    static public function get_update_db_sql($tbl_name, $info, $condition)
    {
        $i = 0;
        $data = '';
        if (is_array($info) && !empty($info)) {
            foreach ($info as $key => $val) {
                if (isset($val)) {
                    $val = $val;
                    if ($i == 0 && $val !== null) {
                        $data = $key . "='" . $val . "'";
                    } else {
                        $data .= "," . $key . " = '" . $val . "'";
                    }
                    $i++;
                }
            }
            $sql = "UPDATE " . $tbl_name . " SET " . $data . " WHERE " . $condition;
            return $sql;
        } else {
            return false;
        }
    }

    /**
     * @desc arraySort php二维数组排序 按照指定的key 对数组进行排序
     * @param array $arr 将要排序的数组
     * @param string $keys 指定排序的key
     * @param string $type 排序类型 asc | desc
     * @return array
     */
    static function arraySort($arr, $keys, $type = 'asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        $type == 'asc' ? asort($keysvalue) : arsort($keysvalue);
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }

    /**
     * 对银行卡号进行掩码处理
     * @param  string $bankCardNo 银行卡号
     * @param  string $sigin 替换的字符
     * @return string  掩码后的银行卡号
     */
    static function formatBankCardNo($bankCardNo, $sigin = "*")
    {
        $str_length = iconv_strlen($bankCardNo, "UTF-8");
        if ($str_length <= 8) {
            return $bankCardNo;
        }
        $returnStr = '';
        for ($x = 0; $x < 12; $x++) {
            if ($x == 4 || $x == 8) {
                $returnStr .= " ";
            }
            $returnStr .= $sigin;
        }
        //截取银行卡号前4位
        $prefix = substr($bankCardNo, 0, 4);
        //截取银行卡号后4位
        $suffix = substr($bankCardNo, -4, 4);
        $maskBankCardNo = $prefix . " $returnStr " . $suffix;
        return $maskBankCardNo;
    }

    /**
     * 16-19 位卡号校验位采用 Luhm 校验方法计算：
     * @param $no
     * @return bool
     */
    static function checkbank($no = "")
    {
        if (empty($no)) {
            return false;
        }
        // $arr_no = str_split($no);
        // $last_n = $arr_no[count($arr_no) - 1];
        // krsort($arr_no);
        // $i = 1;
        // $total = 0;
        // foreach ($arr_no as $n) {
        //     if ($i % 2 == 0) {
        //         $ix = $n * 2;
        //         if ($ix >= 10) {
        //             $nx = 1 + ($ix % 10);
        //             $total += $nx;
        //         } else {
        //             $total += $ix;
        //         }
        //     } else {
        //         $total += $n;
        //     }
        //     $i++;
        // }
        // $total -= $last_n;
        // $x = 10 - ($total % 10);
        // if ($x == $last_n) {
        //     return true;
        // } else {
        //     return false;
        // }

        $arr_no = str_split($no);
        $last_n = $arr_no[count($arr_no)-1];
        krsort($arr_no);
        $i = 1;
        $total = 0;
        foreach ($arr_no as $n){
            if($i%2==0){
                $ix = $n*2;
                if($ix>=10){
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                }else{
                    $total += $ix;
                }
            }else{
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $total *= 9;
        if ($last_n == ($total%10)) {
            return true;
        } else {
            return false;
        }
    }
   /**
     *数字金额转换成中文大写金额的函数
     *String Int  $num  要转换的小写数字或小写字符串
     *return 大写字母
     *小数位为两位
     **/
    static function toChineseNumber($num){
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿";
        $num = round($num, 2);
        $num = $num * 100;
        if (strlen($num) > 10) {
            return "数据太长，没有这么大的钱吧，检查下";
        }
        $i = 0;
        $c = "";
        while (1) {
            if ($i == 0) {
                $n = substr($num, strlen($num)-1, 1);
            } else {
                $n = $num % 10;
            }
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            $num = $num / 10;
            $num = (int)$num;
            if ($num == 0) {
                break;
            }
        }
        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            $m = substr($c, $j, 6);
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j-3;
                $slen = $slen-3;
            }
            $j = $j + 3;
        }

        if (substr($c, strlen($c)-3, 3) == '零') {
            $c = substr($c, 0, strlen($c)-3);
        }
        if (empty($c)) {
            return "零元整";
        }else{
            return $c . "整";
        }
    }
	 /**
     * 去除二维数组的重复项
     * @param $arr
     * @param $key
     * @return mixed
     */
    static public function assoc_unique($arr, $key) {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;
    }
	/**
     * 判断一个字符串是否属于序列化后的数据
     * @param $data
     * @return bool
     */
    static function is_serialized($data) {
        $data = trim( $data );
        if ( 'N;' == $data )
            return true;
        if ( !preg_match( '/^([adObis]):/', $data, $badions ) )
            return false;
        switch ( $badions[1] ) {
            case 'a' :
            case 'O' :
            case 's' :
                if ( preg_match( "/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data ) )
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if ( preg_match( "/^{$badions[1]}:[0-9.E-]+;\$/", $data ) )
                    return true;
                break;
        }
        return false;
    }
}
