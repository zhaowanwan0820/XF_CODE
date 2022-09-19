<?php

/**
 * 人脸识别
 */
namespace core\service\face;

use libs\utils\Risk;
use libs\utils\Logger;

class FaceService {
    // 人脸验证
    const VERIFY_FACE = 3;

    const VERIFY_PREFIX = 'PH_APP_VERIFY_KEY_';

    // 活体检测失败冻结小时数
    const FACE_CHECK_USER_FREEZE_HOURS = 3;

    // 人脸识别失败冻结小时数
    const FACE_COMPARE_USER_FREEZE_HOURS = 24;

    const FACE_FREEZE_KEY_PREFIX = 'PH_FACE_COMPARE_USER_FREEZE_';
    const FACE_RETRY_KEY_PREFIX = 'PH_FACE_COMPARE_RETRY_COUNT_';

    // 风控类型
    const TYPE_LOGIN = 1;           // 登录
    const TYPE_REGISTER = 2;        // 注册
    const TYPE_REAL_NAME_AUTH = 3;  // 实名认证
    const TYPE_BIND = 4;            // 绑卡
    const TYPE_CHANGE_PWD = 5;      // 修改密码
    const TYPE_LIVING = 6;          // 活体检测

    public static $CMDS = [
        self::TYPE_LOGIN => 'Login',
        self::TYPE_REGISTER => 'Register',
        self::TYPE_REAL_NAME_AUTH => 'NameAuth',
        self::TYPE_BIND => 'Bind',
        self::TYPE_CHANGE_PWD=> 'ChangePwd',
        self::TYPE_LIVING => 'Living',            // 这个类型不需要人脸比对，只检测活体
    ];

    public static $TYPES = [
        self::TYPE_LOGIN => Risk::BC_LOGIN,
        self::TYPE_REGISTER => Risk::BC_REGISTER,
        self::TYPE_REAL_NAME_AUTH => Risk::BC_REAL_NAME_AUTH,
        self::TYPE_BIND => Risk::BC_BIND,
        self::TYPE_CHANGE_PWD=> Risk::BC_CHANGE_PWD,
        self::TYPE_LIVING => 'PAY.LIVING',            // 这个类型不需要人脸比对，只检测活体
    ];

    /**
     * 人脸识别开关是否开启
     * @param $type int 风险类别
     * @return bool true开启，false关闭
     */
    public static function isFaceSwitchOn($type) {
        if (!array_key_exists($type, self::$TYPES)) {
            return false;
        }

        $action = substr(self::$TYPES[$type], 4);
        // 开关关闭，直接返回不用人脸
        $riskSwitch = app_conf('RISK_FACE_SWITCHS_'. $action);
        $verifyMode = intval(app_conf('APP_VERIFY_MODE'));
        if ($riskSwitch == 1 && $verifyMode == self::VERIFY_FACE) {
            return true;
        }

        return false;
    }

    /**
     * 创建cmd
     */
    public static function createCmd(array $formData) {
        if (empty($formData['type']) || !array_key_exists($formData['type'], self::$CMDS)) {
            Logger::info("type illegal. type:".$formData['type']);
            return false;
        }

        $type = $formData['type'];
        $cmdName = '\\core\\service\\face\\cmd\\'.self::$CMDS[$type].'Cmd';
        $cmd = new $cmdName($formData);
        return $cmd;
    }

    /**
     * 检查账号是否被冻结
     * @param $mobile string 手机号
     * @return string 没冻结返回false，否则返回冻结文案
     */
    public static function checkFreeze($mobile) {
        $freeze_key = self::FACE_FREEZE_KEY_PREFIX . $mobile;
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $res = false;
        if (!empty($redis)) {
            $freeze = $redis->get($freeze_key);
            if (!empty($freeze)) {
                $ttl = $redis->ttl($freeze_key);
                $hour = intval($ttl / 3600);
                $min = intval(($ttl % 3600) / 60);
                $sec = $ttl % 60;

                $res = "账号已冻结，{$hour}小时{$min}分{$sec}秒后可再次登录";
            }
        }

        return $res;
    }

    /**
     * 是否验证过
     */
    public static function hasVerified() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $verifyToken = $redis->get(self::getVerifyCacheKey());
        return $verifyToken === 'SUCCESS' ? true : false;
    }

    /**
     * 验证
     * @param $verifyCode string 验证码，返回的verifyToken
     * @return bool
     */
    public static function checkVerify($verifyCode) {
        if (empty($verifyCode)) {
            return false;
        }

        // 去除左右空格
        $verifyCode = trim($verifyCode);
        if (empty($verifyCode)) {
            return false;
        }

        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $verifyToken = $redis->get(self::getVerifyCacheKey());
        Logger::info('verifyToken:'.$verifyToken."|usercode:".$verifyCode);

        // 已经验证通过了
        if ($verifyToken == 'SUCCESS') {
            return true;
        }

        if ($verifyCode == $verifyToken) {
            $redis->setEx(self::getVerifyCacheKey(), 300, 'SUCCESS');
            return true;
        }

        return false;
    }

    /**
     * 生成验证的verify token
     */
    public static function genVerifyToken() {
        $verifyToken = md5(self::getVerifyCacheKey().microtime().rand(1, 999));
        $redis = \SiteApp::init()->dataCache->getRedisInstance();

        // 2分钟的有效期
        $redis->setEx(self::getVerifyCacheKey(), 120, $verifyToken);
        return $verifyToken;
    }

    /**
     * 获取验证的cache key
     */
    private static function getVerifyCacheKey() {
        if (empty($_SERVER['HTTP_FINGERPRINT'])) {
            throw new \Exception('设备指纹为空');
        }

        return self::VERIFY_PREFIX.md5($_SERVER['HTTP_FINGERPRINT']);
    }

    /**
     * 获取重试次数
     * @param $mobile string 手机号
     * @param $type int 风控类型
     * @return int
     */
    public static function getFaceRetryTimes($mobile, $type) {
        $retry_key = self::FACE_RETRY_KEY_PREFIX . $mobile;
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $res = 0;
        if ($redis) {
            $res =  $redis->hGet($retry_key, $type);
        }

        return $res;
    }

    /**
     * 检查是否需要人脸识别
     * @param $name string 真实姓名
     * @param $idno string 身份证号
     * @param $idType int 证件类型
     * @return bool
     */
    public static function needVeriFace($name, $idno, $idType) {
        $need_compare = false;
        if (!empty($idno) && !empty($name) && $idType == 1) {
            $age = self::getAgeByID($idno);
            // 18-70岁的才需要人脸识别
            if ($age >=18 && $age <= 70) {
                $need_compare = true;
            }
        }

        return $need_compare;
    }

    /**
     * 通过身份证号获取年龄
     * @param $id string 身份证号
     * @return int
     */
    public static function getAgeByID($id) {
        if (empty($id)) return '';

        $date = substr($id, 6, 8);
        $today = date("Ymd");
        $diff = substr($today, 0, 4) - substr($date, 0, 4);
        $age = substr($date, 4) > substr($today, 4) ? ($diff - 1) : $diff;

        return $age;
    }
}