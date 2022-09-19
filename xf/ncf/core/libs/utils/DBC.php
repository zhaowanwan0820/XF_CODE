<?php
namespace libs\utils;

class DBC
{
    public static $needDBC = 0;

    public static function requireTrue($express, $message = "")
    {
        if (!$express && self::$needDBC <= 0) {
            throw new AssertException($message);
        }
    }

    public static function requireFalse($express, $message = "")
    {
        if ($express && self::$needDBC <= 0) {
            throw new AssertException($message);
        }
    }
    public static function requireNotNull($obj, $message = "")
    {
        self::requireTrue($obj !== null, $message);
    }

    public static function requireNull($obj, $message = "")
    {
        self::requireTrue($obj === null, $message);
    }    

    public static function requireNotEmptyString($str, $message = "")
    {
        self::requireTrue($str !== null, $message);
        self::requireTrue(trim($str) != '', $message);
    }

    public static function requireNotEmptyArray($array, $message = "")
    {
        self::requireTrue(is_array($array), $message);
        self::requireNotEmpty($array, $message);
    }

    public static function requireNotEmpty($obj, $message = "")
    {
        self::requireTrue(!empty($obj), $message);
    }

    public static function requireEmpty($obj, $message = "")
    {
        self::requireTrue(empty($obj), $message);
    }

    public static function requireEquals($first, $second, $message = "")
    {
        self::requireTrue($first == $second, $message);
    }

    public static function requireNotEquals($first, $second, $message = "")
    {
        self::requireTrue($first != $second, $message);
    }

    public static function requireLess($first, $second, $message = "")
    {
        self::requireTrue($first < $second, $message);
    }

    public static function requireMore($first, $second, $message = "")
    {
        self::requireTrue($first > $second, $message);
    }

    public static function requireMoreThan($first, $second, $message = "")
    {
        self::requireTrue($first >= $second, $message);
    }

    public static function requireLessThan($first, $second, $message = "")
    {
        self::requireTrue($first <= $second, $message);
    }

    public static function requireBetween($obj, $min, $max, $message = "")
    {
        if (is_string($obj)) {
            $len = strlen($obj);
            self::requireTrue($len >= $min && $len <= $max, $message);
        } else {
            self::requireTrue($obj >= $min && $obj <= $max, $message);
        }
    }

    public static function requireObjNotNull($obj)
    {
        foreach($obj as $key=>$value)
        {
            self::requireNotNull($value,"$key cannot null");
        }
    }

    public static function requireIn($obj, $array) 
    {
        if (in_array($obj, $array) == false) {
            throw new AssertException("$obj not in array :\n".print_r($array, true));
        }
    }

    public static function requireNum($str, $message='')
    {
        self::requireTrue(is_numeric($str), $message);
    }
}
