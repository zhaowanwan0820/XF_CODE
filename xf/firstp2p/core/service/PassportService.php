<?php
/**
 * PassportService.php
 * @date 2017-08-03
 */

namespace core\service;
use libs\passport\Passport;
use libs\passport\CodeEnum;
use core\service\user\BOFactory;
use libs\utils\Logger;
use core\dao\WangxinPassportModel;
use core\dao\UserModel;
use core\dao\UserBankcardModel;
use core\dao\BankModel;
use core\service\UserService;
use core\service\PaymentService;
use libs\utils\ABControl;

class PassportService extends BaseService {

    // 网信通行证类型-手机号
    const IDENTITY_TYPE = 'mobile';

    const PASSPORT_AUTH_RESPONSE = 'PASSPORT_AUTH_RESPONSE_CACHE_KEY_%s';

    const PASSPORT_SESSION_CACHE_KEY = 'PASSPORT_SESSION_CACHE_KEY_%s';

    // 判断开关类型 1为手机号 2为用户ID
    const TYPE_MOBILE = 1;

    const TYPE_USERID = 2;

    // 身份证类型对通行证映射
    public $idTypeMap = [
        '1' => 'IDC', // 身份证
        '4' => 'GAT', // 港澳通行证
        '6' => 'GAT', // 台湾通行证
        '2' => 'PASS_PORT', // 护照
        '3' => 'MILIARY',
        '99' => '其他',
    ];

    /**
     * 登录平台
     */
    public $platforms = [
        'web',
        'app',
    ];

    /**
     * passport请求分发接口
     */
    public function handle($params)
    {
        if (!Passport::verifySignature($params, $params['signature'])) {
            return Passport::response(CodeEnum::SYS_BIZ_PARAM_ILLEGAL);
        }
        $params = Passport::decode($params);
        $api = 'api' . str_replace(' ', '', ucwords(str_replace(".", " ", trim($params['service'], 'api.'))));
        Logger::info('PassportWX Start ' . $api);
        $logParams = $params;
        if ($api == 'apiLoginEndorse') {
            unset($logParams['requestParam']['password']);
        }
        Logger::info('PassportWX  params' . $logParams);
        if (!method_exists($this, $api)) {
            return Passport::response(CodeEnum::SYS_BIZ_PARAM_ILLEGAL);

        }
        return $this->$api($params);
    }

    /**
     * 登录鉴权投票, 给Passport回调用
     */
    public function apiLoginEndorse($params)
    {
        $identity = $params['requestParam']['identity'];
        $password = $params['requestParam']['password'];
        // user check
        try {
            $user = UserModel::instance()->getUserByMobile($identity);
            if (empty($user)) {
                throw new \Exception('用户不存在');
            }
            if (!$this->checkPassword($user, $password)) {
                throw new \Exception('用户密码不正确');
            }
            $userBank = UserBankcardModel::instance()->getCardByUser($user['id']);
            if (!empty($userBank)) {
                $bank = BankModel::instance()->find($userBank['bank_id'], '*', true);
            }
            $passportInfo = WangxinPassportModel::instance()->getPassportByUser($user['id']);
        } catch (\Exception $e) {
            Logger::info('Passport api login error,' . $e->getMessage());
            return Passport::response(CodeEnum::AUTH_FAILED);
        }

        $userInfo = [
            'ppId' => $passportInfo['ppid'],
            'identity' => $user['mobile'],
            'identityType' => self::IDENTITY_TYPE,
            'username' => $user['user_name'],
            'mobile' => $user['mobile'],
            'certType' => !empty($this->idTypeMap[$user['id_type']]) ? $this->idTypeMap[$user['id_type']] : '',
            'certNo' => isset($user['idno']) ? $user['idno'] : '',
            'realname' => isset($user['real_name']) ? $user['real_name'] : '',
            'bizInfo' => [],
            'properties' => []
        ];

        if (!empty($userBank)) {
            $userInfo['bankcardList'] = [
                'bankcardNo' => $userBank['bankcard'],
                'bankCode' => $bank['short_name'],
                'bankName' => $bank['name'],
                'realname' => $userBank['card_name'],
                'mobile' => $user['mobile'],
                'certType' => !empty($this->idTypeMap[$user['id_type']]) ? $this->idTypeMap[$user['id_type']] : '',
                'certNo' => $user['idno']
            ];
        }

        $userData = [
            'bizAuthorizeFlag' => true,
            'riskFlag' => true,
            'userInfo' => $userInfo
        ];

        return Passport::response(CodeEnum::AUTH_SUCCESS, $userData);
    }

    /**
     * session退出, 供Passport回调用
     */
    public function apiSessionDelete($params)
    {
        $ppId = $params['requestParam']['ppId'];

        if (!$this->markUserLocalVerify($ppId)) {
            Logger::info('用户添加登录需校验标记失败' . $ppId);
            return Passport::response(CodeEnum::SESSION_DEL_FAILED);
        }

        // TODO set user mark
        try {
            $redis = $this->getRedis();
            $cacheKey = sprintf(self::PASSPORT_SESSION_CACHE_KEY, $ppId);
            $sessions = $redis->hgetall($cacheKey);

            // 空Map直接当成功处理
            if (empty($sessions)) {
                return Passport::response(CodeEnum::SESSION_DEL_SUCCESS);
            }
            foreach ($sessions as $platform => $sessionId) {
                $function = $platform. 'SessionDestroy';
                $this->$function($sessionId);
            }
        } catch (\Exception $e) {
            Logger::info($e->getMessage());
            return Passport::response(CodeEnum::SESSION_DEL_FAILED);
        }

        return Passport::response(CodeEnum::SESSION_DEL_SUCCESS);
    }

    /**
     * Passport 鉴权接口
     */
    public function authenticate($mobile, $password, $riskData = [], $extProperties = [])
    {
        $authResult = [
            'authPass' => false,
            'showAuth' => false,
            'needVerify' => false,
            'ppUserInfo' => [],
            'userInfo' => []
        ];

        if (!$this->isEnable($mobile)) {
            Logger::info('Passport Service is down, mobile:' . $mobile);
            return $authResult;
        }

        $service = 'api.login.authenticate';
        $requestParam = [
            'identity' => $mobile,
            'password' => $password,
            'riskData' => json_encode($riskData, JSON_FORCE_OBJECT)
        ];

        $response = Passport::request($requestParam, $service, $extProperties);

        // 鉴权失败直接返回，走本地登录
        if (empty($response['bizResponse'])) {
            return $authResult;
        }

        // 鉴权成功，验证本地用户信息
        try {
            $authResult = $this->localCheck($response, $password);
        } catch (\Exception $e) {
            Logger::info('Passport Local Check, Exception:' . $e->getMessage());
        }

        if ($authResult['authPass']) {
            $authResult['ppUserInfo'] = $response['bizResponse']['ppUserInfo'];
        }

        // 鉴权成功且不需要弹窗，直接绑定
        if ($authResult['authPass'] == true && !$authResult['showAuth'] && !$authResult['needVerify'] && !$authResult['passportExists']) {
            $this->userBind($response['bizResponse']['ppUserInfo']['ppId']);
        }

        return $authResult;
    }

    /**
     * Passport 修改密码通知退出session接口
     */
    public function sessionDestroy($ppId, $extProperties = [])
    {
        $service = 'api.session.delete';
        $requestParam = [
            'ppId' => $ppId
        ];
        $response =  Passport::request($requestParam, $service, $extProperties);
        if (empty($response)) {
            throw new \Exception('请求通行证失败');
        }

        if ($response['code'] . $response['subCode'] != CodeEnum::SESSION_DEL_SUCCESS) {
            throw new \Exception($response['respMsg']);
        }

        return true;
    }

    /**
     * Passport 身份信息修改接口
     */
    public function updateCert($ppId, $oldCertInfo, $newCertInfo, $requestId)
    {

        if ($newCertInfo == $oldCertInfo) {
            Logger::info('Passport updateCert: no change');
            return true;
        }

        $service = 'api.user.updateCert';
        $requestParam = [
            'ppId' => $ppId,
            'oldCertInfo' => $oldCertInfo,
            'newCertInfo' => $newCertInfo,
            'requestId' => $requestId
        ];
        $response = Passport::request($requestParam, $service, $extProperties);
        if (empty($response)) {
            throw new \Exception('请求通行证失败');
        }

        if ($response['code'] . $response['subCode'] != CodeEnum::UPDATE_CERT_SUCCESS) {
            throw new \Exception($response['respMsg']);
        }

        return true;
    }

    /**
     * Passport修改用户标识接口
     */
    public function updateIdentity($ppId, $oldIdentity, $newIdentity, $requestId)
    {
        if ($newIdentity == $oldIdentity) {
            Logger::info('Passport updateIdentity: no change');
            return true;
        }

        $service = 'api.user.updateIdentity';
        $requestParam = [
            'ppId' => $ppId,
            'oldIdentity' => $oldIdentity,
            'newIdentity' => $newIdentity,
            'requestId' => $requestId
        ];
        $response = Passport::request($requestParam, $service, $extProperties);
        if (empty($response)) {
            throw new \Exception('请求通行证失败');
        }

        if ($response['code'] . $response['subCode'] != CodeEnum::UPDATE_IDENTITY_SUCCESS) {
            throw new \Exception($response['respMsg']);
        }

        return true;
    }

    /**
     * Passport 获取商户列表
     */
    public function getBizInfoList()
    {
        $service = 'api.biz.getBizInfoList';
        $requestParam = [
        ];
        $response = Passport::request($requestParam, $service, $extProperties);
        if (empty($response)) {
            throw new \Exception('请求通行证失败');
        }

        if (empty($response['bizResponse'])) {
            throw new \Exception($response['respMsg']);
        }

        $bizList = [];
        foreach($response['bizResponse']['bizInfoList'] as $bizInfo) {
            $bizList[$bizInfo['bizName']] = $bizInfo;
        }

        return $bizList;
    }

    /**
     * 获取单个商户信息
     */
    public function getBizInfo($bizName)
    {
        try {
            $bizList = $this->getBizInfoList();
        } catch (\Exception $e) {
            Logger::info('Passport getBizInfo, err:' . $e->getMessage());
            return ['platformName' => '', 'url' => ''];
        }

        return $bizList[$bizName];
    }

    /**
     * 获取本地通信证信息
     */
    public function getPassportByUser($userId)
    {
        return WangxinPassportModel::instance()->getPassportByUser($userId);
    }

    /**
     * 通行证导流用户暗绑
     */
    public function userRegister($mobile, $idno, $realName)
    {
        $userInfo = UserModel::instance()->getUserByMobile($mobile, 'id');
        if (!empty($userInfo)) {
            throw new \Exception('手机号已注册', 1);
        }

        //注册新用户
        $password = substr(md5($mobile.mt_rand(1000000, 9999999)), 0, 10);
        $siteId = \libs\utils\Site::getId();
        $userInfoExtra = array(
            'site_id' => $siteId,
        );

        $userService = new UserService();
        $result = $userService->Newsignup('', $password, '', $mobile, '', '', $userInfoExtra, false);

        if (empty($result) || $result['status'] != 0) {
            throw new \Exception('注册失败:'.$result['reason']);
        }

        $userId = $result['user_id'];

        //实名认证并支付开户
        $data = array(
            'cardNo' => $idno,
            'realName' => $realName,
        );

        $paymentService = new PaymentService();
        if ($result = $paymentService->register($userId, $data) === PaymentService::REGISTER_FAILURE) {
            throw new \Exception('实名认证开户失败');
        }

        return $userId;
    }

    /**
     * 绑定通行证
     */
    public function userBind($ppId)
    {
        $redis = $this->getRedis();
        $redisKey = sprintf(self::PASSPORT_AUTH_RESPONSE, $ppId);
        $response = $redis->get($redisKey);
        // 用户登录信息过期，重新登录
        if (empty($response)) {
            throw new \Exception('授权信息过期');
        }

        $response = json_decode($response, true);
        // 用户开户逻辑, 注册，实名认证，支付开户，通行证绑定
        try {
            $ppUserInfo = $response['bizResponse']['ppUserInfo'];
            $user = UserModel::instance()->getUserByMobile($ppUserInfo['identity']);

            // 用户是粉底
            $localFlag = WangxinPassportModel::FLAG_LOCAL;
            // 用户暗绑
            if (empty($user)) {
                $localFlag = WangxinPassportModel::FLAG_PASSPORT;
                $userId = $this->userRegister($ppUserInfo['mobile'], $ppUserInfo['certNo'], $ppUserInfo['realname']);
                $user = UserModel::instance()->find($userId);
            }

            try {
                $res = WangxinPassportModel::instance()->savePassport($user['id'], $ppUserInfo, $localFlag);
                // 第一次绑定，账户页显示账户提示
                if ($res) {
                    \es_session::set('passportNotice', 1);
                }
            } catch (\Exception $e) {
                Logger::info('Passport Mapping Error:' . $e->getMessage());
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $user;
    }

    /**
     * 判断服务是否启用
     */
    public function isEnable($userIdentity, $type = self::TYPE_MOBILE)
    {
        if (app_conf('PASSPORT_ENABLE')) {
            return true;
        }

        if (empty($userIdentity)) {
            return false;
        }

        if ($type == self::TYPE_USERID) {
            $userInfo = UserModel::instance()->find($userIdentity, '*', true);
        } else {
            $userInfo = UserModel::instance()->getUserByMobile($userIdentity, '*', true);
            $userInfo = !empty($userInfo) ? $userInfo : ['mobile' => $userIdentity];
        }

        if (!ABControl::getInstance()->hit('passport', $userInfo)) {
            return false;
        }

        return true;
    }

    /**
     * 判断是否是第三方用户
     */
    public function isThirdPassport($mobile, $bizInfo = true)
    {
        if (!$this->isEnable($mobile)) {
            return false;
        }
        $passportInfo = WangxinPassportModel::instance()->getPassportByMobile($mobile);
        if (empty($passportInfo) || ($passportInfo['local_flag'] == 1 && $passportInfo['biz_name'] == 'WANGXINLICAI')) {
            return false;
        }

        if ($bizInfo) {
            $bizInfo = $this->getBizInfo($passportInfo['biz_name']);
            return $bizInfo;
        }

        return true;
    }

    /**
     * 这个方法仅供加入GTM判断使用, 开关关掉，不加入
     */
    public function isLocalPassport($userId)
    {
        if (!$this->isEnable($userId, self::TYPE_USERID)) {
            return false;
        }

        $passportInfo = WangxinPassportModel::instance()->getPassportByUser($userId);
        if (!empty($passportInfo) && $passportInfo['local_flag'] == 1 && $passportInfo['biz_name'] == 'WANGXINLICAI') {
            return $passportInfo;
        }

        return false;
    }

    /**
     * 本地修改密码同步通行证逻辑
     */
    public function sessionDestroyByUserId($userId)
    {
        if (!$this->isEnable($userId, self::TYPE_USERID)) {
            Logger::info('Passport Service is down');
            return true;
        }

        $passportInfo = WangxinPassportModel::instance()->getPassportByUser($userId);
        if (empty($passportInfo) || $passportInfo['local_flag'] != 1) {
            return true;
        }

        try {
            $res = $this->sessionDestroy($passportInfo['ppid']);
        } catch (\Exception $e) {
            Logger::info('Passport notice session failed, ', $e->getMessage());
            $res = false;
        }

        return $res;
    }

    /**
     * 通行证验证通过后本地校验
     */
    private function localCheck($response, $password)
    {
        $result = ['authPass' => true, 'needVerify' => false, 'showAuth' => false, 'userInfo' => [], 'passportExists' => false];
        // TODO cache AuthResult
        $this->cacheAuthResponse($response);
        $ppUserInfo = $response['bizResponse']['ppUserInfo'];

        // 获取通行证绑定信息
        $localPassportInfo = WangxinPassportModel::instance()->getPassportByPPid($ppUserInfo['ppId']);
        // 通行证本地存在, 直接通过
        if (!empty($localPassportInfo)) {
            $user = UserModel::instance()->find($localPassportInfo['user_id'], '*', true);
            $result['userInfo'] = $user->getRow();
            $result['passportExists'] = true;
            Logger::info('Passport Local Check, Passport Exists, ' . $ppUserInfo['ppId']);
            return $result;
        }

        $user = UserModel::instance()->getUserByMobile($ppUserInfo['identity']);
        // 本地用户不存在，无法校验本地, 保存结果， 准备引流用户
        if (empty($user)) {
            $result['showAuth'] = true;
            Logger::info('Passport Local Check, User Not Exists, Prepare Register' . $ppUserInfo['ppId']);
            return $result;
        }

        $result['userInfo'] = $user;
        $userCheck = $this->checkPassword($user, $password);

        // 第一次创建，且用户密码一致，直接通过
        if ($response['createPassportFlag'] && $userCheck) {
            Logger::info('Passport Local Check, CreatePassportFlag is True' . $ppUserInfo['ppId']);
            return $result;
        }

        // 身份信息验证
        $userIdnoCheck = empty($user['idcardpassed']) || $user['id_type'] != 1 || $this->idTypeMap[$user['id_type']] != $ppUserInfo['certType']
                         || $user['idno'] != $ppUserInfo['certNo'] || $user['real_name'] != $ppUserInfo['realname'];

        // 身份信息验证失败,拒绝登录
        if ($userIdnoCheck) {
            $result['authPass'] = false;
            unset($result['userInfo']);
            Logger::info('Passport Local Check, 身份信息不一致');
            return $result;
        }

        // 用户密码验证失败，通行证登录验证用户信息
        if (!$userCheck) {
            $result['needVerify'] = true;
            Logger::info('Passport Local Check, 用户名账号密码验证失败，需二次验证');
            return $result;
        }

        return $result;
    }

    /**
     * 用户密码手机号验证
     */
    private function checkPassword($user, $password)
    {
        $bo = BOFactory::instance('web');
        // 用户不存在或用户名密码错误，用户校验失败
        if ($bo->compilePassword($password) !== $user['user_pwd'])  {
            return false;
        }
        return true;
    }

    private function getRedis()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception('getRedisInstance failed');
        }
        return $redis;
    }

    /**
     * 暂存通行证鉴权信息
     */
    private function cacheAuthResponse($response)
    {
        $redis = $this->getRedis();
        $redisKey = sprintf(self::PASSPORT_AUTH_RESPONSE, $response['bizResponse']['ppUserInfo']['ppId']);
        return $redis->set($redisKey, json_encode($response), 'ex', 180);
    }

    public function savePPSession($ppId, $sessionId, $platform)
    {
        if (!in_array($platform, $this->platforms)) {
            throw new \Exception('错误的平台类型');
        }
        $redis = $this->getRedis();
        $cacheKey = sprintf(self::PASSPORT_SESSION_CACHE_KEY, $ppId);
        return $redis->HSET($cacheKey, $platform, $sessionId);
    }

    /**
     * 销毁web端会话，踢出用户
     */
    private function webSessionDestroy($sessionId)
    {
        session_start();
        session_id($sessionId);
        session_destroy();
    }

    /**
     * 销毁app端会话，踢出用户
     */
    private function appSessionDestroy($userId)
    {
        $bo = BOFactory::instance('app');
        $bo->kickoffToken($userId);
        return true;
    }

    /**
     * 用户通行证密码修改 本地打标记逻辑
     */

    private function markUserLocalVerify($ppId)
    {
        $passportModel = WangxinPassportModel::instance();
        $passportInfo = $passportModel->getPassportByPPid($ppId);
        if (empty($passportInfo)) {
            return true;
        }

        // 通行证引流用户不标记, 网信本地用户不标记
        if ($passportInfo['local_flag'] == WangxinPassportModel::FLAG_PASSPORT || $passportInfo['biz_name'] == 'WANGXINLICAI') {
            return true;
        }

        return $passportModel->updatePassportByPPid($ppId, ['verify_mark' => WangxinPassportModel::VERIFY_MARK_NEED]);
    }

    /**
     * 更新用户验证状态
     */
    public function localVerifyPass($mobile)
    {
        return WangxinPassportModel::instance()->updatePassportByMobile($mobile, ['verify_mark' => WangxinPassportModel::VERIFY_MARK_PASS]);
    }

    /**
     * 验证是否需要二次验证
     */
    public function needLocalVerify($mobile)
    {
        $passportInfo = WangxinPassportModel::instance()->getPassportByMobile($mobile);
        if (empty($passportInfo)) {
            return false;
        }

        if ($passportInfo['biz_name'] == 'WANGXINLICAI') {
            return false;
        }

        if ($passportInfo['local_flag'] == WangxinPassportModel::FLAG_PASSPORT) {
            return false;
        }

        if ($passportInfo['verify_status'] == WangxinPassportModel::VERIFY_MARK_PASS) {
            return false;
        }

        return true;
    }

    /**
     * 更新通行证信息
     */
    public function updatePassportInfo($ppId, $oldMobile, $newMobile, $requestId) {
        try{
            $result = ['code'=>0, 'msg'=>''];
            if (empty($ppId) || empty($oldMobile) || empty($newMobile) || empty($requestId)) {
                throw new \Exception('参数不能为空');
            }

            // 同步更新本地通行证
            $res = WangxinPassportModel::instance()->updatePassportByPPid($ppId, ['identity' => $newMobile]);
            if (!$res) {
                throw new \Exception('本地通行证信息更新失败');
            }

            // 修改用户标识接口
            $res = $this->updateIdentity($ppId, $oldMobile, $newMobile, $requestId);
            if (!$res) {
                throw new \Exception('修改通行证手机号失败');
            }
            return $result;
        } catch (\Exception $e) {
            $result = ['code'=>-1, 'msg'=>$e->getMessage()];
            Logger::info('Passport_updatePassportInfo_error:' . $e->getMessage());
            return $result;
        }
    }
}