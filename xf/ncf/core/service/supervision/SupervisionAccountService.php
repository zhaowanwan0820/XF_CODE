<?php
namespace core\service\supervision;

use core\enum\MsgbusEnum;
use core\service\msgbus\MsgbusService;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Common\Library\GTM\Toolkit\EventMaker;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\Zhuge;
use libs\db\Db;
use libs\utils\Logger;
use libs\common\ErrCode;
use libs\common\WXException;
use libs\utils\Alarm;
use libs\utils\Monitor;
use libs\utils\PaymentApi;
use core\enum\AccountAuthEnum;
use core\enum\SupervisionEnum;
use core\enum\UserEnum;
use core\enum\UserAccountEnum;
use core\enum\UserBankCardEnum;
use core\enum\P2pIdempotentEnum;
use core\enum\AccountEnum;
use core\service\user\UserService;
use core\service\user\BankService;
use core\service\deal\DealService;
use core\service\account\AccountService;
use core\service\supervision\SupervisionService;
use core\service\deal\P2pIdempotentService;
use core\dao\account\AccountAuthModel;
use core\dao\user\UserIdentityModifyLogModel;
use core\dao\deal\DealModel;
use core\tmevent\supervision\WxCancelUserEvent;
use core\tmevent\supervision\SupervisionCancelUserEvent;
use core\tmevent\supervision\SupervisionCardUnbindEvent;
use core\tmevent\supervision\WxUpdateUserIdentityByLogEvent;
use core\service\UserAccessLogService;
use core\enum\UserAccessLogEnum;
use core\enum\DeviceEnum;

/**
 * P2P存管-会员相关服务
 *
 */
class SupervisionAccountService extends SupervisionBaseService {

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
     * @param int $account 账户ID 或 账户信息
     * @return boolean
     */
    public function isSupervisionUser($account, $syncStatus = true) {
        if (empty($account)) {
            return false;
        }
        return in_array(
            AccountService::getAccountStatus($account, $syncStatus),
            [AccountEnum::STATUS_OPENED, AccountEnum::STATUS_UNACTIVATED]
        ) ? true : false;
    }

    /**
     * 是否监管
     * 整合存管开关和用户开户
     * @param mix $accountId 账户ID
     * @return array
     */
    public function isSupervision($accountId) {
        $isSvOpen = SupervisionService::isSupervisionOpen();
        $isSvUser = false;
        if ($isSvOpen && !empty($accountId)) {
            $isSvUser = $this->isSupervisionUser($accountId);
        }
        return ['isSvOpen' => $isSvOpen, 'isSvUser' => $isSvUser];
    }

    /**
     * 是否激活存管
     * @param $userId 用户ID
     * @return boolean
     */
    public static function isActivated($accountId) {
        return AccountService::isUnactivated($accountId) ? false : true;
    }

    /**
     * 用户存管标准注册页面
     * @param int $accountId 账户ID
     * @param string $platform 平台标识
     * @param array $bizParams
     * @param string $returnForm
     * @param string $isOnekeyRegister
     * @param string $isApi
     * @throws \Exception
     * @return array
     */
    public function memberStandardRegisterPage($accountId, $platform = 'pc', $bizParams = [], $returnForm = true, $isOnekeyRegister = false, $isApi = false) {
        try {
            $userId = AccountService::getUserId($accountId);
            // 获取该用户在超级账户的基本信息
            $userBaseData = UserService::getUserById((int)$userId, 'id,real_name,id_type,idno,mobile_code,mobile,user_purpose');
            if (empty($userBaseData)) {
                PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, sprintf('accountId:%d,userId:%d,ErrorMsg:memberStandardRegisterPage_UserNotExist', $accountId, $userId))));
            }
            $isOpenSupervision = $this->isSupervisionUser($accountId);
            $params = [
                'orderId' => Idworker::instance()->getId(),
                'userId' => $accountId,
                'realName' => $userBaseData['real_name'],
                'certType' => !empty(UserEnum::$idCardType[$userBaseData['id_type']]) ? UserEnum::$idCardType[$userBaseData['id_type']] : UserEnum::$idCardType['default'],
                'certNo' => $userBaseData['idno'],
                'regionCode' => !empty($userBaseData['mobile_code']) ? $userBaseData['mobile_code'] : $GLOBALS['dict']['MOBILE_CODE']['cn']['code'], // 国家区域码
                'registeredCell' => $userBaseData['mobile'],
            ];
            // 用户的账户类型
            $userPurposeInfo = AccountService::getUserPurposeInfo($userBaseData['user_purpose']);
            !empty($userPurposeInfo['supervisionBizType']) && $params['bizType'] = $userPurposeInfo['supervisionBizType'];

            if (!empty($bizParams)) {
                $params = array_merge($params, $bizParams);
            }
            // 生成存管开户表单
            if ($userBaseData['id_type'] == 1) {
                $params['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/registerStandardNotify';
                $method = 'memberStandardRegister';

                // 未激活且已绑卡验卡走老开户流程，不用重新验证四要素
                $userBankCardData = BankService::getNewCardByUserId($userId);
                if (AccountService::isUnactivated($accountId) && !empty($userBankCardData['bankcard']) && $userBankCardData['verify_status'] == 1) {
                    $params['phone'] = $userBaseData['mobile'];
                    $params['bankCardNo'] = $userBankCardData['bankcard'];
                    $bankData = BankService::getBankInfoByBankId($userBankCardData['bank_id']);
                    $params['bankCode'] = !empty($bankData['short_name']) ? strtoupper($bankData['short_name']) : '';
                    $params['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/registerNotify';
                    $method = 'memberRegister';
                }
            }else{
                $params['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/foreignMemberRegisterNotify';
                $method = 'foreignMemberRegister';
            }
            $formId = 'bindCardForm';
            $targetNew = false;
            $result = $this->{$method}($params, $platform, $returnForm, $formId, $targetNew, $isOnekeyRegister);
            if (!isset($result['status']) || $result['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                throw new \Exception($result['respMsg'], $result['respCode']);
            }
            // 清除用户存管redis缓存
            AccountService::clearAccountStatusCache($accountId);
            return $result;
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 调用[Web/H5个人开户]页面
     * @param int $userId 用户ID
     * @param string $platform 平台来源
     * @param array $bizParams 业务参数数组
     * @param boolean $returnForm 返回form还是url
     * @param boolean $isOnekeyRegister 是否掌众用户开户页面
     * @return 跳转到存管系统
     */
    public function memberRegisterPage($accountId, $platform = 'pc', $bizParams = [], $returnForm = true, $isOnekeyRegister = false, $isApi = false) {
        try {
            $userId = AccountService::getUserId($accountId);
            // 获取该用户在超级账户的基本信息
            $userBaseData = UserService::getUserById((int)$userId, 'id,real_name,id_type,idno,mobile_code,mobile,idcardpassed,user_type,user_purpose,payment_user_id');

            // 查询用户在超级账户是否已完成实名
            if ($userBaseData['idcardpassed'] != 1) {
                throw new WXException('ERR_USER_NOT_REALNAME');
            }

            // 获取该用户在超级账户的绑卡信息
            $userBankCardData = BankService::getNewCardByUserId($userId);
            // 查询用户在超级账户是否已完成绑卡
            if (empty($userBankCardData) || empty($userBankCardData['bankcard'])) {
                throw new WXException('ERR_NOT_BANDCARD');
            }

            // 查询用户在超级账户是否已完成验卡
            if (empty($userBankCardData['verify_status']) || $userBankCardData['verify_status'] != 1) {
                throw new WXException('ERR_CARD_NOT_VERIFY');
            }

            $isEnterprise = UserService::isEnterprise($userId);
            if ($isEnterprise) {
                // 企业用户列表里面的企业用户
                if ((int)$userBaseData['user_type'] == UserEnum::USER_TYPE_ENTERPRISE) {
                    $enterpriseInfo = UserService::getEnterpriseInfo($userId);
                    $userPurposeInfo = AccountService::getUserPurposeInfo($enterpriseInfo['company_purpose']);
                }else{
                    // 个人用户列表里面的企业用户
                    $userPurposeInfo = AccountService::getUserPurposeInfo($userBaseData['user_purpose']);
                }
                // 配置里面对应的存管账户类型为空时，不需要开存管账户
                if (empty($userPurposeInfo['supervisionBizType'])) {
                    throw new WXException('ERR_USER_NOOPEN_SUPERVISION');
                }
                $params = [
                    'userId' => $userId,
                    'bizType' => $userPurposeInfo['supervisionBizType'], // 业务类型(01-投资户|02-借款户|03-担保户|04-咨询户|05-平台户|06-借贷混合户|08-平台营销户|10-平台收费户|11-代偿户|12-第三方营销账户|13-垫资户)
                    'noticeUrl' => app_conf('NOTIFY_DOMAIN') . '/supervision/enterpriseRegisterNotify',
                ];
                // 合并业务参数
                if (!empty($bizParams)) {
                    $params = array_merge($params, $bizParams);
                }
                $result = $this->enterpriseRegister($params, $returnForm, 'registerForm', false, $platform);
            }else{
                // 请求参数
                $params = [
                    'userId' => $accountId,
                    'realName' => $userBaseData['real_name'],
                    'certType' => !empty(UserEnum::$idCardType[$userBaseData['id_type']]) ? UserEnum::$idCardType[$userBaseData['id_type']] : UserEnum::$idCardType['default'],
                    'certNo' => $userBaseData['idno'],
                    'regionCode' => !empty($userBaseData['mobile_code']) ? $userBaseData['mobile_code'] : $GLOBALS['dict']['MOBILE_CODE']['cn']['code'], // 国家区域码
                    'phone' => $userBaseData['mobile'],
                ];
                // 用户的账户类型
                $userPurposeInfo = AccountService::getUserPurposeInfo($userBaseData['user_purpose']);
                !empty($userPurposeInfo['supervisionBizType']) && $params['bizType'] = $userPurposeInfo['supervisionBizType'];

                if (!empty($userBankCardData)) {
                    $params['bankCardNo'] = $userBankCardData['bankcard'];
                    $bankData = BankService::getBankInfoByBankId($userBankCardData['bank_id']);
                    $params['bankCode'] = !empty($bankData['short_name']) ? strtoupper($bankData['short_name']) : '';
                }

                if ($isApi == false) {
                    // 合并业务参数
                    if (!empty($bizParams)) {
                        $params = array_merge($params, $bizParams);
                    }
                    // 生成存管开户表单
                    if ($userBaseData['id_type'] == 1) {
                        $params['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/registerNotify';
                        $method = 'memberRegister';
                    }else{
                        $params['noticeUrl'] = app_conf('NOTIFY_DOMAIN') . '/supervision/foreignMemberRegisterNotify';
                        $method = 'foreignMemberRegister';
                    }
                    $formId = 'registerForm';
                    $targetNew = false;
                    $result = $this->{$method}($params, $platform, $returnForm, $formId, $targetNew, $isOnekeyRegister);
                } else {
                    $result = $this->memberRegisterApi($params);
                }
            }
            if (!isset($result['status']) || $result['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                throw new \Exception($result['respMsg'], $result['respCode']);
            }
            // 清除用户存管redis缓存
            AccountService::clearAccountStatusCache($accountId);
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
        $result = $this->api->request('memberRegisterApi', $params);
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('memberRegisterApiRes:%s', json_encode($result)))));

        //用户已经开户
        if (isset($result['respSubCode']) && $result['respSubCode'] == '200101') {
            return $this->responseSuccess();
        }
        if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
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
            $service = $platform === 'pc' ? 'memberStandardRegister' : 'h5MemberStandardRegister';
            // 掌众H5开户增加simple接口配置
            if ($isOnekeyRegister && $platform == 'h5') {
                $service = 'memberQuickRegister';
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
        $accountId = $responseData['userId'];
        if (isset($responseData['status']) && $responseData['status'] == 'S' ) {
            // 同步用户绑卡信息到用户表
            $memberCardInfo = $this->memberCardSearch($accountId);
            if (isset($memberCardInfo['status']) && $memberCardInfo['status'] == 'S') {
                $bankCards = $memberCardInfo['data'];
                $bankcard = !empty($bankCards[0]) && is_array($bankCards[0]) ? $bankCards[0] : null;
                if ($bankcard) {
                    $bankId = 0;
                    $userId = AccountService::getUserId($accountId);
                    // 检查是否可以绑卡
                    $canBind = BankService::canBankcardBind($bankcard['cardNo'], $userId);
                    if ($canBind === false) {
                        return $this->responseFailure('绑卡失败，卡号已经被其他用户绑定', 10000);
                    }
                    //检查用户是否绑卡，兼容异常情况
                    $userBankInfo = BankService::getNewCardByUserId($userId);
                    if (!empty($userBankInfo)) {
                        return $this->responseSuccess();
                    }
                    $bank =[];
                    $bank['name'] = '';
                    if (!empty($bankcard['bankCode'])) {
                        $bankCode = addslashes(trim($bankcard['bankCode']));
                        $bank = BankService::getBankInfoByCode($bankCode);
                        if (!empty($bank)) {
                            $bankId = $bank['id'];
                        }
                    }
                    $userInfo = UserService::getUserById($userId, 'real_name,user_type,supervision_user_id');
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

                    $ucfpayData = [
                        'userId' => $userId,
                        'cardNo' => trim($bankcard['cardNo']),
                        'bankCode' => trim($bankcard['bankCode']),
                        'bankName' => trim($bank['name']),
                        'bankCardName' => $userInfo['real_name'],
                        'cardType' => 1, // 默认借记卡
                        'businessType' => 1, //新增银行卡
                    ];

                    $ret = BankService::insertUserBankCard($data, $ucfpayData);
                    if (!$ret) {
                        return $this->responseFailure('添加用户绑卡数据失败', 10000);
                    }

                    //生产用户访问日志
                    $extraInfo = [
                        'userId' => (int) $userId,
                        'cardNo' => trim($bankcard['cardNo']),
                        'bankName' => trim($bank['name']),
                    ];
                    UserAccessLogService::produceLog($userId, UserAccessLogEnum::TYPE_BIND_BANK_CARD, '绑定银行卡成功', $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN);
                }
            }
            // 更新用户银行卡数据
            $this->memberInitAuth($accountId);
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
            if (empty($responseData['userId']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            // 开户失败，不处理
            if ($responseData['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                return $this->responseSuccess();
            }

            // 账户ID
            $accountId = (int)$responseData['userId'];

            // 查询用户是否已在存管系统开户
            $isOpen = AccountService::isOpened($accountId);
            if ($isOpen) {
                return $this->responseSuccess();
            }

            // 删除激活TAG
            UserService::delUserTagsByConstName($responseData['userId'], UserEnum::SV_UNACTIVATED_USER);

            // 更新用户的存管开户状态
            $openRet = AccountService::openAccount($accountId);
            if (false === $openRet) {
                throw new WXException('ERR_OPEN_ACCOUNT_FAILED');
            }
            // 诸葛统计埋点
            (new Zhuge(Zhuge::APP_PHWEB))->event('注册成功', $responseData['userId'], []);


            // 同步网信存管状态
            $userId = AccountService::getUserId($accountId);
            UserService::updateSupervisionUserId($userId);

            //生产用户访问日志
            UserAccessLogService::produceLog($userId, UserAccessLogEnum::TYPE_OPEN_P2P_ACCOUNT, '开通存管账户成功', $responseData, '', DeviceEnum::DEVICE_UNKNOWN);

           // $message = array('userId'=>$userId);
            //MsgbusService::produce(MsgbusEnum::TOPIC_USER_REGISTER_SUCCESS,$message);
            return $this->responseSuccess();
        } catch(\Exception $e) {
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
            // 请求接口
            $result = $this->api->request('enterpriseUpdateApi', $params);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200001') {
                // 用户未在存管开户不用报错，否则gtm会一直重试
                //throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200101') {
                throw new WXException('ERR_ENTERPRISE_AUDITING');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
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
     *     userId:账户ID
     *     status:状态(S-成功；F-失败)
     */
    public function enterpriseUpdateNotify($responseData) {
        try {
            if (empty($responseData['userId']) || empty($responseData['status'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 用户信息查询
     * @param int $accountId 账户ID
     * @return array
     */
    public function memberSearch($accountId) {
        try {
            // 请求接口
            $result = $this->api->request('memberSearch', ['userId'=>$accountId]);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200001') {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_SEARCH');
            }
            unset($result['respCode'], $result['respSubCode'], $result['respMsg']);
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
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
     * @param int $accountId 账户ID
     * @param array $grantList 授权列表数组
     * @return array
     */
    public function memberAuthorizationCancel($accountId, $grantList) {
        try {
            // 请求参数
            $params = [
                'userId' => intval($accountId),
                'grantList' => !empty($grantList) ? join(',', $grantList) : '',
            ];
            // 请求接口
            $result = $this->api->request('memberAuthorizationCancel', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_AUTHORIZATION_CANCEL');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 用户授权查询
     * @param int $accountId 账户ID
     * @return array
     */
    public function memberAuthorizationSearch($accountId) {
        try {
            // 请求参数
            $params = [
                'userId' => intval($accountId),
            ];
            // 请求接口
            $result = $this->api->request('memberAuthorizationSearch', $params);
            // 接口请求异常
            if (empty($result)) {
                throw new WXException('ERR_AUTHORIZATION_SEARCH_REQUEST');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_AUTHORIZATION_SEARCH');
            }
            $grantList = !empty($result['grantList']) ? $result['grantList'] : '';
            return $this->responseSuccess($grantList);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 用户存管余额查询
     * @param int $accountId 账户ID
     * @return array
     */
    public function balanceSearch($accountId) {
        try {
            // 请求接口
            $result = $this->api->request('memberBalanceSearch', ['userId'=>$accountId]);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200001') {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_BALANCE_SEARCH');
            }
            unset($result['respCode'], $result['respSubCode'], $result['respMsg']);
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 用户存管余额查询-批量
     * @param array $accountIds 账户ID列表
     * @return array
     */
    public function batchBalanceSearch($accountIds) {
        try {
            // 请求接口
            $result = $this->api->request('memberBatchBalanceSearch', ['userIds'=>implode(',', $accountIds)]);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_BALANCE_SEARCH');
            }
            unset($result['respCode'], $result['respSubCode'], $result['respMsg']);
            return $this->responseSuccess($result['usersBalance']);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 会员银行卡查询/会员绑卡查询
     * @param int $accountId 账户ID
     * @return array
     */
    public function memberCardSearch($accountId) {
        try {
            // 请求接口
            $result = $this->api->request('memberCardSearch', ['userId'=>$accountId]);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_CARD_SEARCH');
            }
            return $this->responseSuccess((!empty($result['bankCards']) ? $result['bankCards'] : []));
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 修改/更换银行卡-接口
     * @param int $accountId 账户ID
     * @return array
     */
    public function memberCardUpdate($accountId, $cardInfo = []) {
        try {
            // 把账户ID转换为用户ID
            $userId = AccountService::getUserId($accountId);

            // 获取该用户在超级账户的绑卡信息
            $userBankCardData = BankService::getNewCardByUserId($userId);
            // 查询用户在超级账户是否已完成绑卡
            if (empty($userBankCardData) || empty($userBankCardData['bankcard'])) {
                throw new WXException('ERR_NOT_BANDCARD');
            }

            // 获取用户绑卡信息
            $bankData = BankService::getBankInfoByBankId($userBankCardData['bank_id']);
            // 请求参数
            $params = [
                'userId' => $accountId, // 账户ID
                'bankCardNo' => $userBankCardData['bankcard'],
                'bankName' => !empty($bankData['name']) ? $bankData['name'] : '',
                'bankCode' => !empty($bankData['short_name']) ? strtoupper($bankData['short_name']) : '',
            ];
            if ($cardInfo !== []) {
                $params['bankCardNo'] = $cardInfo['bank_bankcard'];
                $params['bankName'] = $cardInfo['bank_name'];
                $params['bankCode'] = $cardInfo['short_name'];
            }

            // 检查用户是否企业用户
            $userInfo = UserService::getUserById($userId, 'id, real_name');
            if ($userInfo['is_enterprise_user']) {
                if (empty($userBankCardData['branch_no'])) {
                    throw new WXException('ERR_ENTERPRISE_NOBRANCHNO');
                }
                // 银行卡联行号
                $branchNo = !empty($cardInfo['branch_no']) ? $cardInfo['branch_no'] : $userBankCardData['branch_no'];
                $params['cardFlag'] = SupervisionEnum::CARD_FLAG_PUB; // 银行卡类型（只能是借记卡）(1：对公账户 2：对私账户)
                // 获取银行卡联行号
                $bankInfo = BankService::getBranchInfoByBranchNo($branchNo, 'name');
                // 支行名称，对公账户必填
                $params['issuerName'] = !empty($bankInfo['name']) ? $bankInfo['name'] : '';
                $params['issuer'] = $branchNo; // 支行-联行号，对公账户必填
            }else{
                $params['cardFlag'] = SupervisionEnum::CARD_FLAG_PRI; // 银行卡类型（只能是借记卡）(1：对公账户 2：对私账户)
            }

            //开户名
            if (!empty($userInfo['real_name'])) {
                $params['bankCardName'] = $userInfo['real_name'];
            }

            // 请求接口
            $result = $this->api->request('memberCardUpdate', $params);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200001') {
                throw new WXException('ERR_NOT_OPEN_ACCOUNT');
            }

            //用户没有绑卡，请求绑卡 200230
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200230') {
                $certStatusMap = array_flip(UserBankCardEnum::$cert_status_map);
                $params['cardType'] = SupervisionEnum::CARD_TYPE_DEBIT;//借记卡
                $params['cardCertType'] = isset($certStatusMap[$userBankCardData['cert_status']]) ? $certStatusMap[$userBankCardData['cert_status']] : 'NO_CERT';// 认证类型
                $bindResult = $this->memberCardBind($params);
                if (!isset($bindResult['status']) || $bindResult['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                    throw new \Exception($bindResult['respMsg'], $bindResult['respCode']);
                }
                return $this->responseSuccess();
            }

            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
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
     * @param int $accountId 账户ID
     * @param int $bankCardNo 银行卡号
     * @return array
     */
    public function memberCardUnbind($accountId, $bankCardNo, $isAdmin = false) {
        try {
            if (empty($accountId) || empty($bankCardNo)) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            // 把账户ID转换为用户ID
            $userId = AccountService::getUserId($accountId);

            // 查询用户在超级账户是否已完成绑卡
            $userBankCardData = BankService::getNewCardByUserId($userId);
            if (empty($userBankCardData) || empty($userBankCardData['bankcard'])) {
                PaymentApi::log(sprintf('memberCardUnbind, WxBankCardInfo Is Empty, Not Need Unbind, accountId:%d, userId:%d', $accountId, $userId));
                return true;
            }
            $gtmName = 'adminCardUnbind';
            if (!$isAdmin) {
                $gtmName = 'memberCardUnbind';
            }
            // 判断用户总资产是否为零
            $isMoneyZero = $this->isZeroUserAssets($accountId);
            if (!$isMoneyZero) {
                throw new WXException('ERR_MEMBERCARD_UNBIND_NOTZERO');
            }

            $gtm = new GlobalTransactionManager();
            $gtm->setName($gtmName);

            // 用户已在存管账户开户或者是存管预开户用户
            $isSupervisionUser = $this->isSupervisionUser($accountId);
            if ($isSupervisionUser || SupervisionService::isUpgradeAccount($userId)) {
                $gtm->addEvent(new SupervisionCardUnbindEvent($accountId, $bankCardNo));
            }
            $unbindRet = $gtm->execute();
            if (true !== $unbindRet) {
                throw new \Exception($gtm->getError());
            }
            return $unbindRet;
        } catch (\Exception $e) {
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, sprintf('memberCardUnbind,accountId:%d,userId:%d,ExceptionCode:%s,ExceptionMsg:%s', $accountId, $userId, $e->getCode(), $e->getMessage()))));
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管解绑银行卡|账户ID:%d，用户ID:%d，银行卡号:%s，异常内容:%s', $accountId, $userId, $bankCardNo, $e->getMessage()));
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
     * @param int $accountId 账户ID
     * @return array
     */
    public function memberPhoneUpdate($accountId, $mobile) {
        try {
            $params = ['userId'=>(int)$accountId, 'phone'=>addslashes($mobile)];
            // 请求接口
            $result = $this->api->request('memberPhoneUpdate', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_PHONE_UPDATE');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 存管行用户资金记录日志
     * @param int $accountId 账户ID
     */
    public function memberLog($accountId, $platform = 'pc', $params = [], $formId = 'memberLogForm', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            $params['userId'] = (int)$accountId;
            $result = $this->api->getForm('accountLogPage', $params, $formId, $targetNew);
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
    public function memberInfo($accountId, $platform = 'pc', $params = [], $formId = 'memberInfoForm', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            $params['userId'] = (int)$accountId;
            $service = $platform === 'pc' ? 'memberInfo' : 'h5MemberInfo';
            $result = $this->api->getForm($service, $params, $formId, $targetNew);
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch (\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage);
        }
    }

    /*
     * 存管用户开通授权大礼包
     * @param integer $accountId 账户Id
     *
     * @return array
     */
    public function memberInitAuth($accountId) {
        try {
            // 请求接口
            $result = $this->api->request('memberInitAuth', ['userId'=>$accountId]);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
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
     * @param integer $accountId 账户Id
     *
     * @return array
     */
    public function superMemberInfo($accountId, $platform = 'pc', $params = [], $formId = 'superMemberInfoFrom', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }
            $params['userId'] = (int)$accountId;
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
            if ($responseData['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                return $this->responseSuccess();
            }

            // 账户ID
            $accountId = intval($responseData['userId']);
            // 把账户ID转换为用户ID
            $userId = AccountService::getUserId($accountId);
            $grantList = explode(',', $responseData['grantList']);
            $grantAmountList = !empty($responseData['grantAmountList']) ? explode(',', $responseData['grantAmountList']) : [];
            $grantTimeList = !empty($responseData['grantTimeList']) ? explode(',', $responseData['grantTimeList']) : [];

            $accountAuthModel = AccountAuthModel::instance();
            $db = Db::getInstance('firstp2p');
            $db->startTrans();
            foreach ($grantList as $index => $grantName) {
                $grantType = AccountAuthEnum::$grantTypeMap[$grantName]; //授权类型
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
     * 绑定银行卡 - 接口
     * @param array $params 传入参数
     * @return array
     */
    public function memberCardBind($params) {
        try {
            // 参数校验-银行卡类型(1：对公账户2：借记卡)
            if (!empty($params['cardType']) && $params['cardType'] == SupervisionEnum::CARD_FLAG_PUB) {
                if (empty($params['issuerName']) || empty($params['issuer'])) {
                    throw new WXException('ERR_PARAM_LOSE');
                }
            }

            // 请求接口
            $result = $this->api->request('memberCardBind', $params);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200108') {
                return $this->responseSuccess();
            }

            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_MEMBER_CARD_BIND');
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 账户注销
     * @param int $accountId 账户ID
     * @return array
     */
    public function memberCancel($accountId) {
        try{
            // 把账户ID转换为用户ID
            $userId = AccountService::getUserId($accountId);

            // 查询超级账户+存管账户的资产总额
            $isMoneyZero = $this->isZeroUserAssets($accountId);
            if (!$isMoneyZero) {
                throw new WXException('ERR_ASSET_NOTZERO');
            }
            // 检查用户是否已注销
            $isOpen = AccountService::isOpened($accountId);
            // 用户已被注销
            if (!$isOpen) {
                return $this->responseSuccess();
            }

            $gtm = new GlobalTransactionManager();
            $gtm->setName('userCancel');

            // 用户已在存管账户开户
            $isOpenSupervision = $this->isSupervisionUser($accountId);
            if (true === $isOpenSupervision || SupervisionService::isUpgradeAccount($userId)) {
                // 存管账户-销户的Event
                $gtm->addEvent(new SupervisionCancelUserEvent($accountId));
            }
            // 网信理财-销户的Event
            $gtm->addEvent(new WxCancelUserEvent($accountId));

            // 同步执行
            $cancelRet = $gtm->execute();
            if (true !== $cancelRet) {
                throw new \Exception($gtm->getError());
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $accountId, sprintf('accountId:%d,userId:%d,isOpenSupervision:%d,ExceptionCode:%s,ExceptionMsg:%s', $accountId, $userId, (int)$isOpenSupervision, $e->getCode(), $e->getMessage()))));
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管账户注销|账户ID:%d，用户ID:%d，异常内容:%s', $accountId, $userId, $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_MEMBERCANCEL');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 账户注销-请求存管系统接口
     * @param int $accountId 账户ID
     * @return array
     */
    public function supervisionMemberCancel($accountId) {
        try {
            // 请求接口
            $result = $this->api->request('memberCancel', ['userId'=>$accountId]);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new \Exception($result['respMsg'], $result['respCode']);
            }
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 检查用户是否存在某个或者某几个权限
     * @param integer $accountId 账户IDd
     * @param array $needPrivileges 需要检查的授权类型
     * @return boolean
     */
    public function checkUserPrivileges($accountId, array $needPrivileges) {
        //服务降级状态下，用户无授权
        if (SupervisionService::isServiceDown() || empty($needPrivileges)) {
            return false;
        }
        // 用户授权查询
        $userPrivilegs = $this->memberAuthorizationSearch($accountId);
        //接口请求异常时，返回已开通
        if ($this->ignoreReqExc && isset($userPrivilegs['respCode']) && $userPrivilegs['respCode'] == ErrCode::getCode('ERR_AUTHORIZATION_SEARCH_REQUEST')) {
            return true;
        }
        if (empty($userPrivilegs) || $userPrivilegs['status'] != SupervisionEnum::RESPONSE_SUCCESS || empty($userPrivilegs['data'])) {
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
     * @param int $accountId 账户ID
     * @param boolean $withWxlc 是否只查询普惠账户余额
     * @return array
     */
    public function isZeroUserAssets($accountId, $withWxlc = false) {
        // 把账户ID转换为用户ID
        $userId = AccountService::getUserId($accountId);
        if (empty($userId)) {
            return false;
        }

        // 查询用户在投资产余额
        $dealObj = new DealService();
        $dealMoney = $dealObj->getUnrepayP2pMoneyByUids([$userId]);
        $isMoneyEmpty = bccomp($dealMoney, '0.00', 2) <= 0;
        if ($withWxlc) {
            return $isMoneyEmpty ? true : false;
        }

        // 总资产为0
        if ($isMoneyEmpty && !$withWxlc) {
            //检查存管开户
            $isSupervisionUser = $this->isSupervisionUser($accountId);
            if (!$isSupervisionUser && !SupervisionService::isUpgradeAccount($userId)) {
                return true;
            }
            //存管降级
            if (SupervisionService::isServiceDown()) {
                return false;
            }
            // 查询存管账户的可用余额
            $memberBalance = $this->balanceSearch($accountId);
            // 用户未在存管系统开户
            if (isset($memberBalance['respCode']) && $memberBalance['respCode'] == ErrCode::getCode('ERR_NOT_OPEN_ACCOUNT')) {
                return true;
            }
            if (!isset($memberBalance['status']) || $memberBalance['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                return false;
            }
            // 存管账户的可用余额+冻结金额
            $supervisionBalance = bcadd($memberBalance['data']['availableBalance'], $memberBalance['data']['freezeBalance'], 2);
            // 存管账户的总余额为0
            if (bccomp($supervisionBalance, '0.00', 2) == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 存管系统解绑银行卡
     * @param string $accountId 账户id
     * @param string $bankcardNo 银行卡号
     */
    public function unbindCard($accountId, $bankcardNo) {
        try {
            if (empty($accountId) || empty($bankcardNo)) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            $params = [];
            $params['userId'] = $accountId;
            $params['bankCardNo'] = $bankcardNo;
            // 请求接口
            $result = $this->api->request('memberCardUnbind', $params);
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200006') {
                throw new WXException('ERR_BANKCARD_NOT_EXIST');
            }
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
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
            $result = $this->api->request('memberInfoModify', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
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

            // 检查订单号
            $logModel = new UserIdentityModifyLogModel();
            $log = $logModel->getLogByOrderId($responseData['orderId']);
            if (empty($log) || $userId != $log['user_id']) {
                throw new WXException('ERR_ORDER_NOT_EXIST');
            }

            // 幂等
            $logStatusMap = [
                SupervisionEnum::RESPONSE_SUCCESS => UserIdentityModifyLogModel::STATUS_SUCCESS,
                SupervisionEnum::RESPONSE_FAILURE => UserIdentityModifyLogModel::STATUS_FAILURE,
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
            $gtmRet = $gtm->execute();
            if (!$gtmRet) {
                throw new \Exception($gtm->getError());
            }

            // 启动GTM管理器
            $gtm = new GlobalTransactionManager();
            $gtm->setName('updateWxUserIdentity');

            //成功才同步支付,取一下旧的用户信息
            if ($responseData['status'] == SupervisionEnum::RESPONSE_SUCCESS) {
                $userInformation = UserService::getUserById($userId, 'mobile_code,mobile,payment_user_id');
                // 已经开户的用户才跟先锋支付同步资料
                if (!empty($userInformation['payment_user_id']))
                {
                    // 修改会员基本信息同步接口
                    $params = [];
                    $params['id'] = $userId;
                    $params['newData'] = [
                        'real_name'   => $log['real_name'],
                        'id_type'     => $log['id_type'],
                        'idno'        => $log['idno'],
                        'mobile'      => $userInformation['mobile'],
                        'mobile_code' => $userInformation['mobile_code'],
                    ];
                    $gtm->addEvent(new EventMaker([
                        'commit' => [(new UserService), 'modifyUserInfo', $params],
                    ]));
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
    public function isQuickBidAuthorization($accountId) {
        return true;
        return $this->checkUserPrivileges($accountId, [AccountAuthEnum::GRANT_INVEST, AccountAuthEnum::GRANT_WITHDRAW_TO_SUPER]);
    }

    /**
     * 检查银信通是否已授权[免密提现至银信通账户]
     * @param int $userId
     * 老授权不校验
     */
    public function isYxtAuthorization($accountId) {
        return $this->checkUserPrivileges($accountId, [AccountAuthEnum::GRANT_WITHDRAW_TO_YXT]);
    }

    /**
     * 清除用户所有的存管缓存
     * @param int $userId
     */
    public function clearUserAllSupervisionCache($userId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $cacheKey2 = sprintf(self::KEY_SUPERVISION_GRANT_LIST, $userId);
        return $redis->del($cacheKey2);
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

    /**
     * 根据用户ID查询用户在存管的银行卡信息
     * @param $userId 用户ID
     * @param @param $accountType 账户类型
     */
    public function getBankInfoByUserId($userId, $accountType) {
        try {
            if (empty($userId) || empty($accountType)) {
                throw new WXException('ERR_PARAM');
            }

            $accountId = AccountService::getUserAccountId($userId, $accountType);
            if (empty($accountId)) {
                throw new WXException('ERR_USER_NOEXIST');
            }

            // 根据账户ID获取用户在存管的银行卡列表
            $cardList = $this->memberCardSearch($accountId);
            if ($cardList['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                throw new \Exception($cardList['respMsg'], $cardList['respCode']);
            }

            // 返回在存管银行卡列表的第一条记录
            $cardInfo = !empty($cardList['data'][0]) ? $cardList['data'][0] : [];
            return $this->responseSuccess($cardInfo);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * supervisionBonusTransfer
     * 存管红包转账记录
     *
     * @param integer $orderId 标的流水号
     * @access public
     * @return void
     */
    public function bonusTransfer($orderId) {
        $idempotentService = new P2pIdempotentService();
        $orderInfo = $idempotentService->getInfoByOrderId($orderId);
        if (empty($orderInfo)) {
            throw new \Exception('订单信息不存在');
        }

        // 投标才有红包
        if ($orderInfo['type'] != P2pIdempotentEnum::TYPE_DEAL) {
            return true;
        }

        // 没有红包使用信息
        $orderDetail = json_decode($orderInfo['params'], true);
        if (!isset($orderDetail['bonusInfo'])) {
            return true;
        }

        // 必须查询report_status字段，不然isP2pPath判断失效  此处改为主库 线上主从延迟导致未获取到标的信息
        $deal = DealModel::instance()->find($orderInfo['deal_id'], 'name, report_status');
        if (empty($deal)) {
            throw new \Exception('标信息不存在');
        }

        //必须是报备网贷标的
        if ($deal['report_status'] != 1) {
            return true;
        }

        $receiverId = AccountService::getUserAccountId($orderInfo['loan_user_id'], UserAccountEnum::ACCOUNT_INVESTMENT);

        foreach ($orderDetail['bonusInfo']['accountInfo'] as $payAccount) {
            $payerId = AccountService::getUserAccountId($payAccount['rpUserId'], UserAccountEnum::ACCOUNT_BONUS); //红包户
            if (!$payerId) {
                $payerId  = AccountService::getUserAccountId($payAccount['rpUserId'],UserAccountEnum::ACCOUNT_COUPON);
            }
            $payAmount = $payAccount['rpAmount'];

            $payerType = app_conf('NEW_BONUS_TITLE') . '充值';
            $payerNote = $receiverId ."使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$deal['name']}";
            $payerBizToken = ['dealId' => $orderInfo['deal_id'], 'orderId' => $orderId];

            $receiverType = '使用' . app_conf('NEW_BONUS_TITLE') . '充值';
            $receiverNote = "使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$deal['name']}";
            $receiverBizToken = ['dealId' => $orderInfo['deal_id'], 'orderId' => $orderId];

            AccountService::transferMoney($payerId, $receiverId, $payAmount, $payerType, $payerNote, $receiverType, $receiverNote, false, false, $payerBizToken, $receiverBizToken);
        }
        return true;
    }

    public function updateAccountType($accountId, $accountType, $supervisionBizType, $grantList = []) {
        try {
            //支付端
            $params = array(
                'userId' => $accountId,
                'bizType' => $supervisionBizType,
            );
            //刷新授权
            if (!empty($grantList)) {
                $params['grantList'] = implode(',', $grantList);
            }
            $result = $this->api->request('biztypeModify', $params);
            PaymentApi::log("supervion biztypeModify request. userId:{$accountId}, supervisionBizType:{$supervisionBizType}, result:".json_encode($result, JSON_UNESCAPED_UNICODE));
            if (!isset($result['respCode']) || $result['respCode'] != '00') {
                throw new \Exception($result['respMsg']);
            }
            //数据库
            $data = array(
                'account_type' => $accountType,
                'update_time' => time(),
            );
            // 账号表中的状态
            $resultAccount = Db::getInstance('firstp2p')->update('firstp2p_account', $data, "id='$accountId'");

            $userId = AccountService::getUserId($accountId);
            //同步更新网信账户类型
            UserService::updateWxUserInfo(['id' => $userId, 'user_purpose' => $accountType, 'update_time' => get_gmtime()]);

            //刷新授权
            $accountAuthModel = AccountAuthModel::instance();
            foreach ($grantList as $grantName) {
                $grantType = AccountAuthEnum::$grantTypeMap[$grantName];
                $authParams = [
                    'accountId'         => $accountId,
                    'userId'            => $userId,
                    'grantType'         => $grantType,
                    'grantAmount'       => 0, //无限制
                    'grantTime'         => 0, //无限制
                ];
                $accountAuthModel->saveAuth($authParams);
            }

            PaymentApi::log("update account type. accountId:{$accountId}, accountType:{$accountType}, result:" . json_encode($result, JSON_UNESCAPED_UNICODE));
            return true;
        } catch (\Exception $e) {
            PaymentApi::log("update account type exception. accountId:{$accountId},accountType:{$accountType} accountType:{$accountType}, result:".$e->getMessage());
            return $e->getMessage();
        }
    }

}
