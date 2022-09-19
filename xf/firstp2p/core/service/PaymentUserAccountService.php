<?php
namespace core\service;

use libs\utils\PaymentApi;
use libs\utils\PaymentGatewayApi;
use libs\utils\Logger;
use core\dao\PaymentNoticeModel; // 充值日志
use core\dao\UserModel;
use core\dao\BankModel;
use core\service\PaymentService;
use libs\db\Db;
use core\dao\UserPassportModel;
use core\dao\UserBankcardModel;
use core\service\AccountService;
use core\service\BankService;

use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\ApiService;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\tmevent\supervision\WxUpdateUserBankCardEvent;
use core\tmevent\supervision\SupervisionUpdateUserBankCardEvent;
use core\service\ncfph\AccountService as PhAccountService;
use core\service\ncfph\SupervisionService as PhSupervisionService;
use core\service\SupervisionFinanceService;
use core\service\UserBankcardService;
use core\service\ChargeService;

/**
 * 资金托管 -  用户账户接口
 *
 * 目前本服务提供的API
 * 1. modifyUserInfo 修改用户基本信息，同步给先锋支付风控，审核结果通过回调方式提供
 * 2. modifyBankInfo 修改银行卡信息，同步给先锋支付风控（二期）,审核结果通过回调方式提供
 * 3. cancleUser 从先锋支付注销用户， 同步返回结果
 *
 */
class PaymentUserAccountService extends BaseService {
    private $_error = ''; // 错误信息

    /**
     * 设置错误信息
     */
    public function setError($error)
    {
        $this->_error = $error;
    }

    /**
     * 读取错误信息
     */
    public function getLastError()
    {
        return $this->_error ?: '';
    }

    /**
     * 修改会员基本信息同步接口
     * @param integer $userId 会员ID
     * @param [] $newInfo 新用户信息
     */
    public function modifyUserInfo($userId, $newInfo)
    {
        $requestParams = [];
        $requestParams['userId'] = $userId;
        $userModel = UserModel::instance()->find($userId);
        if (empty($userModel))
        {
            throw new \Exception('用户数据读取失败');
        }

        // 旧数据
        $paymentService = new PaymentService();
        $oldData = $userModel->getRow();
        //用户类型
        $oldMobile = $oldData['mobile'];
        $requestParams['userType'] = ($oldData['mobile_code'] == '86' && $oldMobile{0} == '6') ? 2 : 1;
        if ($oldData['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
        {
            $requestParams['userType'] = 2;
        }

        $requestParams['oldCertType'] = $paymentService->getUserType($oldData['id_type']);
        // 港澳台用户
        if ($requestParams['oldCertType'] == '02' || $requestParams['oldCertType'] == '04')
        {
            $accountService = new AccountService;
            $userPassport = $accountService->hasPassport($userId);
            if (empty($userPassport))
            {
                throw new \Exception('港澳台用户读取护照信息失败或者未通过审核');
            }
            $userPassport = UserPassportModel::instance()->findByViaSlave(" uid = '{$userId}' AND status = 1 ");
            if (!empty($userPassport))
            {
                $requestParams['oldCertNo'] = $userPassport['idno'];
                $requestParams['oldRealName'] = $userPassport['name'];
            }
            // 如果没有护照信息，默认使用user表数据填充
            else
            {
                $requestParams['oldCertNo'] = $oldData['idno'];
                $requestParams['oldRealName'] = $oldData['real_name'];
            }
        }
        else
        {
            $requestParams['oldCertNo'] = $oldData['idno'];
            $requestParams['oldRealName'] = $oldData['real_name'];
        }
        $requestParams['oldCell'] = $oldData['mobile'];
        $requestParams['oldRegionCode'] = $oldData['mobile_code'];
        // 新数据
        $requestParams['certType'] = $paymentService->getUserType($newInfo['id_type']);
        $requestParams['certNo'] = $newInfo['idno'] ?: $requestParams['oldCertNo'];
        $requestParams['realName'] = $newInfo['real_name'] ?: $requestParams['oldRealName'];
        $requestParams['cell'] = $newInfo['mobile'] ?: $oldData['mobile'];
        $requestParams['regionCode'] = $newInfo['mobile_code'] ?: $oldData['mobile_code'];

        $result = PaymentApi::instance()->request('modifyuserinfo', $requestParams);
        if (empty($result) || $result['respCode'] != '00' || $result['status'] != '00')
        {
            throw new \Exception($result['respMsg']?:'请求支付接口失败');
        }
        return true;
    }

    /**
     * 修改会员银行卡送审接口
     */
    public function modifyBankInfo($userId, $newInfo)
    {
    }

    /**
     * 注销用户
     */
    public function cancelUser($userId)
    {
        try{
            if (empty($userId))
            {
                throw new \Exception('解除用户绑卡失败，用户ID不能为空');
            }
            $requestParams = [
                'userId' => $userId,
            ];

            $result = PaymentApi::instance()->request('canceluser', $requestParams);
            if ($result['respCode'] != '00' || $result['status'] != '00')
            {
                // 用户不存在,返回注销成功
                if ($result['respCode'] === '00' && $result['status'] === '06') {
                    return ['ret'=>true, 'msg'=>'success'];
                }
                throw new \Exception($result['respMsg']?:'请求支付接口失败');
            }
            return ['ret'=>true, 'msg'=>'success'];
        } catch(\Exception $e) {
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $userId, '超级账户注销异常,' . $e->getMessage())));
            return ['ret'=>false, 'msg'=>$e->getMessage()];
        }
    }

    /**
     * 根据开关控制限额获取路径
     * @return array
     */
    public function getChargeLimit($bankId, $userId = 0)
    {
        $limitOpen = SupervisionFinanceService::isNewBankLimitOpen();
        if (!$limitOpen) {
            return $this->getChargeLimitGateway($bankId, $userId);
        }

        // 兼容易宝支付的情况
        $planBInfo = self::isForcePlanB($userId);
        if ($planBInfo['isPlanB']) {
            // 检查是否有配置的默认充值限额
            $defaultBankLimit = SupervisionFinanceService::getDefaultBankLimit();
            if ($defaultBankLimit > 0) {
                $response = ['respCode' => '01', 'respMsg' => '易宝不显示限额信息', 'data' => ''];
                // 获取银行基本信息
                $bankInfo = BankModel::instance()->find($bankId);
                $response['data'] = [
                    'dayLimit' => bcdiv($defaultBankLimit, 100, 0), // 日最大限额,单位分(-1:未知0:无限额)
                    'singleLimit' => bcdiv($defaultBankLimit, 100, 0), // 单笔最大限额,单位分(-1:未知0:无限额)
                    'monthLimit' => bcdiv($defaultBankLimit, 100, 0), // 月最大限额,单位分(-1:未知0:无限额)
                    'bankName' => !empty($bankInfo['name']) ? $bankInfo['name'] : '',
                    'bankCode' => !empty($bankInfo['short_name']) ? $bankInfo['short_name'] : '',
                ];
                return $response;
            }
        }
        return PhSupervisionService::getPhChargeLimit($bankId);
    }

    /**
     * 获取移动端充值银行单笔限额和日限额
     * @param integer $bankId 用户银行卡对应的银行ID
     * @param integer $userId 用户ID
     * @return array
     */
    public function getChargeLimitGateway($bankId, $userId = 0)
    {
        $response = [
            'respCode' => '00',
            'respMsg' => '',
            'data' => '',
        ];
        try {
            if (intval($bankId)<=0)
            {
                throw new \Exception('银行信息无效');
            }
            $bank = BankModel::instance()->find($bankId);
            if (empty($bank))
            {
                throw new \Exception('查询银行信息失败，请稍后再试');
            }

            $requestParams = [
                'reqSn' => microtime(true).rand(10000,99999),
                'bankCode' => $bank['short_name'],
            ];

            $result = PaymentGatewayApi::instance()->request('banklimit', $requestParams);
            if (empty($result) || $result['status'] != '00')
            {
                throw new \Exception($result['respMsg']?:'查询银行卡限额失败');
            }
            $result = $result['bankList'][0];
            $response['data'] = [
                'dayLimit' => bcdiv($result['dayLimit'], 100, 0),
                'singleLimit' => bcdiv($result['singleLimit'], 100, 0),
                'monthLimit' => bcdiv($result['monthLimit'], 100, 0),
                'bankName' => $result['bankName'],
                'bankCode' => $result['bankCode'],
            ];

            // 兼容易宝支付的情况
            $planBInfo = self::isForcePlanB($userId);
            if ($planBInfo['isPlanB']) {
                throw new \Exception(sprintf('平台开启了支付B方案，用户ID：%d', $userId));
            }
        }
        catch (\Exception $e)
        {
            $response['respCode'] = '01';
            $response['respMsg'] = $e->getMessage();
            // 检查是否有配置的默认充值限额
            $defaultBankLimit = SupervisionFinanceService::getDefaultBankLimit();
            if ($defaultBankLimit > 0) {
                $response['data'] = [
                    'dayLimit' => bcdiv($defaultBankLimit, 100, 0), // 日最大限额,单位元(-1:未知0:无限额)
                    'singleLimit' => bcdiv($defaultBankLimit, 100, 0), // 单笔最大限额,单位元(-1:未知0:无限额)
                    'monthLimit' => bcdiv($defaultBankLimit, 100, 0), // 月最大限额,单位元(-1:未知0:无限额)
                    'bankName' => !empty($bank['name']) ? $bank['name'] : '',
                    'bankCode' => !empty($bank['short_name']) ? $bank['short_name'] : '',
                ];
            }
        }
        return $response;
    }

    /**
     * 获取移动端绑卡界面链接
     * @param array $params
     * @return string $url
     */
    public function h5AuthBindCard($params) {
        if (empty($params['userId'])  || empty($params['reqSource'])) {
            throw new \Exception('参数不正确');
        }
        if ($params['reqSource'] == 1) {
            $params['returnUrl'] = 'ucfpay://api?method=bindcard&result=1';
            $params['failUrl'] = 'ucfpay://api?method=bindcard&result=0';
        }
        //不显示快捷认证方式
        if (!$this->isShowFastCert($params['userId'])) {
            $params['isNeedFast'] = 0;
        }
        //0安全卡  1充值卡
        $params['bankCardType'] = isset($params['bankCardType']) ? intval($params['bankCardType']) : UserBankcardService::CARD_TYPE_SAFE;
        // 商户订单号-新增（支付要区分用户多次绑卡的流程）
        $params['outOrderId'] = !empty($params['outOrderId']) ? intval($params['outOrderId']) : Idworker::instance()->getId();
        $requestUrl = PaymentGatewayApi::instance()->getRequestUrl('h5AuthBindCard', $params);
        return $requestUrl;
    }

    /**
     * 获取移动端绑卡Form
     * @param array $params
     * @return array
     */
    public function h5AuthBindCardForm($params, $targetNew = false) {
        if (empty($params['userId'])  || empty($params['reqSource'])) {
            throw new \Exception('参数不正确');
        }
        if ($params['reqSource'] == 1) {
            $params['returnUrl'] = 'ucfpay://api?method=bindcard&result=1';
            $params['failUrl'] = 'ucfpay://api?method=bindcard&result=0';
        }
        //不显示快捷认证方式
        if (!$this->isShowFastCert($params['userId'])) {
            $params['isNeedFast'] = 0;
        }
        // 银行卡业务类型（0：安全卡  1：充值卡）
        $params['bankCardType'] = isset($params['bankCardType']) ? intval($params['bankCardType']) : UserBankcardService::CARD_TYPE_SAFE;
        // 商户订单号-新增（支付要区分用户多次绑卡的流程）
        $params['outOrderId'] = !empty($params['outOrderId']) ? intval($params['outOrderId']) : Idworker::instance()->getId();
        $data = [];
        $data['formId'] = 'h5AuthBindCardForm';
        $data['form'] = PaymentGatewayApi::instance()->getForm('h5AuthBindCard', $params, $data['formId'], $targetNew);
        return ['status' => 'S', 'respCode' => '00', 'data' => $data];
    }

    /**
     * 是否显示快捷验证方式
     */
    private function isShowFastCert($userId) {
        $userInfo = UserModel::instance()->find($userId);
        //大陆身份证才显示快捷认证方式
        if ($userInfo['id_type'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 查询银行卡信息-获取安全卡
     * @param integer $userId
     * @return array
     */
    public function queryBankCards($userId, $isSync = false) {
        if (empty($userId)) {
            throw new \Exception('userId参数必须');
        }

        // 获取支付系统所有银行卡列表中的安全卡数据
        $userBankCardObj = new UserBankcardService();
        $result = $userBankCardObj->queryBankCardsList($userId, true);
        if (!isset($result['respCode']) || $result['respCode'] != '00') {
            throw new \Exception($result['respMsg']?:'查询银行卡失败');
        }

        if (empty($result['list'])) {
            return [];
        }

        $bankData = $result['list'];
        if ($isSync == true) {
            try {
                $bankData['userId'] = $userId;
                $PaymentService = (new PaymentService())->bindcardNotifyCallback($bankData);
            } catch (\Exception $e) {
                Logger::error('SyncBankinfoException:'.$e->getMessage());
            }
        }
        return $bankData;
    }

    /**
     * 四要素验卡换卡接口 静默换卡接口
     * @param array $params
     * @return boolean true | false
     */
    public function quickAuthChangeCard($params) {
        $params['orderId'] = md5(microtime(true));
        // 处理支付换卡成功用户
        try {
            $paymentService = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_UCFPAY);
            $result = $paymentService->request('quickAuthChangeCard', $params);
            // 处理超时结果
            if (empty($result)) {
                return ['status' => '-2', 'respMsg' => '请求超时', 'respCode' => '01'];
            }
            // 不是成功状态 返回错误消息
            if ($result['status'] != PaymentService::PAYMENT_SUCCESS) {
                return  ['status' => $result['status'], 'respMsg' => $result['respMsg'], 'respCode' => $result['respCode']];
            }
            // 启动网信和存管行换卡流程
            $userId = $params['userId'];
            $gtm = new GlobalTransactionManager();
            $gtm->setName('SilentChangeCard');
            $wxUpdateParams = [];
            $wxUpdateParams['id'] = $userId;
            $wxUpdateParams['bank_bankcard'] = $params['bankCardNo'];
            $wxUpdateParams['bank_card_name'] = $params['userName'];
            $wxUpdateParams['bankcode'] = $result['bankCode'];
            $wxUpdateParams['cert_status'] = $result['certType'];
            $db = \libs\db\Db::getInstance('firstp2p', 'slave');
            $userbankcard = $db->getRow("SELECT * FROM firstp2p_user_bankcard WHERE user_id ='{$userId}' ORDER BY id DESC LIMIT 1");
            if (!empty($userbankcard)) {
                $wxUpdateParams['bankcard_id'] = $userbankcard['id'];
            }
            $gtm->addEvent(new WxUpdateUserBankCardEvent($wxUpdateParams));
            if (!empty($params['updateSvBank'])) {
                $PhService = new PhAccountService();
                $phRes = $PhService->getUserAccountId($userId, $params['userPurpose']);
                if (!empty($phRes['accountId'])) {
                    $accountId = $phRes['accountId'];
                    $gtm->addEvent(new SupervisionUpdateUserBankCardEvent($accountId));
                }
            }
            $gtmResult = $gtm->execute();
            if ($gtmResult == false) {
                throw new \Exception('SilentChangeCard failed, userId:'.$userId.' retrying...');
            }
            return ['status' => '00', 'respMsg' => '换卡成功', 'respCode' => '00'];
        } catch(\Exception $e) {
            PaymentApi::log($e->getMessage());
            return ['status' => '-1', 'respMsg' => $e->getMessage(), 'respCode' => '01'];
        }
    }

    /**
     * 是否需要切换到支付B方案
     * @param int $userId
     * @return array
     * isPlanB:是否强制切换支付B计划
     * isAllChannel:是否所有渠道都打开
     */
    public static function isForcePlanB($userId, $userBankCardInfo = []) {
        // 是否强制切换支付B计划、是否所有渠道都打开
        $data = ['isPlanB'=>false, 'isAllChannel'=>false];
        // 获取用户绑卡信息
        empty($userBankCardInfo) && $userBankCardInfo = UserBankcardModel::instance()->getNewCardByUserId($userId);
        if (!empty($userBankCardInfo) && $userBankCardInfo['status'] == UserBankcardModel::STATUS_BINDED) {
            // 获取先锋支付需要更换支付渠道的银行列表
            $changeChannelBankListString = app_conf('XFZF_CHANGECHANNEL_BANKLIST');
            $changeChannelBankList = explode(',', $changeChannelBankListString);
            if (!empty($changeChannelBankList)) {
                $bankObj = new BankService();
                foreach ($changeChannelBankList as $bankItem) {
                    // 检查该配置是否有配置银行开关
                    if (strpos($bankItem, '|') !== false) {
                        list($bankShortName, $bankOpen) = explode('|', $bankItem);
                        $bankShortName = trim(strtoupper($bankShortName));
                    }else{
                        // 没有配置的银行，默认打开
                        $bankShortName = trim(strtoupper($bankItem));
                        $bankOpen = 1;
                    }
                    // 通过银行编码获取银行信息
                    $bankInfo = $bankObj->getBankByCode($bankShortName);
                    if (!empty($bankInfo) && intval($bankInfo['id']) === intval($userBankCardInfo['bank_id'])) {
                        $data['isPlanB'] = true;
                        $data['isAllChannel'] = (bool)$bankOpen;
                        break;
                    }
                }
            }
        }
        PaymentApi::log(var_export($data, true));
        return $data;
    }

    /**
     * 用户当前银行是否在后台配置的名单内
     * @param string $bankCode 银行简码
     * @param string $bankConfigKey 后台配置银行简码的键值
     * @return array
     */
    public static function inBankListConfig($bankCode, $bankConfigKey = 'XFZF_CHANGECHANNEL_BANKLIST') {
        if (empty($bankCode) || empty($bankConfigKey)) {
            return false;
        }

        // 获取先锋支付需要更换支付渠道的银行列表
        $changeChannelBankListString = app_conf($bankConfigKey);
        $changeChannelBankList = explode(',', $changeChannelBankListString);
        if (empty($changeChannelBankList)) {
            return false;
        }
        foreach ($changeChannelBankList as $bankItem) {
            // 检查该配置是否有配置银行开关
            if (strpos($bankItem, '|') !== false) {
                list($bankShortName, $bankOpen) = explode('|', $bankItem);
                $bankShortName = trim(strtoupper($bankShortName));
            }else{
                // 没有配置的银行，默认打开
                $bankShortName = trim(strtoupper($bankItem));
                $bankOpen = 1;
            }
            if (!$bankOpen) {
                continue;
            }

            if (!empty($bankShortName) && $bankShortName == $bankCode) {
                return true;
            }
        }
    }

    /**
     * 获取该用户可用的充值渠道
     * @param int $userId 用户ID
     * @param array $channelParams 充值渠道参数
     * ['money'=>'充值金额，单位元', 'ucfLimitTips'=>'网信限额提示文案', 'yeeLimitTips'=>'网贷限额提示文案',
     * 'ucfLimitToast'=>'网信限额提示文案', 'yeeLimitToast'=>'网贷限额提示文案']
     */
    public static function getAvailableChargeChannel($userId, $platform = UserAccountEnum::PLATFORM_WANGXIN, $channelParams = [], $userBankCardInfo = []) {
        try {
            if (!empty($channelParams['money']) && bccomp($channelParams['money'], '0.00', 2) <= 0) {
                throw new \Exception('[money]参数错误');
            }

            // 获取用户绑卡数据
            empty($userBankCardInfo) && $userBankCardInfo = UserBankcardModel::instance()->getNewCardByUserId($userId);
            if (empty($userBankCardInfo)) {
                throw new \Exception('用户暂未绑卡');
            }

            // 获取当前可用的支付方式
            $paymentChannelListConfig = PaymentApi::getPaymentChannel();
            if (empty($paymentChannelListConfig)) {
                throw new \Exception('暂无可用的支付方式');
            }

            // 网贷账户，排除易宝支付充值渠道
            $isDisplayPlanB = $platform == UserAccountEnum::PLATFORM_WANGXIN ? true : false;
            // 是否需要切换到支付B方案
            $planBInfo = self::isForcePlanB($userId, $userBankCardInfo);

            $paymentChannelList = array();
            // 2018-01-04 屏蔽app 普惠app中的易宝充值功能
            if ($planBInfo['isPlanB'] && 100 != \libs\utils\Site::getId()) {
                // 开启易宝后，是否同时支持先锋支付等其他充值渠道
                if ($planBInfo['isAllChannel']) {
                    // 限额提示文案
                    $limitTips = !empty($channelParams['ucfLimitTips']) ? $channelParams['ucfLimitTips'] : '';
                    // 超出限额Toast
                    $limitToast = !empty($channelParams['ucfLimitToast']) ? $channelParams['ucfLimitToast'] : '';
                    $paymentChannelList[] = array('paymentMethod'=>PaymentApi::PAYMENT_SERVICE_UCFPAY, 'paymentName'=>'先锋支付',
                        'paymentH5ForAPP'=>'', 'paymentToast'=>'', 'limitTips'=>$limitTips, 'limitToast'=>$limitToast,
                        'chargeChannel'=>PaymentNoticeModel::CHARGE_QUICK_CHANNEL,
                    );
                }

                if ($isDisplayPlanB) {
                    $paymentH5ForAPP = !empty($channelParams['money']) ? sprintf('/payment/start?money=%s', $channelParams['money']) : '/payment/start';
                    // 限额提示文案
                    $limitTips = !empty($channelParams['yeeLimitTips']) ? $channelParams['yeeLimitTips'] : '';
                    // 超出限额Toast
                    $limitToast = !empty($channelParams['yeeLimitToast']) ? $channelParams['yeeLimitToast'] : '';
                    $paymentChannelList[] = array('paymentMethod'=>PaymentApi::PAYMENT_SERVICE_YEEPAY, 'paymentName'=>'易宝支付',
                        'paymentH5ForAPP'=>$paymentH5ForAPP, 'paymentToast'=>'', 'limitTips'=>$limitTips, 'limitToast'=>$limitToast,
                        'chargeChannel'=>PaymentNoticeModel::CHARGE_YEEPAY_CHANNEL,
                    );
                }
            }else{
                $paymentChannelCount = count($paymentChannelListConfig);
                foreach ($paymentChannelListConfig as $paymentMethod => $paymentName) {
                    // 点击进入支付方式前，toast提示消息
                    $paymentH5ForAPP = $paymentToast = '';
                    // 限额提示文案
                    $limitTips = !empty($channelParams['ucfLimitTips']) ? $channelParams['ucfLimitTips'] : '';
                    // 超出限额Toast
                    $limitToast = !empty($channelParams['ucfLimitToast']) ? $channelParams['ucfLimitToast'] : '';
                    // 充值渠道标识
                    $chargeChannel = PaymentNoticeModel::CHARGE_QUICK_CHANNEL;
                    if ($isDisplayPlanB && $paymentMethod === PaymentApi::PAYMENT_SERVICE_YEEPAY) {
                        // 点击支付方式后，需要跳转的地址
                        $paymentH5ForAPP = !empty($channelParams['money']) ? sprintf('/payment/start?money=%s', $channelParams['money']) : '/payment/start';
                        $paymentToast = $paymentChannelCount == 1 ? PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_UCFPAY)->getGateway()->getConfig('common', 'CREATE_ORDER_TIPS') : '';
                        // 限额提示文案
                        $limitTips = !empty($channelParams['yeeLimitTips']) ? $channelParams['yeeLimitTips'] : '';
                        // 超出限额Toast
                        $limitToast = !empty($channelParams['yeeLimitToast']) ? $channelParams['yeeLimitToast'] : '';
                        // 充值渠道标识
                        $chargeChannel = PaymentNoticeModel::CHARGE_YEEPAY_CHANNEL;
                    }
                    $paymentChannelList[] = array('paymentMethod'=>$paymentMethod, 'paymentName'=>$paymentName,
                        'paymentH5ForAPP'=>$paymentH5ForAPP, 'paymentToast'=>$paymentToast,
                        'limitTips'=>$limitTips, 'limitToast'=>$limitToast,
                        'chargeChannel'=>$chargeChannel,);
                }
            }
            PaymentApi::log(sprintf('%s, userId：%d, 充值渠道参数：%s, 充值方式列表：%s, 获取充值方式列表成功', __METHOD__, $userId, json_encode($channelParams), json_encode($paymentChannelList)));
            return ['ret'=>true, 'list'=>$paymentChannelList];
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s, userId：%d, 充值渠道参数：%s, 获取充值方式列表失败', __METHOD__, $userId, json_encode($channelParams)));
            return ['ret'=>false, 'errmsg'=>$e->getMessage(), 'list'=>[]];
        }
    }

    /**
     * 获取指定账户的限额信息
     * @param int $userId 用户ID
     * @param int $platform 账户类型(1:网贷2:网信)
     */
    public function getLimitDescByPlatform($userId, $platform = UserAccountEnum::PLATFORM_WANGXIN, $userBankCardInfo = []) {
        $result = ['limitDesc'=>'', 'singleMaxLimit'=>'-1', 'dayTotalLimit'=>'-1', 'monthTotalLimit'=>'-1', 'totalDayChargeAmount' => '0', 'list'=>[]];
        // 获取用户绑卡数据
        empty($userBankCardInfo) && $userBankCardInfo = UserBankcardModel::instance()->getNewCardByUserId($userId);
        if (empty($userBankCardInfo)) {
            return $result;
        }

        if ($platform == UserAccountEnum::PLATFORM_WANGXIN) {
            // 获取该用户可用的充值渠道
            $availableChannel = self::getAvailableChargeChannel($userId, $platform, [], $userBankCardInfo);
            if (empty($availableChannel) || $availableChannel['ret'] === false) {
                return $result;
            }

            $chargeChannelList = [];
            foreach ($availableChannel['list'] as $channelItem) {
                if (empty($channelItem['paymentMethod']) || empty($channelItem['chargeChannel'])) {
                    continue;
                }
                $chargeChannelList[] = $channelItem['chargeChannel'];
            }
        } else {
            // 网贷账户限额读取订阅数据
            $chargeChannelList[] = PaymentNoticeModel::CHARGE_NCFPH_CHANNEL;
        }

        // 获取银行简码
        $bankService = new BankService();
        $bankInfo = $bankService->getBank($userBankCardInfo['bank_id']);
        if (empty($bankInfo)) {
            return $result;
        }

        // 批量获取多个充值渠道，某银行的限额列表，单位元
        $limitList = self::getPhChargeLimitListBatch($chargeChannelList, $bankInfo['short_name']);
        if (empty($limitList)) {
            PaymentApi::log(sprintf('%s, userId:%d, bankId:%d, 该银行卡限额信息不存在', __METHOD__, $userId, $userBankCardInfo['bank_id']));
            return $result;
        }
        $accountService = new AccountService();
        $dayChargeAmount = $accountService->getUserDayChargeAmount($userId, $platform, PaymentNoticeModel::$mobilePlatform); //获取用户日充值总金额

        // 网贷限额数据
        if ($platform == UserAccountEnum::PLATFORM_SUPERVISION) {
            $paymentService = new PaymentService();
            $limitInfo = $limitList[0];
            $limitDesc = [];
            // 单笔最大限额
            if (isset($limitInfo['max_quota']) && $limitInfo['max_quota'] > 0) {
                $limitDesc[] = $paymentService->formatMoneyNew($limitInfo['max_quota']) . '元/笔';
            }
            // 日最大限额
            if (isset($limitInfo['day_quota']) && $limitInfo['day_quota'] > 0) {
                $limitDesc[] = $paymentService->formatMoneyNew($limitInfo['day_quota']) . '元/日';
            }
            // 月最大限额
            if (isset($limitInfo['month_quota']) && $limitInfo['month_quota'] > 0) {
                $limitDesc[] = $paymentService->formatMoneyNew($limitInfo['month_quota']) . '元/月';
            }
            if (!empty($limitDesc)) {
                $result['limitDesc'] = '充值限额：' . join('，', $limitDesc);
            }
            $result['singleMaxLimit'] = strval(intval($limitInfo['max_quota']));
            $result['dayTotalLimit'] = strval(intval($limitInfo['day_quota']));
            $result['monthTotalLimit'] = strval(intval($limitInfo['month_quota']));
            $result['totalDayChargeAmount'] = strval($dayChargeAmount['total']);
            // 后台尚未配置限额则返回-1
            if ($limitInfo['day_quota'] < 0) {
                $result['dayRemainLimit'] = strval(intval($limitInfo['day_quota']));
            } else {
                // 计算网贷当日剩余充值额度
                $remainLimit = bcsub($result['dayTotalLimit'], $result['totalDayChargeAmount'], 2);
                $result['dayRemainLimit'] = strval(max('0.00', $remainLimit));
            }
            $result['singlelimitTips'] = '';
            if (isset($limitInfo['max_quota']) && $limitInfo['max_quota'] > 0) {
                $result['singlelimitTips'] = '本次单笔充值限额为'.$paymentService->formatMoneyNew($limitInfo['max_quota']).'元';
            }
            $result['daylimitTips'] = '';
            if (isset($limitInfo['day_quota']) && $limitInfo['day_quota'] > 0) {
                $result['daylimitTips'] = '今日充值限额为'.$paymentService->formatMoneyNew($limitInfo['day_quota']).'元';
            }
            unset($result['list']);
            // 记录日志
            PaymentApi::log(sprintf('%s, userId:%d, platform:%d, bankId:%d, limitList:%s, limitInfo:%s, result:%s, 获取网贷账户的限额数据成功', __METHOD__, $userId, $platform, $userBankCardInfo['bank_id'], json_encode($limitList), json_encode($limitInfo), json_encode($result)));
            return $result;
        }

        // 新协议支付开关是否打开
        $isH5Charge = SupervisionFinanceService::isNewBankLimitOpen();
        $busType = $isH5Charge ? ChargeService::LIMIT_TYPE_NEWH5 : ChargeService::LIMIT_TYPE_APP;
        // 累加多个充值渠道的单笔限额、当日限额、当月限额，单位元
        // 改为走接口获取合并充值限额
        $paymentServ = new PaymentService();
        $newChargeLimitResult = $paymentServ->getNewChargeLimit(['userId' => $userId, 'bankCode' => $bankInfo['short_name'], 'bankCardNo' => $userBankCardInfo['bankcard'], 'busType' => $busType]);
        if (!empty($newChargeLimitResult))
        {
            $result['singleMaxLimit'] = strval($newChargeLimitResult['singleLimit']);
            $result['dayTotalLimit'] = strval($newChargeLimitResult['dailyLimit']);
        }
        // 完善数据结构
        $channelSwitch = [
            'ucfpay' => false,
            'yeepay' => false,
        ];
        // 设置是否存在无限日限额的
        $flagExistUnlimitChannel = false;
        foreach ($limitList as $k => $list)
        {
            if ($list['pay_channel'] == PaymentNoticeModel::CHARGE_QUICK_CHANNEL)
            {
                $paymentId = PaymentNoticeModel::PAYMENT_UCFPAY;
                $limitList[$k]['dayChargeAmount'] = isset($dayChargeAmount[$paymentId]) ? $dayChargeAmount[$paymentId] : '0.00';
                // 先锋支付单笔限额按照接口返回值
                $limitList[$k]['max_quota'] = $result['singleMaxLimit'];
                // 先锋支付限额按照接口返回
                $limitList[$k]['day_quota'] = $result['dayTotalLimit'];
                // 设置状态
                $channelSwitch['ucfpay'] = true;

                // 如果先锋支付日限额无限, 则设置状态位
                if ($result['dayTotalLimit'] < 0)
                {
                    $flagExistUnlimitChannel = true;
                }
            }

            // 累加易宝日限额
            if ($list['pay_channel'] == PaymentNoticeModel::CHARGE_YEEPAY_CHANNEL )
            {
                $paymentId = PaymentNoticeModel::PAYMENT_YEEPAY;
                $limitList[$k]['dayChargeAmount'] = isset($dayChargeAmount[$paymentId]) ? $dayChargeAmount[$paymentId] : '0.00';
                // 设置状态
                $channelSwitch['yeepay'] = true;
                if ($list['day_quota'] >= 0) {
                    $result['dayTotalLimit'] = bcadd($result['dayTotalLimit'], $list['day_quota'], 2);
                } else {
                   // 易宝无限额则,日限额无限额
                   $result['dayTotalLimit'] = '-1';
                   // 如果先锋支付日限额无限, 则设置状态位
                   $flagExistUnlimitChannel = true;
                }
            }
        }
        // 任何渠道只要存在日限额无限, 则认定日限额为无限
        if ($flagExistUnlimitChannel === true)
        {
            $result['dayTotalLimit'] =  -1;
        }
        // 网信限额信息
        if ($result['dayTotalLimit'] > 0)
        {
            if ($channelSwitch['ucfpay'] && !$channelSwitch['yeepay'])
            {
                $result['limitDesc'] = '充值限额：' . $paymentServ->formatMoneyNew($result['singleMaxLimit']).'元/笔 '.$paymentServ->formatMoneyNew($result['dayTotalLimit']) .'元/日';
            } else {
                $result['limitDesc'] = '充值限额：' . $paymentServ->formatMoneyNew($result['dayTotalLimit']) .'元/日';
            }
        }

        $result['list'] = $limitList;
        // 记录日志
        PaymentApi::log(sprintf('%s, userId:%d, platform:%d, bankId:%d, limitList:%s, result:%s, 获取网信账户的限额数据成功', __METHOD__, $userId, $platform, $userBankCardInfo['bank_id'], json_encode($limitList), json_encode($result)));
        return $result;
    }

    /**
     * 根据用户ID、银行ID获取本地银行限额
     * @param int $userId 用户ID
     * @param string $payChannel 充值渠道
     * @return array
     */
    public function getNewChargeLimit($userId, $payChannel = PaymentNoticeModel::CHARGE_QUICK_CHANNEL, $userBankInfo = [])
    {
        $result = ['limitDesc'=>'', 'singleLimitDesc'=>'', 'dayLimitDesc'=>'', 'monthLimitDesc'=>'', 'limitIntro'=>[]];
        try {
            if (empty($userBankInfo)) {
                // 获取用户绑卡数据
                $userBankInfo = UserBankcardModel::instance()->getNewCardByUserId($userId);
            }
            // 检查绑卡状态是否正常
            if (empty($userBankInfo) || $userBankInfo['status'] != UserBankcardModel::STATUS_BINDED) {
                throw new \Exception('用户银行卡绑定状态异常');
            }
            // 银行ID
            $bankId = (int)$userBankInfo['bank_id'];

            // 检查充值渠道
            if (empty(PaymentNoticeModel::$chargeChannelConfig[$payChannel])) {
                throw new \Exception('充值渠道错误');
            }

            // 获取本地限额数据，单位元
            $limitInfo = PhSupervisionService::getPhChargeLimit($bankId, $userId, $payChannel);
            // 调用接口失败
            if (ApiService::hasError()) {
                throw new \Exception(ApiService::getErrorMsg(), ApiService::getErrorCode());
            }

            $limitDesc = [];
            if (!isset($limitInfo['respCode']) || $limitInfo['respCode'] != '00') {
                $errorMsg = !empty($limitInfo['respMsg']) ? $limitInfo['respMsg'] : '获取充值限额失败';
                Logger::error(sprintf('userId:%d, bankId:%d, GetBankLimitServiceError:%s', $userId, $userBankInfo['bank_id'], $errorMsg));
                throw new \Exception($errorMsg);
            }

            // 单笔最大限额，单位元
            $single = floatval(bcdiv($limitInfo['data']['singleLimit'], 10000, 2));
            if ($single > 0) {
                $limitDesc[] = $single . '万/笔';
                $result['singleLimitDesc'] = $single . '万';
            }

            // 日最大限额，单位元
            $day = floatval(bcdiv($limitInfo['data']['dayLimit'], 10000, 2));
            if ($day > 0) {
                $limitDesc[] = $day . '万/日';
                $result['dayLimitDesc'] = $day . '万';
            }

            // 月最大限额，单位元
            $month = floatval(bcdiv($limitInfo['data']['monthLimit'], 10000, 2));
            if ($month > 0) {
                $limitDesc[] = $month . '万/月';
                $result['monthLimitDesc'] = $month . '万';
            }

            if (!empty($limitDesc)) {
                $result['limitDesc'] = '充值限额：' . join('，', $limitDesc);
            }
            // 记录日志
            PaymentApi::log(sprintf('%s，userId：%d，bankId：%d，payChannel：%s，获取本地限额数据成功，limitInfo：%s', __METHOD__, $userId, $bankId, $payChannel, json_encode($limitInfo)));
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s，userId：%d，bankId：%d，payChannel：%s，获取本地限额数据失败，errorCode：%s，errorMsg：%s', __METHOD__, $userId, $bankId, $payChannel, $e->getCode(), $e->getMessage()));
        }

        // 处理限额描述字段
        if (!empty($limitInfo['data']['limitIntro'])) {
            $limitIntroArray = explode("\r\n", $limitInfo['data']['limitIntro']);
            $result['limitIntro'] = !empty($limitIntroArray) ? $limitIntroArray : [];
        }

        $result['singleLimit'] = !empty($limitInfo['data']['singleLimit']) ? strval(intval($limitInfo['data']['singleLimit'])) : '';
        $result['dayLimit'] = !empty($limitInfo['data']['dayLimit']) ? strval(intval($limitInfo['data']['dayLimit'])) : '';
        $result['monthLimit'] = !empty($limitInfo['data']['monthLimit']) ? strval(intval($limitInfo['data']['monthLimit'])) : '';
        $result['bankName'] = !empty($limitInfo['data']['bankName']) ? strval($limitInfo['data']['bankName']) : '';
        $result['bankCode'] = !empty($limitInfo['data']['bankCode']) ? strval($limitInfo['data']['bankCode']) : '';
        $result['limitJson'] = !empty($limitInfo['data']['limitJson']) ? strval($limitInfo['data']['limitJson']) : '';
        return $result;
    }

    /**
     * 批量获取普惠充值限额列表
     * @param array $chargeChannelList 可用的充值渠道列表
     * @param string $bankCode 银行简码
     */
    public static function getPhChargeLimitListBatch($chargeChannelList, $bankCode) {
        // 批量获取多个充值渠道，某银行的限额列表，单位元
        $list = PhSupervisionService::getPhChargeLimitList($chargeChannelList, $bankCode);
        foreach ($chargeChannelList as $channelKey => $channelName) {
            $existChannel = false;
            $loop = 0;
            foreach ($list as $key => $value) {
                if ($value['pay_channel'] == $channelName) {
                    $existChannel = true;
                    break;
                }
                ++$loop;
            }
            if (!$existChannel) {
                $list[$loop] = [
                    'type' => PaymentNoticeModel::$chargeTypeConfig[$channelName],
                    'pay_channel' => $channelName,
                    'code' => $bankCode,
                    'name' => '',
                    'min_quota' => '-1',
                    'max_quota' => '-1',
                    'day_quota' => '-1',
                    'month_quota' => '-1',
                    'limit_intro' => '',
                    'limit_json' => '',
                ];
            }
        }
        return $list;
    }
}
