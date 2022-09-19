<?php

/**
 * 人脸识别
 */
namespace core\service\face\cmd;

use libs\utils\Logger;
use libs\utils\Risk;
use core\service\UserTokenService;
use core\service\risk\RiskServiceFactory;
use core\service\face\FaceService;
use core\service\face\cmd\AbstractCmd;

/**
 * 绑定充值卡
 */
class BindChargeCmd extends AbstractCmd {
    // 绑定充值卡
    protected $type = FaceService::TYPE_BIND_CHARGE;

    /**
     * 检查check
     */
    public function check() {
        if (empty($this->params['token'])) {
            Logger::info("Face Check. token is required. type:BindCharge");
            return $this->error("ERR_PARAMS_ERROR", 'token is required');
        }

        // 开关关闭，直接返回不用人脸
        if (!FaceService::isFaceSwitchOn($this->type)) {
            return $this->buildCheckResult(0);
        }

        $token = $this->params['token'];
        // 通过token获取用户信息
        $tokenInfo = (new UserTokenService())->getUserByToken($token);
        if (!empty($tokenInfo['code'])) {
            return $this->error('ERR_GET_USER_FAIL', $tokenInfo['reason']);
        }

        $user = $tokenInfo['user'];
        $mobile = $user['mobile'];
        // 检查账号是否被冻结
        $freeze = FaceService::checkFreeze($mobile);
        if ($freeze) {
            return $this->buildCheckResult(0, 1, $freeze);
        }

        // 绑定充值卡，开启人脸识别
        return $this->buildCheckResult(1);
    }

    /**
     * 比较
     */
    public function compare() {
        if (empty($this->params['token'])) {
            Logger::info("Face Compare. token is required. type:BindCharge");
            return $this->error("ERR_PARAMS_ERROR", 'token is required');
        }

        $token = $this->params['token'];
        // 通过token获取用户信息
        $tokenInfo = (new UserTokenService())->getUserByToken($token);
        if (!empty($tokenInfo['code'])) {
            return $this->error('ERR_GET_USER_FAIL', $tokenInfo['reason']);
        }

        $user = $tokenInfo['user'];

        // 需要人脸识别
        if (FaceService::needVeriFace($user['real_name'], $user['idno'], $user['id_type'])) {
            return $this->faceImageVerify($user['real_name'], $user['idno'], $user['mobile'], $user['id']);
        }

        // 活体识别
        return $this->livenessDetect($user['mobile'], $user['id']);
    }
}
