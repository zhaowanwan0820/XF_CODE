<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 1/8/2016
 * Time: 15:10
 */

namespace iauth\helpers;


class Number
{
    /**
     * 判断是否是整型的主键 （大于 0 的正整数）
     * @param $num
     * @return bool
     */
    public static function isIntPk($num)
    {
        return filter_var($num, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
    }

    public static function intDiv($num, $div)
    {
        return (int)($num / $div);
    }
}