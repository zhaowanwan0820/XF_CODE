<?php

/**
 * 修改银行卡
 */
namespace core\service\face\cmd;

use libs\utils\Logger;
use core\service\face\FaceService;
use core\service\UserTokenService;
use core\service\face\cmd\AbstractCmd;

class ChangeBankCardCmd extends AbstractCmd {
    // 修改银行卡
    protected $type = FaceService::TYPE_CHANGE_BANKCARD;

    /**
     * 检查check
     */
    public function check() {
        if (empty($this->params['token'])) {
            Logger::info("Face Check. token is required. type:ChangeBankCard");
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
        // 检查账号是否被冻结
        $freeze = FaceService::checkChangeBankCardFreeze($user['mobile']);
        if ($freeze) {
            return $this->error("ERR_MANUAL_REASON", $freeze);
        }

        // 修改银行卡，开启人脸识别
        return $this->buildCheckResult(1);
    }

    /**
     * 比较
     */
    public function compare() {
        if (empty($this->params['token'])) {
            Logger::info("Face Compare. token is required. type:ChangeBankCard");
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
        $res = $this->faceImageVerify(
            $user['real_name'],
            $user['idno'],
            $user['mobile'],
            $user['id']
        );

        return $res;
    }
}