<?php

/**
 * 人脸识别
 */
namespace core\service\face\cmd;

use libs\utils\Logger;
use core\service\risk\RiskService;
use NCFGroup\Common\Library\HttpLib;
use core\service\face\FaceService;
use core\service\user\UserService;
use core\service\face\cmd\AbstractCmd;

class NameAuthCmd extends AbstractCmd {
    // 实名认证
    protected $type = FaceService::TYPE_REAL_NAME_AUTH;

    /**
     * 检查check
     */
    public function check() {
        if (empty($this->params['token'])) {
            Logger::info("Face Check. token is required. type:NameAuth");
            return $this->error("ERR_PARAMS_ERROR", 'token is required');
        }

        if (empty($this->params['idno'])) {
            Logger::info("Face Check. idno is required. type:NameAuth");
            return $this->error("ERR_PARAMS_ERROR", 'idno is required');
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
        // 异常情况，已经实名认证过了
        if ($user['idcardpassed'] ==  1) {
            return $this->buildCheckResult(0);
        }

        $idno = $this->params['idno'];
        if (!preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $idno)) {
            Logger::info("Face Check. idno is illegal. type:NameAuth, idno:" . $idno.', userid: '.$user['id']);
            return $this->error("ERR_PARAM_IDNO_ILLEGAL");
        }

        $mobile = $user['mobile'];

        // 检查账号是否被冻结
        $freeze = FaceService::checkFreeze($mobile);
        if ($freeze) {
            return $this->buildCheckResult(0, 1, $freeze);
        }

        $formatMobile = format_mobile($mobile);
        if (RiskService::check('REALNAME', array(
            'user_id'=>$user['id'],
            'mobile'=>$mobile,
            'idno'=>$idno,
            'user_type'=>$user['user_type'],
            'account_type'=>$user['user_purpose'],
            'invite_code'=>$user['invite_code']
        )) === true) {
            Logger::info("Face Check. risk is normal. type:NameAuth, mobile:" . $formatMobile.', userid:'.$user['id']);
            return $this->buildCheckResult(0);
        }

        // 实名认证,没有邀请码用户强制人脸识别
        $retry = FaceService::getFaceRetryTimes($mobile, FaceService::TYPE_REAL_NAME_AUTH);
        $retry = intval($retry) + 1;

        $ip = HttpLib::getClientIp();
        Logger::info("Face Check. type:NameAuth,mobile:{$formatMobile},ip:{$ip},times:{$retry}");
        return $this->buildCheckResult(1, 0, '风控异常');
    }

    /**
     * 比较
     */
    public function compare() {
        if (empty($this->params['token'])) {
            Logger::info("Face Compare. token is required. type:Bind");
            return $this->error("ERR_PARAMS_ERROR", 'token is required');
        }

        if (empty($this->params['idno'])) {
            Logger::info("Face Compare. idno is required. type:NameAuth");
            return $this->error("ERR_PARAMS_ERROR", 'idno is required');
        }

        if (empty($this->params['name'])) {
            Logger::info("Face Compare. name is required. type:NameAuth");
            return $this->error("ERR_PARAMS_ERROR", 'name is required');
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
        $idno = $this->params['idno'];
        $name = $this->params['name'];

        // 检查是否需要人脸识别
        if (FaceService::needVeriFace($name, $idno, $user['id_type'])) {
            // 人脸识别
            $res = $this->faceImageVerify($name, $idno, $user['mobile'], $user['id']);
        } else {
            // 活体识别
            $res = $this->livenessDetect($user['mobile'], $user['id']);
        }

        // 上报结果
        $status = isset($res['facePassed']) && $res['facePassed'] == 0
            ? RiskService::STATUS_FAIL
            : RiskService::STATUS_SUCCESS;

        RiskService::report('REALNAME', $status, array(
            'user_id'=>$user['id'],
            'mobile'=>$user['mobile'],
            'idno'=>$idno,
            'invite_code'=>$user['invite_code'],
            'user_type'=>$user['user_type'],
            'account_type'=>$user['user_purpose']
        ));

        return $res;
    }
}
