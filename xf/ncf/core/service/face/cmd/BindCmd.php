<?php

/**
 * 人脸识别
 */
namespace core\service\face\cmd;

use libs\utils\Logger;
use libs\utils\Risk;
use core\service\risk\RiskServiceFactory;
use core\service\face\FaceService;
use core\service\face\cmd\AbstractCmd;

/**
 * 绑卡
 */
class BindCmd extends AbstractCmd {
    // 绑卡
    protected $type = FaceService::TYPE_BIND;

    /**
     * 检查check
     */
    public function check() {
        if (empty($this->params['token'])) {
            Logger::info("Face Check. token is required. type:Bind");
            return $this->error("ERR_PARAMS_ERROR", 'token is required');
        }

        // 开关关闭，直接返回不用人脸
        if (!FaceService::isFaceSwitchOn($this->type)) {
            return $this->buildCheckResult(0);
        }

        $token = $this->params['token'];
        // 通过token获取用户信息
        try {
            $tokenInfo = UserService::getUserByCode($token);
        } catch (\Exception $ex) {
            return $this->error('ERR_SYSTEM', $ex->getMessage());
        }

        if (!empty($tokenInfo['code'])) {
            return $this->error($tokenInfo['code'], $tokenInfo['reason']);
        }

        $user = $tokenInfo['user'];
        $mobile = $user['mobile'];
        // 检查账号是否被冻结
        $freeze = FaceService::checkFreeze($mobile);
        if ($freeze) {
            return $this->buildCheckResult(0, 1, $freeze);
        }

        // 风控检测
        $riskRet = RiskServiceFactory::instance(
            Risk::BC_BIND,
            Risk::PF_API,
            Risk::getDevice($_SERVER['HTTP_OS'])
        )->checkFace([
            'id'=>$user['id']
        ]);

        $formatMobile = format_mobile($mobile);
        // 风控正常
        if ($riskRet === true) {
            Logger::info("Face Check. risk is normal. type:Bind, mobile:" . $formatMobile);
            return $this->buildCheckResult(0);
        }

        // 风控异常
        $retry = FaceService::getFaceRetryTimes($mobile, FaceService::TYPE_BIND);
        $retry = intval($retry) + 1;
        $ip = HttpLib::getClientIp();

        // 未实名需要活体检测
        if ($user['idcardpassed'] != 1) {
            Logger::info("FaceVerifyStats. type:Bind,userid:{$user['id']},mobile:{$formatMobile},ip:{$ip},faceCompare:1,times:{$retry}");
            return $this->buildCheckResult(1, 0, '风控异常');
        }

        // 实名&大陆身份证&18-70岁的需要人脸识别
        if (FaceService::needVeriFace($user['real_name'], $user['idno'], $user['id_type'])) {
            Logger::info("FaceVerifyStats. type:Bind,userid:{$user['id']},mobile:{$formatMobile},ip:{$ip},faceCompare:1,times:{$retry}");
            return $this->buildCheckResult(1, 0, '风控异常');
        }

        return $this->buildCheckResult(0);
    }

    /**
     * 比较
     */
    public function compare() {
        if (empty($this->params['token'])) {
            Logger::info("Face Compare. token is required. type:Bind");
            return $this->error("ERR_PARAMS_ERROR", 'token is required');
        }

        $token = $this->params['token'];
        // 通过token获取用户信息
        try {
            $tokenInfo = UserService::getUserByCode($token);
        } catch (\Exception $ex) {
            return $this->error('ERR_SYSTEM', $ex->getMessage());
        }

        if (!empty($tokenInfo['code'])) {
            return $this->error($tokenInfo['code'], $tokenInfo['reason']);
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