<?php

/**
 * 人脸识别
 */
namespace core\service\face\cmd;

use libs\utils\Logger;
use libs\utils\Risk;
use core\service\face\FaceService;
use core\service\face\cmd\AbstractCmd;
use core\service\MobileCodeService;
use core\service\risk\RiskServiceFactory;
use NCFGroup\Common\Library\HttpLib;

class RegisterCmd extends AbstractCmd {
    // 注册
    protected $type = FaceService::TYPE_REGISTER;

    /**
     * 检查check
     */
    public function check() {
        // 参数校验
        if (empty($this->params['mobile'])) {
            Logger::info("Face Check. mobile is required. type:Register");
            return $this->error("ERR_PARAMS_ERROR", 'mobile is required');
        }

        // 开关关闭，直接返回不用人脸
        if (!FaceService::isFaceSwitchOn($this->type)) {
            return $this->buildCheckResult(0);
        }

        $mobile = $this->params['mobile'];

        // 手机号验证码发送频率超限不需要弹出人脸识别
        $ret = (new MobileCodeService())->frequencyCheck($mobile, 0);
        if ($ret != MobileCodeService::SUCESS) {
            Logger::info("Face Check. mobile code is sent frequency, type:Register, mobile:{$mobile}");
            return $this->buildCheckResult(0);
        }

        // 检查账号是否被冻结
        $freeze = FaceService::checkFreeze($mobile);
        if ($freeze) {
            return $this->buildCheckResult(0, 1, $freeze);
        }

        // 风控检测
        $riskRet = RiskServiceFactory::instance(
            Risk::BC_REGISTER,
            Risk::PF_API,
            Risk::getDevice($_SERVER['HTTP_OS'])
        )->checkFace(['phone'=>$mobile]);

        $formatMobile = format_mobile($mobile);
        // 风控正常
        if ($riskRet === true) {
            Logger::info("Face Check. risk is normal. type:Register, mobile:" . $formatMobile);
            return $this->buildCheckResult(0);
        }

        // 风控异常
        $retry = FaceService::getFaceRetryTimes($mobile, FaceService::TYPE_REGISTER);
        $retry = intval($retry) + 1;
        $ip = HttpLib::getClientIp();
        Logger::info("FaceVerifyStats. type:Register,mobile:{$formatMobile},ip:{$ip},faceCompare:0,times:{$retry}");
        return $this->buildCheckResult(1, 0, '风控异常');
    }

    /**
     * 比较
     */
    public function compare() {
        // 参数校验
        if (empty($this->params['mobile'])) {
            Logger::info("Face Compare. mobile is required. type:Register");
            return $this->error("ERR_PARAMS_ERROR", 'mobile is required');
        }

        // 活体识别
        return $this->livenessDetect($this->params['mobile'], 0);
    }
}