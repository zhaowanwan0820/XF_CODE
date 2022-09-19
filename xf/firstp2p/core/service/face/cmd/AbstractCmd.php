<?php

/**
 * 人脸识别
 */
namespace core\service\face\cmd;

use NCFGroup\Common\Library\Face\Face;
use core\service\face\FaceService;
use libs\utils\Logger;
use NCFGroup\Common\Library\HttpLib;

class AbstractCmd {
    /**
     * 请求参数
     */
    protected $params;

    /**
     * 风险类型
     */
    protected $type;

    /**
     * 是否有错误
     */
    protected $hasError = false;

    /**
     * 错误码
     */
    public $errorNo = '';

    /**
     * 错误消息
     */
    public $errorMsg = '';

    public function __construct(array $params) {
        $this->params = $params;
    }

    public function hasError() {
        return $this->hasError;
    }

    public function error($errno, $errmsg) {
        $this->hasError = true;
        $this->errorNo = $errno;
        $this->errorMsg = $errmsg;
        return false;
    }

    // check是否开启人脸
    public function check() {
        return $this->buildCheckResult(0);
    }

    // compare比较人脸识别结果
    public function compare() {
        return ['facePassed'  => 1, 'compareRetry' => 1, 'message' => '', 'verifyToken' => ''];
    }

    /**
     * 构造人脸检测结果
     * @param $needFaceVerify int 0:关闭人脸检测  1:打开检测
     * @param $freeze int 是否冻结,0:不冻结，1：冻结
     * @param $msg string 提示信息
     * @return array
     */
    public function buildCheckResult($needFaceVerify = 0, $freeze = 0, $msg = '风控正常') {
        // 触发人脸识别
        if ($needFaceVerify) {
            \libs\utils\Monitor::add('FACE_NEED_VERIFY');
        }

        return [
            'needFaceVerify' => $needFaceVerify,
            'freeze' => $freeze,
            'message' => $msg
        ];
    }

    /**
     * 构造人脸对比结果
     */
    public function buildCompareResult($pass, $mobile, $userid, $freeze_hours = 3) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (empty($redis)) {
            Logger::info("Face Compare. redis object is empty");
            return ['facePassed'  => 1, 'compareRetry' => 1, 'message' => ''];
        }

        $formatMobile = format_mobile($mobile);
        $type = $this->type;
        $retryKey = FaceService::FACE_RETRY_KEY_PREFIX . $mobile;
        if ($pass) {
            // 白泽数据统计埋点, 统计验证通过的次数
            $retry = $redis->hGet($retryKey, $type);
            $retry = intval($retry) + 1;

            $ip = HttpLib::getClientIp();
            Logger::info("FaceVerifySuccStats. type:{$type},userid:{$userid},mobile:{$formatMobile},ip:{$ip},times:{$retry}");

            // 验证通过删除redis里保存的重试次数
            $redis->hDel($retryKey, $type);

            // 人脸通过后设置密钥
            $token = (new \core\service\UserVerifyService())->getVerifyToken();

            // 人脸识别成功
            \libs\utils\Monitor::add('FACE_VERIFY_SUCCESS');

            return ['facePassed'  => 1, 'compareRetry' => 1, 'message' => '验证成功', 'verifyToken' => $token];
        }

        // 人脸识别失败
        \libs\utils\Monitor::add('FACE_VERIFY_FAILED');

        // 重试阈值
        $count = app_conf('FACE_COMPARE_RETRY_COUNT');
        if (empty($count)) {
            $count = 3;
        }

        $ret = $redis->hIncrby($retryKey, $type, 1);
        if (empty($ret)) {
            Logger::info("Face Compare. redis hincrby error");
            return ['facePassed' => 0, 'compareRetry' => 1, 'message' => '本次验证不匹配，' . $count . '次失败后账号会被冻结'];
        }

        if ($ret > $count) {
            // 重试次数达到上限，冻结
            Logger::info("Face Compare. user freeze, userid:{$userid}, mobile:{$formatMobile}");

            $compareRetry = 0;
            $message = '';
            // 用户修改银行卡单独冻结
            if ($this->type == FaceService::TYPE_CHANGE_BANKCARD) {
                $compareRetry = 1;
                $expire = 86400 + strtotime(date('Y-m-d')) - time();
                $redis->setex(FaceService::FACE_CBCARD_FREEZE_KEY_PREFIX . $mobile, $expire, 1);
                $message = '人脸服务已暂时锁定，次日零点解锁，您可以使用人工审核换卡';
            } else {
                $redis->setex(FaceService::FACE_FREEZE_KEY_PREFIX . $mobile, $freeze_hours * 3600, 1);
                $compareRetry = 0;

                $code = $this->getCodeByFreezeHours($freeze_hours);
                $message = '验证多次失败，账号已冻结，请' . $freeze_hours . '小时后再次尝试（错误代码' . $code. '）';
            }

            // 重试次数清0
            $redis->del($retryKey);
            return [
                'facePassed' => 0,
                'compareRetry' => $compareRetry,
                'message' => $message,
                'verifyToken' => ''
            ];
        }

        $remain = $count - $ret + 1;
        Logger::info("Face Compare. verify fail, userid:{$userid}, mobile:{$formatMobile}, retry remain:{$remain}");
        return [
            'facePassed' => 0,
            'compareRetry' => 1,
            'message' => '验证失败，请核对信息后重试，' . $remain . '次失败后账号会被冻结!',
            'verifyToken' => ''
        ];
    }

    private function getCodeByFreezeHours($freezeHours) {
        $type = $this->type;
        $code = 500;
        $isCheck = ($freeze_hours == FaceService::FACE_CHECK_USER_FREEZE_HOURS) ? true : false;
        $isCompare = ($freeze_hours == FaceService::FACE_COMPARE_USER_FREEZE_HOURS) ? true : false;

        // 错误代码
        if ($type == FaceService::TYPE_LOGIN && $isCompare) {
            $code = 501;
        }

        if ($type == FaceService::TYPE_LOGIN && $isCheck) {
            $code = 502;
        }

        if ($type == FaceService::TYPE_REGISTER) {
            $code = 503;
        }

        if ($type == FaceService::TYPE_REAL_NAME_AUTH) {
            $code = 504;
        }

        if ($type == FaceService::TYPE_BIND) {
            $code = 505;
        }

        if ($type == FaceService::TYPE_CHANGE_PWD && $isCompare) {
            $code = 506;
        }

        if ($type == FaceService::TYPE_CHANGE_PWD && $isCheck) {
            $code = 507;
        }

        if ($type == FaceService::TYPE_CHANGE_BANKCARD) {
            $code = 508;
        }

        return $code;
    }

    /**
     * 人脸检测，包括活体检测
     */
    protected function faceImageVerify($name, $idno, $mobile, $userid) {
        if (empty($this->params['query_image_package'])) {
            return $this->error("ERR_PARAMS_ERROR", 'query_image_package is required');
        }

        $type = $this->type;
        $formatMobile = format_mobile($mobile);
        $ret = Face::faceImageVerify($name, $idno, $this->params['query_image_package']);
        if (!$ret) {
            Logger::info("Face Compare. rongshu request error, type:{$type}, name:{$name}, userid:{$userid}, mobile:{$formatMobile}");
            return $this->buildCompareResult(true, $mobile, $userid);
        }

        $freezeHours = FaceService::FACE_COMPARE_USER_FREEZE_HOURS;
        if ($ret['ResultCode'] != 1000) {
            Logger::info("Face Compare. rongshu code:{$ret['ResultCode']}, type:{$type}, userid:{$userid}, mobile:{$formatMobile}");
            return $this->buildCompareResult(false, $mobile, $userid, $freezeHours);
        }

        // 比对照片相似度判断
        $score = app_conf('FACE_COMPARE_SCORE');
        if (empty($score)) {
            $score = 0.7;
        }

        if ($ret['Confidence'] < $score) {
            Logger::info("Face Compare. rongshu score is less than our setting, type:{$type}, userid:{$userid}, mobile:{$formatMobile}");
            return $this->buildCompareResult(false, $mobile, $userid, $freezeHours);
        }

        Logger::info("Face Compare.compare success, type:{$type}, userid:{$userid}, mobile:{$formatMobile}");
        return $this->buildCompareResult(true, $mobile, $userid, $freezeHours);
    }

    /**
     * 活体检测
     * @param $mobile string 手机号
     * @param $userid int 用户id
     * @return array
     */
    protected function livenessDetect($mobile, $userid) {
        if (empty($this->params['query_image_package'])) {
            return $this->error("ERR_PARAMS_ERROR", 'query_image_package is required');
        }

        $result = Face::livenessDetect($this->params['query_image_package']);
        $formatMobile = format_mobile($mobile);
        $type = $this->type;
        if ($result === false) {
            Logger::info("Face Compare. yitu request error, type:{$type}, userid:{$userid}, mobile:{$formatMobile}");
            // 依图接口异常，按通过处理
            return $this->buildCompareResult(true, $mobile, $userid);
        }

        // 活体检测
        if (!$result['query_image_package_result']['is_valid_package']) {
            Logger::info("Face Compare. yitu package is invalid, type:{$type}, userid:{$userid}, mobile:{$formatMobile}");
            return $this->buildCompareResult(false, $mobile, $userid, FaceService::FACE_CHECK_USER_FREEZE_HOURS);
        }

        // 活体检测失败
        if (!$result['query_image_package_result']['is_same_person']) {
            Logger::info("Face Compare. yitu return is not same person, type:{$type}, userid:{$userid}, mobile:{$formatMobile}");
            return $this->buildCompareResult(false, $mobile, $userid, FaceService::FACE_CHECK_USER_FREEZE_HOURS);
        }

        return $this->buildCompareResult(true, $mobile, $userid);
    }
}
