<?php
/**
 * ItzUtil
 *
 *
 */
class ItzUtil{

    //获取上传图片的缩略图地址，可能没有缩略图
    public function getThumbImg($img_url){
        $position  = strripos($img_url,"/");
        if($position != false){
            return $img_url = substr($img_url, 0,$position+1)."thumb_".substr($img_url,$position+1);
        }else{
            return $img_url;
        }
    }
    //根据权限获取当前按钮是否显示
    static public function button_exists($code) {
        //获取当前账号所有子权限
        $authList = \Yii::app()->user->getState('_auth');
        if (!empty($authList) && strstr($authList,$code) || empty($authList)) {
           return true;
        }
        return false;
    }
    //生成唯一的guid
    function create_guid() {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);// "}"
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
        if(!function_exists("array_column"))
        {
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
        }else{
            return array_column($input, $columnKey,$indexKey);
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
    static public function checkJson($string, $filed){
        if(self::is_json($string)){
            $jsonArr = json_decode($string,true);
            foreach($jsonArr as $k => $v){
                if(empty($v[$filed]) || !isset($v[$filed])){
                    return false;
                }
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
    static public function enterTenderIdFnLock($exchange_id, $fnLock_pre, $is_lock = 1){
        $exchange_id = (int)$exchange_id;
        if($exchange_id <= 0) {
            return false;
        }
        if(!in_array($is_lock,[1,2])){
            return false;
        }
        $fnLock_tenderid = $fnLock_pre.$exchange_id.'.pid';
        if(empty($fnLock_tenderid)){
            return false;
        }
        if($is_lock == 1){
            $fpLock = fopen($fnLock_tenderid, 'w+');
            if($fpLock){
                if (flock($fpLock, LOCK_EX | LOCK_NB)){
                    return ['fnLock' => $fnLock_tenderid, 'fpLock' => $fpLock];
                }
            }
        }elseif($is_lock == 2){
            if(is_file($fnLock_tenderid)){
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
    static public function releaseLock($fpLock, $fnLock){
        if (!$fpLock){
            return false;
        }
        if(is_file($fnLock)){
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
                $tempArray[] = "'".implode("','", $val)."'";
            }
            $s_fields = "(" . implode(",", $keys) . ")";
            $s_values = "(".implode("),(", $tempArray).")";
            $sql = "INSERT INTO
                       $tbl_name
                       $s_fields
                   VALUES
                       $s_values";
            return $sql;
        }else{
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
    /**
     * 格式化金额
     *
     * @param int $money
     * @param int $len
     * @param string $sign
     * @return string
     */
    static function format_money($money, $len =2, $sign = ','){
        $negative =  '';
        $int_money = intval(abs($money));
        $len = intval(abs($len));
        $decimal = '';//小数
        if ($len > 0) {
            $decimal = '.'.substr(sprintf('%01.'.$len.'f', $money),-$len);
        }
        $tmp_money = strrev($int_money);
        $strlen = strlen($tmp_money);
        $format_money = '';
        for ($i = 3; $i < $strlen; $i += 3) {
            $format_money .= substr($tmp_money,0,3).',';
            $tmp_money = substr($tmp_money,3);
        }
        $format_money .= $tmp_money;
        $format_money = strrev($format_money);
        return $negative.$format_money.$decimal;
    }
    /**
     * 过滤数组中为空的值
     * @param $arr
     * @return string
     */
    static function checkGetArr($arr)
    {
        if(!is_array($arr)){
            return false;
        }
        $arr_new = [];
        foreach($arr as $key => $value){
            if($arr[$key] != ''){
                $arr_new[$key] = $value;
            }
        }
        return $arr_new;
    }

    /**
     * 二维数组的差集|比较二维数组的不同 array_diff
     * @param $array1
     * @param $array2
     * @return array
     */
    static function array_diff_assoc2_deep($array1, $array2) {
        $ret = array();
        foreach ($array1 as $k => $v) {
            if (!isset($array2[$k])) $ret[$k] = $v;
            else if (is_array($v) && is_array($array2[$k])) $ret[$k] = self::array_diff_assoc2_deep($v, $array2[$k]);
            else if ($v !=$array2[$k]) $ret[$k] = $v;
            else
            {
                unset($array1[$k]);
            }

        }
        return $ret;
    }
}
