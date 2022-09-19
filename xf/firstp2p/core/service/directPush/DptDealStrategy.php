<?php
namespace core\service\directPush;

use core\service\directPush\DptStrategy;

class DptDealStrategy extends DptStrategy
{
    /**
     * 存储在数据库conditions字段的参数
     */
    public $fields = array(
        'count_start_deal' => '次数大于',
        'count_end_deal' => '次数小于',
        'money_start_deal' => '金额大于',
        'money_end_deal' => '金额小于',
        'scope_time' => '时间类型',
        'time_start' => '开始时间',
        'time_end' => '结束时间',
        'days_start' => '过去天数大于',
        'days_end' => '过去天数小于',
        'is_yield_deal' => '年化',
        'send_type_deal' => '类型',
        'norepeat_days' => '未复投天数',
    );

    /**
     * 请求接口的参数
     */
    public $requestParams = array(
        'count_start',
        'count_end',
        'money_start',
        'money_end',
        'time_start',
        'time_end',
        'is_yield',
        'norepeat_days',
        'scope',
        'scope_ids'
    );

    const SPARK_SQL_TYPE_DEAL = '1';
    const SPARK_SQL_TYPE_NOREPEAT = '2';

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
        $data = array_merge($data, $conditions);
        foreach ($this->requestParams as $field) {
            if (in_array($field, array('count_start', 'count_end', 'money_start', 'money_end', 'is_yield'))) {
                $value = $data[$field."_deal"];
            } elseif ($field == 'scope') {
                $value = $data[$field."_type"];
            } else {
                $value = $data[$field];
            }
            if ($value !== null && $value !== '') {
                $request[$field] = $value;
            }
        }
        $ymd = date('Y-m-d', $data['start_time'] + 28800);
        list($year, $month, $day) = explode('-', $ymd);
        if ($conditions['scope_time']== '1') {
            $request['time_start'] = mktime(0, 0, 0, $month, $day, $year) - $conditions['days_start'] * 86400 - 1;
            $request['time_end']   = mktime(0, 0, 0, $month, $day, $year) - $conditions['days_end'] * 86400 - 1;
        } elseif ($conditions['scope_time'] == '2') {
            $request['time_start'] += 28800;
            $request['time_end'] += 28800;
        } else {
            $request['time_end']   = mktime(0, 0, 0, $month, $day, $year) - $conditions['days_end'] * 86400 - 1;
        }

        if (!$request['scope']) {
            unset($request['scope_type'], $request['scope_ids']);
        }

        if ($data['send_type_deal'] == '2') {
            if ($request['norepeat_days']) {
                $request['days'] = $request['norepeat_days'];
            }
            unset($request['norepeat_days']);
            $request['spark_sql_type'] = self::SPARK_SQL_TYPE_NOREPEAT;
        } else {
            $request['spark_sql_type'] = self::SPARK_SQL_TYPE_DEAL;
        }

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
            if ($data[$field] === '' || $data[$field] === null) {
                continue;
            }
            $conditions[$field] = $data[$field];
        }

        if ($conditions['scope_time']== '0') {
            unset($conditions['time_start'], $conditions['time_end']);
            unset($conditions['days_start'], $conditions['days_end']);
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
        if ($data['send_type_deal'] == 1) {
            $str = '投资';
        } elseif ($data['send_type_deal'] == 2) {
            $str = '未复投';
        }
        unset($data['send_type_deal']);
        foreach ($this->fields as $field => $name) {
            if ($data[$field] != '' && $data[$field] != '0') {
                if ($field == 'scope_time') {
                    if ($data[$field] == 1) {
                        $str .= '<br> 时间段';
                        if ($data['days_start'] > 0) {
                            $str .= ' 天数大于'.$data['days_start'];
                        }
                        if ($data['days_end'] > 0) {
                            $str .= ' 天数小于'.$data['days_end'];
                        }
                        $str .= "<br>";
                    } elseif ($data[$field] == 2) {
                        $str .= '<br> 时间段';
                        if ($data['time_start'] > 0) {
                            $str .= ' 开始时间'.date('Y-m-d H:i:s', $data['time_start']);
                        }
                        if ($data['time_end'] > 0) {
                            $str .= ' 结束时间'.date('Y-m-d H:i:s', $data['time_end']);
                        }
                        $str .= "<br>";
                    }
                    unset($data['scope_time'], $data['time_start'], $data['time_end'], $data['days_start'], $data['days_end']);
                    continue;
                }
                if ($field == 'is_yield_deal') {
                    if ($data['is_yield_deal']) {
                        $str .= ' 年化';
                    }
                    unset($data['is_yield_deal']);
                    continue;
                }
                $str .= ' ' . $name . $data[$field];
            }
        }
        return trim($str);
    }

}

