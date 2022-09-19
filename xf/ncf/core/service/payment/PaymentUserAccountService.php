<?php
namespace core\service\payment;

use libs\utils\PaymentApi;
use libs\utils\PaymentGatewayApi;
use core\enum\SupervisionEnum;
use core\service\BaseService;
use core\service\user\UserService;
use core\service\user\BankService;
use core\dao\supervision\BankLimitModel;
use core\service\supervision\SupervisionFinanceService;

/**
 * 资金托管 -  用户账户接口
 *
 * 目前本服务提供的API
 * 2. modifyBankInfo 修改银行卡信息，同步给先锋支付风控（二期）,审核结果通过回调方式提供
 * 3. cancleUser 从先锋支付注销用户， 同步返回结果
 *
 */
class PaymentUserAccountService extends BaseService {

    /**
     * 判断用户是否是港澳台、军官证、护照用户
     */
    public static function hasPassport($userId, $userInfo = []) {
        $userId = intval($userId);
        if ($userId <= 0) {
            return false;
        }

        if (empty($userInfo) || empty($userInfo['id_type'])) {
            $userInfo = UserService::getUserById((int)$userId, 'id_type');
        }
        return (!empty($userInfo) && $userInfo['id_type'] > 1) ?  true : false;
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
    public function getChargeLimit($bankId)
    {
        $limitOpen = SupervisionFinanceService::isNewBankLimitOpen();
        if (!$limitOpen) {
            return $this->getChargeLimitGateway($bankId);
        }
        return $this->getChargeLimitSubscription($bankId);
    }

    /**
     * 获取移动端充值银行单笔限额和日限额
     * @param integer $bankId 用户银行卡对应的银行ID
     * @return array
     */
    public function getChargeLimitGateway($bankId)
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
            $bank = BankService::getBankInfoByBankId($bankId);
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
        }
        catch (\Exception $e)
        {
            $response['respCode'] = '01';
            $response['respMsg'] = $e->getMessage();
            // 检查是否有配置的默认充值限额
            $defaultBankLimit = SupervisionFinanceService::getDefaultBankLimit();
            if ($defaultBankLimit > 0) {
                $response['data'] = [
                    'dayLimit' => bcdiv($defaultBankLimit, 100, 0), // 日最大限额,单位分(-1:未知0:无限额)
                    'singleLimit' => bcdiv($defaultBankLimit, 100, 0), // 单笔最大限额,单位分(-1:未知0:无限额)
                    'monthLimit' => bcdiv($defaultBankLimit, 100, 0), // 月最大限额,单位分(-1:未知0:无限额)
                    'bankName' => !empty($bank['name']) ? $bank['name'] : '',
                    'bankCode' => !empty($bank['short_name']) ? $bank['short_name'] : '',
                ];
            }
        }
        return $response;
    }

    /**
     * 获取限额订阅的单笔限额和日限额，单位元
     * @param integer $bankId 用户银行卡对应的银行ID
     * @param int $userId 用户ID
     * @param string $payChannel 充值渠道
     * @return array
     */
    public function getChargeLimitSubscription($bankId, $userId = 0, $payChannel = '')
    {
        $response = [
            'respCode' => '00',
            'respMsg' => '',
            'data' => [],
        ];
        try {
            if (empty($payChannel)) {
                $payChannel = SupervisionEnum::CHARGE_QUICK_CHANNEL;
            }
            // 检查充值渠道
            if (empty(SupervisionEnum::$chargeChannelConfig[$payChannel])) {
                throw new \Exception('充值渠道错误');
            }

            if (intval($bankId) <= 0) {
                throw new \Exception('银行信息无效');
            }

            $bank = BankService::getBankInfoByBankId($bankId);
            if (empty($bank)) {
                PaymentApi::log(sprintf('%s | %s, bankId:%d, 该银行卡基本信息不存在', __CLASS__, __FUNCTION__, $bankId));
                throw new \Exception(sprintf('查询银行ID[%d]信息失败，请稍后再试', $bankId));
            }

            // 查询该银行编号的限额数据是否已存在
            $bankLimitInfo = BankLimitModel::instance()->getLimitByChannelCode($payChannel, $bank['short_name']);
            if (empty($bankLimitInfo)) {
                PaymentApi::log(sprintf('%s | %s, bankId:%d, bankName:%s, 该银行卡限额信息不存在', __CLASS__, __FUNCTION__, $bankId, $bank['short_name']));
                throw new \Exception('该银行卡暂无限额数据');
            }

            $response['data'] = [
                'dayLimit' => bcdiv($bankLimitInfo['day_quota'], 100, 0), // 日最大限额,单位元(-1:未知0:无限额)
                'singleLimit' => bcdiv($bankLimitInfo['max_quota'], 100, 0), // 单笔最大限额,单位元(-1:未知0:无限额)
                'monthLimit' => bcdiv($bankLimitInfo['month_quota'], 100, 0), // 月最大限额,单位元(-1:未知0:无限额)
                'limitIntro' => !empty($bankLimitInfo['limit_intro']) ? $bankLimitInfo['limit_intro'] : '',
                'limitJson' => !empty($bankLimitInfo['limit_json']) ? $bankLimitInfo['limit_json'] : '',
                'bankName' => $bank['name'],
                'bankCode' => $bank['short_name'],
            ];
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
                    'limitIntro' => '',
                    'limitJson' => '',
                    'bankName' => !empty($bank['name']) ? $bank['name'] : '',
                    'bankCode' => !empty($bank['short_name']) ? $bank['short_name'] : '',
                ];
            }
        }
        return $response;
    }

    /**
     * 批量获取限额订阅的单笔限额和日限额，单位元
     * @param integer $bankId 用户银行卡对应的银行ID
     * @param string $payChannel 充值渠道
     * @return array
     */
    public function getChargeLimitSubscriptionBatch($payChannel, $bankId = 0)
    {
        $response = [
            'respCode' => '00',
            'respMsg' => '',
            'data' => [],
        ];
        try {
            // 检查充值渠道
            if (empty(SupervisionEnum::$chargeChannelConfig[$payChannel])) {
                throw new \Exception('充值渠道错误');
            }

            $bankCode = '';
            if (intval($bankId) > 0) {
                $bank = BankService::getBankInfoByBankId($bankId);
                if (empty($bank)) {
                    PaymentApi::log(sprintf('%s | %s, bankId:%d, 该银行卡基本信息不存在', __CLASS__, __FUNCTION__, $bankId));
                    throw new \Exception(sprintf('查询银行ID[%d]信息失败，请稍后再试', $bankId));
                }
                $bankCode = $bank['short_name'];
            }

            // 查询该银行编号的限额列表是否已存在
            $bankLimitList = BankLimitModel::instance()->getChargeLimitList($payChannel, $bankCode);
            if (empty($bankLimitList)) {
                PaymentApi::log(sprintf('%s | %s, bankId:%d, bankName:%s, 该银行卡限额信息不存在', __CLASS__, __FUNCTION__, $bankId, $bank['short_name']));
                throw new \Exception('该银行卡暂无限额数据');
            }

            foreach ($bankLimitList as $limitItem) {
                $response['data'][] = [
                    'dayLimit' => bcdiv($limitItem['day_quota'], 100, 0), // 日最大限额,单位元(-1:未知0:无限额)
                    'singleLimit' => bcdiv($limitItem['max_quota'], 100, 0), // 单笔最大限额,单位元(-1:未知0:无限额)
                    'monthLimit' => bcdiv($limitItem['month_quota'], 100, 0), // 月最大限额,单位元(-1:未知0:无限额)
                    'limitIntro' => !empty($limitItem['limit_intro']) ? $limitItem['limit_intro'] : '',
                    'limitJson' => !empty($limitItem['limit_json']) ? $limitItem['limit_json'] : '',
                    'bankName' => $bank['name'],
                    'bankCode' => $bank['short_name'],
                ];
            }
        }
        catch (\Exception $e)
        {
            $response['respCode'] = '01';
            $response['respMsg'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * 根据开关控制限额获取路径-m站
     * @param integer $userId 用户ID
     * @param string $payChannel 充值渠道
     * @return array
     */
    public function getChargeLimitH5($userId, $payChannel = '')
    {
        $limitOpen = SupervisionFinanceService::isNewBankLimitOpen();
        if (!$limitOpen) {
            $response = ['respCode' => '00', 'respMsg' => '', 'data' => []];
            $data = UserService::limitBankInfo($userId);
            if ($data === false) {
                $response['respCode'] = UserService::getErrorCode();
                $response['respMsg'] = UserService::getErrorMsg();
                return $response;
            }
            $response['data'] = $data;
            return $response;
        }
        return $this->getChargeLimitSubscriptionH5($userId, $payChannel);
    }

    /**
     * 获取限额订阅的单笔限额和日限额
     * @param integer $userId 用户ID
     * @param string $payChannel 充值渠道
     * @return array
     */
    public function getChargeLimitSubscriptionH5($userId, $payChannel = '')
    {
        $response = [
            'respCode' => '00',
            'respMsg' => '',
            'data' => [],
        ];
        try {
            if (empty($payChannel)) {
                $payChannel = SupervisionEnum::CHARGE_QUICK_CHANNEL;
            }
            // 检查充值渠道
            if (empty(SupervisionEnum::$chargeChannelConfig[$payChannel])) {
                throw new \Exception('充值渠道错误');
            }

            $bankInfo = BankService::getNewCardByUserId($userId, 'bank_id');
            if (empty($bankInfo)) {
                PaymentApi::log(sprintf('%s | %s, userId:%d, 用户尚未绑定银行卡', __CLASS__, __FUNCTION__, $userId));
                throw new \Exception('用户尚未绑定银行卡');
            }

            $bank = BankService::getBankInfoByBankId($bankInfo['bank_id']);
            if (empty($bank)) {
                PaymentApi::log(sprintf('%s | %s, userId:%d, bankId:%d, 该银行卡基本信息不存在', __CLASS__, __FUNCTION__, $userId, $bankInfo['bank_id']));
                throw new \Exception('查询银行信息失败，请稍后再试');
            }

            // 查询该银行编号的限额数据是否已存在
            $bankLimitInfo = BankLimitModel::instance()->getLimitByChannelCode($payChannel, $bank['short_name']);
            if (empty($bankLimitInfo)) {
                PaymentApi::log(sprintf('%s | %s, userId:%d, bankId:%d, bankName:%s, 该银行卡限额信息不存在', __CLASS__, __FUNCTION__, $userId, $bankInfo['bank_id'], $bank['short_name']));
                throw new \Exception('该银行卡暂无限额数据');
            }

            $response['data'] = [
                'bank_id'  => $bank['id'],
                'bankCode' => $bank['short_name'],
                'bankName' => $bank['name'],
                'perDayLimit' => $bankLimitInfo['day_quota'], // 日最大限额,单位分(-1:未知0:无限额)
                'perLimit' => $bankLimitInfo['max_quota'], // 单笔最大限额,单位分(-1:未知0:无限额)
                'limitIntro' => !empty($bankLimitInfo['limit_intro']) ? $bankLimitInfo['limit_intro'] : '',
                'limitJson' => !empty($bankLimitInfo['limit_json']) ? $bankLimitInfo['limit_json'] : '',
            ];
        }
        catch (\Exception $e)
        {
            $response['respCode'] = '01';
            $response['respMsg'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * 获取移动端绑卡界面地址
     * @param integer $userId
     * @return string $url
     */
    public function h5AuthBindCard($params) {
        if (empty($params['userId']) || empty($params['reqSource'])) {
            throw new \Exception('参数不正确');
        }
        if ($params['reqSource'] == 1) {
            $params['returnUrl'] = 'ucfpay://api?method=bindcard&result=1';
            $params['failUrl'] = 'ucfpay://api?method=bindcard&result=0';
        }
        //不显示快捷认证方式
        if (!self::isShowFastCert($params['userId'])) {
            $params['isNeedFast'] = 0;
        }
        $requestUrl = PaymentGatewayApi::instance()->getRequestUrl('h5AuthBindCard', $params);
        return $requestUrl;
    }

    /**
     * 获取移动端绑卡Form
     * @param integer $userId
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
        if (!self::isShowFastCert($params['userId'])) {
            $params['isNeedFast'] = 0;
        }
        $data = [];
        $data['formId'] = 'h5AuthBindCardForm';
        $data['form'] = PaymentGatewayApi::instance()->getForm('h5AuthBindCard', $params, $data['formId'], $targetNew);
        return ['status' => 'S', 'respCode' => '00', 'data' => $data];
    }

    /**
     * 是否显示快捷验证方式
     */
    private static function isShowFastCert($userId) {
        $userInfo = UserService::getUserById((int)$userId, 'id_type');
        //大陆身份证才显示快捷认证方式
        if ($userInfo['id_type'] == 1) {
            return true;
        }
        return false;
    }

    /**
     * 查询银行卡信息
     * @param integer $userId
     * @return array
     */
    public static function queryBankCards($userId, $isSync = false) {
        if (empty($userId)) {
            throw new \Exception('userId参数必须');
        }

        $params['userId'] = $userId;
        $result = PaymentApi::instance()->request('searchbankcards', $params);
        if (empty($result) || $result['status'] != '00') {
            throw new \Exception($result['respMsg']?:'查询银行卡失败');
        }
        if (count($result['bankCards']) > 0) {
            $bankData = $result['bankCards'][0];
            if ($isSync == true) {
                $bankData['userId'] = $userId;
            }
            return $bankData;
        }
        return [];
    }

}
