<?php
/**
 * 异步用户入账核对
 */

namespace core\event;

use NCFGroup\Task\Events\AsyncEvent;
use libs\utils\PaymentApi;
use core\event\BaseEvent;
use libs\utils\Alarm;
use libs\db\Db;

use core\service\SupervisionOrderService;
use core\service\SupervisionFinanceService;
use core\dao\SupervisionOrderModel;
use core\dao\SupervisionChargeModel;
use core\dao\SupervisionWithdrawModel;
use core\dao\SupervisionTransferModel;
use core\service\SupervisionDealService;
use core\service\SupervisionBaseService;
use core\service\P2pIdempotentService;
use libs\common\WXException;


/**
 * SupervisionCheckEvent
 * 异步处理存管用户入账核查
 *
 * @uses AsyncEvent
 * @package default
 */
class SupervisionCheckEvent extends BaseEvent
{
    public $userId;
    const PAYMENT_YEEPAY = 'Yeepay';
    const PAYMENT_XFJR = 'Xfjr';
    const PAYMENT_ZJTG = 'Zjtg';
    const PAYMENT_HKSUPERVISION = 'HkSupervision';

    /**
     * 后台可用的充值平台
     * @var array
     */
    private static $enablePaymentName = [self::PAYMENT_YEEPAY, self::PAYMENT_XFJR, self::PAYMENT_ZJTG, self::PAYMENT_HKSUPERVISION];

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
        0 => ['id'=>0, 'platform'=>'海口联合农商银行', 'name'=>'充值', 'type'=>1, 'notifyMethod'=>'chargeNotify'],
        1 => ['id'=>1, 'platform'=>'海口联合农商银行', 'name'=>'提现', 'type'=>1, 'notifyMethod'=>'withdrawNotify'],
        2 => ['id'=>2, 'platform'=>'海口联合农商银行', 'name'=>'网信理财账户余额 划转到 网贷P2P账户余额', 'type'=>1, 'notifyMethod'=>'superRechargeNotify', 'businessType'=>SupervisionTransferModel::DIRECTION_TO_SUPERVISION],
        3 => ['id'=>3, 'platform'=>'海口联合农商银行', 'name'=>'网贷P2P账户余额 划转到 网信理财账户余额', 'type'=>1, 'notifyMethod'=>'superRechargeNotify', 'businessType'=>SupervisionTransferModel::DIRECTION_TO_WX],
        4 => ['id'=>4, 'platform'=>'海口联合农商银行', 'name'=>'放款', 'type'=>2, 'notifyMethod'=>'dealGrantNotify', 'businessType'=>SupervisionFinanceService::BATCHORDER_TYPE_GRANT],
        5 => ['id'=>5, 'platform'=>'海口联合农商银行', 'name'=>'还款', 'type'=>2, 'notifyMethod'=>'dealRepayNotify', 'businessType'=>SupervisionFinanceService::BATCHORDER_TYPE_REPAY],
        6 => ['id'=>6, 'platform'=>'海口联合农商银行', 'name'=>'流标', 'type'=>2, 'notifyMethod'=>'dealCancelNotify', 'businessType'=>SupervisionFinanceService::BATCHORDER_TYPE_DEALCANCEL],
    ];


    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * 对账
     */
    public function execute()
    {
        $this->startUserOrderCheck();
    }

    /**
     * 取需要补单数据
     */
    public function startUserOrderCheck()
    {
        $bizTypes = [
            SupervisionOrderModel::BIZ_TYPE_RECHARGE => 0,
            SupervisionOrderModel::BIZ_TYPE_SUPER_RECHARGE => 3,
            SupervisionOrderModel::BIZ_TYPE_DEAL_REPAY => 5,
        ];
        $bizTypeCondition = ' AND  biz_type IN ('.implode(',', array_keys($bizTypes)).') ';
        $sql = "SELECT * FROM firstp2p_supervision_order WHERE user_id = '{$this->userId}' {$bizTypeCondition} AND check_status = 0 AND order_status IN (0,3,4)";
        $orders = Db::getInstance('firstp2p_payment','slave')->getAll($sql);

        foreach ($orders as $order) {
            $orderTypeId = $bizTypes[$order['biz_type']];
            $this->_checkOrder($orderTypeId, $order['out_order_id']);
        }
    }

    private function _checkOrder($bizType, $outOrderId)
    {
        $GLOBALS['db']->startTrans();
        try {
            // 业务类型
            $orderTypeId = (int)$bizType;
            // 订单号
            $orderId = addslashes(trim($outOrderId));
            if (!is_numeric($orderTypeId)) {
                throw new \Exception('业务类型参数错误');
            }
            if (empty($orderId)) {
                throw new \Exception('订单号不能为空');
            }
            $orderTypeList = self::$supervisionBusinessConfig;
            // 检查业务类型是否存在
            if (empty($orderTypeList) || empty($orderTypeList[$orderTypeId])) {
                throw new \Exception('业务类型不存在');
            }
            // 业务类型配置信息
            $orderTypeConfig = $orderTypeList[$orderTypeId];
            // 业务类型名称
            $orderTypeName = $orderTypeConfig['name'];
            // 业务类型回调方法
            $orderTypeNotifyMethod = $orderTypeConfig['notifyMethod'];

            switch ($orderTypeId) {
                case 0: // 充值
                case 3: // 网贷P2P账户余额划转到网信理财账户余额
                    // 查询业务数据
                    $orderInfo = $this->_getBusinessInfo($orderId, $orderTypeId, $orderTypeName);
                    // 根据充值订单号，调用支付的订单查询接口，查询该笔订单的状态
                    $supervisionFinanceObj = new SupervisionFinanceService();
                    $orderCheckInfo = $supervisionFinanceObj->orderSearch($orderId);
                    // 存管订单查询接口，调用失败
                    if ($orderCheckInfo['status'] == SupervisionBaseService::RESPONSE_FAILURE) {
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
                        case SupervisionBaseService::RESPONSE_FAILURE: // 失败
                        case SupervisionBaseService::RESPONSE_SUCCESS: // 成功
                            if (in_array($orderTypeId, [2, 3])) { // 余额划转的回调逻辑
                                $callbackRet = $supervisionFinanceObj->{$orderTypeNotifyMethod}($orderCheckInfo['data']['orderId'], $orderTypeConfig['businessType']);
                            } else {
                                $callbackRet = $supervisionFinanceObj->{$orderTypeNotifyMethod}($orderCheckInfo['data']);
                            }
                            if ($callbackRet['status'] !== SupervisionBaseService::RESPONSE_SUCCESS) {
                                throw new \Exception($orderTypeName . '-' . $callbackRet['respMsg']);
                            }
                            // 交易流水号
                            $outer_notice_sn = trim($orderCheckInfo['data']['orderId']);
                            break;
                        case SupervisionBaseService::RESPONSE_PROCESSING: // 处理中
                        case 'AS': // 处理中
                            throw new \Exception($orderTypeName . '-处理中');
                            break;
                        default:
                            throw new \Exception($orderTypeName . '-未知状态');
                            break;
                    }
                    break;
                case 5: // 还款
                case 6: // 流标
                    $orderInfo = $this->_getBusinessInfo($orderId, $orderTypeId);
                    if (empty($orderInfo)) {
                        throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
                    }

                    $supervisionFinanceObj = new SupervisionFinanceService();
                    $orderCheckInfo = $supervisionFinanceObj->batchOrderSearch($orderId, $orderTypeConfig['businessType']);
                    // 存管订单查询接口，调用失败
                    if ($orderCheckInfo['status'] == SupervisionBaseService::RESPONSE_FAILURE) {
                        throw new \Exception($orderTypeName . '-' . $orderCheckInfo['respMsg']);
                    }

                    $supervisionDealObj = new SupervisionDealService();
                    switch ($orderCheckInfo['data']['status'])
                    {
                        case SupervisionBaseService::RESPONSE_FAILURE: // 失败
                        case SupervisionBaseService::RESPONSE_SUCCESS: // 成功
                            $callbackRet = $supervisionDealObj->{$orderTypeNotifyMethod}($orderCheckInfo['data']);
                            if ($callbackRet['status'] !== SupervisionBaseService::RESPONSE_SUCCESS) {
                                throw new \Exception($orderTypeName . '-' . $callbackRet['respMsg']);
                            }
                            // 交易流水号
                            $outer_notice_sn = trim($orderCheckInfo['data']['orderId']);
                            break;
                        case SupervisionBaseService::RESPONSE_PROSESSING: // 处理中
                        case 'AS': // 处理中
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
            PaymentApi::log('存管系统-实时对账-业务补单成功，会员id['.$this->userId.']，订单号['.$orderId.']');
            return true;
        }catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            PaymentApi::log('存管系统-实时对账-业务补单失败，会员id['.$this->userId.']，订单号['.$orderId.'], msg: '.$e->getMessage());
        }
        return true;
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
                if ($data['withdraw_status'] == SupervisionWithdrawModel::WITHDRAW_STATUS_SUCCESS) {
                    throw new \Exception($orderTypeName . '-该订单已经提现成功');
                }
                // 订单状态已经失败
                if ($data['withdraw_status'] == SupervisionWithdrawModel::WITHDRAW_STATUS_FAILED) {
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



    public function alertMails()
    {
        return '';
        //return array('wangqunqiang@ucfgroup.com');
    }
}
