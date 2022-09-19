<?php
/* ExceptionLib.php ---
 *
 * Filename: ExceptionLib.php
 * Description: <put the file description here>
 * Author: zhounew
 * Maintainer: <put maintainers here>
 * Created: 2014-10-03 22:09
 * Version: v1.0
 *
 * Copyright (c) 2014-2020 NCFGroup.com
 * http://www.firstp2p.com
 */
namespace NCFGroup\Common\Library;

class ExceptionLib
{
    public static function toHtml(\Exception $e) {
        $arr = array("File: " . $e->getFile(),
                     "Line: " . $e->getLine(),
                     "Code: " . $e->getCode(),
                     "Message: " . $e->getMessage());
        return implode("<br>", $arr);
    }

    public static function toString(\Exception $e) {
        $arr = array("File: " . $e->getFile(),
            "Line: " . $e->getLine(),
            "Code: " . $e->getCode(),
            "Message: " . $e->getMessage());
        return implode(PHP_EOL, $arr);
    }
}