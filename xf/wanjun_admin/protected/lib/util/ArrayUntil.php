<?php

class ArrayUntil
{
    /**
     * 模拟低版本array_column函数
     * @param $input
     * @param $columnKey
     * @param null $indexKey
     * @return array
     */
    public static function array_column($input, $columnKey, $indexKey = null)
    {
        if (!function_exists('array_column')) {
            $result = [];
            foreach ($input as $arr) {
                if (!is_array($arr)) {
                    continue;
                }

                if (is_null($columnKey)) {
                    $value = $arr;
                } else {
                    $value = $arr[$columnKey];
                }

                if (!is_null($indexKey)) {
                    $key = $arr[$indexKey];
                    $result[$key] = $value;
                } else {
                    $result[] = $value;
                }
            }

            return $result;
        } else {
            return array_column($input, $columnKey, $indexKey);
        }
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
}