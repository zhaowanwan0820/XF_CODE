<?php
/**
 * Created by PhpStorm.
 * User: Yihui
 * Date: 2016/1/27
 * Time: 14:30
 */

namespace libs\utils;

class AntiSqlInjection {

    public static $sqlReplacePatterns = array(
        "\'" => "\'",  /* pattern = ', replace = \' */
        "\"" => "\"", /* pattern = ", replace = \" */
        "\\\'" => "\\\'", /* pattern = \', replace = \\\' */
        "\\\"" => "\\\"",  /* pattern = \", replace = \\\" */
        "&&" => "and", /* pattern = &&, replace = and */
        "||" => "or", /* pattern = ||, replace = or */
    );

    public static $sqlInjectionPatterns = array(
        '/\s+or\s+/i',
        '/\s+and\s+/i',
        "/select\s+.*?\s+from.*/i",
        "/insert\s+/i",
        "/update\s+.*?\s+set\s*=.*/i",
        "/delete\s+from.*/i",
        "/truncate\s+/i",
        "/\s+count\(.*/i",
        "/\s+drop\s+/i",
        "/fetch.*?\s+into\s+/i",
        "/union\(.*/i",
        "/union\s+select.*/i",
        "/varchar\(\d+\)/i",
        "/\s+declare\s+/i",
        "/\schar\(/i",
        "/\s+char\s+/i",
    );

    public static function checkSqlInjection($str) {
        foreach(self::$sqlInjectionPatterns as $pattern) {
            if(preg_match($pattern, $str)) {
                return false;
            }
        }
        return true;
    }

    public static function checkInput(array $input, $level = 1) {
        foreach($input as $key => $value) {
            if (is_array($value)) {
                // 这里防止循环攻击，控制递归深度
                if ($level < 10) {
                    $res = self::checkInput($value, $level + 1);
                    if ($res === false) {
                        return false;
                    }
                }
            } else {
                $value = urldecode($value);
                if (!self::checkSqlInjection($value)) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function checkAllInput() {
        if(!empty($_GET)) {
            $noInject = self::checkInput($_GET);
            if(!$noInject) {
                return false;
            }
        }
        if(!empty($_POST)) {
            $noInject = self::checkInput($_POST);
            if(!$noInject) {
                return false;
            }
        }
        if(!empty($_COOKIE)) {
            $noInject = self::checkInput($_COOKIE);
            if(!$noInject) {
                return false;
            }
        }
        return true;
    }

}
