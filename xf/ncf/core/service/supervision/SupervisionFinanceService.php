<?php
namespace core\service\supervision;

use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\Zhuge;
use libs\db\Db;
use libs\common\ErrCode;
use libs\common\WXException;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\utils\Alarm;
use libs\mail\Mail;
use libs\utils\Monitor;
use core\enum\SupervisionEnum;
use core\enum\UserAccountEnum;
use core\enum\PaymentEnum;
use core\service\user\UserService;
use core\service\user\UserCarryService;
use core\service\account\AccountLimitService;
use core\service\coupon\CouponService;
#use core\service\CreditLoanService;
#use core\service\LoanThirdService;
use core\service\payment\PaymentService;
use core\service\account\AccountService;
use core\service\user\BankService;
use core\service\supervision\SupervisionBaseService; // 存管资金相关服务
use core\service\supervision\SupervisionAccountService; // 存管账户相关服务
use core\service\supervision\SupervisionOrderService; // 存管账户相关服务
use core\service\repay\P2pDealRepayService;
use core\service\deal\P2pDealGrantService;
use core\service\risk\RiskService;
use core\dao\supervision\SupervisionTransferModel;
use core\dao\supervision\SupervisionWithdrawModel;
use core\dao\supervision\SupervisionChargeModel;
use core\dao\supervision\BankLimitModel;
#use core\exception\UserThirdBalanceException;
use core\service\msgbus\MsgbusService;
use core\enum\MsgbusEnum;
use core\service\UserAccessLogService;
use core\enum\UserAccessLogEnum;
use core\enum\DeviceEnum;

/**
 * P2P存管 - 资金相关服务
 */
class SupervisionFinanceService extends SupervisionBaseService {
    /**
     * 不在提示划转的缓存key
     * @var string
     */
    const KEY_NOT_PROMPT_TRANSFER = 'supervision_not_prompt_transfer_%s';

    /**
     * 提现是否终态
     * @var boolean
     */
    private $isWithdrawFinalState = false;

    /**
     * 充值接口
     * @param array $params 业务参数列表
     * @param string $platform 请求方式
     * @param string $formId 返回表单id和name
     * @param boolean $targetNew 是否新窗口打开表单
     * @return array
     */
    public function charge($params, $platform = 'pc', $formId = 'chargeForm', $targetNew = false) {
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }

            $db = Db::getInstance('firstp2p');
            $db->startTrans();
            $supervisionCharge = SupervisionChargeModel::instance();
            $chargePlatform = PaymentEnum::PLATFORM_WEB;
            switch (strtolower($platform))
            {
                case 'ios':
                case 'android':
                    $chargePlatform = PaymentEnum::PLATFORM_ANDROID;
                    break;
                case 'h5':
                    $chargePlatform = PaymentEnum::PLATFORM_MOBILEWEB;
                    break;
                default:
                    $chargePlatform = PaymentEnum::PLATFORM_WEB;
            }
            if (!$supervisionCharge->createOrder($params['userId'], $params['amount'], $params['orderId'], $chargePlatform)) {
                throw new WXException('ERR_CREATE_CHARGE_FAILED');
            }

            // 上报ITIL
            Monitor::add('sv_payment_apply');
            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_RECHARGE, $params);

            $service = $platform === 'pc' ? 'webCharge' : 'h5Charge';
            $form = $this->api->getForm($service, $params, $formId, $targetNew);
            $data = [
                'form' => $form,
                'formId' => $formId,
            ];
            $db->commit();

            //生产用户访问日志
            $extraInfo = [
                'orderId'       => $params['orderId'],
                'chargeAmount'  => (int) $params['amount'],
                'chargeTime'    => time(),
                'chargeChannel' => UserAccessLogEnum::CHARGE_CHANNEL_SUPERVISION,
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_CHARGE, sprintf('网贷充值申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT);

            return $this->responseSuccess($data);
        } catch(\Exception $e) {
            $db->rollback();
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 充值回调处理
     */
    public function chargeNotify($response, $userLogType = '充值'){
        $paymentNoticeSn = isset($response['orderId']) ? trim($response['orderId']) : '';
        $orderStatus = isset($response['status']) ? trim($response['status']) : '';
        $orderStatus = SupervisionEnum::$statusMap[$orderStatus];
        $amount = isset($response['amount']) ? intval($response['amount']) : '';
        try{
            if (empty($paymentNoticeSn) || empty($orderStatus) || empty($amount)) {
                throw new WXException('ERR_PARAM');
            }

            $chargeOrder = SupervisionChargeModel::instance()->getChargeRecordByOutId($paymentNoticeSn);
            if (empty($chargeOrder)) {
                throw new WXException('ERR_CHARGE_ORDER_NOT_EXSIT');
            }

            //处理中的回调当成功处理
            if ($orderStatus == SupervisionEnum::PAY_STATUS_PROCESS || $orderStatus == $chargeOrder['pay_status']) {
                return $this->responseSuccess();
            }

            $db = Db::getInstance('firstp2p');
            $db->startTrans();

            if (bccomp($chargeOrder['amount'], $amount) !== 0) {
                throw new WXException('ERR_CHARGE_AMOUNT');
            }

            // 根据充值订单号，调用支付的订单查询接口，查询该笔订单的状态
            $orderCheckInfo = $this->orderSearch($paymentNoticeSn);
            if ($orderCheckInfo['status'] == SupervisionEnum::RESPONSE_SUCCESS && isset($orderCheckInfo['data'])) {
                if (isset($orderCheckInfo['data']['status']) && $orderCheckInfo['data']['status'] != $response['status']) {
                    throw new WXException('ERR_CHARGE_DISACCORD');
                }
                $checkAmount = $orderCheckInfo['data']['amount'];
                $orderAmount = $chargeOrder['amount'];
                if (bccomp($checkAmount, $orderAmount) != 0) {
                    throw new WXException('ERR_CHARGE_AMOUNT');
                }
            }
            //成功回调处理
            $orderPaidResult = SupervisionChargeModel::instance()->orderPaid($paymentNoticeSn, $orderStatus, $amount, $userLogType);
            if (!$orderPaidResult) {
                throw new WXException('ERR_ORDER_PAID');
            }
            // 诸葛统计埋点
            if(in_array($chargeOrder['platform'],PaymentEnum::$wapPlatform) || in_array($chargeOrder['platform'],PaymentEnum::$pcPlatform)){
                $eventName = '网贷账户_充值成功';
                $zhugeSource = Zhuge::APP_MOBILE;
                (new Zhuge($zhugeSource))->event($eventName,$chargeOrder['user_id'], ['money'=>bcdiv($amount, 100, 2)]);
                $zhugeSource = Zhuge::APP_WEB;
                (new Zhuge($zhugeSource))->event($eventName,$chargeOrder['user_id'], ['money'=>bcdiv($amount, 100, 2)]);
                $eventName = '网贷账户_充值成功';
                $zhugeSource = Zhuge::APP_PHMOBILE;
                (new Zhuge($zhugeSource))->event($eventName,$chargeOrder['user_id'], ['money'=>bcdiv($amount, 100, 2)]);
                $zhugeSource = Zhuge::APP_PHWEB;
                (new Zhuge($zhugeSource))->event($eventName,$chargeOrder['user_id'], ['money'=>bcdiv($amount, 100, 2)]);
            }

            // 充值成功触发O2O请求
            if ($response['status'] == SupervisionEnum::CHARGE_SUCCESS)
            {
                $paymentService = new PaymentService();
                $paymentService->chargeTriggerO2O($chargeOrder);
            }

            //异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($response['orderId'], $response['status']);

            // 业务来源-存管自动扣款充值代扣
            if ($chargeOrder['platform'] == PaymentEnum::PLATFORM_SUPERVISION_AUTORECHARGE) {
                $p2pDealRepayService = new P2pDealRepayService();
                $response['failReason'] = isset($response['failReason']) ? $response['failReason'] : $response['errMsg'];
                $dkRepayRet = $p2pDealRepayService->dealDkRepayCallBack($response['orderId'], $response['status'],$response['failReason']);
                if (!$dkRepayRet) {
                    throw new WXException('ERR_AUTOCHARGE_NOTIFY_FAILED');
                }
            }

            $db->commit();

            if ($orderStatus == SupervisionEnum::PAY_STATUS_SUCCESS) {
                $paymentService->setChargeStatusCache($chargeOrder['user_id'], $paymentNoticeSn);
            }

            // 业务来源-存管自动代扣充值，不需要发送短信
            if ($chargeOrder['platform'] != PaymentEnum::PLATFORM_SUPERVISION_AUTORECHARGE) {
                //发送充值成功短信
                send_supervision_charge_msg($paymentNoticeSn);
            }

            //生产用户访问日志
            if ($orderStatus == SupervisionEnum::PAY_STATUS_SUCCESS || $orderStatus == SupervisionEnum::PAY_STATUS_FAILURE) {
                $extraInfo = [
                    'orderId'       => $response['orderId'],
                    'chargeAmount'  => (int) $amount,
                    'chargeTime'    => time(),
                    'chargeChannel' => $chargeOrder['platform'] == PaymentEnum::PLATFORM_OFFLINE_V2 ? UserAccessLogEnum::CHARGE_CHANNEL_OFFLINE : UserAccessLogEnum::CHARGE_CHANNEL_SUPERVISION,
                ];
                $logStatus = UserAccessLogService::getLogStatus($orderStatus);
                $statusName = $orderStatus == SupervisionEnum::PAY_STATUS_SUCCESS ? '成功' : '失败';
                UserAccessLogService::produceLog($chargeOrder['user_id'], UserAccessLogEnum::TYPE_CHARGE, sprintf('网贷充值%s元%s', bcdiv($chargeOrder['amount'], 100, 2), $statusName), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, $logStatus);
            }

            return $this->responseSuccess();
        } catch (\Exception $e) {
            isset($db) && $db->rollback();
            // 记录告警
            // 订单不存在不告警
            if ($e->getCode() != ErrCode::getCode('ERR_CHARGE_ORDER_NOT_EXSIT') ) {
                Alarm::push('supervision', __METHOD__, sprintf('存管充值回调异常|订单ID:%s，存管回调参数:%s，异常内容:%s', $response['orderId'], json_encode($response), $e->getMessage()));
            }
            // 添加监控
            Monitor::add('SUPERVISION_CHARGECALLBACK');
            return $this->responseFailure($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 提现准备
     * 免密提现和验密提现使用
     * @param string $service
     * @param array $params
     * @param boolean $checkPrivileges 检查权限
     * @return array $params
     */
    public function withdrawPrepare($service, $params, $checkPrivileges = false, $withdrawType = SupervisionEnum::TYPE_TO_BANKCARD, $limitId = 0) {
        // 如果是放款提现，则根据用户ID、标的ID，检查提现记录是否存在、状态是否终态
        $withdrawModel = SupervisionWithdrawModel::instance();
        if (!empty($params['bidId'])) {
            // 根据账户ID、标的ID查询提现成功的记录
            $orderInfo = $withdrawModel->getWithdrawSuccessByUserIdBid($params['userId'], $params['bidId']);
            if (!empty($orderInfo)) {
                $this->isWithdrawFinalState = true;
                return $params;
            }
        }

        $supervisionAccountService = new SupervisionAccountService();
        // 检查账户是否在存管开户
        $isSvUser = $supervisionAccountService->isSupervisionUser($params['userId']);
        if (!$isSvUser) {
            throw new WXException('ERR_NOT_OPEN_ACCOUNT');
        }

        // 检查账户存管余额
        $balanceResult = $supervisionAccountService->balanceSearch($params['userId']);
        if (empty($balanceResult) || $balanceResult['status'] != SupervisionEnum::RESPONSE_SUCCESS || $balanceResult['respCode'] != SupervisionEnum::RESPONSE_CODE_SUCCESS) {
            throw new WXException('ERR_BALANCE_SEARCH');
        }
        if ($params['amount'] > $balanceResult['data']['availableBalance']) {
            throw new WXException('ERR_BALANCE_NOT_ENOUGHT');
        }

        try{
            $db = Db::getInstance('firstp2p');
            $db->startTrans();

            $bidId = 0;
            if (!empty($params['bidId'])) {
                $bidId = $params['bidId'];
            }
            // 生成该账户的存管提现订单
            $createOrderResult = $withdrawModel->createOrder($params['userId'], $params['amount'], $params['orderId'], $bidId, $withdrawType, $limitId);
            if (empty($createOrderResult)) {
                $this->exception('ERR_CARRY_ORDER_CREATE');
            }

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_WITHDRAW, $params);

            // 上报 ITIL
            Monitor::add('sv_withdraw_apply');
            $db->commit();
        } catch(\Exception $e) {
            $db->rollback();
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return $params;
    }

    /**
     * 免密提现至银行卡
     * @param array $params 传入参数
     * @return array 输出结果
     */
    public function withdraw($params) {
        try {
            // 开启新的快速受托提现开关
            if ($this->canFastWithdraw($params['userId'], $params['amount']) && $this->isWithdrawFastOpen()) {
                return $this->entrustedWithdrawFast($params);
            }

            //提现准备
            $params = $this->withdrawPrepare('withdraw', $params, true);
            // 该笔提现记录已经是终态
            if (true === $this->isWithdrawFinalState) {
                $this->isWithdrawFinalState = false;
                return $this->responseSuccess(['respCode'=>SupervisionEnum::RESPONSE_CODE_SUCCESS, 'respMsg'=>'该笔提现记录已经终态']);
            }

            // 请求接口
            $result = $this->api->request('withdraw', $params);
            // 存管接口返回[商户流水已存在]正常处理
            if (!isset($result['respCode']) || ($result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== SupervisionEnum::CODE_ORDER_EXIST)) {
                // TODO 反向冻结存管余额
                throw new WXException('ERR_WITHDRAW');
            }

            //生产用户访问日志
            $extraInfo = [
                'orderId' => $params['orderId'],
                'withdrawAmount' => (int) $params['amount'],
                'withdrawTime' => time(),
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT);

            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 行内代发提现-掌众专用
     * @param array $params 传入参数
     * @return array 输出结果
     */
    public function bankpayupWithdraw($params) {
        try {
            // 开启新的快速受托提现开关
            if ($this->canFastWithdraw($params['userId'], $params['amount']) && $this->isWithdrawFastOpen()) {
                return $this->entrustedWithdrawFast($params);
            }

            //提现准备
            $params = $this->withdrawPrepare('bankpayupWithdraw', $params, true, SupervisionEnum::TYPE_LOCKMONEY);
            // 该笔提现记录已经是终态
            if (true === $this->isWithdrawFinalState) {
                $this->isWithdrawFinalState = false;
                return $this->responseSuccess(['respCode'=>SupervisionEnum::RESPONSE_CODE_SUCCESS, 'respMsg'=>'该笔提现记录已经终态']);
            }

            // 请求接口
            $result = $this->api->request('bankpayupWithdraw', $params);
            // 存管接口返回[商户流水已存在]正常处理
            if (!isset($result['respCode']) || ($result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== SupervisionEnum::CODE_ORDER_EXIST)) {
                // TODO 反向冻结存管余额
                throw new WXException('ERR_WITHDRAW_BANKPAYUP');
            }
            //生产用户访问日志
            $extraInfo = [
                'orderId' => $params['orderId'],
                'withdrawAmount' => (int) $params['amount'],
                'withdrawTime' => time(),
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT);

            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 终极提现回调
     * 说明：支持存管直接返回最终状态
     */
    public function finalWithdrawNotify($responseData, $userLogType = '提现') {
        try {
            PaymentApi::log(sprintf('%s | %s, responseData: %s', __CLASS__, __FUNCTION__, json_encode($responseData)));
            if (empty($responseData['orderId']) || empty($responseData['status']) || empty($responseData['amount'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            //检查提现
            $withdrawModel = SupervisionWithdrawModel::instance();
            $withdrawInfo = $withdrawModel->getWithdrawRecordByOutId($responseData['orderId']);
            if (empty($withdrawInfo)) {
                throw new WXException('ERR_CARRY_ORDER_NOT_EXIST');
            }

            //请求存管查单接口，校验订单状态
            $orderResult = $this->orderSearch($responseData['orderId']);
            if (empty($orderResult) || $orderResult['status'] != SupervisionEnum::RESPONSE_SUCCESS || $orderResult['respCode'] != SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_ORDER_SEARCH');
            }
            if ($responseData['status'] != SupervisionEnum::WITHDRAW_PROCESSING && $orderResult['data']['status'] !== $responseData['status']) {
                throw new WXException('ERR_CALLBACK_STATUS');
            }

            try {
                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                //如果提现订单是初始状态，并且存管通知状态是终态，则先按照处理中逻辑处理
                if ($withdrawInfo['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_NORMAL
                    && in_array($responseData['status'], [SupervisionEnum::WITHDRAW_SUCCESS, SupervisionEnum::WITHDRAW_FAILURE]) ) {
                        $tmpResponseData = $responseData;
                        $tmpResponseData['status'] = SupervisionEnum::WITHDRAW_PROCESSING;
                        $tmpRet = $this->withdrawNotify($tmpResponseData, $userLogType, false);
                        if ($tmpRet['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
                            throw new \Exception($tmpRet['respMsg']);
                        }
                }
                $ret = $this->withdrawNotify($responseData, $userLogType, false);
                if ($ret['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
                    throw new \Exception($ret['respMsg']);
                }
                $db->commit();
            } catch (\Exception $e) {
                $db->rollback();
                throw new \Exception($e->getMessage(), $e->getCode());
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($responseData)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 免密/验密提现至银行卡-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     orderId:外部订单号
     *     amount:金额
     *     status:订单处理状态 S-成功
     *     remark:备注
     */
    public function withdrawNotify($responseData, $userLogType = '提现', $isCheckOrder = true) {
        try {
            PaymentApi::log(sprintf('%s | %s, responseData: %s', __CLASS__, __FUNCTION__, json_encode($responseData)));
            if (empty($responseData['orderId']) || empty($responseData['status']) || empty($responseData['amount'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            //请求存管查单接口，校验订单状态
            if ($isCheckOrder) {
                $orderResult = $this->orderSearch($responseData['orderId']);
                if (empty($orderResult) || $orderResult['status'] != SupervisionEnum::RESPONSE_SUCCESS || $orderResult['respCode'] != SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                    throw new WXException('ERR_ORDER_SEARCH');
                }
                if ($responseData['status'] != SupervisionEnum::WITHDRAW_PROCESSING && $orderResult['data']['status'] !== $responseData['status']) {
                    throw new WXException('ERR_CALLBACK_STATUS');
                }
            }

            //检查提现金额
            $withdrawModel = SupervisionWithdrawModel::instance();
            $withdrawInfo = $withdrawModel->getWithdrawRecordByOutId($responseData['orderId']);
            if (empty($withdrawInfo)) {
                throw new WXException('ERR_CARRY_ORDER_NOT_EXIST');
            }
            if (bccomp($withdrawInfo['amount'], $responseData['amount']) != 0) {
                throw new WXException('ERR_CARRY_AMOUNT_WRONG');
            }
            //检查账户是否存在
            $accountInfo = AccountService::getAccountInfoById($withdrawInfo['user_id']);
            if (empty($accountInfo)) {
                throw new WXException('ERR_USER_NOEXIST');
            }

            //检查状态
            $_withdrawStatus = array(
                SupervisionEnum::WITHDRAW_SUCCESS => SupervisionEnum::WITHDRAW_STATUS_SUCCESS,
                SupervisionEnum::WITHDRAW_FAILURE => SupervisionEnum::WITHDRAW_STATUS_FAILED,
                SupervisionEnum::WITHDRAW_PROCESSING => SupervisionEnum::WITHDRAW_STATUS_PROCESS,
            );
            if (!isset($_withdrawStatus[$responseData['status']])) {
                throw new WXException('ERR_RESPONSE_STATUS');
            }
            // 如果订单状态已经跟返回的状态一致
            if ($_withdrawStatus[$responseData['status']] == $withdrawInfo['withdraw_status']) {
                return $this->responseSuccess();
            }
            // 如果提现订单已经是终态，还返回AS状态当成功处理
            if (in_array($withdrawInfo['withdraw_status'], SupervisionEnum::$finalStatus)
                && $_withdrawStatus[$responseData['status']] == SupervisionEnum::WITHDRAW_STATUS_PROCESS) {
                return $this->responseSuccess();
            }

            try {
                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                $withdrawMoney = bcdiv($withdrawInfo['amount'], 100, 2);
                $withdrawStatus = $_withdrawStatus[$responseData['status']];
                $withdrawMsg = isset($responseData['remark']) ? $responseData['remark'] : '';

                //处理提现
                $orderProcessResult = $withdrawModel->orderProcess($responseData['orderId'], $withdrawStatus, $responseData['amount'], $withdrawMsg, $userLogType);
                if (!$orderProcessResult) {
                    throw new WXException('ERR_WITHDRAW');
                }

                //异步更新存管订单
                $supervisionOrderService = new SupervisionOrderService();
                $orderWithdrawStatus = $responseData['status'] == SupervisionEnum::WITHDRAW_PROCESSING ? SupervisionEnum::NOTICE_PROCESSING : $responseData['status'];
                $supervisionOrderService->asyncUpdateOrder($responseData['orderId'], $orderWithdrawStatus);

                // 处理放款提现
                $dealGrantService = new P2pDealGrantService();
                $dealGrantService->withdrawNotify($responseData['orderId'], $responseData['status'], $withdrawMoney);

                $db->commit();

                //生产用户访问日志
                if ($withdrawStatus == SupervisionEnum::WITHDRAW_STATUS_SUCCESS || $withdrawStatus == SupervisionEnum::WITHDRAW_STATUS_FAILED) {
                    $extraInfo = [
                        'orderId' => $responseData['orderId'],
                        'withdrawAmount' => (int) $responseData['amount'],
                        'withdrawTime' => time(),
                    ];
                    $logStatus = UserAccessLogService::getLogStatus($withdrawStatus);
                    $statusName = $withdrawStatus == SupervisionEnum::WITHDRAW_STATUS_SUCCESS ? '成功' : '失败';
                    UserAccessLogService::produceLog($withdrawInfo['user_id'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现%s元%s', bcdiv($responseData['amount'], 100, 2), $statusName), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, $logStatus);
                }

                //发送提现消息和短信
                send_supervision_withdraw_msg($responseData['orderId']);

            } catch (\Exception $e) {
                $db->rollback();
                throw new \Exception($e->getMessage(), $e->getCode());
            }
            return $this->responseSuccess();
        } catch(\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($responseData)));
            // 记录告警
            if (!isset($responseData['failReason']) || $responseData['failReason'] != '资金账户余额不足') {
                Alarm::push('supervision', __METHOD__, sprintf('存管提现回调异常|订单ID:%s，提现金额:%s，存管回调参数:%s，异常内容:%s', $responseData['orderId'], $responseData['amount'], json_encode($responseData), $e->getMessage()));
            }
            // 添加监控
            Monitor::add('SUPERVISION_WITHDRAWCALLBACK');
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 网信账户划转到网贷理财账户第一步，创建划转记录单
     */
    public function superRechargeCreateOrder($params, $requestOnly = false)
    {
        //异步添加存管订单
        $supervisionOrderService = new SupervisionOrderService();
        $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_SUPER_RECHARGE, $params);

        if (!$requestOnly) {
            $transferModel = SupervisionTransferModel::instance();
            $createOrderResult = $transferModel->createOrder($params['userId'], $params['amount'], $params['orderId'], SupervisionTransferModel::DIRECTION_TO_SUPERVISION);
            if (!$createOrderResult) {
                return false;
            }
        }
        return true;
    }

    /**
     * 网信划转到网贷理财回滚方法
     */
    public function rechargeRollback() {
        return true;
    }

    /**
     * 网信账户划转到网贷账户
     */
    public function superRechargeRequestInterface($params)
    {
        // 请求接口
        $exceptionKey = 'ERR_SUPERRECHARGE';

        // 如果重试时订单已经处理，则直接返回
        $orderId = isset($params['orderId']) ? trim($params['orderId']) : 0;
        if (!empty($orderId)) {
            $orderStatus = $this->getTransferStatusByOutId($orderId);
            if ($orderStatus == SupervisionTransferModel::TRANSFER_STATUS_SUCCESS || $orderStatus == SupervisionTransferModel::TRANSFER_STATUS_FAILURE) {
                return true;
            }
        }

        $result = $this->api->request('superRecharge', $params);
        // 无响应
        if (!isset($result['respCode'])) {
            $searchOrderResult = $this->api->request('orderSearch', ['orderId' => $params['orderId']]);
            if (empty($searchOrderResult['respCode'])) {
                throw new WXException('ERR_SUPERRECHARGE');
            }
            $result = $searchOrderResult;
            $result['data']['status'] = $result['status'];
            $result['data']['amount'] = $result['amount'];
            $result['data']['orderId'] = $result['orderId'];
        } else {
            $result['data']['status'] = $result['respCode'] == SupervisionEnum::RESPONSE_CODE_SUCCESS ? SupervisionEnum::RESPONSE_SUCCESS : SupervisionEnum::RESPONSE_FAILURE;
            $result['data']['amount'] = $params['amount'];
            $result['data']['orderId'] = $params['orderId'];
        }
        // 接口请求失败或返回错误码非订单流水已存在
        if ($result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== SupervisionEnum::CODE_ORDER_EXIST) {
            throw new WXException($exceptionKey);
        }

        //  如果支付同步返回订单已受理，则等待回调处理， 结束gtm任务
        if ($result['respSubCode'] == SupervisionEnum::CODE_ORDER_EXIST) {
            return true;
        }
        // 划转处理逻辑
        $processResult = $this->superRechargeNotify($params['orderId'], null, $result);
        if (empty($processResult['status']) || $processResult['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
            throw new WXException($exceptionKey);
        }
        return true;
    }

    /**
     * 网信理财账户和存管账户之间互相转账
     * @param array $params 传入参数
     * @param boolean $requestOnly 只请求接口，不生成订单
     * @return array 输出结果
     */
    public function superRecharge($params, $requestOnly = false) {
        try {
            //$db = Db::getInstance('firstp2p', 'master');
            //$db->startTrans();
            if (empty($params['amount']) || empty($params['userId'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $userAccountService = new SupervisionAccountService();
            $isSvUser = $userAccountService->isSupervisionUser($params['userId']);
            if (!$isSvUser) {
                throw new WXException('ERR_SUPERVISION_OPEN_ACCOUNT');
            }

            $createOrderResult = $this->superRechargeCreateOrder($params, $requestOnly);
            if ($createOrderResult == false) {
                throw new \Exception('余额划转创建订单失败');
            }
            $result = $this->superRechargeRequestInterface($params);
            if ($result == false) {
                throw new \Exception('余额划转请求接口失败');
            }

            Monitor::add('SUPERVISION_SUPERRECHARGE_SUCC');
            //$db->commit();
            return $this->responseSuccess();
        } catch (\Exception $e) {
            //$db->rollback();
            // 记录告警
            //Alarm::push('supervision', __METHOD__, sprintf('网信理财账户和存管账户之间互相转账异常|订单ID:%s，用户ID:%d，异常内容:%s', $params['orderId'], $params['userId'], $e->getMessage()));
            Alarm::push('supervision_transfer_exception', __METHOD__, sprintf('网信理财账户和存管账户之间互相转账异常|订单ID:%s，用户ID:%d，异常内容:%s', $params['orderId'], $params['userId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_SUPERRECHARGE_FAIL');
            PaymentApi::log('Supervision superRecharge FAILED, code:'.$e->getCode().' message:'.$e->getMessage());
            if ($e instanceof UserThirdBalanceException) {
                throw $e;
            }
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 生成验密提现至网贷账户表单
     * @param array $params 传入参数
     * @return 表单数据
     */
    public function superRechargeSecret($params, $platform = 'pc', $formId = 'superRechargeForm', $targetNew = false) {
        $service = $platform == 'pc' ? 'superRechargeSecret' : 'h5SuperRechargeSecret';
        return $this->_createTransferForm($service, SupervisionTransferModel::DIRECTION_TO_SUPERVISION,  $params, $platform, $formId, $targetNew);
    }

    /**
     * 生成验密提现至超级账户表单
     * @param array $params 传入参数
     * @return array 输出结果
     */
    public function superWithdrawSecret($params, $platform = 'pc', $formId = 'superWithdrawForm', $targetNew = false) {
        $service = $platform === 'pc' ? 'superWithdrawSecret' : 'h5SuperWithdrawSecret';
        return $this->_createTransferForm($service, SupervisionTransferModel::DIRECTION_TO_WX,  $params, $platform, $formId, $targetNew);
    }

    private function _createTransferForm($service, $direction, $params, $platform, $formId, $targetNew) {
        try {
            $db = Db::getInstance('firstp2p', 'master');
            $db->startTrans();
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM_PLATFORM');
            }
            // 生成充值记录单号
            $transferModel = SupervisionTransferModel::instance();
            //TODO 资产中心 done
            $createOrderResult = $transferModel->createOrder($params['userId'], $params['amount'], $params['orderId'], $direction);
            if (!$createOrderResult) {
                throw new WXException('ERR_CHARGE_ORDER_CREATE');
            }
            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_SUPER_RECHARGE, $params);

            $result = $this->api->getForm($service, $params, $formId, $targetNew);
            $db->commit();
            // 请求接口
            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch (\Exception $e) {
            $db->rollback();
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 单笔订单查询
     * @param int $orderId 订单号
     * @return array
     */
    public function orderSearch($orderId) {
        try {
            if (empty($orderId)) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $result = $this->api->request('orderSearch', ['orderId' => $orderId]);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_ORDER_SEARCH');
            }
            // 构造订单结果数组
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 批次订单查询
     * @param int $orderId 订单号
     * @param int $orderType 订单类型 5000 = 放款、7000 = 还款、3100 = 流标、8000 = 返利红包收费
     * @return array
     */
    public function batchOrderSearch($orderId, $orderType) {
        try {
            if (empty($orderId) || empty($orderType)) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $result = $this->api->request('batchOrderSearch', ['batchId' => $orderId, 'orderType' => $orderType]);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_ORDER_SEARCH');
            }
            // 构造订单结果数组
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 批量订单查询接口
     * @param array $orderIds 订单号
     * @return array
     */
    public function batchSearch($orderIds) {
        try {
            if (empty($orderIds)) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $result = $this->api->request('batchSearch', ['orderIds' => $orderIds]);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_BATCH_SEARCH');
            }
            // 构造订单结果数组
            return $this->responseSuccess((!empty($result['orderIds']) ? $result['orderIds'] : []));
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 网贷账户提现到网信账户回滚
     */
    public function accountSuperWithdrawRollback() {
        return true;
    }

    /**
     * 网贷账户提现到网信账户
     */
    public function accountSuperWithdrawCreateOrder($params, $requestOnly = false)
    {
        $transferModel = SupervisionTransferModel::instance();
        //异步添加存管订单
        $supervisionOrderService = new SupervisionOrderService();
        $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_SUPER_WITHDRAW, $params);

        $createOrderResult = false;
        if (!$requestOnly) {
            // 生成充值记录单号
            $createOrderResult = $transferModel->createOrder($params['userId'], $params['amount'], $params['orderId'], SupervisionTransferModel::DIRECTION_TO_WX);
        } else {
            // 不生成充值记录单
            $createOrderResult = true;
        }
        return $createOrderResult;
    }

    /**
     * 网贷账户提现到网信账户 接口请求
     */
    public function accountSuperWithdrawReqeustInterface($params)
    {
        $orderId = isset($params['orderId']) ? trim($params['orderId']): 0;
        if (!empty($orderId)) {
            $orderStatus = $this->getTransferStatusByOutId($orderId);
            if ($orderStatus == SupervisionTransferModel::TRANSFER_STATUS_SUCCESS || $orderStatus == \core\dao\SupervisionTransferModel::TRANSFER_STATUS_FAILURE) {
                return true;
            }
        }
        $accountSuperWithdrawResult = [];
        $accountSuperWithdrawResult = $this->api->request('accountSuperWithdraw', $params);
        //请求超时，查询接口
        if (!isset($accountSuperWithdrawResult['respCode'])) {
            $searchOrderResult = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway()->request('orderSearch', ['orderId' => $params['orderId']]);
            if (empty($searchOrderResult['respCode']) || $searchOrderResult['respCode'] != SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_SUPERRECHARGE');
            }
            $accountSuperWithdrawResult = $searchOrderResult;
            $accountSuperWithdrawResult['data']['amount'] = $searchOrderResult['amount'];
            $accountSuperWithdrawResult['data']['orderId'] = $searchOrderResult['orderId'];
            $accountSuperWithdrawResult['data']['status'] = $searchOrderResult['status'];
        } else {
            $accountSuperWithdrawResult['data']['amount'] = $params['amount'];
            $accountSuperWithdrawResult['data']['orderId'] = $params['orderId'];
            $accountSuperWithdrawResult['data']['status'] = $accountSuperWithdrawResult['respCode'] == SupervisionEnum::RESPONSE_CODE_SUCCESS ? SupervisionEnum::RESPONSE_SUCCESS : SupervisionEnum::RESPONSE_FAILURE;
        }
        if ($accountSuperWithdrawResult['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS && $accountSuperWithdrawResult['respSubCode'] !== SupervisionEnum::CODE_ORDER_EXIST) {
            throw new WXException('ERR_AVOID_ACCOUNT_SUPERWITHDRAW');
        }
        //  如果支付同步返回订单已受理，则等待回调处理， 结束gtm任务
        if ($accountSuperWithdrawResult['respSubCode'] == SupervisionEnum::CODE_ORDER_EXIST) {
            return true;
        }
        $notifyResult = $this->superRechargeNotify($params['orderId'], SupervisionTransferModel::DIRECTION_TO_WX, $accountSuperWithdrawResult);
        if (empty($notifyResult['status']) || $notifyResult['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
            throw new WXException('ERR_TRANSFER_ORDER_FAILED');
        }
        return true;
    }

    /**
     * 免密提现至超级账户-接口
     * @param array $params 参数列表
     * @param boolean $requestOnly 只请求接口,不生成订单
     * @return array
     */
    public function accountSuperWithdraw($params, $requestOnly = false) {
        try {
            //$db = Db::getInstance('firstp2p', 'master');
            //$db->startTrans();
            // 检查用户是否设置免密提现
            $needPrivileges = array(
                'WITHDRAW_TO_SUPER',
            );
            $accountService = new SupervisionAccountService();
            $hasPrivilege = $accountService->checkUserPrivileges($params['userId'], $needPrivileges);
            if (!$hasPrivilege) {
                throw new WXException('ERR_HAVE_NO_PRIVILEGES');
            }
            $createOrderResult = $this->accountSuperWithdrawCreateOrder($params, $requestOnly);
            if ($createOrderResult == false) {
                throw new \Exception('余额划转创建订单失败');
            }
            $result = $this->accountSuperWithdrawReqeustInterface($params);
            if ($result == false) {
                throw new WXException('余额划转请求接口失败');
            }
            //$db->commit();
            Monitor::add('SUPERVISION_ACCOUNTSUPERWITHDRAW_SUCCESS');
            return $this->responseSuccess();
        } catch(\Exception $e) {
            //$db->rollback();
            // 记录告警
            Alarm::push('supervision_transfer_exception', __METHOD__, sprintf('网信超级账户划转至网贷P2P账户异常|订单ID:%s，用户ID:%d，异常内容:%s', $params['orderId'], $params['userId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_ACCOUNTSUPERWITHDRAW_FAIL');
            PaymentApi::log('FinanceService accountSuperWithdraw FAILED, code:'.$e->getCode().' message:'.$e->getMessage());
            if ($e instanceof UserThirdBalanceException) {
                throw $e;
            }
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 收费接口
     * @param array $params
     *          orderId string  业务单号
     *          bidId string  标的Id
     *          payUserId string  收费处款房P2P用户Id
     *          totalAmount int 该批次总金额
     *          totalNum int 该批次总笔数
     *          currency string 货币
     *          repayOrderList array 收费订单集合
     *              subOrderId string 外部订单号
     *              receiverUserId string 收款人P2P用户Id
     *              amount string 收款人P2P用户Id
     * @return array
     */
    public function gainFees($params) {
        try {
            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_GAINFEES, $params);

            $result = $this->api->request('gainFees', $params);
            if(isset($result['respCode']) && $result['respCode'] == SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                return $this->responseSuccess();
            } else {
                PaymentApi::log('Supervision gainFees failure, ret:'.json_encode($result, JSON_UNESCAPED_UNICODE));
                throw new WXException('ERR_RETURN_FAILURE');
            }
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /*
     * 收费回调接口
     */
    public function gainFeesNotify($response) {
        if (empty($response['orderId']) || empty($response['status'])) {
            throw new WXException('ERR_PARAM_LOSE');
        }
        //异步更新存管订单
        $supervisionOrderService = new SupervisionOrderService();
        $supervisionOrderService->asyncUpdateOrder($response['orderId'], $response['status']);
        return $this->responseSuccess();
    }

    /**
     * 提现至银信通电子账户-接口
     * @param array $params 参数列表
     * 必传参数：
     *     bidId:标的Id
     *     orderId:外部订单号
     *     userId:P2P用户Id
     *     totalAmount:总金额
     *     repayAmount:还款金额
     * @return array
     */
    public function bidElecWithdraw($params) {
        try {
            // 生成提现订单
            $withdrawModel = SupervisionWithdrawModel::instance();
            $createOrderResult = $withdrawModel->createOrder($params['userId'], $params['repayAmount'],$params['orderId'],$params['bidId'], SupervisionEnum::TYPE_TO_CREDIT_ELEC_ACCOUNT);

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_ELEC_WITHDRAW, $params);

            // 请求存管接口
            $result = $this->api->request('bidElecWithdraw', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_AVOID_ACCOUNT_SUPERWITHDRAW');
            }

            return $this->responseSuccess();
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 提现至银信通电子账户-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     merchantId:商户号
     *     orderId:外部订单号
     *     amount:金额
     *     status:订单处理状态(S-成功；F-失败)
     *     remark:备注
     */
    public function bidElecWithdrawNotify($responseData, $withdrawType = 0, $userLogType = '提现至银信通电子账户') {
        try {
            $orderId = $responseData['orderId'];
            $withdrawModel = SupervisionWithdrawModel::instance();
            $withdrawModel->db->startTrans();
            if (empty($responseData['orderId']) || empty($responseData['status']) || empty($responseData['amount'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $orderSearchResult = $this->orderSearch($orderId);
            // 判断订单查询结果
            if (empty($orderSearchResult['respCode']) || $orderSearchResult['respCode'] != SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_ORDER_NOT_EXIST');
            }
            // 判断业务处理结果
            $bizData = $orderSearchResult['data'];
            if (empty($bizData['respCode']) || $bizData['status'] != $responseData['status']) {
                throw new WXException('ERR_BID_ELEC_WITHDRAW_STATUS');
            }

            $status = SupervisionEnum::WITHDRAW_STATUS_NORMAL;
            if ($bizData['status'] == SupervisionEnum::RESPONSE_SUCCESS) {
                $status = SupervisionEnum::WITHDRAW_STATUS_SUCCESS;
            } else if ($bizData['status'] == SupervisionEnum::RESPONSE_FAILURE) {
                $status = SupervisionEnum::WITHDRAW_STATUS_FAILED;
            }
            $remark = isset($responseData['remark']) ? $responseData['remark'] : '';

            $orderProcessResult = $withdrawModel->orderProcess($orderId, $status, $bizData['amount'], $remark, $userLogType);
            if (!$orderProcessResult) {
                throw new WXException('ERR_SUPERRECHARGE');
            }

            switch ($withdrawType) {
                case 0: // 银信通
                default:
                    // 处理银信通回调后逻辑
                    $creditLoanService = new CreditLoanService();
                    $creditLoanService->repaySupervisionCallBack($orderId, $status);
                    break;
                case 1: // 享花
                    $loanThirdService = new LoanThirdService();
                    $loanThirdService->repayLoanThirdSupervisionCallBack($responseData);
                    break;
            }

            //异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($responseData['orderId'], $responseData['status']);

            $withdrawModel->db->commit();
            return $this->responseSuccess();
        } catch(\Exception $e) {
            $withdrawModel->db->rollback();
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('提现至银信通电子账户回调异常|订单ID:%s，存管回调参数:%s，异常内容:%s', $responseData['orderId'], json_encode($responseData), $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_BIDELECWITHDRAWCALLBACK');
            PaymentApi::log('supervision bidElecWithdrawNotify FAILED, code:'.$e->getCode().' message:'.$e->getMessage());
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * web/h5验密提现至银行卡-页面
     * @param array $params 参数列表
     * 必传参数:
     *      userId:P2P用户Id
     *      bizType:提现业务类型
     *      efficType:提现时效类型
     *      amount:提现金额，单位分
     * @return array
     */
    public function secretWithdraw($params, $platform = 'pc', $formId = 'accountWithdrawForm', $targetNew = false) {
        try {
            // 开启新的快速受托提现开关
            if ($this->canFastWithdraw($params['userId'], $params['amount']) && $this->isWithdrawFastOpen()) {
                return $this->secretWithdrawFast($params, $platform, $formId, $targetNew);
            }

            PaymentApi::log(sprintf('%s | %s, params: %s', __CLASS__, __FUNCTION__, json_encode($params)));
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM_PLATFORM');
            }
            $accountId = $params['userId'];
            $userId = AccountService::getUserId($accountId);
            // 限制提现
            if (!((new UserCarryService())->canWithdrawAmount($userId, bcdiv($params['amount'], 100, 2), true, false))) {
                throw new WXException('ERR_WITHDRAW_LIMIT');
            }

            $service = $platform === 'pc' ? 'secretWithdraw' : 'h5SecretWithdraw';
            //提现准备
            // TODO 双账户之后要优化
            $rules = (new AccountLimitService())->getAllLimits($userId, true);
            $withdrawType = SupervisionEnum::TYPE_TO_BANKCARD;
            $limitId = 0;
            if (!empty($rules[0]['account_type']) && $rules[0]['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
            {
                $withdrawType = SupervisionEnum::TYPE_LIMIT_WITHDRAW;
                $limitId = $rules[0]['id'];
                if (!(new AccountLimitService())->minusRemainMoney($rules[0]['id'], $params['amount']))
                {
                    throw new \Exception('ERR_WITHDRAW_LIMIT');
                }
            } else if (!empty($rules[0]['account_type']) && $rules[0]['account_type'] == UserAccountEnum::ACCOUNT_INVESTMENT) {
                $withdrawType = SupervisionEnum::TYPE_LIMIT_WITHDRAW_BLACKLIST;
            }
            $params = $this->withdrawPrepare($service, $params, false, $withdrawType, $limitId);

            // 请求存管接口
            $result = $this->api->getForm($service, $params, $formId, $targetNew);

            //生产用户访问日志
            $extraInfo = [
                'orderId' => $params['orderId'],
                'withdrawAmount' => (int) $params['amount'],
                'withdrawTime' => time(),
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT);

            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch(\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            //Alarm::push('supervision', 'secretWithdrawFailure', $e->getMessage() . ', ' . json_encode($params));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * web/h5验密提现至银行卡-页面
     * @param array $params 参数列表
     * 必传参数:
     *      userId:P2P用户Id
     *      bizType:提现业务类型
     *      efficType:提现时效类型
     *      amount:提现金额，单位分
     * @return array
     */
    public function secretWithdrawFast($params, $platform = 'pc', $formId = 'secretWithdrawForm', $targetNew = false) {
        try {
            PaymentApi::log(sprintf('%s | %s, params: %s', __CLASS__, __FUNCTION__, json_encode($params)));
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM_PLATFORM');
            }

            $accountId = $params['userId'];
            $userId = AccountService::getUserId($accountId);
            // 限制提现
            if (!((new UserCarryService())->canWithdrawAmount($userId, bcdiv($params['amount'], 100, 2), true, false))) {
                throw new WXException('ERR_WITHDRAW_LIMIT');
            }

            if ($platform === 'pc') {
                $service = 'pcSecretWithdrawFast';
                unset($params['bizType'], $params['efficType']);
            }else{
                $service = 'h5SecretWithdrawFast';
            }
            //提现准备
            // TODO 双账户之后要优化
            $rules = (new AccountLimitService())->getAllLimits($userId, true);
            $withdrawType = SupervisionEnum::TYPE_TO_BANKCARD;
            $limitId = 0;
            if (!empty($rules[0]['account_type']) && $rules[0]['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
            {
                $withdrawType = SupervisionEnum::TYPE_LIMIT_WITHDRAW;
                $limitId = $rules[0]['id'];
                if (!(new AccountLimitService())->minusRemainMoney($rules[0]['id'], $params['amount']))
                {
                    throw new \Exception('ERR_WITHDRAW_LIMIT');
                }
            } else if (!empty($rules[0]['account_type']) && $rules[0]['account_type'] == UserAccountEnum::ACCOUNT_INVESTMENT) {
                $withdrawType = SupervisionEnum::TYPE_LIMIT_WITHDRAW_BLACKLIST;
            }
            $params = $this->withdrawPrepare($service, $params, false, $withdrawType, $limitId);

            // 请求存管接口
            $result = $this->api->getForm($service, $params, $formId, $targetNew);

            //生产用户访问日志
            $extraInfo = [
                'orderId' => $params['orderId'],
                'withdrawAmount' => (int) $params['amount'],
                'withdrawTime' => time(),
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT);

            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch(\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 受托提现
     * @param array $params 业务参数列表
     * 必传参数:
     *      bidId:标的ID
     *      userId:借款用户Id
     *      grandOrderId:原放款单号
     *      bizType:提现业务类型
     *      efficType:提现时效类型
     *      amount:批次总金额
     * @return array
     */
    public function entrustedWithdraw($params) {
        try {
            // 开启新的快速受托提现开关
            if ($this->canFastWithdraw($params['userId'], $params['amount']) && $this->isWithdrawFastOpen()) {
                return $this->entrustedWithdrawFast($params);
            }

            if (empty($params['bidId']) || empty($params['userId'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            //$supervisionAccountService = new SupervisionAccountService();
            //if (!$supervisionAccountService->checkUserPrivileges($params['userId'], [self::GRANT_WITHDRAW_TO_ENTRUSTED])) {
            ///throw new WXException('ERR_HAVE_NO_PRIVILEGES');
            //}

            try{
                $db = Db::getInstance('firstp2p');
                $db->startTrans();

                //生成提现单
                $withdrawModel = SupervisionWithdrawModel::instance();
                $createOrderResult = $withdrawModel->createOrder($params['userId'], $params['amount'], $params['orderId'], $params['bidId'], SupervisionEnum::TYPE_ENTRUSTED);
                if (empty($createOrderResult)) {
                    $this->exception('ERR_CARRY_ORDER_CREATE');
                }

                //异步添加存管订单
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_ENTRUSTED_WITHDRAW, $params);

                $db->commit();
            } catch(\Exception $e) {
                $db->rollback();
                throw new \Exception($e->getMessage(), $e->getCode());
            }

            unset($params['bidId'], $params['userId']);//请求接口时去掉
            $result = $this->api->request('entrustedWithdraw', $params);
            // 原放款单已经存在受托支付
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200202') {
                throw new WXException('ERR_ENTRUSTED_WITHDRAW_EXIST');
            }
            // 存管接口返回报错，兼容"商户流水已存在"的逻辑
            if(empty($result['respCode']) || ($result['respCode'] != SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== SupervisionEnum::CODE_ORDER_EXIST)) {
                throw new WXException('ERR_ENTRUSTED_WITHDRAW');
            }

            // 业务处理
            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('受托提现异常|订单ID:%s，异常内容:%s', $params['orderId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_ENTRUSTEDWITHDRAW');
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            //Alarm::push('supervision', 'entrustedWithdrawFailure', $e->getMessage() . ', ' . json_encode($params));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 快速受托提现-新的提现接口（代替withdraw、bankpayupWithdraw、entrustedWithdraw）
     * @param array $params 传入参数
     * @return array 输出结果
     */
    public function entrustedWithdrawFast($params) {
        try {
            // 补充grandOrderId参数
            if (!isset($params['grandOrderId']) && isset($params['grantOrderId'])) {
                $params['grandOrderId'] = $params['grantOrderId'];
                unset($params['grantOrderId']);
            }

            if (empty($params['bidId']) || empty($params['userId'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            try{
                $db = Db::getInstance('firstp2p');
                $db->startTrans();

                // 生成提现单
                $withdrawModel = SupervisionWithdrawModel::instance();
                $createOrderResult = $withdrawModel->createOrder($params['userId'], $params['amount'], $params['orderId'], $params['bidId'], SupervisionEnum::TYPE_ENTRUSTED);
                if (empty($createOrderResult)) {
                    $this->exception('ERR_CARRY_ORDER_CREATE');
                }

                // 异步添加存管订单
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_ENTRUSTED_WITHDRAW, $params);

                $db->commit();
            } catch(\Exception $e) {
                $db->rollback();
                throw new \Exception($e->getMessage(), $e->getCode());
            }

            unset($params['bidId'], $params['userId']);// 请求接口时去掉
            $result = $this->api->request('entrustedWithdrawFast', $params);
            // 原放款单已经存在受托支付
            if (isset($result['respSubCode']) && $result['respSubCode'] == '200202') {
                throw new WXException('ERR_ENTRUSTED_WITHDRAW_EXIST');
            }
            // 存管接口返回报错，兼容"商户流水已存在"的逻辑
            if(empty($result['respCode']) || ($result['respCode'] != SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== SupervisionEnum::CODE_ORDER_EXIST)) {
                throw new WXException('ERR_WITHDRAW_ENTRUSTED_FAST');
            }

            //生产用户访问日志
            $extraInfo = [
                'orderId' => $params['orderId'],
                'withdrawAmount' => (int) $params['amount'],
                'withdrawTime' => time(),
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT);


            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('快速受托提现异常|订单ID:%s，异常内容:%s', $params['orderId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_ENTRUSTEDWITHDRAW');
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * h5 快捷认证
     * @param array $params
     *      userid string 商户用户Id
     *      returnUrl string 返回商户地址
     * @return array
     */
    public function h5MemberCardAuth($params) {
        try {
            if (empty($params['userId']) || empty($params['returnUrl'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $result = $this->api->request('h5MemberCardAuth', $params);

            if(isset($result['respCode']) && $result['respCode'] == SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                // 业务处理
                $this->responseSuccess();
            } else {
                // 失败处理
                PaymentApi::log('Supervision h5MemberCardAuth return failure, ret:'.json_encode($result, JSON_UNESCAPED_UNICODE));
                throw new WXException('ERR_H5_MEMBER_CARD_AUTH_FAILED');
            }
        } catch(\Exception $e) {
            $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 划转回调方法
     * @param string $orderId 外部订单号
     * @param integer $direction 余额划转方向
     * @param array $result 同步返回结果
     * @return array
     */
    public function superRechargeNotify($orderId, $direction = SupervisionTransferModel::DIRECTION_TO_SUPERVISION, $result = []) {
        $transferModel = SupervisionTransferModel::instance();
        try {
            $transferModel->db->startTrans();
            $orderSearchResult = $result;
            if (empty($orderSearchResult)) {
                $orderSearchResult = $this->orderSearch($orderId);
                PaymentApi::log('search order :'.json_encode($orderSearchResult));
                // 判断订单查询结果
                if (empty($orderSearchResult['data']['respCode']) || $orderSearchResult['data']['respCode'] != SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                    throw new WXException('ERR_SUPERRECHARGE');
                }
            }
            // 判断业务处理结果
            $bizData = $orderSearchResult['data'];
            $status = SupervisionTransferModel::TRANSFER_STATUS_NORMAL;
            if ($bizData['status'] == SupervisionEnum::RESPONSE_SUCCESS) {
                $status = SupervisionTransferModel::TRANSFER_STATUS_SUCCESS;
            } else if ($bizData['status'] == SupervisionEnum::RESPONSE_FAILURE) {
                $status = SupervisionTransferModel::TRANSFER_STATUS_FAILURE;
            }

            $orderProcessResult = $transferModel->orderProcess($orderId, $status, $bizData['amount'], '余额划转', $direction);
            if (!$orderProcessResult) {
                throw new WXException('ERR_SUPERRECHARGE');
            }

            //异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($orderId, $bizData['status']);

            $transferModel->db->commit();
            return $this->responseSuccess();
        } catch(\Exception $e) {
            $transferModel->db->rollback();
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('网贷P2P余额划转至理财超级账户回调异常|订单ID:%s，异常内容:%s', $orderId, $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_SUPERRECHARGECALLBACK');
            PaymentApi::log('Supervision superRechargeNotify FAILED, code:'.$e->getCode().' message:'.$e->getMessage());
            if ($e instanceof UserThirdBalanceException) {
                throw $e;
            }
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取划转状态
     * @param int $outOrderId 外部订单号
     * @return mix
     * 划转状态 0未处理 1成功 2失败
     */
    public function getTransferStatusByOutId($outOrderId) {
        $supervisionTransferModel = new SupervisionTransferModel();
        $result = $supervisionTransferModel->getTransferRecordByOutId($outOrderId);
        if (!empty($result)) {
            return (int) $result['transfer_status'];
        }
        return false;
    }

    /**
     * 设置不在提示划转
     * @param int $userId
     * @throws \Exception
     * @return boolean
     */
    public function setNotPromptTransfer($userId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception('getRedisInstance failed');
        }
        $cacheKey = sprintf(self::KEY_NOT_PROMPT_TRANSFER, $userId);
        return $redis->set($cacheKey, 1) ? true : false;
    }

    /**
     * 是否提示提示划转
     * @param int $userId
     * @throws \Exception
     * @return boolean
     */
    public function isPromptTransfer($userId) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception('getRedisInstance failed');
        }
        $cacheKey = sprintf(self::KEY_NOT_PROMPT_TRANSFER, $userId);
        return $redis->get($cacheKey) ? false : true;
    }

    /**
     * 获取充值记录
     * @param int $userId
     * @param int $ctime after time
     * @return mix
     */
    public function getChargeLogs($userId, $ctime = 0, $count = 0, $offset = 0) {
        $svChargeLogs = new SupervisionChargeModel();
        $result = $svChargeLogs->getChargeLogs($userId, $ctime, $count, $offset);
        if (!empty($result)) {
            return $result;
        }
        return false;
    }

    /*
     * 代扣-存管自动扣款充值接口
     * @param int $orderId 外部流水号
     * @param int $userId 用户ID
     * @param int $amount 充值金额，单位分
     * @param string $expireTime 订单超时时间,格式YYYYMMDDhhmmss
     * @return array
     * 1.仅支持个人借款方、混合角色进行自动充值；企业用户直接受理失败；失败原因：企业用户暂不支持自动充值
     * 2.同一用户单日单卡自动充值会有余额不足失败次数限制，默认2次/天（受限于支付通道，次数可能会变化）
     * 3.自动充值支持银行列表、限额与手机端充值相同（即支持绑定银行账户）
     */
    public function autoRecharge($orderId, $userId, $amount, $expireTime = '') {
        $startTrans = false;
        try {
            if (empty($orderId) || empty($userId) || empty($amount)) {
                throw new WXException('ERR_PARAM');
            }

            // 订单超时时间，默认当天15点
            if (empty($expireTime)) {
                $expireTime = date('Ymd150000');
            }
            $params = ['orderId'=>$orderId, 'userId'=>(int)$userId, 'amount'=>(int)$amount, 'expireTime'=>addslashes($expireTime),'noticeUrl'=> app_conf('NOTIFY_DOMAIN') .'/supervision/chargeNotify'];
            // 检查充值订单是否已存在 并且状态是成功
            $chargeInfo = SupervisionChargeModel::instance()->getChargeRecordByOutId($orderId);
            if (isset($chargeInfo['status']) && $chargeInfo['status'] == SupervisionEnum::CHARGE_SUCCESS) {
                return $this->responseSuccess();
            }

            // 支持业务重试, 重试不创建订单
            if (empty($chargeInfo)) {
                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                $startTrans = true;

                // 创建充值记录单
                if (!SupervisionChargeModel::instance()->createOrder($userId, $amount, $orderId, PaymentEnum::PLATFORM_SUPERVISION_AUTORECHARGE)) {
                    throw new WXException('ERR_CREATE_CHARGE_FAILED');
                }
                // 上报ITIL
                Monitor::add('sv_autorecharge_apply');
                // 异步添加存管订单
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_AUTORECHARGE, $params);
                // 提交事务
                $db->commit();
                $startTrans = false;
            }

            // 设置请求存管超时时间
            $result = $this->api->request('autoRecharge', $params);
            // 订单处理中
            if (isset($result['respSubCode']) && $result['respSubCode'] === '000001') {
                return $this->responseProsessing();
            }
            // 如果返回订单重复,查询
            if (isset($result['respSubCode']) && $result['respSubCode'] == SupervisionEnum::CODE_ORDER_EXIST) {
                $result = $this->orderSearch($orderId);
                if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                    throw new \WXException('ERR_AUTOCHARGE_FAILED');
                }
                $result = $result['data'];
                // 没响应或者充值失败,返回失败
                if (!isset($result['status']) || (isset($result['status']) && $result['status'] == SupervisionEnum::CHARGE_FAILURE)) {
                    throw new WXException('ERR_AUTOCHARGE_FAILED');
                }
                if (isset($result['status']) && $result['status'] == SupervisionEnum::CHARGE_PENDING) {
                    return $this->responseProsessing();
                }
                if (isset($result['status']) && $result['status'] == SupervisionEnum::CHARGE_SUCCESS) {
                    return $this->responseSuccess($result);
                }
            }

            // 返回超时
            if (!isset($result['respCode'])) {
                $wxException = new WXException('ERR_AUTOCHARGE_TIMEOUT');
                $msg = empty($result['respMsg']) ? '交易超时,请稍后再试' : $result['respMsg'];
                throw new \Exception($msg, $wxException->getCode());
            }

            // 存管服务器繁忙，请稍后再试
            if ($result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['respSubCode'] == SupervisionEnum::CODE_SV_SERVER_BUSY) {
                $wxException = new WXException('ERR_SV_SERVER_BUSY');
                $msg = empty($result['respMsg']) ? '存管服务器繁忙，请稍后再试' : $result['respMsg'];
                throw new \Exception($msg, $wxException->getCode());
            }

            // 返回失败
            if ($result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                $wxException = new WXException('ERR_AUTOCHARGE_FAILED');
                $msg = empty($result['respMsg']) ? '交易失败,请稍后再试' : $result['respMsg'];
                throw new \Exception($msg, $wxException->getCode());
            }

            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            $startTrans && $db->rollback();
            PaymentApi::log('Supervision autoRecharge Exception, code:'.$e->getCode().' message:'.$e->getMessage());
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /*
     * 自动划转至网信账户
     * @param int $userId 用户id
     * @param int $money 使用金额
     */
    public function autoTransferToSuper($userId, $money, $orderId) {
        $user = UserModel::instance()->find($userId);
        $userService = new UserService();
        $moneyInfo = $userService->getMoneyInfo($user, 0);
        $balance = bcadd($moneyInfo['lc'], $moneyInfo['bank'], 2);
        //检查余额
        if (bccomp($balance, $money, 2) == -1) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP,sprintf("用户余额不足，userId:%s,balance:%s,money:%s", $userId, $balance, $money))));
            return false;
        }

        //自动划转
        if (bccomp($moneyInfo['lc'], $money, 2) === -1 && bccomp($moneyInfo['bank'], 0 , 2) === 1) {
            $transferAmount = (int) bcmul(bcsub($money, $moneyInfo['lc'], 2), 100);//划转金额 分
            $data = [
                'orderId' => $orderId,
                'userId' => $userId,
                'amount' => $transferAmount,
                'superUserId' => $userId,
                'currency' => 'CNY',
            ];
            try{
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP,sprintf("开始划转资金到超级账户，userId:%s,transferMoney:%s", $userId, $transferAmount))));
                $res = $this->accountSuperWithdraw($data);
            }catch (\Exception $e){
                $res = ['status'=>SupervisionEnum::RESPONSE_FAILURE];
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP,'支付提现到超级账户异常 orderId:'.$orderId . " errMsg:".$e->getMessage())));
            }
            if ($res['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
                return false;
            }
        }
        return true;
    }

    /**
     * 批量转账接口
     * @param array $params 业务参数列表
     * 必传参数:
     *      subOrderList:子单集合 json
     *      amount:金额
     *      bizType:业务类型 8001返利 8003红包 1902资金迁移
     *      payUserId:出款方
     *      receiveUserId:收款方
     *      subOrderId:子订单id
     */
    public function batchTransfer($params) {
        if (empty($params['orderId'])) {
            $params['orderId'] = Idworker::instance()->getId(); //生成交易流水号
        }
        try {
            // 异步添加存管订单
            //$supervisionOrderService = new SupervisionOrderService();
            //$supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_BATCH_TRANSFER, $params);
            // 请求接口
            $params['noticeUrl'] = app_conf('NOTIFY_DOMAIN') .'/supervision/batchTransferNotify';
            $result = $this->api->request('batchTransfer', $params);
            // 存管接口返回[商户流水已存在]正常处理
            if (!isset($result['respCode']) || ($result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== SupervisionEnum::CODE_ORDER_EXIST)) {
                throw new WXException('ERR_BATCH_TRANSFER');
            }
            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 批量转账-回调逻辑
     * @param array $responseData 存管回调的参数数组
     * 必传参数：
     *     orderId:外部订单号
     *     status:订单处理状态 S-成功
     *     remark:备注
     */
    public function batchTransferNotify($responseData) {
        try {
            if (empty($responseData['orderId']) || empty($responseData['status']) || empty($responseData['amount'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $p2pDealRepayService = new P2pDealRepayService();
            $p2pDealRepayService->batchTransferCallBack($responseData['orderId'], $responseData['status']);
            return $this->responseSuccess();
        } catch(\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($responseData)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 大额充值-转账充值订单信息查询接口
     * 1.网贷平台通过个人用户ID，起始日期，订单状态等条件调用该服务进行查询
     * 2.存管系统返回订单信息列表
     * 3.只能查询近一个月数据
     * 4.返回银行卡信息为掩码
     * 5.每页默认30条
     * @param array $params 业务参数列表
     * 参数:
     *      userId:用户ID
     *      startDate:起始日期，精确到天
     *      endDate:终止日期，精确到天
     *      status:订单状态(全部:A|处理中:I|成功:S|失败（订单关闭）:F)
     *      bankCardNo:银行卡号(非必填)
     *      page:页码
     */
    public function offlineRechargeSearch($params) {
        try {
            $result = $this->api->request('offlineRechargeSearch', $params);
            // 存管接口返回[商户流水已存在]正常处理
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_BATCH_SEARCH');
            }
            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 大额充值-专户资金流水查询接口
     * 1.网贷平台通过该接口查询近期专户入款资金流水明细
     * 2.存管系统返回资金流水明细
     * 3.只能查询近一个月数据
     * 4.返回银行卡信息做掩码处理
     * @param array $params 业务参数列表
     * 参数:
     *      bankCardNo:对手方账户
     *      startDate:起始日期，精确到天
     *      endDate:终止日期，精确到天
     *      status:订单状态(A-全部|I-未匹配|S-匹配成功|RS-退款)
     *      page:页码
     */
    public function coreAccountLogSearch($params) {
        try {
            $result = $this->api->request('coreAccountLogSearch', $params);
            // 存管接口返回[商户流水已存在]正常处理
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_BATCH_SEARCH');
            }
            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 多卡代扣充值
     * @param array $params
     *      integer $orderId 订单号
     *      integer $userId 用户id
     *      integer $amount 代扣金额
     *      string $bankCardNo 银行卡号
     *      string $realName 真实姓名
     *      string $certNo 证件号码
     *      string $expireTime 订单超时时间,格式YYYYMMDDhhmmss
     */
    public function multicardRecharge($params) {
        $startTrans = false;
        try {
            // 订单超时时间，默认当天15点
            if (empty($params['expireTime'])) {
                $params['expireTime'] = date('Ymd170000');
            }

            // 检查充值订单是否已存在 并且状态是成功
            $chargeInfo = SupervisionChargeModel::instance()->getChargeRecordByOutId($params['orderId']);
            if (isset($chargeInfo['status']) && $chargeInfo['status'] == SupervisionEnum::CHARGE_SUCCESS) {
                return $this->responseSuccess();
            }

            // 支持业务重试, 重试不创建订单
            if (empty($chargeInfo)) {
                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                $startTrans = true;

                // 创建充值记录单
                if (!SupervisionChargeModel::instance()->createOrder($params['userId'], $params['amount'], $params['orderId'], PaymentEnum::PLATFORM_SUPERVISION_AUTORECHARGE)) {
                    throw new WXException('ERR_CREATE_CHARGE_FAILED');
                }
                // 上报ITIL
                Monitor::add('sv_multicard_recharge_apply');
                // 异步添加存管订单
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_AUTORECHARGE, $params);
                // 提交事务
                $db->commit();
                $startTrans = false;
            }

            // 设置请求存管超时时间
            $result = $this->api->request('multiCardRecharge', $params);
            // 订单处理中
            if (isset($result['respSubCode']) && $result['respSubCode'] === '000001') {
                return $this->responseProsessing();
            }
            // 如果返回订单重复,查询
            if (isset($result['respSubCode']) && $result['respSubCode'] == SupervisionEnum::CODE_ORDER_EXIST) {
                $result = $this->orderSearch($params['orderId']);
                if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                    throw new WXException('ERR_AUTOCHARGE_FAILED');
                }
                $result = $result['data'];
                // 没响应或者充值失败,返回失败
                if (!isset($result['status']) || (isset($result['status']) && $result['status'] == SupervisionEnum::CHARGE_FAILURE)) {
                    throw new WXException('ERR_AUTOCHARGE_FAILED');
                }
                if (isset($result['status']) && $result['status'] == SupervisionEnum::CHARGE_PENDING) {
                    return $this->responseProsessing();
                }
                if (isset($result['status']) && $result['status'] == SupervisionEnum::CHARGE_SUCCESS) {
                    return $this->responseSuccess($result);
                }
            }

            // 返回超时
            if (!isset($result['respCode'])) {
                $wxException = new WXException('ERR_AUTOCHARGE_TIMEOUT');
                $msg = empty($result['respMsg']) ? '交易超时,请稍后再试' : $result['respMsg'];
                throw new \Exception($msg, $wxException->getCode());
            }

            // 存管服务器繁忙，请稍后再试
            if ($result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['respSubCode'] == SupervisionEnum::CODE_SV_SERVER_BUSY) {
                $wxException = new WXException('ERR_SV_SERVER_BUSY');
                $msg = empty($result['respMsg']) ? '存管服务器繁忙，请稍后再试' : $result['respMsg'];
                throw new \Exception($msg, $wxException->getCode());
            }

            // 返回失败
            if (($result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS )) {
                $wxException = new WXException('ERR_AUTOCHARGE_FAILED');
                $msg = empty($result['respMsg']) ? '交易失败,请稍后再试' : $result['respMsg'];
                throw new \Exception($msg, $wxException->getCode());
            }
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            $startTrans && $db->rollback();
            PaymentApi::log('Supervision autoRecharge Exception, code:'.$e->getCode().' message:'.$e->getMessage());
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取网贷大额充值开关
     * 0:不显示1:显示
     * @return boolean
     */
    public static function isOfflineChargeOpen()
    {
        if((int)app_conf('P2P_OFFLINECHARGE_SWITCH') === 1) {
            return true;
        }
        return false;
    }

    /**
     * 获取网贷大额充值显示
     * @return string
     */
    public static function getOfflineChargeName()
    {
        $p2pOfflineChargeName = app_conf('P2P_OFFLINECHARGE_NAME');
        if(!empty($p2pOfflineChargeName)) {
            return $p2pOfflineChargeName;
        }
        return str_replace('余额', '', SupervisionTransferModel::SUPERVISION_NAME);
    }

    /**
     * 获取大额充值地址
     * 二期需要根据黑名单来控制是否显示大额充值入口
     * @param int $userId 用户ID
     * @param array $bankcardInfo 用户绑卡信息
     */
    public function getOfflineChargeApiUrl($userId, $bankcardInfo = array()) {
        if (self::isOfflineChargeOpen()) {
            return get_http() . get_host() . '/account/p2pOfflineCharge';
        } else {
            return '';
        }
    }

    /**
     * 检查用户绑定的银行卡是否在大额充值受限名单里
     * @param array $bankcardInfo 用户绑定的银行卡信息
     * 配置格式：HXB&ICBC
     */
    public static function isRestrictOffline($bankcardInfo) {
        if (empty($bankcardInfo) || empty($bankcardInfo['bank_id'])) {
            return true;
        }

        // 获取配置
        $restrictConf = app_conf('P2P_OFFLINECHARGE_RESTRICT');
        if (empty($restrictConf)) {
            return true;
        }
        $p2pOfflineRestrict = [];
        $restrictConf = strtoupper(preg_replace('/\s+/', '', $restrictConf));
        parse_str($restrictConf, $p2pOfflineRestrict);
        if (empty($p2pOfflineRestrict)) {
            return true;
        }

        // 获取该银行的基本信息
        $bankInfo = BankService::getBankInfoByBankId($bankcardInfo['bank_id']);
        if (empty($bankInfo) || empty($bankInfo['short_name'])) {
            return true;
        }

        // 配置了该银行简码，则不显示大额充值入口
        return isset($p2pOfflineRestrict[$bankInfo['short_name']]) ? false : true;
    }

    /**
     * 大额充值接口
     * @param array $params 业务参数列表
     * @param string $platform 请求方式
     * @param string $formId 返回表单id和name
     * @param boolean $targetNew 是否新窗口打开表单
     * @return array
     */
    public function offlineCharge($params, $platform = 'pc', $formId = 'SvOfficeChargeForm', $targetNew = false) {
        $startTrans = false;
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }

            // 检查充值订单是否已存在 并且状态是成功
            $chargeInfo = SupervisionChargeModel::instance()->getChargeRecordByOutId($params['orderId']);
            // 支持业务重试, 重试不创建订单
            if (empty($chargeInfo)) {
                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                $startTrans = true;

                $supervisionCharge = SupervisionChargeModel::instance();
                if (!$supervisionCharge->createOrder($params['userId'], $params['amount'], $params['orderId'], PaymentEnum::PLATFORM_OFFLINE_V2)) {
                    throw new WXException('ERR_CREATE_CHARGE_FAILED');
                }

                // 上报ITIL
                Monitor::add('sv_payment_apply');
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_RECHARGE, $params);
                $db->commit();
                $startTrans = false;
            }

            $service = $platform === 'pc' ? 'webOfflineCharge' : 'h5OfflineCharge';
            $form = $this->api->getForm($service, $params, $formId, $targetNew);
            $data = [
                'form' => $form,
                'formId' => $formId,
            ];

            //生产用户访问日志
            $extraInfo = [
                'orderId'       => $params['orderId'],
                'chargeAmount'  => (int) $params['amount'],
                'chargeTime'    => time(),
                'chargeChannel' => UserAccessLogEnum::CHARGE_CHANNEL_OFFLINE,
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_CHARGE, sprintf('网贷充值申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT);


            return $this->responseSuccess($data);
        } catch(\Exception $e) {
            $startTrans && $db->rollback();
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 企业用户充值接口
     * 支持绑定海口联合行企业账户用户在手机端进行充值操作
     * @param array $params 业务参数列表
     * @param string $platform 请求方式
     * @param string $formId 返回表单id和name
     * @param boolean $targetNew 是否新窗口打开表单
     * @return array
     */
    public function enterpriseCharge($params, $platform = 'h5', $formId = 'SvEnterpriseChargeForm', $targetNew = false) {
        $startTrans = false;
        try {
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM');
            }

            // 检查充值订单是否已存在 并且状态是成功
            $chargeInfo = SupervisionChargeModel::instance()->getChargeRecordByOutId($params['orderId']);
            // 支持业务重试, 重试不创建订单
            if (empty($chargeInfo)) {
                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                $startTrans = true;

                $supervisionCharge = SupervisionChargeModel::instance();
                if (!$supervisionCharge->createOrder($params['userId'], $params['amount'], $params['orderId'], PaymentEnum::PLATFORM_ENTERPRISE_H5CHARGE)) {
                    throw new WXException('ERR_CREATE_CHARGE_FAILED');
                }

                // 上报ITIL
                Monitor::add('sv_payment_apply');
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_RECHARGE, $params);
                $db->commit();
                $startTrans = false;
            }

            $service = $platform === 'pc' ? 'h5EnterpriseCharge' : 'h5EnterpriseCharge';
            $form = $this->api->getForm($service, $params, $formId, $targetNew);
            $data = [
                'form' => $form,
                'formId' => $formId,
            ];
            return $this->responseSuccess($data);
        } catch(\Exception $e) {
            $startTrans && $db->rollback();
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 判断用户是否可以使用快速到账提现
     * 投资户 历史没有投资， 提现发生在充值24小时之后
     *
     * @param integer $accountId 用户账户id
     * @param integer $amount 提现金额 默认是0
     * @return boolean true 可以， false 不可以
     */
    public function canFastWithdraw($accountId, $amount = 0)
    {
        //火眼检查
        $accountInfo = AccountService::getAccountInfoById($accountId);
        $userInfo = UserService::getUserById($accountInfo['user_id'], 'id, user_name,user_purpose, group_id');
        $extraData = [
            'user_id' => $accountInfo['user_id'],
            'user_name' => $userInfo['user_name'],
            'group_id' => $userInfo['group_id'],
            'amount' => $amount,
            'platform' => UserAccountEnum::PLATFORM_SUPERVISION,
            'account_type' => $userInfo['user_purpose'],
        ];
        $checkRet = RiskService::check('WITHDRAW', $extraData);
        if (false === $checkRet) {
            PaymentApi::log(sprintf('%s | %s, userId: %s, amount: %s, %s', __CLASS__, __FUNCTION__, $accountInfo['user_id'], $amount, 'risk check false'));
            return false;
        }

        // 注册时间在2018-09-01之前的用户 允许快速到账
        if ($accountId < 11743842)
        {
            return true;
        }
        // 非投资户，允许快速到账
        if($accountInfo['account_type'] != UserAccountEnum::ACCOUNT_INVESTMENT)
        {
            return true;
        }

        // 有绑定的邀请人或者服务人邀请码， 允许快速到账
        $couponInfo = CouponService::getByUserId($accountId);
        if (!empty($couponInfo['refer_user_id']) || !empty($couponInfo['invite_user_id']))
        {
            return true;
        }

        // 首次充值 24小时内提现
        $withdrawTime = time();
        $chargeModel = new SupervisionChargeModel();
        $chargeRecord = $chargeModel->getEarlyChargeInfo($accountId);
        // 没有充值记录或者充值记录发生在提现前24小时以外
        if (empty($chargeRecord['update_time']) || $withdrawTime - $chargeRecord['update_time'] > 86400)
        {
            return true;
        }

        // 发送消息
        $params = [];
        $params['accountId'] = $accountId;
        $params['money'] = $accountInfo['money'];
        $params['lock_money'] = $accountInfo['lock_money'];
        $params['refer_user_id'] = $couponInfo['refer_user_id'];
        $params['invite_user_id'] = $couponInfo['invite_user_id'];
        $params['update_time'] = $chargeRecord['update_time'];
        $params['amount'] = $chargeRecord['amount'];
        MsgbusService::produce(MsgbusEnum::TOPIC_SUPERVISION_WITHDRAW_DELAY_MSG, $params);

        // 不允许走快速提现到账
        return false;
    }

    /**
     * 获取普惠的新协议限额开关
     * @return int
     */
    public static function isNewBankLimitOpen() {
        return (int)app_conf('APP_USE_H5_CHARGE');
    }

    /**
     * 获取普惠的默认充值限额，单位分
     * @return int
     */
    public static function getDefaultBankLimit() {
        return (int)app_conf('APP_USE_H5_CHARGE_DEFAULT');
    }

    /**
     * 银行卡限额订阅接口
     * @param string $noticeUrl 通知地址
     * @param int $serviceType 是否订阅(0:订阅 1:取消订阅)
     * @return array
     */
    public function bankLimitSubscription($noticeUrl, $serviceType = 0) {
        try {
            if (empty($noticeUrl)) {
                throw new WXException('ERR_PARAM_LOSE');
            }

            // 生成订单号
            $outOrderId = Idworker::instance()->getId();
            // 订阅通知地址
            $chargeLimitConfig = $this->api->getGatewayApi()->getServices()->get('chargeLimitSubscription');
            $noticeUrl = !empty($chargeLimitConfig['noticeUrl']) ? app_conf('NOTIFY_DOMAIN') . $chargeLimitConfig['noticeUrl'] : '';

            $params = ['outOrderId'=>$outOrderId, 'noticeUrl'=>$noticeUrl, 'serviceType'=>(int)$serviceType];
            $result = $this->api->request('chargeLimitSubscription', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new \Exception($result['respMsg'], $result['respSubCode']);
            }
            $result['outOrderId'] = $outOrderId;
            return $this->responseSuccess($result);
        } catch(\Exception $e) {
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 银行卡限额订阅回调通知
     * @param array $response 回调数组
     * @return array
     */
    public function bankLimitSubscriptionNotify($response) {
        try{
            PaymentApi::log(sprintf('%s | %s, params:%s', __CLASS__, __FUNCTION__, json_encode($response)));
            $outOrderId = isset($response['outOrderId']) ? trim($response['outOrderId']) : '';
            // 订阅类型
            $type = isset($response['type']) ? trim($response['type']) : '';
            // 支付通道
            $payChannel = isset($response['payChannel']) ? trim($response['payChannel']) : '';
            // 银行卡限额列表
            $bankJson = isset($response['bankInfo']) ? $response['bankInfo'] : [];
            // 银行卡限额列表转成数组
            $bankInfo = json_decode($bankJson, true);
            if (empty($outOrderId) || empty($type) || empty($payChannel) || empty($bankInfo)) {
                throw new WXException('ERR_PARAM');
            }

            $exists = [];
            // 遍历银行卡限额列表
            $db = Db::getInstance('firstp2p');
            $db->startTrans();
            foreach ($bankInfo as $bankItem) {
                // 缺少银行简码字段
                if (empty($bankItem['bankCode'])) {
                    continue;
                }
                // 单笔最大限额、日最大限额如果是未知或无限额的话，不处理
                if ((int)$bankItem['maximumQuota'] < 0 && (int)$bankItem['dayQuota'] < 0 && (int)$bankItem['monthQuota'] < 0) {
                    PaymentApi::log(sprintf('%s | %s, bankCode:%s, bankName:%s, maximumQuota_dayQuota_monthQuota_Empty', __CLASS__, __FUNCTION__, $bankItem['bankCode'], $bankItem['bankName']));
                    continue;
                }

                if (isset($exists[$bankItem['bankCode']])) {
                    continue;
                }
                $exists[$bankItem['bankCode']] = 1;

                // 查询该银行编号的限额数据是否已存在
                $bankLimitObj = new BankLimitModel();
                $bankLimitInfo = $bankLimitObj->getLimitByChannelCode($payChannel, $bankItem['bankCode']);
                if (empty($bankLimitInfo)) {
                    $limitRet = $bankLimitObj->createLimit($payChannel, $bankItem['bankCode'], $type, $bankItem);
                }else{
                    $limitRet = $bankLimitObj->updateLimitByChannelCode($payChannel, $bankItem['bankCode'], $bankItem);
                }
                if (!$limitRet) {
                    throw new WXException('ERR_RETURN_FAILURE');
                }
            }
            $db->commit();
            return $this->responseSuccess();
        } catch (\Exception $e) {
            isset($db) && $db->rollback();
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('存管银行卡限额回调异常|存管回调参数:%s，异常内容:%s', json_encode($response), $e->getMessage()));
            return $this->responseFailure($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 用户提现被延时的通知
     * @param $params array
     *      integer accountId 用户账户id
     *      integer money 用户可用余额
     *      integer lock_money 用户冻结余额
     *      integer refer_user_id 服务人id
     *      integer invite_user_id 邀请人id
     *      integer update_time 首次充值时间
     *      integer amount 首次充值金额
     */
    public static function LimitNotice($params)
    {
        $accountId = $params['accountId'];
        // 被拦截用户进入微信报警通知
        // 读取用户预留手机号
        $accountService = new SupervisionAccountService();
        $memberCardInfo = $accountService->memberCardSearch($accountId);
        $bankCardPhone = '';
        if (isset($memberCardInfo['status']) && $memberCardInfo['status'] == 'S') {
            $bankCards = $memberCardInfo['data'];
            $bankcard = !empty($bankCards[0]) && is_array($bankCards[0]) ? $bankCards[0] : null;
            $bankCardPhone = $bankcard['bankCardPhone'];
        }
        // 格式化数据
        $params['money'] = bcdiv($params['money'], 100, 2);
        $params['lock_money'] = bcdiv($params['lock_money'], 100, 2);
        $params['refer_user_id'] = !empty($params['refer_user_id']) ? $params['refer_user_id'] : 'N/A';
        $params['invite_user_id'] = !empty($params['invite_user_id']) ? $params['refer_user_id'] : 'N/A';
        $firstChargeTime = date('Y-m-d H:i:s', $params['update_time']);
        $chargeAmount = bcdiv($params['amount'], 100, 2);

        $content = "用户ID:{$accountId}提现因命中规则被延迟,银行卡预留手机号:{$bankCardPhone}, 网贷可用金额{$params['money']}元, 网贷冻结金额:{$params['lock_money']}元, 服务人用户ID:{$params['refer_user_id']}, 邀请人用户ID:{$params['invite_user_id']}, 首次充值时间:{$firstChargeTime}, 首次充值金额:{$chargeAmount}元";
        $mailAddress = 'wangqunqiang@ucfgroup.com,zhangruoshi@ucfgroup.com,menjie@ucfgroup.com,chenming3@ucfgroup.com,yuanyanyan@ucfgroup.com,zhouxinxin@ucfgroup.com,wangxu1@ucfgroup.com,lianjing@ucfgroup.com,miaozongpei@ucfgroup.com';
        // 邮件群发
        $mailAddressArray = explode(',', $mailAddress);
        $mail = new Mail();
        // 发件人邮箱
        $fromMailer = 'noreply@unitedbank.cn';
        // 发件人姓名
        $fromMailerName = '海口联合农商银行';
        $mail->setFrom($fromMailer, $fromMailerName);
        $ret = $mail->send("网信普惠提现策略命中 - {$accountId}", $content, $mailAddressArray);

        // 微信群发
        $wxRecieverNames = 'wangqunqiang|zhangruoshi|liangqiang|yanbingrong';
        $bankCardPhone = formatBankcard($bankCardPhone);
        $content = "用户ID:{$accountId}提现因命中规则被延迟,银行卡预留手机号:{$bankCardPhone}, 网贷可用金额{$params['money']}元, 网贷冻结金额:{$params['lock_money']}元, 服务人用户ID:{$params['refer_user_id']}, 邀请人用户ID:{$params['invite_user_id']}, 首次充值时间:{$firstChargeTime}, 首次充值金额:{$chargeAmount}元";
        // 微信推送地址
        $wxPushUrl = 'http://itil.firstp2p.com/api/weixin/sendText?to=%s&content=%s&sms=0&appId=payment';
        $result = file_get_contents(sprintf($wxPushUrl, $wxRecieverNames, urlencode($content)));
    }

    /**
     * 用户签约银行卡信息查询
     * 1.网贷平台通过个人用户ID，请求查询绑定的银行卡信息
     * 2.存管系统返回银行卡信息、绑卡状态
     * 3.企业用户申请阶段，审核中或审核不通过，则查询结果均为用户不存在
     * 4.港澳台用户申请阶段，审核中或审核不通过，则查询结果均为用户不存在
     * 5.迁移的用户未完成绑卡的，查询结果用户银行卡信息为空
     * @param array $params 业务参数列表
     * 参数:
     *      'userId' => '用户ID',
     */
    public function memberCardSearch($params)
    {
        try {
            if (empty($params['userId'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $result = $this->api->request('memberCardSearch', $params);
            if(!isset($result['userId']) || !isset($result['cardId']) || !isset($result['cardNo']) || !isset($result['cardType']) || !isset($result['bankCode']) || !isset($result['bankName']) || !isset($result['branchBankId']) || !isset($result['applyStatus'])) {
                throw new WXException('ERR_MEMBER_CARD_SEARCH');
            }
            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', '
. json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 非绑定银行卡签约申请
     * 1.网贷平台收集用户请求
     * 2.发起签约申请
     * 3.接收签约申请结果
     * 4.成功后可发起确认签约
     * 5.实名信息需与userid一致
     * 6.仅支持个人身份证用户
     * @param array $params 业务参数列表
     * 参数:
     *      'userId' => '用户ID',
     *      'orderId' => '签约申请订单号',
            'realName' => '姓名',
            'certNo' => '身份证号',
            'bankCardNo' => '银行卡号',
            'mobile' => '银行预留手机号',
     */
    public function noBindCardSign($params)
    {
        $params['certType'] = empty($params['certType']) ? 'IDC' : $params['certType'];
        try {
            if (empty($params['userId']) || empty($params['orderId']) || empty($params['realName']) || empty($params['certNo']) || empty($params['bankCardNo']) || empty($params['mobile'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $result = $this->api->request('noBindCardSign', $params);
            if(!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                $errMsg = !empty($result['respMsg']) ? $result['respMsg'] : ErrCode::getMsg('ERR_BANK_CARD_SIGN_APPLY');
                $errCode = !empty($result['respSubCode']) ? $result['respSubCode'] : ErrCode::getCode('ERR_BANK_CARD_SIGN_APPLY');
                throw new \Exception($errMsg, $errCode);
            }
            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', '
. json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 签约重发短信
     * 1.网贷平台为用户提供签约重发短信服务，并进行收集
     * 2.用户重复发送后，可接收新短信验证码
     * 3.网贷平台可再次调用确认签约服务完成签约
     * @param array $params 业务参数列表
     * 参数:
     *      'orderId' => '签约申请订单号',
     */
    public function signResendMessage($params)
    {
        try {
            if (empty($params['orderId'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $result = $this->api->request('signResendMessage', $params);
            if(!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_RESEND_MESSAGE');
            }
            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', '
. json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 签约确认
     * 1.网贷平台收集用户接收到短信验证码后
     * 2.调用本接口，进行签约确认后，完成签约
     * @param array $params 业务参数列表
     * 参数:
     *      'orderId' => '签约申请订单号',
     *      'smsCode' => '短信验证码',
     */
    public function signConfirm($params)
    {
        try {
            if (empty($params['orderId']) || empty($params['smsCode'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $result = $this->api->request('signConfirm', $params);
            if(!isset($result['respCode']) || $result['respCode'] !== SupervisionEnum::RESPONSE_CODE_SUCCESS) {
                $errMsg = !empty($result['respMsg']) ? $result['respMsg'] : ErrCode::getCode('ERR_RESPONSE_STATUS');
                $errCode = !empty($result['respSubCode']) ? $result['respSubCode'] : ErrCode::getCode('ERR_RESPONSE_STATUS');
                throw new \Exception($errMsg, $errCode);
            }
            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', '
. json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 非绑定银行卡信息签约查询
     * 1.网贷平台通过个人四要素信息，请求查询绑定的银行卡信息
     * 2.存管系统返回银行卡签约信息
     * 3.本期只支持个人身份证用户
     * @param array $params 业务参数列表
     * 参数:
     *      'bankCardNo' => '银行卡号',
            'realName' => '真实姓名',
            'certNo' => '用户证件号',
            'mobileNo' => '银行预留手机号',
            'contractChannelId' => '签约渠道',
     */
    public function notBindSignQuery($params)
    {
        try {
            if (empty($params['bankCardNo']) || empty($params['realName']) || empty($params['certNo']) || empty($params['mobileNo']) || empty($params['contractChannelId'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            $result = $this->api->request('notBindSignQuery', $params);
            if(!isset($result['bankCardNo']) || !isset($result['contractResult']) || !isset($result['certNo']) || !isset($result['mobileNo'])) {
                throw new WXException('ERR_MEMBER_CARD_SEARCH');
            }
            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', '
. json_encode($params)));
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

}
