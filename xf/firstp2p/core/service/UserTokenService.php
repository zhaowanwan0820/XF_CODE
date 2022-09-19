<?php
namespace core\service;

use libs\utils\Logger;
use core\service\PassportService;
use core\service\UserService;
use core\service\UserTrackService;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

/**
 * app token相关的服务
 * @author longbo
 */
class UserTokenService extends BaseService
{
    const TOKEN_PREFIX_KEY = 'apit_token_key_';
    const TOKEN_SET_PREFIX = 'API_TOKEN_SET';
    const API_TOKEN_EXPIRE = 2592000;

    const LOGIN_FROM_WX_APP = 1;    // 网信app
    const LOGIN_FROM_WX_WAP = 2;    // 网信wap
    const LOGIN_FROM_WX_PC = 3;     // 网信pc
    const LOGIN_FROM_PH_APP = 4;    // 普惠app
    const LOGIN_FROM_PH_WAP = 5;    // 普惠wap
    const LOGIN_FROM_PH_PC = 6;     // 普惠pc
    const LOGIN_FROM_QIYE_APP = 7;  // 企业app
    const LOGIN_FROM_QIYE_WAP = 7;  // 企业wap，现在是企业wap嵌入到app里面
    const LOGIN_FROM_QIYE_PC = 8;   // 企业pc

    // 生成token
    public function genAppToken($uid, $ppID = '', $loginFrom = self::LOGIN_FROM_WX_APP)
    {
        $token = $this->getApiToken($uid, null, compact('ppID', 'loginFrom'));
        if ($ppID) {
            (new PassportService())->savePPSession($ppID, $uid, 'app');
        }

        return $token;
    }

    public function getApiToken($userId, $tokenExpire = null, $params = [])
    {
        $userId = intval($userId);
        if ($userId <= 0){
            return false;
        }

        $token = hash('sha256', self::TOKEN_PREFIX_KEY . microtime() . rand(1, 99999) . $userId);
        $tokenExpire = (intval($tokenExpire) > 0) ? $tokenExpire : self::API_TOKEN_EXPIRE;
        if ($params) {
            $userInfo = $params;
        }

        $userInfo['uid'] = $userId;
        $tokenKey = md5($token);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $res = $redis->setEx($tokenKey, $tokenExpire, json_encode($userInfo));
        $redis->hSet(self::TOKEN_SET_PREFIX.$userId, $tokenKey, time());
        return $token;
    }

    /**
     * 通过token获取用户uid
     */
    public function getUidByToken($token)
    {
        if (empty($token)){
            throw new \Exception('传入的参数不能为空!');
        }

        $userArr = $this->getInfoByToken($token);
        $ret = [];
        if (isset($userArr['uid'])) {
            $ret['uid'] = $userArr['uid'];
        }

        return $ret;
    }

    /**
     * 通过token获取用户信息
     */
    public function getUserByToken($token)
    {
        try {
            if (empty($token)){
                throw new \Exception('用户Token不存在', 306);
            }

            $userArr = $this->getInfoByToken($token);
            Logger::info(__FUNCTION__.'_userArr:'.var_export($userArr,true));
            if (empty($userArr['uid'])){
                throw new \Exception('用户Token已经过期', 308);
            }

            if (!empty($userArr['isKickedOut'])){
                throw new \Exception('用户Token已经被踢出', 309);
            }

            $userService = new UserService();
            $userInfo = $userService->getUser($userArr['uid'], false, false, true);
            if (empty($userInfo)){
                throw new \Exception('不存在的用户，禁止登录', 310);
            }

            if ($userInfo['is_effect'] == 0 || $userInfo['is_delete'] == 1) {
                throw new \Exception('无效用户，禁止登录', 311);
            }

            $userService->isSeller($userArr['uid'], $userInfo);
        } catch (\Exception $e) {
            Logger::error(__FUNCTION__.'_error:'.$e->getMessage());
            return ['code' => $e->getCode(), 'reason' => $e->getMessage()];
        }

        $ret = array();
        $userInfo['ppID'] = empty($userArr['ppID']) ? '' : $userArr['ppID'];
        $ret['user'] = $userInfo;
        $ret['status'] = 1;
        return $ret;
    }


    /**
     * 通过token获取登陆信息
     */
    public function getInfoByToken($token)
    {
        if (empty($token)){
            return false;
        }

        $tokenKey = md5($token);
        $userJson = \SiteApp::init()->dataCache->getRedisInstance()->get($tokenKey);
        $userArr = json_decode($userJson, true);
        return is_array($userArr) ? $userArr : [];
    }

    public function kickOutForLogin($userId, $loginToken = '')
    {
        return; //wap也用api token,暂时先不kickout

        $userInfo = $this->getInfoByToken($loginToken);
        $saveToken = md5($loginToken);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $userTokenSetKey = self::TOKEN_SET_PREFIX.$userId;
        $tokens = $redis->hKeys($userTokenSetKey);
        foreach ($tokens as $token) {
            if ($token !== $saveToken) {
                $userInfo['isKickedOut'] = 1;
                $redis->setEx($token, 86400, json_encode($userInfo));
            }
        }
        return;
    }

    /**
     * 用户退出删除token
     */
    public function deleteToken($logoutToken = '')
    {
        $userInfo = $this->getInfoByToken($logoutToken);
        $logoutToken = md5($logoutToken);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $userTokenSetKey = self::TOKEN_SET_PREFIX.$userInfo['uid'];
        $tokens = $redis->hKeys($userTokenSetKey);
        foreach ($tokens as $token) {
            if ($token == $logoutToken) {
                $redis->del($token);
                $redis->hDel($userTokenSetKey, $token);
                break;
            }
        }
        //生产用户访问日志
        UserAccessLogService::produceLog($userInfo['uid'], UserAccessLogEnum::TYPE_LOGOUT, '退出成功', '', '', UserAccessLogService::getDevice($_SERVER['HTTP_OS']));

        return;
    }

}
