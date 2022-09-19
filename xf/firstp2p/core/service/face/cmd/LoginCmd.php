<?php

/**
 * 人脸识别
 */
namespace core\service\face\cmd;

use core\service\UserService;
use core\service\face\FaceService;
use core\service\face\cmd\AbstractCmd;
use libs\utils\Logger;

class LoginCmd extends AbstractCmd {
    // 登录
    protected $type = FaceService::TYPE_LOGIN;

    /**
     * 检查check
     */
    public function check() {
        // 开关关闭，直接返回不用人脸
        if (!FaceService::isFaceSwitchOn($this->type)) {
            return $this->buildCheckResult(0);
        }

        // 正常情况，登录不需要调用check操作，直接在user/login接口判断是否启动人脸
        return $this->buildCheckResult(0);
    }

    /**
     * 比较
     */
    public function compare() {
        // 检查手机号
        if (empty($this->params['mobile'])) {
            return $this->error("ERR_PARAMS_ERROR", 'missing mobile');
        }

        $mobile = $this->params['mobile'];
        $user = (new UserService())->getUserByMobile($mobile);
        if (empty($user)) {
            Logger::info("Face Compare. getUserByMobile is empty, type:login, mobile:{$mobile}");
        }

        // 检查是否需要做人脸识别，目前只有已实名的大陆身份证需要做人脸对比
        if ($user && FaceService::needVeriFace($user['real_name'], $user['idno'], $user['id_type'])) {
            return $this->faceImageVerify($user['real_name'], $user['idno'], $mobile, $user['id']);
        }

        // 活体检测
        return $this->livenessDetect($mobile, $user ? $user['id'] : 0);
    }
}