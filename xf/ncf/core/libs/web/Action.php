<?php
/**
 * Action class file.
 */

namespace libs\web;

\FP::import('libs.interceptors.LockInterceptor');
use libs\interceptors\LockInterceptor;

/**
 * Action基类
 */
class Action {
    protected $errorCode = 0;
    protected $errorMsg = '';
    protected $json_data = false;
    protected $log = [];

    public function __construct() {}
    protected function logInit() {}
    public function init() {}

    /**
     * 执行业务逻辑，通常是一个RPC调用
     *
     * @return void
     **/
    public function invoke() {}

    public function _before_invoke() {
        return true;
    }

    public function authCheck() {
        return true;
    }

    public function execute() {
        // 初始化日志
        $this->logInit();

        try {
            if ($this->init() !== false) {
                if ($this->before() && $this->authCheck()) {
                    $this->invoke();
                }
            }
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMsg = $e->getMessage();
        }

        $this->after();
    }

    /**
     * 预留统一记录日志的方法
     */
    protected function log() {}

    public function _after_invoke() {
        if(!empty($this->template)) {
            $this->tpl->display($this->tplStrategy());
        }
    }
    protected function tplStrategy() {
        return $this->template;
    }
    protected function before() {
        return $this->_before_invoke() && $this->beforeCommon();
    }

    protected function after() {
        $this->afterCommon();
        $this->_after_invoke();
    }

    private function beforeCommon() {
        if (!LockInterceptor::before(get_class($this))) {
            header("HTTP/1.1 423 Locked");
            return false;
        }
        return true;
    }

    private function afterCommon() {
        return LockInterceptor::after();
    }

    public function __destruct() {
        // 此代码只是为了防止action中直接用exit而造成锁不能立即释放的情况.
        LockInterceptor::after();
        $this->log();
    }
}
