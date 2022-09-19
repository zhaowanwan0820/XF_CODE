<?php
use NCFGroup\Common\Library\StandardApi;
use core\enum\SupervisionEnum;
use core\dao\account\PaymentNoticeModel;
use core\dao\supervision\SupervisionChargeModel;
use core\dao\supervision\SupervisionWithdrawModel;
use core\dao\supervision\SupervisionTransferModel;
use core\service\supervision\SupervisionFinanceService;
use core\service\supervision\SupervisionDealService;
use core\service\deal\P2pIdempotentService;
use libs\common\WXException;
use libs\utils\Logger;

class PaymentNoticeAction extends CommonAction
{
    const PAYMENT_HKSUPERVISION = 'HkSupervision';

    /**
     * 后台可用的充值平台
     * @var array
     */
    private static $enablePaymentName = [self::PAYMENT_HKSUPERVISION];

    /**
     * 充值平台-海口联合农商银行配置
     * @var array
     */
    private static $supervisionPaymentConfig = [
        'id' => 100, 'class_name' => 'HkSupervision','is_effect' => 1,'online_pay' => 1,
        'fee_amount' => '0.0000','name' => '海口联合农商银行','description' => '','total_amount' => '0.0000',
        'config' => '','logo' => '','sort' => 8,'fee_type' => 1,'max_fee' => '0.0000',
    ];

    /**
     * 存管系统-业务补单列表
     * @var array
     */
    private static $supervisionBusinessConfig = [
        1 => ['id'=>1, 'platform'=>'海口联合农商银行', 'name'=>'提现', 'type'=>1, 'notifyMethod'=>'withdrawNotify'],
        2 => ['id'=>2, 'platform'=>'海口联合农商银行', 'name'=>'网信理财账户余额 划转到 网贷P2P账户余额', 'type'=>1, 'notifyMethod'=>'superRechargeNotify', 'businessType'=>SupervisionTransferModel::DIRECTION_TO_SUPERVISION],
        4 => ['id'=>4, 'platform'=>'海口联合农商银行', 'name'=>'放款', 'type'=>2, 'notifyMethod'=>'dealGrantNotify', 'businessType'=>SupervisionEnum::BATCHORDER_TYPE_GRANT],
        5 => ['id'=>5, 'platform'=>'海口联合农商银行', 'name'=>'还款', 'type'=>2, 'notifyMethod'=>'dealRepayNotify', 'businessType'=>SupervisionEnum::BATCHORDER_TYPE_REPAY],
        6 => ['id'=>6, 'platform'=>'海口联合农商银行', 'name'=>'流标', 'type'=>2, 'notifyMethod'=>'dealCancelNotify', 'businessType'=>SupervisionEnum::BATCHORDER_TYPE_DEALCANCEL],
    ];

    /**
     * 存管系统普惠-业务补单列表
     * @var array
     */
    private static $supervisionBusinessCnConfig = [
        1 => ['id'=>1, 'platform'=>'海口联合农商银行', 'name'=>'提现', 'type'=>1, 'notifyMethod'=>'withdrawNotify'],
        4 => ['id'=>4, 'platform'=>'海口联合农商银行', 'name'=>'放款', 'type'=>2, 'notifyMethod'=>'dealGrantNotify', 'businessType'=>SupervisionEnum::BATCHORDER_TYPE_GRANT],
        5 => ['id'=>5, 'platform'=>'海口联合农商银行', 'name'=>'还款', 'type'=>2, 'notifyMethod'=>'dealRepayNotify', 'businessType'=>SupervisionEnum::BATCHORDER_TYPE_REPAY],
        6 => ['id'=>6, 'platform'=>'海口联合农商银行', 'name'=>'流标', 'type'=>2, 'notifyMethod'=>'dealCancelNotify', 'businessType'=>SupervisionEnum::BATCHORDER_TYPE_DEALCANCEL],
    ];

    // 银行卡限额订阅的缓存key
    const BANKLIMIT_SUBSCRIPTION_KEY = 'NCFPH_BANKLIMIT_SUBSCRIPTION_CONF';

    /**
     * 存管系统-业务补单
     */
    public function supplement_business()
    {
        if ($this->is_cn) {
            $orderTypeList = self::$supervisionBusinessCnConfig;
        } else {
            $orderTypeList = self::$supervisionBusinessConfig;
        }
        if (empty($_POST)) {
            $this->assign('ot', (int)$_GET['ot']);
            $this->assign('orderTypeList', $orderTypeList);
            $this->display();
            return ;
        }
        $GLOBALS['db']->startTrans();
        try {
            // 业务类型
            $orderTypeId = (int)$_POST['orderTypeId'];
            // 订单号
            $orderId = addslashes(trim($_POST['orderId']));
            if (!is_numeric($orderTypeId)) {
                throw new \Exception('业务类型参数错误');
            }
            if (empty($orderId)) {
                throw new \Exception('订单号不能为空');
            }
            // 检查业务类型是否存在
            if (empty($orderTypeList) || empty($orderTypeList[$orderTypeId])) {
                throw new \Exception('充值平台不存在');
            }
            $this->assign('jumpUrl', u(MODULE_NAME . '/supplement_business', ['ot'=>$orderTypeId]));
            // 业务类型配置信息
            $orderTypeConfig = $orderTypeList[$orderTypeId];
            // 业务类型所在平台
            $orderTypePlatform = $orderTypeConfig['platform'];
            // 业务类型名称
            $orderTypeName = $orderTypeConfig['name'];
            // 业务类型回调方法
            $orderTypeNotifyMethod = $orderTypeConfig['notifyMethod'];

            switch ($orderTypeId) {
                case 0: // 充值
                case 1: // 提现
                case 2: // 网信理财账户余额划转到网贷P2P账户余额
                case 3: // 网贷P2P账户余额划转到网信理财账户余额
                    // 查询业务数据
                    $orderInfo = $this->_getBusinessInfo($orderId, $orderTypeId, $orderTypeName);
                    // 根据充值订单号，调用支付的订单查询接口，查询该笔订单的状态
                    $supervisionFinanceObj = new SupervisionFinanceService();
                    $orderCheckInfo = $supervisionFinanceObj->orderSearch($orderId);
                    // 存管订单查询接口，调用失败
                    if ($orderCheckInfo['status'] == SupervisionEnum::RESPONSE_FAILURE) {
                        throw new \Exception($orderTypeName . '-' . $orderCheckInfo['respMsg']);
                    }
                    // 存管系统返回的金额
                    $checkAmount = $orderCheckInfo['data']['amount'];
                    // 充值订单表的金额
                    $orderAmount = $orderInfo['amount'];
                    if (bccomp($checkAmount, $orderAmount) != 0) {
                        throw new \Exception($orderTypeName . '-金额不一致');
                    }

                    switch ($orderCheckInfo['data']['status'])
                    {
                        case SupervisionEnum::RESPONSE_FAILURE: // 失败
                        case SupervisionEnum::RESPONSE_SUCCESS: // 成功
                            if (in_array($orderTypeId, [2, 3])) { // 余额划转的回调逻辑
                                $callbackRet = $supervisionFinanceObj->{$orderTypeNotifyMethod}($orderCheckInfo['data']['orderId'], $orderTypeConfig['businessType']);
                            } else {
                                // 提现的回调逻辑，提现表的状态是未处理
                                if ($orderTypeId == 1 && $orderInfo['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_NORMAL) {
                                    // 需要先把提现记录，更新为处理中
                                    $tmpSupervisionData = $orderCheckInfo['data'];
                                    $tmpSupervisionData['status'] = SupervisionEnum::WITHDRAW_PROCESSING;
                                    $supervisionFinanceObj->{$orderTypeNotifyMethod}($tmpSupervisionData);
                                }
                                $callbackRet = $supervisionFinanceObj->{$orderTypeNotifyMethod}($orderCheckInfo['data']);
                            }
                            if ($callbackRet['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                                throw new \Exception($orderTypeName . '-' . $callbackRet['respMsg']);
                            }
                            // 交易流水号
                            $outer_notice_sn = trim($orderCheckInfo['data']['orderId']);
                            break;
                        case SupervisionEnum::RESPONSE_PROCESSING: // 处理中
                        case SupervisionEnum::WITHDRAW_PROCESSING: // 处理中
                            throw new \Exception($orderTypeName . '-处理中');
                            break;
                        default:
                            throw new \Exception($orderTypeName . '-未知状态');
                            break;
                    }
                    break;
                case 4: // 放款
                case 5: // 还款
                case 6: // 流标
                    $orderInfo = $this->_getBusinessInfo($orderId, $orderTypeId);
                    if (empty($orderInfo)) {
                        throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                    }

                    $supervisionFinanceObj = new SupervisionFinanceService();
                    $orderCheckInfo = $supervisionFinanceObj->batchOrderSearch($orderId, $orderTypeConfig['businessType']);
                    // 存管订单查询接口，调用失败
                    if ($orderCheckInfo['status'] == SupervisionEnum::RESPONSE_FAILURE) {
                        throw new \Exception($orderTypeName . '-' . $orderCheckInfo['respMsg']);
                    }

                    $supervisionDealObj = new SupervisionDealService();
                    switch ($orderCheckInfo['data']['status'])
                    {
                        case SupervisionEnum::RESPONSE_FAILURE: // 失败
                        case SupervisionEnum::RESPONSE_SUCCESS: // 成功
                            $callbackRet = $supervisionDealObj->{$orderTypeNotifyMethod}($orderCheckInfo['data']);
                            if ($callbackRet['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                                throw new \Exception($orderTypeName . '-' . $callbackRet['respMsg']);
                            }
                            // 交易流水号
                            $outer_notice_sn = trim($orderCheckInfo['data']['orderId']);
                            break;
                        case SupervisionEnum::RESPONSE_PROCESSING: // 处理中
                        case SupervisionEnum::WITHDRAW_PROCESSING: // 处理中
                            throw new \Exception($orderTypeName . '-处理中');
                            break;
                        default:
                            throw new \Exception($orderTypeName . '-未知状态');
                            break;
                    }
                    break;
                default:
                    throw new \Exception($orderTypeName . '-未知类型');
                    break;
            }
            // 提交事务
            $GLOBALS['db']->commit();
            // 投log
            FP::import('libs.utils.logger');
            $admin_data = es_session::get(md5(conf("AUTH_KEY")));
            $log = array(
                'type' => 'payment',
                'user_name' => 'admId:' . $admin_data['adm_id'] . ',userId:' . $orderInfo['user_id'],
                'money' => $orderInfo['amount'],
                'notice_sn' => $orderInfo['out_order_id'],
                'outer_notice_sn' => $outer_notice_sn,
                'path' => __FILE__,
                'function' => 'paymentNotice->supplement_business',
                'msg' => "后台操作（存管系统-{$orderTypeName}）补单",
                'time' => time(),
            );
            logger::wLog($log);
            save_log('存管系统-业务补单，会员id['.$orderInfo['user_id'].']，订单号['.$orderId.']' . L('UPDATE_SUCCESS'), 1, [], $log);
            $this->success('操作成功');
        }catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $this->error($orderTypePlatform . '：' . $e->getMessage());
        }
    }

    /**
     * 存管系统-银行卡限额订阅
     */
    public function banklimit_subscription()
    {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!$redis) {
            throw new \Exception('getRedisInstance failed');
        }

        // 获取配置里的订阅通知地址
        $standardObj = StandardApi::instance(StandardApi::SUPERVISION_GATEWAY);
        $chargeLimitConfig = $standardObj->getGatewayApi()->getServices()->get('chargeLimitSubscription');

        // 获取缓存里的订阅数据
        $bankLimitJson = $redis->get(self::BANKLIMIT_SUBSCRIPTION_KEY);
        $bankLimitData = json_decode($bankLimitJson, true);
        if (!empty($bankLimitData)) {
            $noticeUrl = $bankLimitData['noticeUrl'];
            $serviceType = (int)$bankLimitData['serviceType'];
        }else{
            $noticeUrl = !empty($chargeLimitConfig['noticeUrl']) ? $chargeLimitConfig['noticeUrl'] : '';
            $serviceType = 0; // 默认：订阅
        }
        $this->assign('noticeUrl', $noticeUrl);
        $this->assign('st', $serviceType);
        if (empty($_POST)) {
            $this->display();
            return ;
        }

        try {
            $bankLimitData = [];
            $bankLimitData['noticeUrl'] = !empty($chargeLimitConfig['noticeUrl']) ? $chargeLimitConfig['noticeUrl'] : '';
            // 是否订阅
            $bankLimitData['serviceType'] = !empty($_POST['serviceType']) ? (int)$_POST['serviceType'] : 0;
            // 订阅提示
            $stName = $bankLimitData['serviceType'] == 1 ? '取消订阅成功' : '订阅成功';

            // 请求存管限额接口
            $sfService = new SupervisionFinanceService();
            $limitResult = $sfService->bankLimitSubscription($bankLimitData['noticeUrl'], $bankLimitData['serviceType']);
            if ($limitResult['status'] !== SupervisionEnum::RESPONSE_SUCCESS) {
                $errorMsg = !empty($limitResult['respMsg']) ? $limitResult['respMsg'] : '限额订阅失败';
                throw new \Exception($errorMsg);
            }

            // 记录redis
            $bankLimitData['outOrderId'] = $limitResult['data']['outOrderId'];
            $redis->set(self::BANKLIMIT_SUBSCRIPTION_KEY, json_encode($bankLimitData));
            // 记录log
            $admin_data = es_session::get(md5(conf("AUTH_KEY")));
            $log = array(
                'type' => 'payment',
                'user_name' => 'admId:' . $admin_data['adm_id'] . ',serviceType:' . $_POST['subscription'],
                'path' => __FILE__,
                'function' => 'paymentNotice->banklimit_subscription',
                'msg' => "后台操作（存管系统-银行卡限额订阅）",
                'response' => $limitResult,
                'time' => time(),
            );
            Logger::wLog($log);
            save_log('存管系统-银行卡限额订阅，通知地址['.$bankLimitData['noticeUrl'].']，[' . $stName . ']，' . L('UPDATE_SUCCESS'), 1, [], $log);
            $this->success('操作成功');
        }catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取可用的充值平台
     * @param int $paymentId
     */
    private function _getEnablePaymentList($paymentId = 0) {
        $enablePaymentList = [];
        $paymentList = M('Payment')->findAll();
        $paymentList[] = self::$supervisionPaymentConfig;
        foreach ($paymentList as $key => $item) {
            if ($item['is_effect'] != 1 || !in_array($item['class_name'], self::$enablePaymentName)) {
                continue;
            }
            $enablePaymentList[$item['id']] = $item;
        }
        if ($paymentId > 0) {
            return !empty($enablePaymentList[$paymentId]) ? true : false;
        }
        return $enablePaymentList;
    }

    /**
     * 查询充值数据
     * @param string $orderId
     * @param string $paymentFlag
     * @param string $paymentName
     * @throws \Exception
     */
    private function _getPaymentInfo($orderId, $paymentFlag, $paymentName, $paymentId = 0) {
        $chargeOrder = [];
        switch ($paymentFlag) {
            case self::PAYMENT_HKSUPERVISION: // 海口联合农商银行
                // 查询【海口联合农商银行】的订单
                $chargeOrder = SupervisionChargeModel::instance()->getChargeRecordByOutId($orderId);
                if (empty($chargeOrder)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                if ($chargeOrder['pay_status'] == SupervisionChargeModel::PAY_STATUS_SUCCESS) {
                    throw new \Exception($paymentName . '：收款订单已经支付成功');
                }
                // 充值订单已经失败
                if ($chargeOrder['pay_status'] == SupervisionChargeModel::PAY_STATUS_FAILURE) {
                    throw new \Exception($paymentName . '：收款订单已经支付失败');
                }
                break;
            case self::PAYMENT_YEEPAY: // 易宝支付
            case self::PAYMENT_XFJR: // 先锋支付
                // 查询【易宝支付、先锋支付】的订单
                $_payment_notice_sql = 'SELECT * FROM ' . DB_PREFIX . "payment_notice WHERE notice_sn = '{$orderId}' AND payment_id = '{$paymentId}'";
                $chargeOrder = $GLOBALS['db']->getRow($_payment_notice_sql);
                if (empty($chargeOrder)) {
                    throw new \Exception($paymentName . '：收款订单号不存在');
                }
                if ($chargeOrder['is_paid'] == PaymentNoticeModel::IS_PAID_SUCCESS) {
                    throw new \Exception($paymentName . '：收款订单已经支付成功');
                }
                // 充值订单已经失败
                if ($chargeOrder['is_paid'] == PaymentNoticeModel::IS_PAID_FAIL) {
                    throw new \Exception($paymentName . '：收款订单已经支付失败');
                }
                break;
            case self::PAYMENT_ZJTG: // 先锋支付资金托管平台
                // 查询【先锋支付资金托管平台】的订单
                $_payment_notice_sql = 'SELECT * FROM ' . DB_PREFIX . "payment_notice WHERE notice_sn = '{$orderId}'";
                $chargeOrder = $GLOBALS['db']->getRow($_payment_notice_sql);
                if (empty($chargeOrder)) {
                    throw new \Exception($paymentName . '：收款订单号不存在');
                }
                if ($chargeOrder['is_paid'] == PaymentNoticeModel::IS_PAID_SUCCESS) {
                    throw new \Exception($paymentName . '：收款订单已经支付成功');
                }
                // 充值订单已经失败
                if ($chargeOrder['is_paid'] == PaymentNoticeModel::IS_PAID_FAIL) {
                    throw new \Exception($paymentName . '：收款订单已经支付失败');
                }
                break;
        }
        return $chargeOrder;
    }

    /**
     * 根据业务订单号，查询存管系统数据表数据
     * @param int $orderId 业务订单号
     * @param int $orderTypeId 业务类型ID
     * @param string $orderTypeName 业务类型名称
     */
    private function _getBusinessInfo($orderId, $orderTypeId, $orderTypeName = '') {
        $data = [];
        switch ($orderTypeId) {
            case 0: // 充值
                $data = SupervisionChargeModel::instance()->getChargeRecordByOutId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                // 订单状态已经成功
                if ($data['pay_status'] == SupervisionChargeModel::PAY_STATUS_SUCCESS) {
                    throw new \Exception($orderTypeName . '-该订单已经支付成功');
                }
                // 订单状态已经失败
                if ($data['pay_status'] == SupervisionChargeModel::PAY_STATUS_FAILURE) {
                    throw new \Exception($orderTypeName . '-该订单已经支付失败');
                }
                break;
            case 1: // 提现
                $data = SupervisionWithdrawModel::instance()->getWithdrawRecordByOutId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                // 订单状态已经成功
                if ($data['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_SUCCESS) {
                    throw new \Exception($orderTypeName . '-该订单已经提现成功');
                }
                // 订单状态已经失败
                if ($data['withdraw_status'] == SupervisionEnum::WITHDRAW_STATUS_FAILED) {
                    throw new \Exception($orderTypeName . '-该订单已经提现失败');
                }
                break;
            case 2: // 网信理财账户余额划转到网贷P2P账户余额
            case 3: // 网贷P2P账户余额划转到网信理财账户余额
                $data = SupervisionTransferModel::instance()->getTransferRecordByOutId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                // 订单状态已经成功
                if ($data['transfer_status'] == SupervisionTransferModel::TRANSFER_STATUS_SUCCESS) {
                    throw new \Exception($orderTypeName . '-该订单已经提现成功');
                }
                // 订单状态已经失败
                if ($data['transfer_status'] == SupervisionTransferModel::TRANSFER_STATUS_FAILURE) {
                    throw new \Exception($orderTypeName . '-该订单已经提现失败');
                }
                break;
            case 4: // 放款
            case 5: // 还款
                $data = P2pIdempotentService::getInfoByOrderId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                break;
            case 6: // 流标
                $data = P2pIdempotentService::getCancelOrderByDealId($orderId);
                if (empty($data)) {
                    throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                }
                break;
        }
        return $data;
    }
}
