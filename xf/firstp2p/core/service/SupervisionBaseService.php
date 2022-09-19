<?php
namespace core\service;

use libs\utils\Logger;
use libs\common\ErrCode;
use NCFGroup\Common\Library\StandardApi;

/**
 * P2P存管服务基类
 *
 */
class SupervisionBaseService extends BaseService {

    const RESPONSE_CODE_SUCCESS = '00';
    const RESPONSE_CODE_FAILURE = '01';
    const RESPONSE_CODE_PROCESSING = '02';

    const RESPONSE_SUCCESS = 'S';
    const RESPONSE_FAILURE = 'F';
    const RESPONSE_PROCESSING = 'I';

    const NOTICE_SUCCESS = 'S';
    const NOTICE_FAILURE = 'F';
    const NOTICE_PROCESSING = 'I';
    const NOTICE_CANCEL = 'C'; //取消状态，对账使用


    protected $api = null;

    //缓存开关
    private static $isSupervisionOpen = null;

    public function __construct()
    {
        $this->api = StandardApi::instance(StandardApi::SUPERVISION_GATEWAY);
        $this->api->setLogId(Logger::getLogId());
    }

    public function getApi()
    {
        return $this->api;
    }

    public $response = array(
        'status' => self::RESPONSE_SUCCESS,
        'respCode' => self::RESPONSE_CODE_SUCCESS,
        'respMsg' => '',
    );

    //免密投资权限
    const GRANT_INVEST = 'INVEST';

    //免密提现权限
    const GRANT_WITHDRAW = 'WITHDRAW';

    //免密提现至超级账户权限
    const GRANT_WITHDRAW_TO_SUPER = 'WITHDRAW_TO_SUPER';

    //免密提现至银信通账户权限
    const GRANT_WITHDRAW_TO_YXT = 'WITHDRAW_TO_YXT';

    //免密受托支付权限
    const GRANT_WITHDRAW_TO_ENTRUSTED = 'WITHDRAW_TO_ENTRUSTED';

    public function setResponseStatus($status) {
        $this->response['status'] = $status;
        return true;
    }

    public function setResponseCode($code = '00000') {
        $this->response['respCode'] = $code;
        return true;
    }

    public function setResponseMessage($message = '') {
        $this->response['respMsg'] = $message;
        return true;
    }

    public function setResponseData($data) {
        $this->response['data'] = $data;
        return true;
    }

    public function unsetResponseData() {
        unset($this->response['data']);
        return true;
    }

    public function getResponse() {
        return $this->response;
    }

    /**
     * 返回成功消息
     * @return  array Response
     */
    public function responseSuccess($data = []) {
        $this->setResponseStatus(self::RESPONSE_SUCCESS);
        $this->setResponseCode(self::RESPONSE_CODE_SUCCESS);
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
        $this->setResponseStatus(self::RESPONSE_FAILURE);
        $this->setResponseCode((!empty($errCode) ? $errCode : self::RESPONSE_CODE_FAILURE));
        $this->setResponseMessage((!empty($errMsg) ? $errMsg : ''));
        $this->unsetResponseData();
        return $this->getResponse();
    }

    /**
     * 返回进行中消息
     * @return  array Response
     */
    public function responseProsessing($data = []) {
        $this->setResponseStatus(self::RESPONSE_PROCESSING);
        $this->setResponseCode(self::RESPONSE_CODE_PROCESSING);
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
    public function checkPlatform($platform) {
        if (!in_array($platform, array('pc', 'h5'))) {
            return false;
        }
        return true;
    }

    /**
     * 存管功能开关
     * 加上ABtest
     * @return boolean
     */
    public function isSupervisionOpen()
    {
        if (self::$isSupervisionOpen === null) {
            self::$isSupervisionOpen = false;
            if((int)app_conf('SUPERVISION_SWITCH') === 1 || \libs\utils\ABControl::getInstance()->hit('supervisionOpen')) {
                self::$isSupervisionOpen = true;
                Logger::info(sprintf("isSupervisionOpen. userId: %s", isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : 0));
            }
        }
        return self::$isSupervisionOpen;
    }

    /**
     * 存管-控制PC取消授权的开关
     * 0:不显示1:显示
     * @return boolean
     */
    public function isCancelAuthOpen()
    {
        if((int)app_conf('SUPERVISION_PCCANCELAUTH_SWITCH') === 1) {
            return true;
        }
        return false;
    }

    /**
     * 存管-是否开启新的快速受托提现的开关
     * 0:关闭1:开启
     * @return boolean
     */
    public function isWithdrawFastOpen()
    {
        // 获取普惠配置信息
        $withdrawFastOpen = \core\service\ncfph\SupervisionService::GetAppConf('SUPERVISION_WITHDRAWFAST_SWITCH');
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