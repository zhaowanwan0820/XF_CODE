<?php

namespace task\lib;

use NCFGroup\Common\Library\Api;
use libs\web\Action;

class ApiAction extends Action {
    protected $params = [];

    public function __construct() {
        $in = file_get_contents('php://input');
        $this->params = json_decode($in, true);
    }

    public function init() {
        if ($this->params === null) {
            throw new \Exception('The JSON body sent to the server was unable to be parsed.', 409);
        }

        // 验证请求
        if (!Api::instance('rpc')->verify($this->params)) {
            throw new \Exception('sign verify error', 400);
        }
    }

    public function show_exception(\Exception $e) {
        $this->errorCode = $e->getCode();
        $this->errorMsg = $e->getMessage();
        $this->_display();
    }

    public function _after_invoke() {
        $this->_display();
    }

    private function _display() {
        // 如果Http头未发送 才设置Http头
        if (!headers_sent()) {
            header("Content-type: application/json");
        }
        echo json_encode(array(
            'data'=>$this->json_data,
            'errorCode'=>$this->errorCode,
            'errorMsg'=>$this->errorMsg
        ));
    }

    public function getParam($key = '', $default = '') {
        if (empty($key)) {
            return $this->params;
        }

        return isset($this->params[$key]) ? $this->params[$key] : $default;
    }
}
