<?php

namespace NCFGroup\Common\Library;

/**
 * Common中公共组件打日志专用
 */
class CommonLogger
{

    public static function info($content)
    {
        //p2p框架
        if (class_exists('\libs\utils\Logger')) {
            \libs\utils\Logger::info($content);
        //phalcon框架
        } elseif (class_exists('\NCFGroup\Common\Library\Logger')) {
            \NCFGroup\Common\Library\Logger::info($content);
        }
    }

}
