<?php
namespace core\service\user;

class BOFactory
{
    static $_intances = array();

    public static function instance($boType = 'web')
    {
        if (isset(self::$_intances[$boType]))
        {
            return self::$_intances[$boType];
        }
        // 注册业务对象
        $bo = null;
        $bo = new WebBO($boType);

        if ($bo) {
            self::$_intances[$boType] = $bo;
            return $bo;
        }
        return null;
    }
}
