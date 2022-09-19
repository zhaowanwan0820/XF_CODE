<?php
namespace core\service\supervision;

use core\service\BaseService;
use libs\utils\Logger;
use libs\utils\Monitor;
use libs\common\ErrCode;
use core\enum\SupervisionEnum;
use NCFGroup\Common\Library\StandardApi;

/**
 * P2P存管服务基类
 *
 */
abstract class SupervisionBaseService extends BaseService {

    protected $api;

    public $response = array(
        'status' => SupervisionEnum::RESPONSE_SUCCESS,
        'respCode' => SupervisionEnum::RESPONSE_CODE_SUCCESS,
        'respMsg' => '',
    );

    public function __construct($apiType = StandardApi::SUPERVISION_GATEWAY)
    {
        $this->api = StandardApi::instance($apiType);
        $this->api->setLogId(Logger::getLogId());
        $this->api->setMonitor(new Monitor());
    }

    public function getApi() {
        return $this->api;
    }

    protected function setResponseStatus($status) {
        $this->response['status'] = $status;
        return true;
    }

    protected function setResponseCode($code = '00000') {
        $this->response['respCode'] = $code;
        return true;
    }

    protected function setResponseMessage($message = '') {
        $this->response['respMsg'] = $message;
        return true;
    }

    protected function setResponseData($data) {
        $this->response['data'] = $data;
        return true;
    }

    protected function unsetResponseData() {
        unset($this->response['data']);
        return true;
    }

    protected function getResponse() {
        return $this->response;
    }

    /**
     * 返回成功消息
     * @return  array Response
     */
    public function responseSuccess($data = []) {
        $this->setResponseStatus(SupervisionEnum::RESPONSE_SUCCESS);
        $this->setResponseCode(SupervisionEnum::RESPONSE_CODE_SUCCESS);
        $this->setResponseMessage('成功');
        $this->setResponseData($data);
        if (empty($data)) {
            $this->unsetResponseData();
        }
        return $this->getResponse();
    }

    /**
     * 返回操作失败信息
     * 返回指定的错误号和错误信息
     * @param string $errCode 错误号(定义在\libs\common\ErrCode.php)
     * @param string $errMsg 错误信息(定义在\libs\common\ErrCode.php)
     * @return array
     */
    public function responseFailure($errCode, $errMsg) {
        $this->setResponseStatus(SupervisionEnum::RESPONSE_FAILURE);
        $this->setResponseCode((!empty($errCode) ? $errCode : SupervisionEnum::RESPONSE_CODE_FAILURE));
        $this->setResponseMessage((!empty($errMsg) ? $errMsg : ''));
        $this->unsetResponseData();
        return $this->getResponse();
    }

    /**
     * 返回进行中消息
     * @return  array Response
     */
    public function responseProsessing($data = []) {
        $this->setResponseStatus(SupervisionEnum::RESPONSE_PROCESSING);
        $this->setResponseCode(SupervisionEnum::RESPONSE_CODE_PROCESSING);
        $this->setResponseMessage('进行中');
        $this->setResponseData($data);
        if (empty($data)) {
            $this->unsetResponseData();
        }
        return $this->getResponse();
    }

    /**
     * 检查platform 是否正确
     * @throws \Exception
     * @return boolean
     */
    protected function checkPlatform($platform) {
        if (!in_array(strtolower($platform), array('pc', 'h5', 'android', 'ios'))) {
            return false;
        }
        return true;
    }

    /**
     * 存管-是否开启新的快速受托提现的开关
     * 0:关闭1:开启
     * @return boolean
     */
    public function isWithdrawFastOpen() {
        // 获取普惠配置信息
        $withdrawFastOpen = app_conf('SUPERVISION_WITHDRAWFAST_SWITCH');
        if((int)$withdrawFastOpen === 1) {
            return true;
        }
        return false;
    }

   /*
     * 抛出异常
     * @param string
     * @throws \Exception
     */
    protected function exception($key) {
        throw new \Exception(ErrCode::getMsg($key), ErrCode::getCode($key));
    }
}
