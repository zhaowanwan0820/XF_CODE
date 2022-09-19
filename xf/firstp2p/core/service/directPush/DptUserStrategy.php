<?php
namespace core\service\directPush;

use core\service\directPush\DptStrategy;

class DptUserStrategy extends DptStrategy
{
    /**
     * 存储在数据库conditions字段的参数
     */
    public $fields = array(
        'send_type_user' => '用户类型',
        'birth_type' => '生日类型',
    );

    /**
     * 请求接口的参数
     */
    public $requestParams = array(
        'time_start',
        'time_end',
        'scope',
        'scope_ids'
    );

    const SPARK_SQL_TYPE = 3;

    /**
     * getParams
     *
     * @param mixed $data
     * @access public
     * @return array
     */
    public function getParams($data)
    {
        $request = array();
        parse_str($data['conditions'], $conditions);
        $ymd = date('Y-m-d', $data['start_time'] + 28800);
        $w = date('w',  $data['start_time'] + 28800);
        $t = date('t',  $data['start_time'] + 28800);
        list($year, $month, $day) = explode('-', $ymd);
        if ($conditions['birth_type'] == 1) { //获取当前的开始结束时间
            $data['time_start'] = mktime(0, 0, 0, $month, $day, $year);
            $data['time_end'] = mktime(23, 59, 59, $month, $day, $year);
        } elseif ($conditions['birth_type'] == 2) { //获取本周的开始结束时间
            $data['time_start'] = mktime(0, 0, 0, $month, $day, $year) - ($w ? ($w - 1) : 6) * 86400;
            $data['time_end'] = $data['start_time'] + 604799;
        } elseif ($conditions['birth_type'] == 3) { //获取本月的开始结束时间
            $data['time_start'] = mktime(0, 0, 0, $month, 1, $year);
            $data['time_end'] = mktime(23, 59, 59, $month, $t, $year);
        }
        foreach ($this->requestParams as $field) {
            if ($field == 'scope') {
                $value = $data[$field."_type"];
            } else {
                $value = $data[$field];
            }
            if ($value !== null && $value !== '') {
                $request[$field] = $value;
            }
        }

        if (!$request['scope']) {
            unset($request['scope_type'], $request['scope_ids']);
        }

        $request['spark_sql_type'] = self::SPARK_SQL_TYPE;

        return $request;
    }

    /**
     * buildConditonString
     *
     * @param mixed $data
     * @access public
     * @return string
     */
    public function buildConditonString($data)
    {
        $conditions = array();
        foreach ($this->fields as $field => $name) {
            $conditions[$field] = $data[$field];
        }

        if ($conditions['scope_type'] == 0) {
            unset($conditions['scope_ids']);
        }

        return http_build_query($conditions);
    }

    /**
     * buildInfo
     *
     * @param mixed $data
     * @access public
     * @return void
     */
    public function buildInfo($data)
    {
        $str = '';
        if ($data['send_type_user'] == 1) {
            $str = '生日';
            if ($data['birth_type'] == 1) {
                $str .= ' 当天';
            } elseif ($data['birth_type'] == 2) {
                $str .= ' 本周';
            } else {
                $str .= ' 本月';
            }
        }
        return $str;
    }
}

