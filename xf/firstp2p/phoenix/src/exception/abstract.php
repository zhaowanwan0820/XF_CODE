<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-24 13:41:53
 * @encode UTF-8编码
 */
class P_Exception_Abstract extends Exception {

    protected $_level = false;

    public function __construct($level, $message = "", $code = P_Conf_Globalerrno::OK, $need_log = false, $previous = null) {
        $this->_level = $level;
        parent::__construct(ucfirst(strtolower($message)), $code, $previous);
        P_Exception_Handler::set_exception($this);
        if ($need_log) {
            P_Log_Slogs::at("level={$level}, message={$message}, code={$code}");
        }
    }

    public function get_level() {
        return $this->_level;
    }

}
