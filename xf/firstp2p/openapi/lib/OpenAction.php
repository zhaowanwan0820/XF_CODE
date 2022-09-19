<?php

namespace openapi\lib;

require_once(APP_ROOT_PATH . 'libs/interceptors/LockInterceptor.php');

use libs\interceptors\LockInterceptor;

/**
 * 继承自Phoenix框架P_Action的Action实现
 * 主要是调整了模板路径规则
 *
 *
 *
 * */
class OpenAction extends \P_Action {

    public function __construct() {

    }

    protected function logInit() {

    }

    public function init() {

    }

    /**
     * 执行业务逻辑，通常是一个RPC调用
     *
     * @return void
     * */
    public function invoke() {

    }

    public function _before_invoke() {
        return true;
    }

    public function authCheck() {
        return true;
    }

    public function execute() {
        $this->logInit();

        if ($this->init() !== false) {
            try {
                if ($this->before() && $this->authCheck()) {
                    $this->invoke();
                }
            } catch (\Exception $exc) {
//                $this->template = NULL;
                $err = \openapi\conf\Error::get($exc->getMessage());
                $this->errorCode = $err['errorCode'];
                $this->errorMsg = $err['errorMsg'];
                $this->excCode = $exc->getCode();
                $this->excMsg = $exc->getMessage();
            }
        }
        $this->after();
        $this->log();
    }

    /**
     * 预留统一记录日志的方法
     */
    protected function log() {

    }

    public function _after_invoke() {
//        if (!empty($this->template)) {
//            $this->tpl->display($this->template);
//        }
    }

    private function before() {
        return $this->_before_invoke() && $this->beforeCommon();
    }

    private function after() {
        $this->afterCommon();
        $this->_after_invoke();
    }

    private function beforeCommon() {
        $successful = LockInterceptor::before(get_class($this));
        if (!$successful) {
            header("HTTP/1.1 423 Locked");
            return false;
        }
        return true;
    }

    private function afterCommon() {
        return LockInterceptor::after();
    }

    public function __destruct() {
        //此代码只是为了防止action中直接用e xit而造成锁不能立即释放的情况.
        LockInterceptor::after();
    }

}

// END class Action extends P_Action
