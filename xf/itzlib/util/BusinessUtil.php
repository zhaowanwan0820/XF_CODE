<?php

/**
 * 业务上会用到的共用的方法抽出
 * Created by PhpStorm.
 * User: ju
 * Date: 2017/8/23
 * Time: 16:21
 */
class BusinessUtil
{
    /**
     * 根据项目天数获取加息利率
     * @param $day
     * @return string
     */
    static function getIncreaseApr($day)
    {
        $apr = '0.00';
        if ($day < 105) {
            $apr = '1.50';
        }
        if ($day < 45) {
            $apr = '3.00';
        }
        if ($day < 35) {
            $apr = '4.00';
        }
        if ($day < 25) {
            $apr = '5.00';
        }
        if ($day <= 0) {
            $apr = '0.00';
        }
        return $apr;
    }
}