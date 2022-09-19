<?php
/**
 * 存管订单服务类
 *
 * @date 2017-02-17
 * @author weiwei12@ucfgroup.com
 */

namespace core\service\supervision;

use libs\utils\PaymentApi;
use libs\utils\Alarm;
use libs\db\Db;
use libs\common\WXException;
use core\enum\SupervisionEnum;
use core\dao\supervision\SupervisionOrderModel;
use core\dao\jobs\JobsModel;
use core\service\supervision\SupervisionBaseService;
use core\enum\JobsEnum;

class SupervisionOrderService extends SupervisionBaseService
{
    //业务名字
    const SERVICE_RECHARGE = 'recharge';
    const SERVICE_WITHDRAW = 'withdraw';
    const SERVICE_SUPER_WITHDRAW = 'superWithdraw';
    const SERVICE_SUPER_RECHARGE = 'superRecharge';
    const SERVICE_ELEC_WITHDRAW = 'elecWithdraw';
    const SERVICE_ENTRUSTED_WITHDRAW = 'entrustedWithdraw';
    const SERVICE_INVEST_CREATE = 'investCreate';
    const SERVICE_DEAL_GRANT = 'dealGrant';
    const SERVICE_DEAL_REPAY = 'dealRepay';
    const SERVICE_DEAL_REPLACE_REPAY = 'dealReplaceRepay';
    const SERVICE_DEAL_RETURN_REPAY = 'dealReturnRepay';
    const SERVICE_DEAL_REPLACE_RECHARGE_REPAY = 'dealReplaceRechargeRepay';
    const SERVICE_GAINFEES = 'gainFees';
    const SERVICE_DT_BID = 'bookfreezeCreate';
    const SERVICE_DT_BATCH_BID = 'bookInvestBatchCreate';
    const SERVICE_DT_CREDITASSIGNGRANT = 'creditAssignmentBatchGrant';
    const SERVICE_DT_REDEEM = 'bookfreezeCancel';
    const SERVICE_AUTORECHARGE = 'autorecharge';
    const SERVICE_BATCH_TRANSFER = 'batchTransfer';
    const SERVICE_CREDIT_LOAN_WITHDRAW = 'creditLoanWithdraw';

    //节点
    const ORDER_NODE = 'orderNode';//订单号节点
    const AMOUNT_NODE = 'amountNode';//订单金额节点
    const BIZ_TYPE = 'bizType'; //订单业务类型
    const DEAL_ID_NODE = 'dealIdNode';// 标的id 可选
    const ORDER_LIST_NODE = 'orderListNode'; //订单列表节点
    const SUB_ORDER_NODE = 'subOrderNode';// 子订单节点
    const SUB_AMOUNT_NODE = 'subAmountNode';// 子订单金额节点
    const SUB_BIZ_TYPE = 'subBizType';// 子订单业务
    const SUB_USER_ID_NODE = 'subUserIdNode';// 子用户id 可选
    const USER_ID_NODE = 'userIdNode';// 用户id 可选
    //const PAYER_USER_ID_NODE = 'payerUserIdNode';// 付款用户id 可选
    //const FEE_USER_ID_NODE = 'feeUserId';// 收费用户id
    //const FEE_AMOUNT_NODE = 'feeAmount';// 收费金额

    //状态映射表
    public static $statusMap = [
        SupervisionEnum::NOTICE_SUCCESS => SupervisionOrderModel::ORDER_STATUS_SUCCESS,
        SupervisionEnum::NOTICE_FAILURE => SupervisionOrderModel::ORDER_STATUS_FAILURE,
        SupervisionEnum::NOTICE_PROCESSING => SupervisionOrderModel::ORDER_STATUS_PROCESSING,
        SupervisionEnum::NOTICE_CANCEL => SupervisionOrderModel::ORDER_STATUS_CANCEL,
    ];

    //节点映射表
    private static $nodeMap = [
        //充值到存管账户
        self::SERVICE_RECHARGE => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'amount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_RECHARGE,
            self::USER_ID_NODE => 'userId', //可选
        ],
        //存管账户提现到银行卡
        self::SERVICE_WITHDRAW => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'amount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_WITHDRAW,
            self::USER_ID_NODE => 'userId', //可选
        ],
        //存管账户划转至超级账户
        self::SERVICE_SUPER_WITHDRAW => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'amount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_SUPER_WITHDRAW,
            self::USER_ID_NODE => 'userId', //可选
        ],
        //超级账户划转至存管账户
        self::SERVICE_SUPER_RECHARGE => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'amount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_SUPER_RECHARGE,
            self::USER_ID_NODE => 'userId', //可选
        ],
        //提现至银信通电子账户
        self::SERVICE_ELEC_WITHDRAW => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'totalAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_ELEC_WITHDRAW,
            self::USER_ID_NODE => 'userId', //可选
        ],
        //受托提现
        self::SERVICE_ENTRUSTED_WITHDRAW => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'amount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_ENTRUSTED_WITHDRAW,
            self::DEAL_ID_NODE => 'bidId', //可选
            self::USER_ID_NODE => 'userId', //可选
        ],
        //投资
        self::SERVICE_INVEST_CREATE => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'totalAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_INVEST_CREATE,
            self::DEAL_ID_NODE => 'bidId', //可选
            self::USER_ID_NODE => 'userId', //可选
            self::ORDER_LIST_NODE => 'rpOrderList',
            self::SUB_ORDER_NODE => 'rpSubOrderId',
            self::SUB_AMOUNT_NODE => 'rpAmount',
            self::SUB_BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_RED_PACKETS,
            self::SUB_USER_ID_NODE => 'rpUserId', //可选
        ],
        //放款业务
        self::SERVICE_DEAL_GRANT => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'totalAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DEAL_GRANT,
            self::DEAL_ID_NODE => 'bidId', //可选
            self::USER_ID_NODE => 'userId', //可选
            self::ORDER_LIST_NODE => 'shareProfitOrderList',
            self::SUB_ORDER_NODE => 'subOrderId',
            self::SUB_AMOUNT_NODE => 'amount',
            self::SUB_BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_SHARE_PROFIT,
            self::SUB_USER_ID_NODE => 'receiveUserId', //可选
        ],
        //还款业务
        self::SERVICE_DEAL_REPAY => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'totalAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DEAL_REPAY,
            self::DEAL_ID_NODE => 'bidId', //可选
            self::USER_ID_NODE => 'payUserId', //可选
            self::ORDER_LIST_NODE => ['repayOrderList', 'chargeOrderList'],
            self::SUB_ORDER_NODE => ['subOrderId', 'chargeOrderId'],
            self::SUB_AMOUNT_NODE => ['amount', 'chargeAmount'],
            self::SUB_BIZ_TYPE => [SupervisionOrderModel::BIZ_TYPE_DEAL_REPAY, SupervisionOrderModel::BIZ_TYPE_DEAL_REPAY_FEE],
            self::SUB_USER_ID_NODE => ['receiveUserId', 'receiveUserId'], //可选
        ],
        //代偿业务
        self::SERVICE_DEAL_REPLACE_REPAY => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'totalAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DEAL_REPLACE_REPAY,
            self::DEAL_ID_NODE => 'bidId', //可选
            self::USER_ID_NODE => 'payUserId', //可选
            self::ORDER_LIST_NODE => ['repayOrderList', 'chargeOrderList'],
            self::SUB_ORDER_NODE => ['subOrderId', 'chargeOrderId'],
            self::SUB_AMOUNT_NODE => ['amount', 'chargeAmount'],
            self::SUB_BIZ_TYPE => [SupervisionOrderModel::BIZ_TYPE_DEAL_REPLACE_REPAY, SupervisionOrderModel::BIZ_TYPE_DEAL_REPAY_FEE],
            self::SUB_USER_ID_NODE => ['receiveUserId', 'receiveUserId'], //可选
        ],
        //还代偿业务
        self::SERVICE_DEAL_RETURN_REPAY => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'totalAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DEAL_RETURN_REPAY,
            self::DEAL_ID_NODE => 'bidId', //可选
            self::USER_ID_NODE => 'payUserId', //可选
            self::ORDER_LIST_NODE => 'repayOrderList',
            self::SUB_ORDER_NODE => 'subOrderId',
            self::SUB_AMOUNT_NODE => 'amount',
            self::SUB_BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DEAL_RETURN_REPAY,
            self::SUB_USER_ID_NODE => 'receiveUserId', //可选
        ],
        //代充值还款业务
        self::SERVICE_DEAL_REPLACE_RECHARGE_REPAY => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'totalAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DEAL_REPLACE_RECHARGE_REPAY,
            self::DEAL_ID_NODE => 'bidId', //可选
            self::USER_ID_NODE => 'payUserId', //可选
            self::ORDER_LIST_NODE => ['repayOrderList', 'chargeOrderList'],
            self::SUB_ORDER_NODE => ['subOrderId', 'chargeOrderId'],
            self::SUB_AMOUNT_NODE => ['amount', 'chargeAmount'],
            self::SUB_BIZ_TYPE => [SupervisionOrderModel::BIZ_TYPE_DEAL_REPLACE_RECHARGE_REPAY, SupervisionOrderModel::BIZ_TYPE_DEAL_REPAY_FEE],
            self::SUB_USER_ID_NODE => ['receiveUserId', 'receiveUserId'],
        ],
        //收费
        self::SERVICE_GAINFEES => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'totalAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_GAINFEES,
            self::DEAL_ID_NODE => 'bidId', //可选
            self::USER_ID_NODE => 'payUserId', //可选
            self::ORDER_LIST_NODE => 'repayOrderList',
            self::SUB_ORDER_NODE => 'subOrderId',
            self::SUB_AMOUNT_NODE => 'amount',
            self::SUB_BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_GAINFEES,
            self::SUB_USER_ID_NODE => 'receiveUserId', //可选
        ],
        // 智多鑫投资
        self::SERVICE_DT_BID => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'freezeSumAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DT_BID,
            self::USER_ID_NODE => 'userId',
            self::ORDER_LIST_NODE => 'rpOrderList',
            self::SUB_ORDER_NODE => 'rpSubOrderId',
            self::SUB_AMOUNT_NODE => 'rpAmount',
            self::SUB_BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_RED_PACKETS,
            self::SUB_USER_ID_NODE => 'rpUserId', //可选
        ],
        // 智多鑫批量投资，这里只写一条
        self::SERVICE_DT_BATCH_BID => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'totalAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DT_BID,
            self::USER_ID_NODE => 'userId',
        ],
        // 智多鑫债转
        self::SERVICE_DT_CREDITASSIGNGRANT => [
            self::ORDER_NODE => 'orderId',
            self::USER_ID_NODE => 'assigneeUserId', // 受让人
            self::AMOUNT_NODE => 'amount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DT_CREDITASSIGN,
            self::ORDER_LIST_NODE => 'creditOrderList',
            self::SUB_ORDER_NODE => 'subOrderId',
            self::SUB_AMOUNT_NODE => 'amount',
            self::SUB_BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DT_CREDITASSIGN,
            self::SUB_USER_ID_NODE => 'assignorUserId', //可选
        ],
        // 智多鑫赎回
        self::SERVICE_DT_REDEEM => [
            self::ORDER_NODE => 'orderId',
            self::USER_ID_NODE => 'userId',
            self::AMOUNT_NODE => 'amount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_DT_REDEEM,
            //self::FEE_USER_ID_NODE => 'feeUserId',
            //self::FEE_AMOUNT_NODE => 'feeAmount',
        ],
        // 存管自动扣款充值
        self::SERVICE_AUTORECHARGE => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE => 'amount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_AUTORECHARGE,
            self::USER_ID_NODE => 'userId', //可选
        ],
        // 批量收费返利红包
        self::SERVICE_BATCH_TRANSFER => [
            self::ORDER_NODE => 'orderId',
            self::ORDER_LIST_NODE   => 'subOrderList',
            self::SUB_ORDER_NODE    => 'subOrderId',
            self::SUB_AMOUNT_NODE   => 'amount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_TYPE_BATCH_TRANSFER,
            self::SUB_USER_ID_NODE  => 'payUserId',
        ],
        // 速贷提现
        self::SERVICE_CREDIT_LOAN_WITHDRAW => [
            self::ORDER_NODE => 'orderId',
            self::AMOUNT_NODE   => 'repayAmount',
            self::BIZ_TYPE => SupervisionOrderModel::BIZ_CREDIT_LOAN_WITHDRAW,
            self::USER_ID_NODE  => 'userId',
        ],
    ];

    /**
     * 生成存管订单 - 通用
     * @param string $service 服务名称
     * @param array $params 请求存管接口参数
     * @return boolean
     */
    public function addSupervisionOrder($service, $params) {
        PaymentApi::log(sprintf('%s | %s, params: %s, service: %s', __CLASS__, __FUNCTION__, json_encode($params), $service));
        if (empty($service) || !isset(self::$nodeMap[$service])) {
            PaymentApi::log(sprintf('%s | %s, Invalid service: %s, Please config', __CLASS__, __FUNCTION__, $service));
            return false;
        }
        //提取节点
        $nodeMap = self::$nodeMap[$service];
        $orderNode = $nodeMap[self::ORDER_NODE];//订单号节点
        $amountNode = $nodeMap[self::AMOUNT_NODE];//订单金额节点
        $bizType = $nodeMap[self::BIZ_TYPE]; //订单业务类型
        $dealIdNode = isset($nodeMap[self::DEAL_ID_NODE]) ? $nodeMap[self::DEAL_ID_NODE] : null; //标的ID  可选
        $userIdNode = isset($nodeMap[self::USER_ID_NODE]) ? $nodeMap[self::USER_ID_NODE] : null; //用户ID  可选

        $supervisionOrderModel = SupervisionOrderModel::instance();
        $outOrderId = $params[$orderNode];
        $dealId = !empty($dealIdNode) && isset($params[$dealIdNode]) ? $params[$dealIdNode] : 0; //可选
        $orderStatus = SupervisionOrderModel::ORDER_STATUS_WAITING; //订单状态默认0
        $userId = !empty($userIdNode) && isset($params[$userIdNode]) ? $params[$userIdNode] : 0; //可选
        if ($supervisionOrderModel->getInfoByOutOrderId($outOrderId)) {
            PaymentApi::log(sprintf('%s | %s, 订单已经添加, outOrderId: %s', __CLASS__, __FUNCTION__, $outOrderId));
            return true;
        }
        try{
            $db = Db::getInstance('firstp2p_payment');
            $db->startTrans();
            //添加主订单
            $parentId = $supervisionOrderModel->addOrder($outOrderId, $bizType, $params[$amountNode], $dealId, 0, $orderStatus, $userId);
            if (empty($parentId)) {
                throw new WXException('ERR_ADD_SUPERVISION_ORDER');
            }

            $orderListNode = isset($nodeMap[self::ORDER_LIST_NODE]) ? $nodeMap[self::ORDER_LIST_NODE] : null;//订单列表节点
            if (!empty($orderListNode)) {
                //提取子订单节点
                $subOrderNodeArr = is_array($nodeMap[self::SUB_ORDER_NODE]) ? $nodeMap[self::SUB_ORDER_NODE] : explode(',', $nodeMap[self::SUB_ORDER_NODE]);//子订单节点
                $subAmountNodeArr = is_array($nodeMap[self::SUB_AMOUNT_NODE]) ? $nodeMap[self::SUB_AMOUNT_NODE] : explode(',', $nodeMap[self::SUB_AMOUNT_NODE]); //子订单金额节点
                $subBizTypeArr = is_array($nodeMap[self::SUB_BIZ_TYPE]) ? $nodeMap[self::SUB_BIZ_TYPE] : explode(',', $nodeMap[self::SUB_BIZ_TYPE]); //子订单类型节点
                $subUserIdNode = isset($nodeMap[self::SUB_USER_ID_NODE]) ? $nodeMap[self::SUB_USER_ID_NODE] : []; //子用户ID  可选
                $subUserIdNodeArr = is_array($subUserIdNode) ? $subUserIdNode : explode(',', $subUserIdNode);

                //添加子订单
                $orderListNodeArr = is_array($orderListNode) ? $orderListNode : explode(',', $orderListNode);
                foreach ($orderListNodeArr as $index => $orderListNodeItem) {
                    //检查配置参数
                    if (!isset($subOrderNodeArr[$index]) || !isset($subAmountNodeArr[$index]) || !isset($subBizTypeArr[$index])) {
                        throw new WXException('ERR_PARAM');
                    }

                    $subOrderNodeItem = $subOrderNodeArr[$index];
                    $subAmountNodeItem = $subAmountNodeArr[$index];
                    $subBizTypeItem = $subBizTypeArr[$index];
                    $subUserIdNodeItem = isset($subUserIdNodeArr[$index]) ? $subUserIdNodeArr[$index] : null;

                    $orderList = !empty($params[$orderListNodeItem]) ? json_decode($params[$orderListNodeItem], true) : []; //解析列表json
                    foreach ($orderList as $subOrder) {
                        $outSubOrderId = $subOrder[$subOrderNodeItem];
                        $subUserId = !empty($subUserIdNodeItem) && isset($subOrder[$subUserIdNodeItem]) ? $subOrder[$subUserIdNodeItem] : 0; //可选

                        //子单号存在更新
                        if (!$supervisionOrderModel->getInfoByOutOrderId($outSubOrderId)) {
                            $result = $supervisionOrderModel->addOrder($outSubOrderId, $subBizTypeItem, $subOrder[$subAmountNodeItem], $dealId, $parentId, $orderStatus, $subUserId);
                        } else {
                            $result = $supervisionOrderModel->updateOrderData($outSubOrderId, $subBizTypeItem, $subOrder[$subAmountNodeItem], $dealId, $parentId, $orderStatus, $subUserId);
                        }
                        if (empty($result)) {
                            throw new WXException('ERR_ADD_SUPERVISION_ORDER');
                        }
                    }
                }
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            isset($db) && $db->rollback();
            PaymentApi::log(sprintf('%s | %s, errmsg: %s, service: %s, params', __CLASS__, __FUNCTION__, $e->getMessage(), $service, json_encode($params)));
            Alarm::push('supervision', 'AddOrderFailure', "Add supervision order failed. " . $e->getMessage());
            return false;
        }
    }

    /**
     * 更新订单
     * @param int $outOrderId
     * @param int $orderStatus
     * @return boolean
     */
    public function updateSupervisionOrder($outOrderId, $orderStatus) {
        PaymentApi::log(sprintf('%s | %s, outOrderId: %s, orderStatus: %s', __CLASS__, __FUNCTION__, $outOrderId, $orderStatus));
        try {
            $supervisionOrderModel = SupervisionOrderModel::instance();
            $supervisionOrder = $supervisionOrderModel->getInfoByOutOrderId($outOrderId);
            //检查订单
            if (!$supervisionOrder) {
                throw new WXException('ERR_OUT_ORDER_NOT_EXIST');
            }
            if ($supervisionOrder['order_status'] == $orderStatus) {
                PaymentApi::log(sprintf('%s | %s, 订单已经处理, outOrderId: %s, orderStatus: %s', __CLASS__, __FUNCTION__, $outOrderId, $orderStatus));
                return true;
            }
            // 订单状态已终态
            if (in_array($supervisionOrder['order_status'], [SupervisionOrderModel::ORDER_STATUS_SUCCESS, SupervisionOrderModel::ORDER_STATUS_FAILURE])) {
                PaymentApi::log(sprintf('%s | %s, 订单已经终态,不能重复处理, outOrderId: %s, orderStatus: %s, orderStatusDb: %d', __CLASS__, __FUNCTION__, $outOrderId, $orderStatus, $supervisionOrder['order_status']));
                return true;
            }
            $db = Db::getInstance('firstp2p_payment');
            $db->startTrans();
            if (!$supervisionOrderModel->updateOrder($outOrderId, $orderStatus)) {
                throw new WXException('ERR_ERR_SUPERVISION_ORDER_UPDATE_FAILED');
            }
            //更新子单的状态
            if ($supervisionOrderModel->getInfoByPid($supervisionOrder['id'])) {
                if (!$supervisionOrderModel->updateOrderByPid($supervisionOrder['id'], $orderStatus)) {
                    throw new WXException('ERR_ERR_SUPERVISION_ORDER_UPDATE_FAILED');
                }
            }
            $db->commit();
            return true;
        } catch (\Exception $e) {
            isset($db) && $db->rollback();
            PaymentApi::log(sprintf('%s | %s, errmsg: %s, outOrderId: %s, orderStatus: %s', __CLASS__, __FUNCTION__, $e->getMessage(), $outOrderId, $orderStatus));
            Alarm::push('supervision', 'UpdateOrderFailure', $e->getMessage());
            return false;
        }
    }

    /**
     * 按标更新订单
     * @param int $outOrderId
     * @param int $orderStatus
     * @return boolean
     */
    public function updateSupervisionOrderByDealId($dealId, $orderStatus) {
        PaymentApi::log(sprintf('%s | %s, dealId: %s, orderStatus: %s', __CLASS__, __FUNCTION__, $dealId, $orderStatus));
        try {
            $supervisionOrderModel = SupervisionOrderModel::instance();
            $supervisionOrderList = $supervisionOrderModel->getListByDealId($dealId);
            //检查订单
            if (!$supervisionOrderList) {
                PaymentApi::log(sprintf('%s | %s, 标的下没有订单, dealId: %s, orderStatus: %s', __CLASS__, __FUNCTION__, $dealId, $orderStatus));
                return true;
            }

            $flag = true;
            foreach ($supervisionOrderList as $supervisionOrder) {
                if ($orderStatus == SupervisionOrderModel::ORDER_STATUS_CANCEL) {
                    if ($supervisionOrder['order_status'] == SupervisionOrderModel::ORDER_STATUS_SUCCESS && $supervisionOrder['order_status'] != $orderStatus) {
                        $flag = false;
                        break;
                    }
                } else {
                    if ($supervisionOrder['order_status'] != $orderStatus) {
                        $flag = false;
                        break;
                    }
                }
            }
            if ($flag) {
                PaymentApi::log(sprintf('%s | %s, 订单已经处理, dealId: %s, orderStatus: %s', __CLASS__, __FUNCTION__, $dealId, $orderStatus));
                return true;
            }
            if (!$supervisionOrderModel->updateOrderByDealId($dealId, $orderStatus)) {
                throw new WXException('ERR_ERR_SUPERVISION_ORDER_UPDATE_FAILED');
            }
            return true;
        } catch (\Exception $e) {
            PaymentApi::log(sprintf('%s | %s, errmsg: %s, dealId: %s, orderStatus: %s', __CLASS__, __FUNCTION__, $e->getMessage(), $dealId, $orderStatus));
            Alarm::push('supervision', 'UpdateOrderFailure', $e->getMessage());
            return false;
        }
    }

    /**
     * 异步添加存管订单
     * @throws \Exception
     * @param integer $service 服务名称
     * @param array $params 请求存管接口参数
     * @return boolean
     */
    public function asyncAddOrder($service, $params) {
        $jobs_model = new JobsModel();
        $function = '\core\service\supervision\SupervisionOrderService::addSupervisionOrder';
        $param = array($service, $params);
        $jobs_model->priority = JobsEnum::PRIORITY_SUPERVISION_ORDER;
        $r = $jobs_model->addJob($function, $param);
        if ($r === false) {
            throw new WXException('ERR_ASYNC_ADD_SUPERVISION_ORDER');
        }
        return true;
    }

    /**
     * 异步更新存管订单
     * @throws \exception
     * @param int $outOrderId 外部订单号
     * @param string $status 订单状态 SFIN
     * @return boolean
     */
    public function asyncUpdateOrder($outOrderId, $status) {
        $jobs_model = new JobsModel();
        $function = '\core\service\supervision\SupervisionOrderService::updateSupervisionOrder';
        $orderStatus = isset(self::$statusMap[$status]) ? self::$statusMap[$status] : 0;
        $param = array($outOrderId, $orderStatus);
        $jobs_model->priority = JobsEnum::PRIORITY_SUPERVISION_ORDER;
        $r = $jobs_model->addJob($function, $param);
        if ($r === false) {
            throw new WXException('ERR_ASYNC_UPDATE_SUPERVISION_ORDER');
        }
    }

    /**
     * 按标异步更新存管订单
     * @throws \exception
     * @param int $dealId 标id
     * @param string $status 订单状态 SFIN
     * @return boolean
     */
    public function asyncUpdateOrderByDealId($dealId, $status) {
        $jobs_model = new JobsModel();
        $function = '\core\service\supervision\SupervisionOrderService::updateSupervisionOrderByDealId';
        $orderStatus = isset(self::$statusMap[$status]) ? self::$statusMap[$status] : 0;
        $param = array($dealId, $orderStatus);
        $jobs_model->priority = JobsEnum::PRIORITY_SUPERVISION_ORDER;
        $r = $jobs_model->addJob($function, $param);
        if ($r === false) {
            throw new WXException('ERR_ASYNC_UPDATE_SUPERVISION_ORDER');
        }
    }
}
