<?php

namespace NCFGroup\Ptp\Apis;

use libs\utils\Logger;
use core\service\AdunionDealService;

class OpenApi {

    protected $_params = [];

    public function __construct() {
        $content = file_get_contents('php://input');
        Logger::info("收到消息, 数据: " . $content);

        $content = json_decode($content, true);
        if (isset($content['Message']) && $content['Message']) {
            $this->_params = json_decode($content['Message'], true);
        }

        if (empty($this->_params)) {
            Logger::error("解析消息失败, 数据: " . $content);
            return $this->retFail();
        }
    }

    public function getParam($key = '', $default = '') {
        if (empty($key)) {
            return $this->_params;
        }

        return isset($this->_params[$key]) ? $this->_params[$key] : $default;
    }

    public function retSucc() {
        Logger::info("注册消息已成功处理");
        die(json_encode(['code' => 0, 'message' => 'success']));
    }

    public function retFail() {
        Logger::error("处理注册消息失败");
        die(json_encode(['code' => 10001, 'message' => 'error']));
    }

    public function recvRegMsg() {
        $service = new AdunionDealService();
        $result  = $service->addAdRecord($this->getParam());
        return $result ? $this->retSucc() : $this->retFail();
    }

}
