<?php
namespace core\service;

use core\tmevent\passport\UpdateCertEvent;
use libs\utils\PaymentApi;
use libs\db\Db;
use \libs\utils\Logger;
use libs\common\ErrCode;
use libs\common\WXException;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Common\Library\Idworker;
use core\dao\UserModel;
use core\dao\UserIdentityModifyLogModel;
use core\dao\AccountAuthorizationModel;
use core\service\SupervisionBaseService; // 存管资金相关服务
use core\service\UserService;
use core\service\UserBankcardService;
use core\service\BankService;
use core\service\AccountService;
use core\service\ncfph\AccountService as PhAccountService;
use core\service\ncfph\DealService as PhDealService;
use core\tmevent\supervision\WxUpdateUserIdentityByLogEvent;
use libs\utils\Alarm;
use libs\utils\Monitor;
use libs\payment\supervision\Supervision;
use NCFGroup\Protos\Ptp\Enum\UserEnum;
use core\service\UserTagService;

/**
 * P2P存管-会员相关服务
 *
 */
class SupervisionAccountService extends SupervisionBaseService {

    const CARD_TYPE_DEBIT = 3;//银行卡类型 3借记卡
    const CARD_FLAG_PUB = 1;//银行卡标识 对公
    const CARD_FLAG_PRI = 2;//银行卡标识 对私

    /**
     * 是否在存管开户缓存key
     * @var string
     */
    const KEY_IS_SUPERVISION_USER = 'is_supervision_user_%s';

    /**
     * 存管权限列表缓存key
     * @var string
     */
    const KEY_SUPERVISION_GRANT_LIST = 'supervision_grant_list_%s';

    //忽略请求异常，默认不忽略
    public $ignoreReqExc = false;

    /**
     * 是否在存管开户
     * @throws \exception
     * @param mix $user 用户id或UserModel对象
     * @return boolean
     */
    public function isSupervisionUser($user, $needUserPurpose = 0) {
        //判断入参格式
        if ($user instanceof UserModel) {
            $userId = $user['id'];
            $userObject = $user;
        } else if (is_numeric($user)) {
            $userId = $user;
            $userObject = null;
        } else {
            throw new WXException('ERR_PARAM');
        }

        //读取缓存
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey = $needUserPurpose ? sprintf(self::KEY_IS_SUPERVISION_USER, $userId.'_'.$needUserPurpose) : sprintf(self::KEY_IS_SUPERVISION_USER, $userId);
        $cache = $redis->get($cacheKey);
        if ($cache !== null) {
            Logger::info('isSupervisionUserCache:'.json_encode($cache));
            return $needUserPurpose ? $cache : (bool)$cache;
        }

        if (empty($userObject)) {
            $userModel = new UserModel();
            $userObject = $userModel->find($userId, 'id,user_type,supervision_user_id,user_purpose', true);
        }


        //查询普惠接口
        $phAccountService = new PhAccountService();
        $accountId = $phAccountService->getUserAccountId($userId, intval($userObject['user_purpose']));
        $result = !empty($accountId) ? true : false;

        $svRes = [];
        if ($needUserPurpose) {
            $svRes['isSvUser'] = intval($result);
            $svRes['userPurpose'] = intval($userObject['user_purpose']);
            $result = json_encode($svRes);
        }

        //写缓存
        $redis->setex($cacheKey, 60, $result);
        Logger::info('isSupervisionUser:'.json_encode($result));
        return $result;
    }

    /**
     * 是否监管
     * 整合存管开关和用户开户
     * @param mix $user 用户id或UserModel对象
     * @return array
     */
    public function isSupervision($user) {
        $isSvOpen = $this->isSupervisionOpen();
        $isSvUser = false;
        if ($isSvOpen && !empty($user)) {
            $isSvUser = $this->isSupervisionUser($user);
        }
        return ['isSvOpen' => $isSvOpen, 'isSvUser' => $isSvUser];
    }

    /**
     * 判断用户是否是开通网信账户
     * @param id|obejct $user
     * @return
     */
    public function isUcfpayUser($user) {
        $result = false;
        if ($user instanceof UserModel) {
            $result = !empty($user['payment_user_id']) ? true : false;
            $userId = $user['id'];
        } else if (is_numeric($user)) {
            $userId = $user;
            $userModel = new UserModel();
            $userObject = $userModel->find($userId, 'payment_user_id', true);
            $result = !empty($userObject['payment_user_id']) ? true : false;
        } else {
            throw new WXException('ERR_PARAM');
        }

        //请求存管接口
        if (!$result) {
            $member = PaymentApi::instance()->request('searchuserinfo', ['userId' => $userId]);
            //用户未在存管开户
            if (isset($member['status']) && $member['status'] == '30003') {
                return false;
            }
            if (empty($member) || $member['respCode'] != self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_SEARCH');
            }
            if (!empty($member['userId'])) {
                $result = true;
            }
        }
        return $result;
    }


    /**
     * @noticeUrl http://xxxx.com/registerNotify
     */
    public function memberStandardRegisterPage($userId, $platform = 'pc', $bizParams = [], $returnForm = true, $isOnekeyRegister = false, $isApi = false) {
        try {
            // 获取该用户在超级账户的基本信息
            $userBaseData = UserModel::instance()->find(intval($userId), '*' , true);
            $isOpenSupervision = $this->isSupervisionUser($userBaseData);
            $userObj = new UserService($userBaseData);
            $params = [
                'orderId' => Idworker::instance()->getId(),
                'userId' => $userBaseData['id'],
                'realName' => $userBaseData['real_name'],
                'certType' => !empty(UserModel::$idCardType[$userBaseData['id_type']]) ? UserModel::$idCardType[$userBaseData['id_type']] : UserModel::$idCardType['default'],
                'certNo' => $userBaseData['idno'],
                'regionCode' => !empty($userBaseData['mobile_code']) ? $userBaseData['mobile_code'] : $GLOBALS['dict']['MOBILE_CODE']['cn']['code'], // 国家区域码
                'registeredCell' => $userBaseData['mobile'],
            ];
            // 用户的账户类型
            $userPurposeInfo = $userObj->getUserPurposeInfo($userBaseData['user_purpose']);
            !empty($userPurposeInfo['supervisionBizType']) && $params['bizType'] = $userPurposeInfo['supervisionBizType'];

            if (!empty($bizParams)) {
                $params = array_merge($params, $bizParams);
            }
            // 生成存管开户表单
            if ($userBaseData['id_type'] == 1) {
                $method = 'memberStandardRegister';

                // 已绑卡验卡走老开户流程，不用重新验证四要素
                $userBankcardObj = new UserBankcardService();
                $userBankCardData = $userBankcardObj->getBankcard($userId);
                if (!empty($userBankCardData['bankcard']) && $userBankCardData['verify_status'] == 1) {
                    $params['phone'] = $userBaseData['mobile'];
                    $params['bankCardNo'] = $userBankCardData['bankcard'];
                    $bankObj = new BankService();
                    $bankData = $bankObj->getBank($userBankCardData['bank_id']);
                    $params['bankCode'] = !empty($bankData['short_name']) ? strtoupper($bankData['short_name']) : '';
                    $method = 'memberRegister';
                }
            }else{
                $method = 'foreignMemberRegister';
            }
            $formId = 'bindCardForm';
            $targetNew = false;
            $result = $this->{$method}($params, $platform, $returnForm, $formId, $targetNew, $isOnekeyRegister);
            if (!isset($result['status']) || $result['status'] !== self::RESPONSE_SUCCESS) {
                throw new \Exception($result['respMsg'], $result['respCode']);
            }
            // 清除用户存管redis缓存
            $this->clearSupervisionUserCache($userId);
            return $result;
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 调用[Web/H5个人开户]页面
     * @param int $userId 用户UID
     * @param string $platform 平台来源
     * @param array $bizParams 业务参数数组
     * @param boolean $returnForm 返回form还是url
     * @param boolean $isOnekeyRegister 是否掌众用户开户页面
     * @return 跳转到存管系统
     */
    public function memberRegisterPage($userId, $platform = 'pc', $bizParams = [], $returnForm = true, $isOnekeyRegister = false, $isApi = false) {
        try {
            // 获取该用户在超级账户的基本信息
            $userBaseData = UserModel::instance()->find(intval($userId), '*' , true);
            $isOpenSupervision = $this->isSupervisionUser($userBaseData);
            // 用户已在存管账户开户
            //if (true === $isOpenSupervision) {
            //    throw new WXException('ERR_SUPERVISION_OPEN_ACCOUNT');
            //}
            // 查询用户在超级账户是否已完成实名
            if ($userBaseData['idcardpassed'] != 1) {
                throw new WXException('ERR_USER_NOT_REALNAME');
            }
            // 获取该用户在超级账户的绑卡信息
            $userBankcardObj = new UserBankcardService();
            $userBankCardData = $userBankcardObj->getBankcard($userId);
            // 查询用户在超级账户是否已完成绑卡
            if (empty($userBankCardData) || empty($userBankCardData['bankcard'])) {
                throw new WXException('ERR_NOT_BANDCARD');
            }
            // 查询用户在超级账户是否已完成验卡
            if (empty($userBankCardData['verify_status']) || $userBankCardData['verify_status'] != 1) {
                throw new WXException('ERR_CARD_NOT_VERIFY');
            }
            // 超级账户尚未开户
            if (empty($userBaseData['payment_user_id'])) {
                try{
                    // 尝试为用户开户
                    $paymentObj = new \core\service\PaymentService();
                    $ucfAccountRet = $paymentObj->register($userId);
                    if (!in_array($ucfAccountRet, array(\core\service\PaymentService::REGISTER_HASREGISTER, \core\service\PaymentService::REGISTER_SUCCESS))) {
                        throw new \Exception('ERR_UCFPAY_NOTOPEN');
                    }
                } catch (\Exception $e) {
                    throw new WXException($e->getMessage());
                }
            }

            $userObj = new UserService($userBaseData);
            $isEnterprise = $userObj->isEnterpriseUser();
            if ($isEnterprise === true) {
                // 企业用户列表里面的企业用户
                if ((int)$userBaseData['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                    $enterpriseInfo = $userObj->getEnterpriseInfo();
                    $userPurposeInfo = $userObj->getUserPurposeInfo($enterpriseInfo['company_purpose']);
                }else{
                    // 个人用户列表里面的企业用户
                    $userPurposeInfo = $userObj->getUserPurposeInfo($userBaseData['user_purpose']);
                }
                // 配置里面对应的存管账户类型为空时，不需要开存管账户
                if (empty($userPurposeInfo['supervisionBizType'])) {
                    throw new WXException('ERR_USER_NOOPEN_SUPERVISION');
                }
                $params = [
                     'userId' => $userId,
                     'bizType' => $userPurposeInfo['supervisionBizType'], // 业务类型(01-投资户|02-借款户|03-担保户|04-咨询户|05-平台户|06-借贷混合户|08-平台营销户|10-平台收费户|11-代偿户|12-第三方营销账户|13-垫资户)
                ];
                // 合并业务参数
                if (!empty($bizParams)) {
                    $params = array_merge($params, $bizParams);
                }
                $result = $this->enterpriseRegister($params, $returnForm, 'registerForm', false, $platform);
            }else{
                // 请求参数
                $params = [
                    'userId' => $userBaseData['id'],
                    'realName' => $userBaseData['real_name'],
                    'certType' => !empty(UserModel::$idCardType[$userBaseData['id_type']]) ? UserModel::$idCardType[$userBaseData['id_type']] : UserModel::$idCardType['default'],
                    'certNo' => $userBaseData['idno'],
                    'regionCode' => !empty($userBaseData['mobile_code']) ? $userBaseData['mobile_code'] : $GLOBALS['dict']['MOBILE_CODE']['cn']['code'], // 国家区域码
                    'phone' => $userBaseData['mobile'],
                ];
                // 用户的账户类型
                $userPurposeInfo = $userObj->getUserPurposeInfo($userBaseData['user_purpose']);
                !empty($userPurposeInfo['supervisionBizType']) && $params['bizType'] = $userPurposeInfo['supervisionBizType'];

                if (!empty($userBankCardData)) {
                    $params['bankCardNo'] = $userBankCardData['bankcard'];
                    $bankObj = new BankService();
                    $bankData = $bankObj->getBank($userBankCardData['bank_id']);
                    $params['bankCode'] = !empty($bankData['short_name']) ? strtoupper($bankData['short_name']) : '';
                }

                if ($isApi == false) {
                    // 合并业务参数
                    if (!empty($bizParams)) {
                        $params = array_merge($params, $bizParams);
                    }
                    // 生成存管开户表单
                    if ($userBaseData['id_type'] == 1) {
                        $method = 'memberRegister';
                    }else{
                        $method = 'foreignMemberRegister';
                    }
                    $formId = 'registerForm';
                    $targetNew = false;
                    $result = $this->{$method}($params, $platform, $returnForm, $formId, $targetNew, $isOnekeyRegister);
                } else {
                    $result = $this->memberRegisterApi($params);
                }
            }
            if (!isset($result['status']) || $result['status'] !== self::RESPONSE_SUCCESS) {
                throw new \Exception($result['respMsg'], $result['respCode']);
            }
            // 清除用户存管redis缓存
            $this->clearSupervisionUserCache($userId);
            return $result;
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 存管开户接口
     * @param array $params 需要参数
     */
    public function memberRegisterApi($params) {
        // 开户通知地址
        $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/registerNotify';

        $result = $this->api->request('memberRegisterApi', $params);
        \libs\utils\Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('memberRegisterApiRes:%s', json_encode($result)))));

        //用户已经开户
        if (isset($result['respSubCode']) && $result['respSubCode'] == '200101') {
            return $this->responseSuccess();
        }
        if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
            return $this->responseFailure($result['respSubCode'], $result['respMsg']);
        }
        return $this->responseSuccess();
    }

    /**
     * 生成存管开户表单
     * @param array $params 传入参数
     * @return array 输出结果
     * @param boolean $returnForm 是否返回formHtml，而不是直接跳转到页面
     * @param boolean $isOnekeyRegister 用户一键开户流程
     */
    public function memberStandardRegister($params, $platform = 'pc', $returnForm = true, $formId = 'registerForm', $targetNew = false, $isOnekeyRegister = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            if ($platform === 'pc') {
                $service = 'memberStandardRegister';
                $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/registerStandardNotify';
            }else{
                $service = 'h5MemberStandardRegister';
                $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/registerStandardNotify';
            }
            // 掌众H5开户增加simple接口配置
            if ($isOnekeyRegister && $platform == 'h5') {
                $service = 'memberQuickRegister';
                $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/registerNotify';
            }
            // 请求接口
            if ($returnForm) {
                $result = $this->api->getForm($service, $params, $formId, $targetNew);
                return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
            }else{
                $result = $this->api->getRequestUrl($service, $params);
                return $this->responseSuccess(['url' => $result]);
            }
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 生成存管开户表单
     * @param array $params 传入参数
     * @return array 输出结果
     * @param boolean $returnForm 是否返回formHtml，而不是直接跳转到页面
     * @param boolean $isOnekeyRegister 用户一键开户流程
     */
    public function memberRegister($params, $platform = 'pc', $returnForm = true, $formId = 'registerForm', $targetNew = false, $isOnekeyRegister = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            $service = $platform === 'pc' ? 'memberRegister' : 'h5MemberRegister';
            // 掌众H5开户增加simple接口配置
            if ($isOnekeyRegister && $platform == 'h5') {
                $service = 'memberQuickRegister';
            }
            // 开户异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/registerNotify';
            // 请求接口
            if ($returnForm) {
                $result = $this->api->getForm($service, $params, $formId, $targetNew);
                return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
            }else{
                $result = $this->api->getRequestUrl($service, $params);
                return $this->responseSuccess(['url' => $result]);
            }
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 存管银行港澳台用户开户
     * @throws \Exception
     * @param array $params 业务参数
     * @param string $platform 请求平台来源
     * @param boolean $returnForm 是否返回formHtml，而不是直接跳转到页面
     *
     * @return array
     */
    public function foreignMemberRegister($params, $platform = 'pc', $returnForm = true, $formId = 'registerForm', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            $apiName = $platform === 'pc' ? 'foreignMemberRegister' : 'h5ForeignMemberRegister';
            // 开户异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/foreignMemberRegisterNotify';
            if ($returnForm) {
                $result = $this->api->getForm($apiName, $params, $formId, $targetNew);
                return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
            }else{
                $result = $this->api->getRequestUrl($apiName, $params);
                return $this->responseSuccess(['url' => $result]);
            }
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 企业用户开户
     * @param array $params 业务参数
     * @param boolean $returnForm 是否返回formHtml，而不是直接跳转到页面
     * 必传参数：
     *     userId:P2P用户ID
     *     bizType:业务类型
     */
    public function enterpriseRegister($params, $returnForm = true, $formId = 'registerForm', $targetNew = false, $platform = 'pc') {
        try {
            $formKey = $platform == 'pc' ? 'enterpriseRegister' : 'h5EnterpriseRegister';
            // 开户异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/enterpriseRegisterNotify';
            // 请求接口
            if ($returnForm) {
                $result = $this->api->getForm($formKey, $params, $formId, $targetNew);
                return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
            }else{
                $result = $this->api->getRequestUrl($formKey, $params);
                return $this->responseSuccess(['url' => $result]);
            }
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 存管临时通知接口
     */
    public function registerStandardNotify($responseData)  {
        $result = $this->registerNotify($responseData);
        $userId = $responseData['userId'];
        if (isset($responseData['status']) && $responseData['status'] == 'S' ) {
            // 同步用户绑卡信息到用户表
            $memberCardInfo = $this->memberCardSearch($userId);
            if (isset($memberCardInfo['status']) && $memberCardInfo['status'] == 'S') {
                $bankCards = $memberCardInfo['data'];
                $bankcard = !empty($bankCards[0]) && is_array($bankCards[0]) ? $bankCards[0] : null;
                if ($bankcard) {
                    $userInfo = UserModel::instance()->find($responseData['userId'], 'real_name,user_type,supervision_user_id');
                    $db = \libs\db\Db::getInstance('firstp2p', 'master');
                    $bankId = 0;
                    // 检查是否可以绑卡
                    $bankService = new \core\service\BankService();
                    $canBind = $bankService->canBankcardBind($bankcard['cardNo'], $userId);
                    if ($canBind === false) {
                        return $this->responseFailure('绑卡失败', 10000);
                    }
                    //检查用户是否绑卡，兼容异常情况
                    $userBankInfo = $db->getRow("SELECT bank_id,bankcard FROM firstp2p_user_bankcard WHERE user_id = '{$userId}'");
                    if (!empty($userBankInfo)) {
                        return $this->responseSuccess();
                    }
                    $bank =[];
                    $bank['name'] = '';
                    if (!empty($bankcard['bankCode'])) {
                        $bankCode = addslashes(trim($bankcard['bankCode']));
                        $bank = $db->getRow("SELECT id,name FROM firstp2p_bank WHERE short_name = '{$bankCode}'");
                        if (!empty($bank)) {
                            $bankId = $bank['id'];
                        }
                    }
                    $data = [
                        'user_id' => $userId,
                        'bank_id' => $bankId, // 银行卡所属银行id
                        'card_name' => $userInfo['real_name'],
                        'bankcard' => addslashes(trim($bankcard['cardNo'])), // 银行卡卡号
                        'verify_status' => 0, // 已验卡
                        'status' => 1,  // 已绑卡
                        'cert_status' => 0, // 四要素认证方式
                        'create_time' => get_gmtime(),
                        'update_time' => get_gmtime(),
                    ];
                    $db->autoExecute("firstp2p_user_bankcard", $data, 'INSERT');
                    // TODO先锋支付绑卡
                    $ucfpayData = [
                        'userId' => $userId,
                        'cardNo' => trim($bankcard['cardNo']),
                        'bankCode' => trim($bankcard['bankCode']),
                        'bankName' => trim($bank['name']),
                        'bankCardName' => $userInfo['real_name'],
                        'cardType' => 1, // 默认借记卡
                        'businessType' => 1, //新增银行卡
                    ];
                    $reqResult = PaymentApi::instance()->request('bindbankcard', $ucfpayData);
                    if (!isset($reqResult['status']) || $reqResult['status'] != '00') {
                        return $this->responseFailure('同步支付绑卡失败', 10000);
                    }
                }
            }
            // 更新用户银行卡数据
            $this->memberInitAuth($userId);
        }
        return $result;
    }

    /**
     * 普通/企业用户开户-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     userId:P2P用户ID
     *     status:开户状态(S-成功；F-失败)
     *     remark:备注
     */
    public function registerNotify($responseData) {
        try {
            // 开户失败，不处理
            if ($responseData['status'] !== self::RESPONSE_SUCCESS) {
                return $this->responseSuccess();
            }

            //删除激活TAG
            $tagService = new UserTagService();
            $tagService->delUserTagsByConstName($responseData['userId'], UserEnum::SV_UNACTIVATED_USER);

            // 查询用户是否已在存管系统开户
            $userInfo = UserModel::instance()->find($responseData['userId'], 'user_type,supervision_user_id');
            if (!empty($userInfo['supervision_user_id'])) {
                return $this->responseSuccess();
            }

            $db = Db::getInstance('firstp2p');
            $db->startTrans();
            if (empty($responseData['userId']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            // 更新用户存管系统id
            $ret = UserModel::instance()->updateSupervisionUserId($responseData['userId']);
            if (false === $ret) {
                throw new WXException('ERR_OPEN_ACCOUNT_FAILED');
            }

            // 更新企业用户存管系统id
            if ($userInfo['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                $ret = \core\dao\EnterpriseModel::instance()->updateEnterpriseSupervisionUserId($responseData['userId']);
                if (false === $ret) {
                    throw new WXException('ERR_OPEN_ACCOUNT_FAILED');
                }
            }
            $db->commit();

            // 清除用户存管redis缓存
            $this->clearSupervisionUserCache($responseData['userId']);
            return $this->responseSuccess();
        } catch(\Exception $e) {
            $db->rollback();
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管开户回调异常|用户ID:%d，存管回调参数:%s，异常内容:%s', $responseData['userId'], json_encode($responseData), $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_REGISTERCALLBACK');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 企业用户修改-接口
     * @param $params 参数列表
     * @return array
     *
     */
    public function enterpriseUpdateApi($params) {
        try {
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/enterpriseUpdateNotify';
            // 请求接口
            $result = $this->api->request('enterpriseUpdateApi', $params);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200001') {
                // 用户未在存管开户不用报错，否则gtm会一直重试
                //throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200101') {
                throw new WXException('ERR_ENTERPRISE_AUDITING');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_ENTERPRISE_UPDATE_FAILED');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 企业用户修改-页面
     * @param $params 参数列表
     * @return array
     *
     */
    public function enterpriseUpdate($params, $platform = 'pc', $formId = 'enterpriseForm', $targetNew = false) {
        try {
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/enterpriseUpdateNotify';
            // 请求接口
            $result = $this->api->getForm('enterpriseUpdate', $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 企业用户更新-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     userId:P2P用户ID
     *     status:状态(S-成功；F-失败)
     */
    public function enterpriseUpdateNotify($responseData) {
        try {
            if (empty($responseData['userId']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            // 更新理财系统的企业用户资料@TODO

            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 用户信息查询
     * @param int $userId 用户ID
     * @return array
     */
    public function memberSearch($userId) {
        try {
            // 请求接口
            $result = $this->api->request('memberSearch', ['userId'=>$userId]);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200001') {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_SEARCH');
            }
            unset($result['respCode'], $result['respSubCode'], $result['respMsg']);
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 用户存管余额查询
     * @param int $userId 用户ID
     * @return array
     */
    public function balanceSearch($userId) {
        try {
            // 请求接口
            $result = $this->api->request('memberBalanceSearch', ['userId'=>$userId]);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200001') {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_BALANCE_SEARCH');
            }
            unset($result['respCode'], $result['respSubCode'], $result['respMsg']);
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 会员银行卡查询/会员绑卡查询
     * @param int $userId 用户ID
     * @return array
     */
    public function memberCardSearch($userId) {
        try {
            // 请求接口
            $result = $this->api->request('memberCardSearch', ['userId'=>$userId]);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_CARD_SEARCH');
            }
            return $this->responseSuccess((!empty($result['bankCards']) ? $result['bankCards'] : []));
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 修改/更换银行卡-接口
     * @param array $params 参数列表
     * @return array
     */
    public function memberCardUpdate($userId, $cardInfo = []) {
        try {
            // 获取该用户在超级账户的绑卡信息
            $userBankcardObj = new UserBankcardService();
            $userBankCardData = $userBankcardObj->getBankcard($userId, false);
            // 查询用户在超级账户是否已完成绑卡
            if (empty($userBankCardData) || empty($userBankCardData['bankcard'])) {
                throw new WXException('ERR_NOT_BANDCARD');
            }
            $bankObj = new BankService();
            $bankData = $bankObj->getBank($userBankCardData['bank_id']);
            // 请求参数
            $params = [
                'userId' => $userId,
                'bankCardNo' => $userBankCardData['bankcard'],
                'bankName' => !empty($bankData['name']) ? $bankData['name'] : '',
                'bankCode' => !empty($bankData['short_name']) ? strtoupper($bankData['short_name']) : '',
            ];
            if ($cardInfo !== []) {
                $params['bankCardNo'] = $cardInfo['bank_bankcard'];
                $params['bankName'] = $cardInfo['bank_name'];
                $params['bankCode'] = $cardInfo['short_name'];
                // 银行开户名
                if (!empty($cardInfo['bank_cardname'])) {
                    $params['bankCardName'] = $cardInfo['bank_cardname'];
                }
            }
            $userObj = new UserService($userId);
            $isEnterprise = $userObj->isEnterpriseUser();
            if ($isEnterprise === true) {
                if (empty($userBankCardData['branch_no'])) {
                    throw new WXException('ERR_ENTERPRISE_NOBRANCHNO');
                }
                // 银行卡联行号
                $branchNo = !empty($cardInfo['branch_no']) ? $cardInfo['branch_no'] : $userBankCardData['branch_no'];
                $params['cardFlag'] = self::CARD_FLAG_PUB; // 银行卡类型（只能是借记卡）(1：对公账户 2：对私账户)
                // 获取银行卡联行号
                $bankInfo = \core\dao\BanklistModel::instance()->findBy('bank_id = \':bank_id\'', 'id,name', array(':bank_id' => $branchNo));
                $params['issuerName'] = !empty($bankInfo['name']) ? $bankInfo['name'] : ''; // 支行名称，对公账户必填
                $params['issuer'] = $branchNo; // 支行-联行号，对公账户必填
            }else{
                $params['cardFlag'] = self::CARD_FLAG_PRI; // 银行卡类型（只能是借记卡）(1：对公账户 2：对私账户)
            }

            //开户名
            $user = UserModel::instance()->find($userId, 'real_name');
            if (!empty($user['real_name'])) {
                $params['bankCardName'] = $user['real_name'];
            }

            // 请求接口
            $result = $this->api->request('memberCardUpdate', $params);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200001') {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }

            //用户没有绑卡，请求绑卡 200230
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200230') {
                $certStatusMap = array_flip(\core\dao\UserBankcardModel::$cert_status_map);
                $params['cardType'] = self::CARD_TYPE_DEBIT;//借记卡
                $params['cardCertType'] = isset($certStatusMap[$userBankCardData['cert_status']]) ? $certStatusMap[$userBankCardData['cert_status']] : 'NO_CERT';//认证类型
                $bindResult = $this->memberCardBind($params);
                if (!isset($bindResult['status']) || $bindResult['status'] !== self::RESPONSE_SUCCESS) {
                    throw new \Exception($bindResult['respMsg'], $bindResult['respCode']);
                }
                return $this->responseSuccess();
            }

            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_CARD_UPDATE');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管修改银行卡|用户ID:%d，异常内容:%s', $userId, $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_MEMBERCARDUPDATE');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 解绑银行卡-接口
     * @param int $userId 用户ID
     * @param int $bankCardNo 银行卡号
     * @return array
     */
    public function memberCardUnbind($userId, $bankCardNo, $isAdmin = false) {
        try {
            if (empty($userId) || empty($bankCardNo)) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            // 查询用户在超级账户是否已完成绑卡
            $userBankcardObj = new UserBankcardService();
            $userBankCardData = $userBankcardObj->getBankcard($userId);
            if (empty($userBankCardData) || empty($userBankCardData['bankcard'])) {
                PaymentApi::log('memberCardUnbind, WxBankCardInfo Is Empty, Not Need Unbind, userId:'.$userId);
                return true;
            }
            $gtmName = 'adminCardUnbind';
            if (!$isAdmin) {
                $gtmName = 'memberCardUnbind';
            }
            // 只查询超级账户的资产总额
            //$isWxlcMoneyZero = $this->isZeroUserAssets($userId, true);
            // 判断用户总资产是否为零
            $isMoneyZero = $this->isZeroUserAssets($userId);
            if (!$isMoneyZero) {
                throw new WXException('ERR_MEMBERCARD_UNBIND_NOTZERO');
            }

            $gtm = new GlobalTransactionManager();
            $gtm->setName($gtmName);
            // 用户已在网信账户开户
            $isUcfpayUser = $this->isUcfpayUser($userId);
            if ($isUcfpayUser) {
                $gtm->addEvent(new \core\tmevent\supervision\UcfpayCardUnbindEvent($userId, $bankCardNo));
            }
            // 用户已在存管账户开户或者是存管预开户用户
            $isSupervisionUser = $this->isSupervisionUser($userId);
            $svService = new \core\service\SupervisionService();
            if ($isSupervisionUser || $svService->isUpgradeAccount($userId)) {
                $gtm->addEvent(new \core\tmevent\supervision\SupervisionCardUnbindEvent($userId, $bankCardNo));
            }
            // 网信理财-解绑银行卡Event
            $gtm->addEvent(new \core\tmevent\supervision\WxCardUnbindEvent($userId, $bankCardNo));
            $unbindRet = $gtm->execute();
            if (true !== $unbindRet) {
                throw new \Exception($gtm->getError());
            }
            return $unbindRet;
        } catch (\Exception $e) {
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, sprintf('memberCardUnbind,ExceptionCode:%s,ExceptionMsg:%s', $e->getCode(), $e->getMessage()))));
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管解绑银行卡|用户ID:%d，银行卡号:%s，异常内容:%s', $userId, $bankCardNo, $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_MEMBERCARDUNBIND');
            return false;
        }
    }

    /**
     * H5修改/更换银行卡-页面
     * @param array $params 参数列表
     * @return array
     */
    public function h5MemberCardChange($params, $formId = 'h5CardChangeForm', $targetNew = false) {
        try {
            // 请求存管页面
            $result = $this->api->getForm('h5MemberCardChange', $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * H5设置密码-页面
     * @param array $params 参数列表
     * @return array
     */
    public function h5MemberPasswordSet($params, $formId = 'h5PasswordSetForm', $targetNew = true) {
        try {
            // 请求存管页面
            $result = $this->api->getForm('h5MemberPasswordSet', $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 修改手机号-接口
     * @param array $params 参数列表
     * @return array
     */
    public function memberPhoneUpdate($userId, $mobile) {
        try {
            $params = ['userId'=>(int)$userId, 'phone'=>addslashes($mobile)];
            // 请求接口
            $result = $this->api->request('memberPhoneUpdate', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_PHONE_UPDATE');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 存管行用户资金记录日志
     *
     * @param integer $userId
     */
    public function memberLog($userId, $platform = 'pc', $params = [], $formId = 'memberLogForm', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            $params['userId'] = $userId;
            $service = 'accountLogPage';
            $result = $this->api->getForm($service, $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage);
        }
    }

    /*
     * 存管银行个人页面
     * @param integer $userId 用户Id
     *
     * @return array
     */
    public function memberInfo($userId, $platform = 'pc', $params = [], $formId = 'memberInfoForm', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            $params['userId'] = $userId;
            $service = $platform === 'pc' ? 'memberInfo' : 'h5MemberInfo';
            $result = $this->api->getForm($service, $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage);
        }
    }

    /*
     * 存管用户开通授权大礼包
     * @param integer $userId 用户Id
     *
     * @return array
     */
    public function memberInitAuth($userId) {
        try {
            // 请求接口
            $result = $this->api->request('memberInitAuth', ['userId'=>$userId]);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_SEARCH');
            }
            unset($result['respCode'], $result['respSubCode'], $result['respMsg']);
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /*
     * 超级账户个人页面
     * @param integer $userId 用户Id
     *
     * @return array
     */
    public function superMemberInfo($userId, $platform = 'pc', $params = [], $formId = 'superMemberInfoFrom', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            $params['userId'] = $userId;

            //网信账户个人信息页，增加bankCardListUrl参数，可控制多卡入口
            if ($platform == 'h5') {
                $accountService = new AccountService();
                $userService = new UserService();
                $userInfo = UserModel::instance()->find($userId);
                $isMainlandRealAuthUser = $accountService->isMainlandRealAuthUser($userInfo);
                $isEnterpriseUser = $userService->checkEnterpriseUser($userId);
                $inMultiCardWhite = $accountService->inMultiCardWhite($userInfo);
                //非企业 大陆实名且在白名单里
                if ( !$isEnterpriseUser && $isMainlandRealAuthUser && $inMultiCardWhite ) {
                    $params['bankCardListUrl'] = 'firstp2p://api?type=native&name=other&pageno=35';
                }
            }

            $service = $platform === 'pc' ? 'queryUserInfo' : 'h5QueryUserInfo';
            $result = PaymentApi::instance()->getGateway()->getForm($service, $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage);
        }
    }

    /**
     * web/h5设置用户授权
     * @param $params 参数列表
     * 必传参数：
     *     userId:P2P用户ID
     *     grantList:用户授权列表(INVEST:免密投标 WITHDRAW:免密提现,此处可传以上一个或多个值，传多个值用“,”英文半角逗号分隔)
     *     returnUrl:前台通知地址URL长度不能大于256
     */
    public function memberAuthorizationCreate($params, $platform = 'pc', $formId = 'authorizationForm', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/memberAuthorizationCreateNotify';
            $service = $platform === 'pc' ? 'memberAuthorizationCreate' : 'h5MemberAuthorizationCreate';
            // 请求接口
            $result = $this->api->getForm($service, $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 授权回调，同步本地数据
     * @param array $responseData
     * 必传参数：
     *     userId:账户ID
     *     status:状态
     *     grantList:授权类型，多笔用逗号分割
     *     grantAmountList:授权金额，单位 分/笔
     *     grantTimeList:授权周期，默认年
     */
    public function memberAuthorizationCreateNotify($responseData) {
        try {
            if (empty($responseData['userId']) || empty($responseData['status']) || empty($responseData['grantList'])) {
                    throw new \Exception('缺少参数');
            }

            // 授权失败，不处理
            if ($responseData['status'] !== self::RESPONSE_SUCCESS) {
                return $this->responseSuccess();
            }

            $accountId = intval($responseData['userId']);//账户ID
            $userId = intval($responseData['userId']);;// 账号ID，由账户ID反查 @todo
            $grantList = explode(',', $responseData['grantList']);
            $grantAmountList = !empty($responseData['grantAmountList']) ? explode(',', $responseData['grantAmountList']) : [];
            $grantTimeList = !empty($responseData['grantTimeList']) ? explode(',', $responseData['grantTimeList']) : [];

            $accountAuthModel = AccountAuthorizationModel::instance();
            $db = Db::getInstance('firstp2p');
            $db->startTrans();
            foreach ($grantList as $index => $grantName) {
                $grantType = AccountAuthorizationModel::$grantTypeMap[$grantName]; //授权类型
                $grantAmount = isset($grantAmountList[$index]) ? bcmul($grantAmountList[$index], 1000000) : 0; //授权金额，万转分
                $grantTime = isset($grantTimeList[$index]) ? strtotime($grantTimeList[$index] . ' 23:59:59') : 0; //授权期限
                $params = [
                    'accountId'         => $accountId,
                    'userId'            => $userId,
                    'grantType'         => $grantType,
                    'grantAmount'       => $grantAmount,
                    'grantTime'         => $grantTime,
                ];
                $accountAuthModel->saveAuth($params);
            }
            $db->commit();
            return $this->responseSuccess();
        } catch (\Exception $e) {
            isset($db) && $db->rollback();
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, sprintf('AuthNotify,ExceptionCode:%s,ExceptionMsg:%s', $e->getCode(), $e->getMessage()))));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }

    }

    /**
     * web/h5取消用户授权-页面
     * @param $params 参数列表
     */
    public function memberAuthorizationCancelPage($params, $platform = 'pc', $formId = 'authorizationCancelForm', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }

            $service = $platform === 'pc' ? 'webMemberAuthorizationCancel' : 'h5MemberAuthorizationCancel';
            // 请求接口
            $result = $this->api->getForm($service, $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 取消用户授权-接口
     * @param int $userId 用户ID
     * @param array $grantList 授权列表数组
     * @return array
     */
    public function memberAuthorizationCancel($userId, $grantList) {
        try {
            // 请求参数
            $params = [
                'userId' => intval($userId),
                'grantList' => !empty($grantList) ? join(',', $grantList) : '',
            ];
            // 请求接口
            $result = $this->api->request('memberAuthorizationCancel', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_AUTHORIZATION_CANCEL');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 用户授权查询
     * @param int $userId 用户ID
     * @return array
     */
    public function memberAuthorizationSearch($userId) {
        try {
            // 请求参数
            $params = [
                'userId' => intval($userId),
            ];
            // 请求接口
            $result = $this->api->request('memberAuthorizationSearch', $params);
            // 接口请求异常
            if (empty($result)) {
                throw new WXException('ERR_AUTHORIZATION_SEARCH_REQUEST');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_AUTHORIZATION_SEARCH');
            }
            $grantList = !empty($result['grantList']) ? $result['grantList'] : '';
            return $this->responseSuccess($grantList);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 绑定银行卡 - 接口
     * @param array $params 传入参数
     * @return array
     */
    public function memberCardBind($params) {
        try {
            // 参数校验-银行卡类型(1：对公账户2：借记卡)
            if (!empty($params['cardType']) && $params['cardType'] == self::CARD_FLAG_PUB) {
                if (empty($params['issuerName']) || empty($params['issuer'])) {
                    throw new WXException('ERR_PARAM_LOSE');
                }
            }

            // 请求接口
            $result = $this->api->request('memberCardBind', $params);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200108') {
                return $this->responseSuccess();
            }

            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_CARD_BIND');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 账户注销
     * @param int $userId 用户ID
     * @return array
     */
    public function memberCancel($userId) {
        try{
            // 只查询超级账户的资产总额
            //$isWxlcMoneyZero = $this->isZeroUserAssets($userId, true);
            // 查询超级账户+存管账户的资产总额
            $isMoneyZero = $this->isZeroUserAssets($userId);
            if (!$isMoneyZero) {
                throw new WXException('ERR_ASSET_NOTZERO');
            }
            // 查询用户信息
            $userInfo = UserModel::instance()->find($userId, 'id,payment_user_id,supervision_user_id,is_effect', true);
            if (empty($userInfo)) {
                throw new WXException('ERR_USER_NOEXIST');
            }
            // 用户已被注销
            if ($userInfo['is_effect'] == 0 && $userInfo['payment_user_id'] == 0 && $userInfo['supervision_user_id'] == 0) {
                return $this->responseSuccess();
            }

            $gtm = new GlobalTransactionManager();
            $gtm->setName('userCancel');
            // 超级账户-销户的Event
            // 用户已在网信账户开户
            $isUcfpayUser = $this->isUcfpayUser($userId);
            if ($isUcfpayUser) {
                $gtm->addEvent(new \core\tmevent\supervision\UcfpayCancelUserEvent($userId));
            }
            // 用户已在存管账户开户
            $isOpenSupervision = $this->isSupervisionUser($userInfo);
            $svService = new \core\service\SupervisionService();
            if (true === $isOpenSupervision || $svService->isUpgradeAccount($userId)) {
                // 存管账户-销户的Event
                $gtm->addEvent(new \core\tmevent\supervision\SupervisionCancelUserEvent($userId));
            }
            // 网信理财-销户的Event
            $gtm->addEvent(new \core\tmevent\supervision\WxCancelUserEvent($userId));
            // 同步执行
            $cancelRet = $gtm->execute();
            if (true !== $cancelRet) {
                throw new \Exception($gtm->getError());
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, sprintf('isUcfpayUser:%d,isOpenSupervision:%d,ExceptionCode:%s,ExceptionMsg:%s', (int)$isUcfpayUser, (int)$isOpenSupervision, $e->getCode(), $e->getMessage()))));
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管账户注销|用户ID:%d，异常内容:%s', $userId, $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_MEMBERCANCEL');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 账户注销-请求存管系统接口
     * @param int $userId 用户ID
     * @return array
     */
    public function supervisionMemberCancel($userId) {
        try {
            // 请求接口
            $result = $this->api->request('memberCancel', ['userId'=>$userId]);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new \Exception($result['respMsg'], $result['respCode']);
            }
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 检查用户是否存在某个或者某几个权限
     * @param integer $userId 用户id
     * @param array $needPrivileges 需要检查的授权类型
     * @return boolean
     */
    public function checkUserPrivileges($userId, array $needPrivileges) {
        //服务降级状态下，用户无授权
        if (Supervision::isServiceDown() || empty($needPrivileges)) {
            return false;
        }
        // 用户授权查询
        $userPrivilegs = $this->memberAuthorizationSearch($userId);
        //接口请求异常时，返回已开通
        if ($this->ignoreReqExc && isset($userPrivilegs['respCode']) && $userPrivilegs['respCode'] == ErrCode::getCode('ERR_AUTHORIZATION_SEARCH_REQUEST')) {
            return true;
        }
        if (empty($userPrivilegs) || $userPrivilegs['status'] != self::RESPONSE_SUCCESS || empty($userPrivilegs['data'])) {
            return false;
        }
        $hasPrivilege = true;
        foreach ($needPrivileges as $privilege) {
            if (strpos($userPrivilegs['data'], $privilege) === false) {
                $hasPrivilege = false;
            }
        }
        return $hasPrivilege;
    }

    /**
     * 判断用户总资产是否为零
     * @param int $userId 用户ID
     * @param boolean $onlyTestWx 是否只查询超级账户余额
     * @return array  false 总资产不为0  true 总资产为0
     */
    public function isZeroUserAssets($userId, $onlyCheckWx = false) {
        $accountObj = new AccountService();
        $dealService = new DealService();
        $isUserHasAssets = $accountObj->isUserHasAssets($userId);
        // 检查用户在网信是否有在途借款
        $unrepayMoney = $dealService->getUnrepayMoneyByUid($userId);
        $hasUnrepay = bccomp($unrepayMoney, '0.00', 2) > 0;
        if ($hasUnrepay || $isUserHasAssets)
        {
            return false;
        }

        // 只查询网信账户总资产是否为0
        if ($onlyCheckWx) {
            return $isUserHasAssets? false : true;
        }

        // 当网信总资产为0并且用户没有网贷在途借款时， 还得检查网贷总资产是否为0
        $isSupervisionUser = $this->isSupervisionUser($userId);
        $svService = new \core\service\SupervisionService();
        if (!$isSupervisionUser && !$svService->isUpgradeAccount($userId)) {
            return true;
        }
        // 查询用户在存管是否有未还清借款
        $hasP2pUnrepay = PhDealService::getUnrepayP2pMoneyByUids([$userId]);
        $hasP2pUnrepay = bccomp($hasP2pUnrepay, '0.00', 2) > 0;
        //存管降级 或者 网贷有在途借款
        if (Supervision::isServiceDown() || $hasP2pUnrepay)
        {
            return false;
        }
        // 查询存管账户的可用余额
        $memberBalance = $this->balanceSearch($userId);
        // 用户未在存管系统开户
        if (isset($memberBalance['respCode']) && $memberBalance['respCode'] == ErrCode::getCode('ERR_NOT_OPEN_ACCOUNT')) {
            return true;
        }
        if (!isset($memberBalance['status']) || $memberBalance['status'] !== self::RESPONSE_SUCCESS) {
            return false;
        }
        // 存管账户的可用余额+冻结金额
        $supervisionBalance = bcadd($memberBalance['data']['availableBalance'], $memberBalance['data']['freezeBalance'], 2);
        // 存管账户的总余额为0
        if (bccomp($supervisionBalance, '0.00', 2) == 0) {
            return true;
        }
        return false;
    }

    /**
     * 存管系统解绑银行卡
     * @param string $userId 用户id
     * @param string $bankcardNo 银行卡号
     */
    public function unbindCard($userId, $bankcardNo) {
        try {
            if (empty($userId) || empty($bankcardNo)) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $params = [];
            $params['userId'] = $userId;
            $params['bankCardNo'] = $bankcardNo;
            // 请求接口
            $result = $this->api->request('memberCardUnbind', $params);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200006') {
                throw new WXException('ERR_BANKCARD_NOT_EXIST');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new \Exception($result['respMsg'], $result['respCode']);
            }
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 实名信息修改 - 接口
     * @param array $params 传入参数
     * @return array
     */
    public function memberInfoModify($params) {
        try {
            // 请求接口
            $params['noticeUrl'] = app_conf('NOTIFY_DOMAIN').'/user/memberInfoModifyNotify';
            $result = $this->api->request('memberInfoModify', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_INFO_MODIFY_FAILED');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 实名信息更改-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     userId:P2P用户ID
     *     orderId:订单号
     *     status:开户状态(S-成功；F-失败)
     *     failReason:失败原因
     */
    public function memberInfoModifyNotify($responseData) {
        try {

            if (empty($responseData['orderId']) || empty($responseData['userId']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $userId = (int) $responseData['userId'];
            $orderId = (int) $responseData['orderId'];
            $failReason = isset($responseData['failReason']) ? addslashes($responseData['failReason']) : '';

            //检查订单号
            $logModel = new UserIdentityModifyLogModel();
            $log = $logModel->getLogByOrderId($responseData['orderId']);
            if (empty($log) || $userId != $log['user_id']) {
                throw new WXException('ERR_ORDER_NOT_EXIST');
            }

            //幂等
            $logStatusMap = [
                self::RESPONSE_SUCCESS => UserIdentityModifyLogModel::STATUS_SUCCESS,
                self::RESPONSE_FAILURE => UserIdentityModifyLogModel::STATUS_FAILURE,
            ];
            $status = $logStatusMap[$responseData['status']];
            if ($status == $log['status']) {
                return $this->responseSuccess();
            }

            // 启动GTM管理器
            $gtm = new GlobalTransactionManager();
            $gtm->setName('updateUserIdentity');

            // 网信理财-更新用户实名信息
            $updateLogParams = [
                'user_id' => $userId,
                'order_id' => $orderId,
                'fail_reason' => $failReason,
                'status' => $status,
            ];
            $gtm->addEvent(new WxUpdateUserIdentityByLogEvent($updateLogParams));

            //成功才同步支付,取一下旧的用户信息
            $userInformation = \core\dao\UserModel::instance()->find($userId, '*', true);
            if ($responseData['status'] == self::RESPONSE_SUCCESS) {
                // 已经开户的用户才跟先锋支付同步资料
                if (!empty($userInformation['payment_user_id']))
                {
                    $params = [];
                    $params['id'] = $userId;
                    $params['newData'] = [
                        'real_name' => $log['real_name'],
                        'id_type' => $log['id_type'],
                        'idno' => $log['idno'],
                        'mobile' => $userInformation['mobile'],
                        'mobile_code' => $userInformation['mobile_code'],
                    ];
                    $gtm->addEvent(new EventMaker([
                        'commit' => [(new \core\service\PaymentUserAccountService), 'modifyUserInfo', $params],
                    ]));
                }
                // 通行证相关
                $passportService = new PassportService();
                if ($passportInfo = $passportService->isLocalPassport($userId)) {
                    $oldCertInfo = [
                        'certType' => $passportService->idTypeMap[$userInformation['id_type']],
                        'certNo' => $userInformation['idno'],
                        'realname' => $userInformation['real_name']
                    ];
                    $newCertInfo = [
                        'certType' => $passportService->idTypeMap[$log['id_type']],
                        'certNo' => $log['idno'],
                        'realname' => $log['real_name']
                    ];
                    $gtm->addEvent(new UpdateCertEvent($passportInfo['ppid'], $oldCertInfo, $newCertInfo));
                }
            }

            $gtmRet = $gtm->execute();
            if (!$gtmRet) {
                throw new \Exception($gtm->getError());
            }

            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管实名更新回调错误|用户ID:%d，存管回调参数:%s，异常内容:%s', $userId, json_encode($responseData), $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_MEMBERINFOMODIFY_CALLBACK');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 检查是否已授权[免密投资]、[免密提现至超级账户]快捷投资服务
     * @param int $userId
     * 老授权不校验
     */
    public function isQuickBidAuthorization($userId) {
        return true;
        return $this->checkUserPrivileges($userId, [self::GRANT_INVEST, self::GRANT_WITHDRAW_TO_SUPER]);
    }

    /**
     * 检查银信通是否已授权[免密提现至银信通账户]
     * @param int $userId
     * 老授权不校验
     */
    public function isYxtAuthorization($userId) {
        return $this->checkUserPrivileges($userId, [self::GRANT_WITHDRAW_TO_YXT]);
    }

    /**
     * 清除用户所有的存管缓存
     * @param int $userId
     */
    public function clearUserAllSupervisionCache($userId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey1 = sprintf(self::KEY_IS_SUPERVISION_USER, $userId);
        $cacheKey2 = sprintf(self::KEY_SUPERVISION_GRANT_LIST, $userId);
        return $redis->del([$cacheKey1, $cacheKey2]);
    }

    /**
     * 清除存管是否在用户开户的redis缓存
     * @param int $userId
     */
    public function clearSupervisionUserCache($userId) {
        //清理缓存
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey = sprintf(self::KEY_IS_SUPERVISION_USER, $userId);
        return $redis->del($cacheKey);
    }

    /**
     * 获取用户权限列表redis缓存
     * @param int $userId
     */
    public function getGrantListCache($userId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey = sprintf(self::KEY_SUPERVISION_GRANT_LIST, $userId);
        return $redis->get($cacheKey);
    }

    /**
     * 获取用户权限列表redis缓存
     * @param int $userId
     * @param string $grantList
     */
    public function setGrantListCache($userId, $grantList) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey = sprintf(self::KEY_SUPERVISION_GRANT_LIST, $userId);
        return $redis->setex($cacheKey, 60, $grantList);
    }

    /**
     * 清除用户权限列表redis缓存
     * @param int $userId
     */
    public function clearGrantListCache($userId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey = sprintf(self::KEY_SUPERVISION_GRANT_LIST, $userId);
        return $redis->del($cacheKey);
    }

    public function updateUserPurpose($userId, $localPurpose, $purpose, $grantList = []) {
        try {
            //支付端
            $params = array(
                'userId' => $userId,
                'bizType' => $purpose,
            );
            //刷新授权
            if (!empty($grantList)) {
                $params['grantList'] = implode(',', $grantList);
            }
            $result = $this->api->request('biztypeModify', $params);
            PaymentApi::log("supervion biztypeModify request. userId:{$userId}, purpose:{$purpose}, result:".json_encode($result, JSON_UNESCAPED_UNICODE));
            if (!isset($result['respCode']) || $result['respCode'] != '00') {
                throw new \Exception($result['respMsg']);
            }
            //数据库
            $data = array(
                'user_purpose' => $localPurpose,
            );
            $accountUpdateData = [
                'account_type' => $localPurpose,
            ];
            // 账号表中的状态
            $resultUser = Db::getInstance('firstp2p')->update('firstp2p_user', $data, "id='$userId'");
            $resultAccount = Db::getInstance('firstp2p')->update('firstp2p_user_third_balance', $accountUpdateData, "user_id='$userId'");

            //刷新授权
            $accountAuthModel = AccountAuthorizationModel::instance();
            foreach ($grantList as $grantName) {
                $grantType = AccountAuthorizationModel::$grantTypeMap[$grantName];
                $authParams = [
                    'accountId'         => $userId,
                    'userId'            => $userId,
                    'grantType'         => $grantType,
                    'grantAmount'       => 0, //无限制
                    'grantTime'         => 0, //无限制
                ];
                $accountAuthModel->saveAuth($authParams);
            }

            PaymentApi::log("update user purpose. userId:{$userId}, purpose:{$localPurpose}, result:{$result}");
            return true;
        } catch (\Exception $e) {
            PaymentApi::log("update user purpose exception. userId:{$userId},localPurpose:{$localPurpose} purpose:{$purpose}, result:".$e->getMessage());
            return $e->getMessage();
        }
    }
}
