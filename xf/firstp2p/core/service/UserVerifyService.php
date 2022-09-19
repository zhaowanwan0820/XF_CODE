<?php
namespace core\service;

use libs\utils\Logger;
use libs\utils\Curl;
use libs\utils\Monitor;
use libs\utils\Block;
use core\dao\UserModel;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use core\service\MobileCodeService;
use core\service\CouponBindService;

/**
 * app 验证相关的服务
 * @author longbo
 */
class UserVerifyService extends BaseService
{
    //验证码, old version
    const VERIFY_CODE = 1;
    //投篮验证
    const VERIFY_SHOOT = 2;
    //人脸验证
    const VERIFY_FACE = 3;
    //短信验证
    const VERIFY_SMS = 4;

    //兼容版本号
    const COMPATIBLE_VER = 497;

    const VERIFY_PREFIX = 'app_verify_key_';

    const AUTH_INFO_USER = 'auth_info_user_key_';

    // PC_H5 不启用人脸
    const SECURITY_ENABLE_DEFUALT_AUTH = 0;
    // PC_H5 双码为空时，去app人脸认证
    const SECURITY_ENABLE_NONE_CODE_FACE_AUTH = 1;

    private $userKey = null;
    private $verifyCachekey = null;

    public function __construct()
    {
        $finger = '';
        if (isset($_SERVER['HTTP_FINGERPRINT'])) {
            $finger = $_SERVER['HTTP_FINGERPRINT'];
        } else if (isset($_COOKIE['FINGERPRINT'])) {
            $finger = $_COOKIE['FINGERPRINT'];
        }

        if (empty($finger)) {
            throw new \Exception('User Key is empty');
        }

        $this->userKey = md5($finger);
        $this->verifyCacheKey = self::VERIFY_PREFIX . $this->userKey;
    }

    /**
     * 验证verifyToken
     * @param $verifyCode string 验证token
     * @return bool
     */
    public function checkVerifyToken($verifyCode) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $verifyToken = $redis->get($this->verifyCacheKey);
        if ($verifyToken == 'SUCCESS') {
            return true;
        }

        Logger::info('verifyToken:'.$verifyToken."|usercode:".$verifyCode.'cacheKey:'.$this->verifyCacheKey);
        if ($verifyCode == $verifyToken) {
            $redis->setEx($this->verifyCacheKey, 300, 'SUCCESS');
            return true;
        }

        return false;
    }

    public function checkVerify($verifyCode = '', $account = '', $appVersion = 0)
    {
        $pass = false;
        // 去除左右空格
        $verifyCode = trim($verifyCode);
        if ($verifyCode) {
            if ($appVersion <= self::COMPATIBLE_VER) {
                $verify = \SiteApp::init()->cache->get("verify_" . md5($account));
                Logger::info('verifyCode:'.$verify."|userCode:".$verifyCode);
                \SiteApp::init()->cache->delete("verify_" . md5($account));

                // 比较之前转化成小写
                if ($verify == md5(strtolower($verifyCode))) {
                    $pass = true;
                }
            } else {
                $redis = \SiteApp::init()->dataCache->getRedisInstance();
                $verifyToken = $redis->get($this->verifyCacheKey);
                Logger::info('verifyToken:'.$verifyToken."|usercode:".$verifyCode);
                if ($verifyCode == $verifyToken) {
                    $pass = true;
                    $redis->setEx($this->verifyCacheKey, 300, 'SUCCESS');
                } elseif ($verifyToken == self::VERIFY_SMS) {
                    //验证短信
                    $code = (new MobileCodeService())->getMobilePhoneTimeVcode($account, 180, 0);
                    Logger::info('smscode:'.$code."|usercode:".$verifyCode);
                    if ($code == $verifyCode) {
                        $pass = true;
                        $redis->setEx($this->verifyCacheKey, 300, 'SUCCESS');
                    }
                }
            }
        }

        return $pass ? 0 : $this->needVerify($appVersion);
    }

    public function hasVerified()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $verifyToken = $redis->get($this->verifyCacheKey);
        if ($verifyToken === 'SUCCESS') {
            return true;
        }
        return false;
    }

    public  function needVerify($appVersion = 0)
    {
        if ($appVersion <= self::COMPATIBLE_VER) {
            return self::VERIFY_CODE;
        }

        $verifyMode = intval(app_conf('APP_VERIFY_MODE'));
        $verifyModes = [
            self::VERIFY_SHOOT,
            self::VERIFY_FACE,
            self::VERIFY_SMS
        ];
        $verifyMode = in_array($verifyMode, $verifyModes) ? $verifyMode : self::VERIFY_SHOOT;
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $res = $redis->setEx($this->verifyCacheKey, 300, $verifyMode);
        return $verifyMode;
    }

    public function shootVerify($sessionid, $from = 'app')
    {
        $shootUrl = $GLOBALS['sys_config']['RISK_SHOOT_AUTH'];
        $params['sessionid'] = $sessionid;
        $params['from'] = $from;
        $shootUrl .= '?'.http_build_query($params);
        $response = Curl::get($shootUrl);
        $isPass = false;
        Logger::info('shoot_verify_res:'.$response);
        if (Curl::$httpCode == 200) {
            $resArr = json_decode($response, true);
            if (isset($resArr['errno']) && $resArr['errno'] == 0) {
                $isPass = true;
            }
        } else {
            Logger::error('shoot_verify_fail_Curl'.Curl::$error);
        }

        if ($isPass) {
            return $this->getVerifyToken();
        }
        Monitor::add('shoot_verify_not_pass');
        return false;
    }

    public function getVerifyToken()
    {
        $verifyToken = md5($this->userKey.microtime().rand(1, 999));
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        Logger::info('verifyToken:'.$verifyToken.', verifyKey:'.$this->verifyCacheKey);
        if ($res = $redis->setEx($this->verifyCacheKey, 120, $verifyToken)) {
            return $verifyToken;
        } else {
            return false;
        }
    }

    // 实名认证,没有邀请码用户的个人投资户强制人脸识别
    public static function isBind($userId,
        $userType = UserModel::USER_TYPE_NORMAL,
        $userPurpose = UserAccountEnum::ACCOUNT_INVESTMENT
    ) {
        // 检查开关, 是否启用app 人脸识别策略
        $switch = intval(app_conf('REAL_NAME_AUTH_TYPE'));
        if ($switch == self::SECURITY_ENABLE_NONE_CODE_FACE_AUTH
            && $userType != UserModel::USER_TYPE_ENTERPRISE
            && $userPurpose == UserAccountEnum::ACCOUNT_INVESTMENT
        ) {
            $couponBindService = new CouponBindService();
            $couponInfo = $couponBindService->getByUserId($userId);
            if (empty($couponInfo['refer_user_id']) && empty($couponInfo['invite_user_id'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * pc h5 启用认证类型
     * @param int $userId
     * @param int $userType 用户类别，0个人，1企业
     * @param int $userPurpose 1投资户 
     */
    public static function PcH5RealNameAuth($userId,
        $userType = UserModel::USER_TYPE_NORMAL,
        $userPurpose = UserAccountEnum::ACCOUNT_INVESTMENT
    ) {
        if(!self::isBind($userId, $userType, $userPurpose)) {
            throw new \Exception("为了您的账户安全，需要下载网信APP完成实名认证，投资体验也会更佳哦", -2);
        }
    }
}
