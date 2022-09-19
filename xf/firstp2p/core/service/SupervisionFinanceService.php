<?php
namespace core\service;

use libs\utils\PaymentApi;
use NCFGroup\Common\Library\Idworker;
use libs\db\Db;
use libs\common\WXException;
use core\service\SupervisionBaseService AS SupervisionBase; // 存管资金相关服务
use libs\common\ErrCode;
use core\dao\PaymentNoticeModel;
use core\service\UserCarryService;
use core\service\AccountLimitService;
use core\dao\UserModel;
use core\dao\UserBankcardModel;
use core\service\UserService;
use core\service\BankService;
use core\service\SupervisionAccountService; // 存管账户相关服务
use core\service\SupervisionOrderService; // 存管账户相关服务
use core\dao\SupervisionTransferModel;
use core\dao\SupervisionWithdrawModel;
use core\dao\SupervisionChargeModel;
use libs\utils\Alarm;
use libs\utils\Monitor;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use core\service\ncfph\SupervisionService AS PhSupervisionService;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

/**
 * P2P存管 - 资金相关服务
 */
class SupervisionFinanceService extends SupervisionBase {

    const CHARGE_PENDING = 'I';
    const CHARGE_FAILURE = 'F';
    const CHARGE_SUCCESS = 'S';

    const WITHDRAW_PROCESSING = 'AS';
    const WITHDRAW_FAILURE = 'F';
    const WITHDRAW_SUCCESS = 'S';

    const BATCHORDER_TYPE_GRANT = '5000'; // 放款
    const BATCHORDER_TYPE_REPAY = '7000'; // 还款
    const BATCHORDER_TYPE_DEALCANCEL = '3100'; // 流标
    const BATCHORDER_TYPE_BENIFIT = '8000'; // 返利红包收费

    // 批量转账接口业务类型
    const BATCHTRANSFER_BENIFIT = 8001; // 返利
    const BATCHTRANSFER_CHARGE  = 8002; // 收费

    const BATCHTRANSFER_PROCESS = 'I';
    const BATCHTRANSFER_SUCCESS = 'S';
    const BATCHTRANSFER_FAILURE = 'F';
    const BATCHTRANSFER_CANCEL  = 'C';

    const BATCHTRANSFER_STATUS_INIT = 0;
    const BATCHTRANSFER_STATUS_PROCESS = 1;
    const BATCHTRANSFER_STATUS_SUCCESS = 2;
    const BATCHTRANSFER_STATUS_FAILURE = 3;
    const BATCHTRANSFER_STATUS_CANCEL  = 4;

    static $batchTransferStatusMap = [
        self::BATCHTRANSFER_STATUS_INIT     => '未处理',
        self::BATCHTRANSFER_STATUS_PROCESS  => '处理中',
        self::BATCHTRANSFER_STATUS_SUCCESS  => '成功',
        self::BATCHTRANSFER_STATUS_FAILURE  => '失败',
        self::BATCHTRANSFER_STATUS_CANCEL   => '取消',
    ];


    static $batchTransferMap = [
        self::BATCHTRANSFER_PROCESS => self::BATCHTRANSFER_STATUS_PROCESS,
        self::BATCHTRANSFER_SUCCESS => self::BATCHTRANSFER_STATUS_SUCCESS,
        self::BATCHTRANSFER_FAILURE => self::BATCHTRANSFER_STATUS_FAILURE,
        self::BATCHTRANSFER_CANCEL  => self::BATCHTRANSFER_STATUS_CANCEL,
    ];

    // 订单号已存在
    const CODE_ORDER_EXIST = '200103';

    // 服务器繁忙，请稍后再试
    const CODE_SV_SERVER_BUSY = '500000';

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
            $service = $platform === 'pc' ? 'webCharge' : 'h5Charge';
            $supervisionApi = $this->api;
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/chargeNotify';

            // 创建普惠存管充值订单
            $chargePlatform = PaymentNoticeModel::PLATFORM_WEB;
            switch (strtolower($platform))
            {
                case 'ios':
                case 'android':
                    $chargePlatform = PaymentNoticeModel::PLATFORM_ANDROID;
                    break;
                case 'h5':
                    $chargePlatform = PaymentNoticeModel::PLATFORM_MOBILEWEB;
                    break;
                default:
                    $chargePlatform = PaymentNoticeModel::PLATFORM_WEB;
            }
            $ret = PhSupervisionService::chargeCreateOrder($params['userId'], $params['amount'], $params['orderId'], $chargePlatform);
            if ( ! $ret) {
                throw new WXException('ERR_CREATE_CHARGE_FAILED');
            }

            $db = Db::getInstance('firstp2p');
            $db->startTrans();
            // 上报ITIL
            \libs\utils\Monitor::add('sv_payment_apply');
            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_RECHARGE, $params);

            $form = $supervisionApi->getForm($service, $params, $formId, $targetNew);
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
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_CHARGE, sprintf('网贷充值申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT, UserAccessLogEnum::PLATFORM_P2P);


            return $this->responseSuccess($data);
        } catch(\Exception $e) {
            isset($db) && $db->rollback();
            return $this->responseFailure($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 充值回调处理
     */
    public function chargeNotify($response, $userLogType = '充值'){
        $paymentNoticeSn = isset($response['orderId']) ? trim($response['orderId']) : '';
        $orderStatus = isset($response['status']) ? trim($response['status']) : '';
        $orderStatus = SupervisionChargeModel::$statusMap[$orderStatus];
        $amount = isset($response['amount']) ? intval($response['amount']) : '';
        try{
            if (empty($paymentNoticeSn) || empty($orderStatus) || empty($amount)) {
                throw new WXException('ERR_PARAM');
            }

            // 获取普惠存管充值订单
            $chargeOrder = PhSupervisionService::chargeGetOrder($paymentNoticeSn);
            if (empty($chargeOrder)) {
                throw new WXException('ERR_CHARGE_ORDER_NOT_EXSIT');
            }

            //处理中的回调当成功处理
            if ($orderStatus == SupervisionChargeModel::PAY_STATUS_PROCESS || $orderStatus == $chargeOrder['pay_status']) {
                return $this->responseSuccess();
            }

            $db = Db::getInstance('firstp2p');
            $db->startTrans();

            if (bccomp($chargeOrder['amount'], $amount) !== 0) {
                throw new WXException('ERR_CHARGE_AMOUNT');
            }

            // 根据充值订单号，调用支付的订单查询接口，查询该笔订单的状态
            $orderCheckInfo = $this->orderSearch($paymentNoticeSn);
            if ($orderCheckInfo['status'] == SupervisionBaseService::RESPONSE_SUCCESS && isset($orderCheckInfo['data'])) {
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

            // 触发O2O请求
            $paymentService = new \core\service\PaymentService();
            $paymentService->chargeTriggerO2O($chargeOrder, 2);

            //异步更新存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncUpdateOrder($response['orderId'], $response['status']);

            // 业务来源-存管自动扣款充值代扣
            if ($chargeOrder['platform'] == PaymentNoticeModel::PLATFORM_SUPERVISION_AUTORECHARGE) {
                $p2pDealRepayService = new \core\service\P2pDealRepayService();
                $response['failReason'] = isset($response['failReason']) ? $response['failReason'] : $response['errMsg'];
                $dkRepayRet = $p2pDealRepayService->dealDkRepayCallBack($response['orderId'], $response['status'],$response['failReason']);
                if (!$dkRepayRet) {
                    throw new WXException('ERR_AUTOCHARGE_NOTIFY_FAILED');
                }
            }

            $db->commit();

            if ($orderStatus == SupervisionChargeModel::PAY_STATUS_SUCCESS) {
                $paymentService->setChargeStatusCache($chargeOrder['user_id'], $paymentNoticeSn);
            }

            // 业务来源-存管自动代扣充值，不需要发送短信
            if ($chargeOrder['platform'] != PaymentNoticeModel::PLATFORM_SUPERVISION_AUTORECHARGE) {
                //发送充值成功短信
                send_supervision_charge_msg($paymentNoticeSn);
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
    public function withdrawPrepare($service, $params, $checkPrivileges = false, $withdrawType = SupervisionWithdrawModel::TYPE_TO_BANKCARD, $limitId = 0) {
        // 如果是放款提现，则根据用户ID、标的ID，检查提现记录是否存在、状态是否终态
        if (!empty($params['bidId'])) {
            $orderInfo = PhSupervisionService::withdrawGetOrder($params['userId'], $params['bidId']);
            if (!empty($orderInfo)) {
                $this->isWithdrawFinalState = true;
                return $params;
            }
        }

        $supervisionApi = $this->api;
        $supervisionAccountService = new SupervisionAccountService();
        //检查用户权限
        //if ($checkPrivileges) {
            //if (!$supervisionAccountService->checkUserPrivileges($params['userId'], [self::GRANT_WITHDRAW])) {
            //    throw new WXException('ERR_HAVE_NO_PRIVILEGES');
            //}
        //}

        //检查用户是否开户
        $isSvUser = $supervisionAccountService->isSupervisionUser($params['userId']);
        if (!$isSvUser) {
            throw new WXException('ERR_NOT_OPEN_ACCOUNT');
        }

        //检查用户存管余额
        $balanceResult = $supervisionAccountService->balanceSearch($params['userId']);
        if (empty($balanceResult) || $balanceResult['status'] != self::RESPONSE_SUCCESS || $balanceResult['respCode'] != self::RESPONSE_CODE_SUCCESS) {
            throw new WXException('ERR_BALANCE_SEARCH');
        }
        if ($params['amount'] > $balanceResult['data']['availableBalance']) {
            throw new WXException('ERR_BALANCE_NOT_ENOUGHT');
        }

        $bankcard_info = UserBankcardModel::instance()->getByUserId($params['userId']);
        if (!$bankcard_info || $bankcard_info['status'] != 1) {
            throw new WXException('ERR_NOT_BANDCARD');
        }

        try{
            $db = \libs\db\Db::getInstance('firstp2p');
            $db->startTrans();

            //生成提现单
            $bidId = 0;
            if (!empty($params['bidId'])) {
                $bidId = $params['bidId'];
            }
            $createOrderResult = PhSupervisionService::withdrawCreateOrder($params['userId'], $params['amount'], $params['orderId'], $bidId, $withdrawType, $limitId);
            if (empty($createOrderResult)) {
                $this->exception('ERR_CARRY_ORDER_CREATE');
            }
            // TODO 资产中心 Lock Supervision Balance createOrder中changeMoney已加
            //UserThirdBalanceModel::instance()->updateUserSupervisionMoney($params['userId'], bcdiv($params['amount'], 100, 2), UserModel::TYPE_LOCK_MONEY);

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_WITHDRAW, $params);

            // 上报 ITIL
            \libs\utils\Monitor::add('sv_withdraw_apply');
            $db->commit();
        } catch(\Exception $e) {
            $db->rollback();
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return $params;
    }

    /**
     * 免密提现至银行卡（存管已废弃该接口）
     * @param array $params 传入参数
     * @return array 输出结果
     */
    public function withdraw($params) {
        try {
            // 回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN').'/supervision/withdrawNotify';

            // 开启新的快速受托提现开关
            if ($this->canFastWithdraw($params['userId'], $params['amount']) && $this->isWithdrawFastOpen()) {
                return $this->entrustedWithdrawFast($params);
            }

            //提现准备
            $params = $this->withdrawPrepare('withdraw', $params, true);
            // 该笔提现记录已经是终态
            if (true === $this->isWithdrawFinalState) {
                $this->isWithdrawFinalState = false;
                return $this->responseSuccess(['respCode'=>self::RESPONSE_CODE_SUCCESS, 'respMsg'=>'该笔提现记录已经终态']);
            }

            // 请求接口
            $result = $this->api->request('withdraw', $params);
            // 存管接口返回[商户流水已存在]正常处理
            if (!isset($result['respCode']) || ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== self::CODE_ORDER_EXIST)) {
                // TODO 反向冻结存管余额
                throw new WXException('ERR_WITHDRAW');
            }

            //生产用户访问日志
            $extraInfo = [
                'orderId' => $params['orderId'],
                'withdrawAmount' => (int) $params['amount'],
                'withdrawTime' => time(),
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT, UserAccessLogEnum::PLATFORM_P2P);

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
            // 回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN').'/supervision/withdrawNotify';

            // 开启新的快速受托提现开关
            if ($this->canFastWithdraw($params['userId'], $params['amount']) && $this->isWithdrawFastOpen()) {
                return $this->entrustedWithdrawFast($params);
            }

            //提现准备
            $params = $this->withdrawPrepare('bankpayupWithdraw', $params, true, SupervisionWithdrawModel::TYPE_LOCKMONEY);
            // 该笔提现记录已经是终态
            if (true === $this->isWithdrawFinalState) {
                $this->isWithdrawFinalState = false;
                return $this->responseSuccess(['respCode'=>self::RESPONSE_CODE_SUCCESS, 'respMsg'=>'该笔提现记录已经终态']);
            }

            // 请求接口
            $result = $this->api->request('bankpayupWithdraw', $params);
            // 存管接口返回[商户流水已存在]正常处理
            if (!isset($result['respCode']) || ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== self::CODE_ORDER_EXIST)) {
                // TODO 反向冻结存管余额
                throw new WXException('ERR_WITHDRAW_BANKPAYUP');
            }

            //生产用户访问日志
            $extraInfo = [
                'orderId' => $params['orderId'],
                'withdrawAmount' => (int) $params['amount'],
                'withdrawTime' => time(),
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT, UserAccessLogEnum::PLATFORM_P2P);

            return $this->responseSuccess($result);
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
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
    public function withdrawNotify($responseData, $userLogType = '提现') {
        try {
            PaymentApi::log(sprintf('%s | %s, responseData: %s', __CLASS__, __FUNCTION__, json_encode($responseData)));
            if (empty($responseData['orderId']) || empty($responseData['status']) || empty($responseData['amount'])) {
                throw new WXException('ERR_PARAM_LOSE');
            }
            //请求存管查单接口，校验订单状态
            $orderResult = $this->orderSearch($responseData['orderId']);
            if (empty($orderResult) || $orderResult['status'] != self::RESPONSE_SUCCESS || $orderResult['respCode'] != self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_ORDER_SEARCH');
            }
            if ($responseData['status'] != self::WITHDRAW_PROCESSING && $orderResult['data']['status'] !== $responseData['status']) {
                throw new WXException('ERR_CALLBACK_STATUS');
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
            //检查用户
            $userService = new UserService();
            $user = $userService->getUser($withdrawInfo['user_id']);
            if (empty($user)) {
                throw new WXException('ERR_USER_NOEXIST');
            }

            //检查状态
            $_withdrawStatus = array(
                self::WITHDRAW_SUCCESS => SupervisionWithdrawModel::WITHDRAW_STATUS_SUCCESS,
                self::WITHDRAW_FAILURE => SupervisionWithdrawModel::WITHDRAW_STATUS_FAILED,
                self::WITHDRAW_PROCESSING => SupervisionWithdrawModel::WITHDRAW_STATUS_PROCESS,
            );
            if (!isset($_withdrawStatus[$responseData['status']])) {
                throw new WXException('ERR_RESPONSE_STATUS');
            }
            // 如果订单状态已经跟返回的状态一致
            if ($_withdrawStatus[$responseData['status']] == $withdrawInfo['withdraw_status']) {
                return $this->responseSuccess();
            }
            // 如果提现订单已经是终态，还返回AS状态当成功处理
            if (in_array($withdrawInfo['withdraw_status'], SupervisionWithdrawModel::$finalStatus)
                && $_withdrawStatus[$responseData['status']] == SupervisionWithdrawModel::WITHDRAW_STATUS_PROCESS) {
                return $this->responseSuccess();
            }

            try {
                $db = \libs\db\Db::getInstance('firstp2p');
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
                $orderWithdrawStatus = $responseData['status'] == self::WITHDRAW_PROCESSING ? SupervisionBaseService::NOTICE_PROCESSING : $responseData['status'];
                $supervisionOrderService->asyncUpdateOrder($responseData['orderId'], $orderWithdrawStatus);

                // 处理放款提现
                $dealGrantService = new \core\service\P2pDealGrantService();
                $dealGrantService->withdrawNotify($responseData['orderId'],$responseData['status'],$withdrawMoney);

                $db->commit();

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
            Alarm::push('supervision', __METHOD__, sprintf('存管提现回调异常|订单ID:%s，提现金额:%s，存管回调参数:%s，异常内容:%s', $responseData['orderId'], $responseData['amount'], json_encode($responseData), $e->getMessage()));
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
            if ($orderStatus == \core\dao\SupervisionTransferModel::TRANSFER_STATUS_SUCCESS || $orderStatus == \core\dao\SupervisionTransferModel::TRANSFER_STATUS_FAILURE) {
                return true;
            }
        }

        $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN').'/supervison/superRechargeNotify';
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
            $result['data']['status'] = $result['respCode'] == self::RESPONSE_CODE_SUCCESS ? self::RESPONSE_SUCCESS : self::RESPONSE_FAILURE;
            $result['data']['amount'] = $params['amount'];
            $result['data']['orderId'] = $params['orderId'];

        }
        // 接口请求失败或返回错误码非订单流水已存在
        if ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== self::CODE_ORDER_EXIST) {
            throw new WXException($exceptionKey);
        }

        //  如果支付同步返回订单已受理，则等待回调处理， 结束gtm任务
        if ($result['respSubCode'] == self::CODE_ORDER_EXIST) {
            return true;
        }
        // 划转处理逻辑
        $processResult = $this->superRechargeNotify($params['orderId'], null, $result);
        if (empty($processResult['status']) || $processResult['status'] != self::RESPONSE_SUCCESS) {
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
            //$db = \libs\db\Db::getInstance('firstp2p', 'master');
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
            if ($e instanceof \core\exception\UserThirdBalanceException) {
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
        // 异步回调地址
        $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/superRechargeSecretNotify';
        return $this->_createTransferForm($service, SupervisionTransferModel::DIRECTION_TO_SUPERVISION,  $params, $platform, $formId, $targetNew);
    }

    /**
     * 生成验密提现至超级账户表单
     * @param array $params 传入参数
     * @return array 输出结果
     */
    public function superWithdrawSecret($params, $platform = 'pc', $formId = 'superWithdrawForm', $targetNew = false) {
        $service = $platform === 'pc' ? 'superWithdrawSecret' : 'h5SuperWithdrawSecret';
        // 异步回调地址
        $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/superWithdrawNotify';
        return $this->_createTransferForm($service, SupervisionTransferModel::DIRECTION_TO_WX,  $params, $platform, $formId, $targetNew);
    }


    private function _createTransferForm($service, $direction, $params, $platform, $formId, $targetNew) {
        try {
            $db = Db::getInstance('firstp2p', 'master');
            $db->startTrans();
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM_PLATFORM');
            }
            $supervisionApi = $this->api;
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

            $result = $supervisionApi->getForm($service, $params, $formId, $targetNew);
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
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
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
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
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
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
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
            if ($orderStatus == \core\dao\SupervisionTransferModel::TRANSFER_STATUS_SUCCESS || $orderStatus == \core\dao\SupervisionTransferModel::TRANSFER_STATUS_FAILURE) {
                return true;
            }
        }
        $accountSuperWithdrawResult = [];
        $supervisionApi = $this->api;
        $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN').'/supervision/superWithdrawNotify';
        $accountSuperWithdrawResult = $supervisionApi->request('accountSuperWithdraw', $params);
        //请求超时，查询接口
        if (!isset($accountSuperWithdrawResult['respCode'])) {
            $searchOrderResult = $this->api->request('orderSearch', ['orderId' => $params['orderId']]);
            if (empty($searchOrderResult['respCode']) || $searchOrderResult['respCode'] != self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_SUPERRECHARGE');
            }
            $accountSuperWithdrawResult = $searchOrderResult;
            $accountSuperWithdrawResult['data']['amount'] = $searchOrderResult['amount'];
            $accountSuperWithdrawResult['data']['orderId'] = $searchOrderResult['orderId'];
            $accountSuperWithdrawResult['data']['status'] = $searchOrderResult['status'];
        } else {
            $accountSuperWithdrawResult['data']['amount'] = $params['amount'];
            $accountSuperWithdrawResult['data']['orderId'] = $params['orderId'];
            $accountSuperWithdrawResult['data']['status'] = $accountSuperWithdrawResult['respCode'] == self::RESPONSE_CODE_SUCCESS ? self::RESPONSE_SUCCESS : self::RESPONSE_FAILURE;
        }
        if ($accountSuperWithdrawResult['respCode'] !== self::RESPONSE_CODE_SUCCESS && $accountSuperWithdrawResult['respSubCode'] !== self::CODE_ORDER_EXIST) {
            throw new WXException('ERR_AVOID_ACCOUNT_SUPERWITHDRAW');
        }
        //  如果支付同步返回订单已受理，则等待回调处理， 结束gtm任务
        if ($accountSuperWithdrawResult['respSubCode'] == self::CODE_ORDER_EXIST) {
            return true;
        }
        $notifyResult = $this->superRechargeNotify($params['orderId'], SupervisionTransferModel::DIRECTION_TO_WX, $accountSuperWithdrawResult);
        if (empty($notifyResult['status']) || $notifyResult['status'] != self::RESPONSE_SUCCESS) {
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
            //$db = \libs\db\Db::getInstance('firstp2p', 'master');
            //$db->startTrans();
            $accountService = new SupervisionAccountService();
            $supervisionApi = $this->api;
            // 检查用户是否设置免密提现
            $needPrivileges = array(
                'WITHDRAW_TO_SUPER',
            );
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
            if ($e instanceof \core\exception\UserThirdBalanceException) {
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
            $supervisionApi = $this->api;

            // 生成提现订单
            $createOrderResult = PhSupervisionService::withdrawCreateOrder($params['userId'], $params['repayAmount'],$params['orderId'],$params['bidId'], SupervisionWithdrawModel::TYPE_TO_CREDIT_ELEC_ACCOUNT);

            //异步添加存管订单
            $supervisionOrderService = new SupervisionOrderService();
            $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_ELEC_WITHDRAW, $params);

            // 请求存管接口
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN').'/supervision/bidElecWithdrawNotify';
            $result = $supervisionApi->request('bidElecWithdraw', $params);
            if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
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
            if (empty($orderSearchResult['respCode']) || $orderSearchResult['respCode'] != self::RESPONSE_CODE_SUCCESS) {
                throw new WXException('ERR_ORDER_NOT_EXIST');
            }
            // 判断业务处理结果
            $bizData = $orderSearchResult['data'];
            if (empty($bizData['respCode']) || $bizData['status'] != $responseData['status']) {
                throw new WXException('ERR_BID_ELEC_WITHDRAW_STATUS');
            }

            $status = SupervisionWithdrawModel::WITHDRAW_STATUS_NORMAL;
            if ($bizData['status'] == self::RESPONSE_SUCCESS) {
                $status = SupervisionWithdrawModel::WITHDRAW_STATUS_SUCCESS;
            } else if ($bizData['status'] == self::RESPONSE_FAILURE) {
                $status = SupervisionWithdrawModel::WITHDRAW_STATUS_FAILED;
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
                    $creditLoanService = new \core\service\CreditLoanService();
                    $creditLoanService->repaySupervisionCallBack($orderId, $status);
                    break;
                case 1: // 享花
                    $loanThirdService = new \core\service\LoanThirdService();
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
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/withdrawNotify';

            // 开启新的快速受托提现开关
            if ($this->canFastWithdraw($params['userId'], $params['amount']) && $this->isWithdrawFastOpen()) {
                return $this->secretWithdrawFast($params, $platform, $formId, $targetNew);
            }

            PaymentApi::log(sprintf('%s | %s, params: %s', __CLASS__, __FUNCTION__, json_encode($params)));
            if (!$this->checkPlatform($platform)) {
                throw new WXException('ERR_PARAM_PLATFORM');
            }
            // 限制提现
            if (!((new UserCarryService())->canWithdrawAmount($params['userId'], bcdiv($params['amount'], 100, 2), true, false))) {
                throw new WXException('ERR_WITHDRAW_LIMIT');
            }

            $service = $platform === 'pc' ? 'secretWithdraw' : 'h5SecretWithdraw';

            //提现准备
            // TODO 双账户之后要优化
            $rules = (new AccountLimitService())->getAllLimits($params['userId'], true);
            $withdrawType = SupervisionWithdrawModel::TYPE_TO_BANKCARD;
            $limitId = 0;
            if (!empty($rules[0]['account_type']) && $rules[0]['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
            {
                $withdrawType = SupervisionWithdrawModel::TYPE_LIMIT_WITHDRAW;
                $limitId = $rules[0]['id'];
                if (!(new AccountLimitService())->minusRemainMoney($rules[0]['id'], $params['amount'], true))
                {
                    throw new \Exception('ERR_WITHDRAW_LIMIT');
                }
            } else if (!empty($rules[0]['account_type']) && $rules[0]['account_type'] == UserAccountEnum::ACCOUNT_INVESTMENT) {
                $withdrawType = SupervisionWithdrawModel::TYPE_LIMIT_WITHDRAW_BLACKLIST;
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
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT, UserAccessLogEnum::PLATFORM_P2P);

            return $this->responseSuccess(['form' => $result, 'formId' => $formId]);
        } catch(\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
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
            // 限制提现
            if (!((new UserCarryService())->canWithdrawAmount($params['userId'], bcdiv($params['amount'], 100, 2), true, false))) {
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
            $rules = (new AccountLimitService())->getAllLimits($params['userId'], true);
            $withdrawType = SupervisionWithdrawModel::TYPE_TO_BANKCARD;
            $limitId = 0;
            if (!empty($rules[0]['account_type']) && $rules[0]['account_type'] == UserAccountEnum::ACCOUNT_FINANCE)
            {
                $withdrawType = SupervisionWithdrawModel::TYPE_LIMIT_WITHDRAW;
                $limitId = $rules[0]['id'];
                if (!(new AccountLimitService())->minusRemainMoney($rules[0]['id'], $params['amount'], true))
                {
                    throw new \Exception('ERR_WITHDRAW_LIMIT');
                }
            } else if (!empty($rules[0]['account_type']) && $rules[0]['account_type'] == UserAccountEnum::ACCOUNT_INVESTMENT) {
                $withdrawType = SupervisionWithdrawModel::TYPE_LIMIT_WITHDRAW_BLACKLIST;
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
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT, UserAccessLogEnum::PLATFORM_P2P);

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
            // 回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN').'/supervision/entrustedWithdrawNotify';

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
                $db = \libs\db\Db::getInstance('firstp2p');
                $db->startTrans();

                //生成提现单
                $createOrderResult = PhSupervisionService::withdrawCreateOrder($params['userId'], $params['amount'],$params['orderId'],$params['bidId'], SupervisionWithdrawModel::TYPE_ENTRUSTED);
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
            if(empty($result['respCode']) || ($result['respCode'] != self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== self::CODE_ORDER_EXIST)) {
                throw new WXException('ERR_ENTRUSTED_WITHDRAW');
            }

            //生产用户访问日志
            $extraInfo = [
                'orderId' => $params['orderId'],
                'withdrawAmount' => (int) $params['amount'],
                'withdrawTime' => time(),
            ];
            UserAccessLogService::produceLog($params['userId'], UserAccessLogEnum::TYPE_WITHDRAW, sprintf('网贷提现申请%s元', bcdiv($params['amount'], 100, 2)), $extraInfo, '', DeviceEnum::DEVICE_UNKNOWN, UserAccessLogEnum::STATUS_INIT, UserAccessLogEnum::PLATFORM_P2P);

            // 业务处理
            return $this->responseSuccess();
        } catch(\Exception $e) {
            // 记录告警
            Alarm::push('supervision', __METHOD__, sprintf('受托提现异常|订单ID:%s，异常内容:%s', $params['orderId'], $e->getMessage()));
            // 添加监控
            Monitor::add('SUPERVISION_ENTRUSTEDWITHDRAW');
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($params)));
            //\libs\utils\Alarm::push('supervision', 'entrustedWithdrawFailure', $e->getMessage() . ', ' . json_encode($params));
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
                $createOrderResult = $withdrawModel->createOrder($params['userId'], $params['amount'], $params['orderId'], $params['bidId'], SupervisionWithdrawModel::TYPE_ENTRUSTED);
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
            if(empty($result['respCode']) || ($result['respCode'] != self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== self::CODE_ORDER_EXIST)) {
                throw new WXException('ERR_WITHDRAW_ENTRUSTED_FAST');
            }

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

            if(isset($result['repsCode']) && $result['respCode'] == SupervisionBase::RESPONSE_CODE_SUCCESS) {
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
                if (empty($orderSearchResult['data']['respCode']) || $orderSearchResult['data']['respCode'] != self::RESPONSE_CODE_SUCCESS) {
                    throw new WXException('ERR_SUPERRECHARGE');
                }
            }
            // 判断业务处理结果
            $bizData = $orderSearchResult['data'];
            $status = SupervisionTransferModel::TRANSFER_STATUS_NORMAL;
            if ($bizData['status'] == self::RESPONSE_SUCCESS) {
                $status = SupervisionTransferModel::TRANSFER_STATUS_SUCCESS;
            } else if ($bizData['status'] == self::RESPONSE_FAILURE) {
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
            if ($e instanceof \core\exception\UserThirdBalanceException) {
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
    public function getChargeLogs($accountId, $ctime = 0, $count = 0, $offset = 0) {
        $result = PhSupervisionService::chargeGetLogs($accountId, $ctime, $count, $offset);
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
            $params = ['orderId'=>$orderId, 'userId'=>(int)$userId, 'amount'=>(int)$amount, 'expireTime'=>addslashes($expireTime)];
            $supervisionApi = $this->api;

            // 检查充值订单是否已存在 并且状态是成功
            $chargeInfo = PhSupervisionService::chargeGetOrder($orderId);
            if (isset($chargeInfo['status']) && $chargeInfo['status'] == SupervisionChargeModel::CHARGE_SUCCESS) {
                return $this->responseSuccess();
            }

            // 支持业务重试, 重试不创建订单
            if (empty($chargeInfo)) {
                // 创建普惠存管充值订单
                $ret = PhSupervisionService::chargeCreateOrder($userId, $amount, $orderId, PaymentNoticeModel::PLATFORM_SUPERVISION_AUTORECHARGE);
                if ( ! $ret) {
                    throw new WXException('ERR_CREATE_CHARGE_FAILED');
                }

                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                $startTrans = true;

                // 上报ITIL
                Monitor::add('sv_autorecharge_apply');
                // 异步添加存管订单
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_AUTORECHARGE, $params);
                // 提交事务
                $db->commit();
                $startTrans = false;
            }

            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/chargeNotify';
            $result = $supervisionApi->request('autoRecharge', $params);
            // 订单处理中
            if (isset($result['respSubCode']) && $result['respSubCode'] === '000001') {
                return $this->responseProsessing();
            }
            // 如果返回订单重复,查询
            if (isset($result['respSubCode']) && $result['respSubCode'] == self::CODE_ORDER_EXIST) {
                $result = $this->orderSearch($orderId);
                if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                    throw new \WXException('ERR_AUTOCHARGE_FAILED');
                }
                $result = $result['data'];
                // 没响应或者充值失败,返回失败
                if (!isset($result['status']) || (isset($result['status']) && $result['status'] == self::CHARGE_FAILURE)) {
                    throw new WXException('ERR_AUTOCHARGE_FAILED');
                }
                if (isset($result['status']) && $result['status'] == self::CHARGE_PENDING) {
                    return $this->responseProsessing();
                }
                if (isset($result['status']) && $result['status'] == self::CHARGE_SUCCESS) {
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
            if ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] == self::CODE_SV_SERVER_BUSY) {
                $wxException = new WXException('ERR_SV_SERVER_BUSY');
                $msg = empty($result['respMsg']) ? '存管服务器繁忙，请稍后再试' : $result['respMsg'];
                throw new \Exception($msg, $wxException->getCode());
            }

            // 返回失败
            if ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
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
                $res = ['status'=>self::RESPONSE_FAILURE];
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP,'支付提现到超级账户异常 orderId:'.$orderId . " errMsg:".$e->getMessage())));
            }
            if ($res['status'] != self::RESPONSE_SUCCESS) {
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
     *          amount:金额
     *          bizType:业务类型 8001返利 8003红包 1902资金迁移
     *          payUserId:出款方
     *          receiveUserId:收款方
     *          subOrderId:子订单id
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
            $supervisionApi = $this->api;
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN').'/supervision/batchTransferNotify';
            $result = $supervisionApi->request('batchTransfer', $params);
            // 存管接口返回[商户流水已存在]正常处理
            if (!isset($result['respCode']) || ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] !== self::CODE_ORDER_EXIST)) {
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
            $p2pDealRepayService = new \core\service\P2pDealRepayService();
            $p2pDealRepayService->batchTransferCallBack($responseData['orderId'], $responseData['status']);
            return $this->responseSuccess();
        } catch(\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, %s', __CLASS__, __FUNCTION__, $e->getMessage() . ', ' . json_encode($responseData)));
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
            $supervisionApi = $this->api;

            // 检查充值订单是否已存在 并且状态是成功
            $chargeInfo = PhSupervisionService::chargeGetOrder($params['orderId']);
            if (isset($chargeInfo['status']) && $chargeInfo['status'] == SupervisionChargeModel::CHARGE_SUCCESS) {
                return $this->responseSuccess();
            }

            // 支持业务重试, 重试不创建订单
            if (empty($chargeInfo)) {
                // 创建普惠存管充值订单
                $ret = PhSupervisionService::chargeCreateOrder($params['userId'], $params['amount'], $params['orderId'], PaymentNoticeModel::PLATFORM_SUPERVISION_AUTORECHARGE);
                // 创建充值记录单
                if ( ! $ret) {
                    throw new WXException('ERR_CREATE_CHARGE_FAILED');
                }

                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                $startTrans = true;

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
            $supervisionApi->setTimeOut(5);
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN').'/supervision/multicardChargeNotify';
            $result = $supervisionApi->request('multiCardRecharge', $params);
            // 订单处理中
            if (isset($result['respSubCode']) && $result['respSubCode'] === '000001') {
                return $this->responseProsessing();
            }
            // 如果返回订单重复,查询
            if (isset($result['respSubCode']) && $result['respSubCode'] == self::CODE_ORDER_EXIST) {
                $result = $this->orderSearch($params['orderId']);
                if (!isset($result['respCode']) || $result['respCode'] !== self::RESPONSE_CODE_SUCCESS) {
                    throw new WXException('ERR_AUTOCHARGE_FAILED');
                }
                $result = $result['data'];
                // 没响应或者充值失败,返回失败
                if (!isset($result['status']) || (isset($result['status']) && $result['status'] == self::CHARGE_FAILURE)) {
                    throw new WXException('ERR_AUTOCHARGE_FAILED');
                }
                if (isset($result['status']) && $result['status'] == self::CHARGE_PENDING) {
                    return $this->responseProsessing();
                }
                if (isset($result['status']) && $result['status'] == self::CHARGE_SUCCESS) {
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
            if ($result['respCode'] !== self::RESPONSE_CODE_SUCCESS && $result['respSubCode'] == self::CODE_SV_SERVER_BUSY) {
                $wxException = new WXException('ERR_SV_SERVER_BUSY');
                $msg = empty($result['respMsg']) ? '存管服务器繁忙，请稍后再试' : $result['respMsg'];
                throw new \Exception($msg, $wxException->getCode());
            }

            // 返回失败
            if (($result['respCode'] !== self::RESPONSE_CODE_SUCCESS )) {
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
        return str_replace(['余额', 'P2P'], '', SupervisionTransferModel::SUPERVISION_NAME);
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

            $service = $platform === 'pc' ? 'webOfflineCharge' : 'h5OfflineCharge';
            $supervisionApi = $this->api;
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/offlineChargeNotify';

            // 检查充值订单是否已存在 并且状态是成功
            $chargeInfo = PhSupervisionService::chargeGetOrder($params['orderId']);
            // 支持业务重试, 重试不创建订单
            if (empty($chargeInfo)) {
                // 创建普惠存管充值订单
                $ret = PhSupervisionService::chargeCreateOrder($params['userId'], $params['amount'], $params['orderId'], PaymentNoticeModel::PLATFORM_OFFLINE_V2);
                if ( ! $ret) {
                    throw new WXException('ERR_CREATE_CHARGE_FAILED');
                }

                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                $startTrans = true;

                // 上报ITIL
                Monitor::add('sv_payment_apply');
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_RECHARGE, $params);
                $db->commit();
                $startTrans = false;
            }

            $form = $supervisionApi->getForm($service, $params, $formId, $targetNew);
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
            $chargeInfo = PhSupervisionService::chargeGetOrder($params['orderId']);
            // 支持业务重试, 重试不创建订单
            if (empty($chargeInfo)) {
                // 创建普惠存管充值订单
                $ret = PhSupervisionService::chargeCreateOrder($params['userId'], $params['amount'], $params['orderId'], PaymentNoticeModel::PLATFORM_ENTERPRISE_H5CHARGE);
                if ( ! $ret) {
                    throw new WXException('ERR_CREATE_CHARGE_FAILED');
                }

                $db = Db::getInstance('firstp2p');
                $db->startTrans();
                $startTrans = true;

                // 上报ITIL
                Monitor::add('sv_payment_apply');
                $supervisionOrderService = new SupervisionOrderService();
                $supervisionOrderService->asyncAddOrder(SupervisionOrderService::SERVICE_RECHARGE, $params);
                $db->commit();
                $startTrans = false;
            }

            $service = $platform === 'pc' ? 'h5EnterpriseCharge' : 'h5EnterpriseCharge';
            // 异步回调地址
            $params['noticeUrl'] = app_conf('PH_NOTIFY_DOMAIN') .'/supervision/enterpriseChargeNotify';
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

    public function canFastWithdraw($userId, $amount = 0)
    {
        $result = PhSupervisionService::canFastWithdraw($userId, $amount);
        return $result === 1 ? true : false;
    }

    /**
     * 获取网信的新协议限额开关
     * @return int
     */
    public static function isNewBankLimitOpen() {
        return intval(app_conf('APP_USE_H5_CHARGE')) == 0 ? intval(\libs\utils\ABControl::getInstance()->hit('useH5Charge')) : 1;
    }

    /**
     * 获取网信的默认充值限额，单位分
     * @return int
     */
    public static function getDefaultBankLimit() {
        return (int)app_conf('APP_USE_H5_CHARGE_DEFAULT');
    }

}
