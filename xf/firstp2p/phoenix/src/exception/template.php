<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-24 13:43:48
 * @encode UTF-8编码
 */
class P_Exception_Template extends P_Exception_Abstract {

    public function __construct($message = "", $code = P_Conf_Globalerrno::OK, $need_log = false, $previous = null) {
        parent::__construct(P_Conf_Exception::LEVEL_TEMPLATE, $message, $code, $need_log, $previous);
    }

}
