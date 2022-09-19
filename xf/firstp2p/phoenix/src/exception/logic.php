<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-2 12:56:48
 * @encode UTF-8编码
 */
class P_Exception_Logic extends P_Exception_Abstract {

    public function __construct($message = "", $code = P_Conf_Globalerrno::OK, $need_log = false, $previous = null) {
        parent::__construct(P_Conf_Exception::LEVEL_LOGIC, $message, $code, $need_log, $previous);
    }

}
