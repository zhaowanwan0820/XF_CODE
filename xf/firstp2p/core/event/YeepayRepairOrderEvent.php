<?php
/**
 * 易宝-充值补单服务
 * 
 * 通过易宝交易订单查询接口，核对订单状态并更新
 * 
 * @package     core\event
 * @author      guofeng3
 * @copyright   (c) 2016, Wxlc Corporation. All rights reserved.
 * @History:
 *     1.0.0 | guofeng3 | 2016-03-23 17:09:00 | initialization
 ********************************** 80 Columns *********************************
*/

namespace core\event;

use libs\utils\PaymentApi;
use libs\utils\Monitor;
use libs\utils\Alarm;
use core\event\BaseEvent;
use core\dao\PaymentNoticeModel;
use core\service\YeepayPaymentService;

/**
 * YeepayRepairOrderEvent
 * 通过易宝交易订单查询接口，核对订单状态并更新
 * 
 * @uses BaseEvent
 * @package default
 */
class YeepayRepairOrderEvent extends BaseEvent
{
    /**
     * 订单充值ID
     * @var int
     */
    private $orderId;

    /**
     * 订单充值编号
     * @var string
     */
    private $noticeSn;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * 请求支付接口
     */
    public function execute()
    {
        try {
            $yeepayPaymentService = new YeepayPaymentService();
            // 根据订单ID查询充值订单数据
            $orderData = PaymentNoticeModel::instance()->getInfoById($this->orderId);
            // 充值订单编号
            $this->noticeSn = isset($orderData['notice_sn']) ? $orderData['notice_sn'] : '';
            if (empty($orderData) || $orderData['status'] == PaymentNoticeModel::IS_PAID_SUCCESS
                || $orderData['status'] == PaymentNoticeModel::IS_PAID_FAIL)
            {
                // 订单状态是成功、失败时，从重试列表踢出
                $yeepayPaymentService->remRepairRetryIdByProcess($orderData['id']);
                $logMessage = sprintf('Yeepay_Query_Order has process|orderId:%d|has kick orderId of retryList', $orderData['id']);
                PaymentApi::log($logMessage);
                return true;
            }

            // 调用【易宝新投资通-4.6交易接口查询】
            $orderResult = $yeepayPaymentService->queryOrder(YeepayPaymentService::SEARCH_TYPE_BINDPAY, $orderData['notice_sn']);
            if (isset($orderResult['ret']) && $orderResult['ret'] === true)
            {
                if (isset($orderResult['respCode']) && $orderResult['respCode'] === '00')
                {
                    // 只处理易宝订单状态[失败]、[成功]、[已撤销]的数据(0:失败1:成功2:未处理3:处理中4:已撤销)
                    switch ($orderResult['data']['status'])
                    {
                        case YeepayPaymentService::YBPAY_STATUS_FAILURE: // 失败
                        case YeepayPaymentService::YBPAY_STATUS_SUCCESS: // 成功
                            $yeepayCallbackRet = $yeepayPaymentService->payYeepayChargeCallback($orderResult['data']);
                            $tips = 'failed';
                            // 处理成功后，从重试列表踢出
                            if (isset($yeepayCallbackRet['respCode']) && $yeepayCallbackRet['respCode'] === '00')
                            {
                                $yeepayPaymentService->remRepairRetryIdByProcess($orderData['id']);
                                $tips = 'success';
                            }
                            $logMessage = sprintf('Yeepay_Query_Order is %s|orderId:%d|yeepayCallbackRet：%s', $tips, $orderData['id'], json_encode($yeepayCallbackRet));
                            PaymentApi::log($logMessage);
                            break;
                        case YeepayPaymentService::YBPAY_STATUS_FAIL: // 系统异常
                        case YeepayPaymentService::YBPAY_STATUS_ING: // 处理中
                        case YeepayPaymentService::YBPAY_STATUS_ACCECPT: // 已接收
                        case YeepayPaymentService::YBPAY_STATUS_FAIL: // 系统异常
                        case YeepayPaymentService::YBPAY_STATUS_TIME_OUT: // 超时失败
                            break;
                        default:
                            throw new \Exception('Unknown_Yeepay_Order_Status');
                            break;
                    }
                }else if (isset($orderResult['respCode']) && $orderResult['respCode'] == 'TZ0200011') 
                {
                    // 订单不存在，从重试列表踢出
                    $yeepayPaymentService->remRepairRetryIdByProcess($orderData['id']);
                    // 充值订单表的备注字段(易宝的错误编号、错误消息)
                    $memo = (isset($orderResult['respCode']) && isset($orderResult['respMsg'])) ? $orderResult['respCode'] . '|' . $orderResult['respMsg'] : '订单不存在';
                    $GLOBALS['db']->update('firstp2p_payment_notice', array('memo'=>$memo, 'update_time'=>get_gmtime()), sprintf('id=%d AND is_paid IN (%d, %d)', $orderData['id'], PaymentNoticeModel::IS_PAID_NO, PaymentNoticeModel::IS_PAID_ING));
                    $logMessage = sprintf('Yeepay_Query_Order is not exist|orderId:%d|has kick orderId of retryList', $orderData['id']);
                    PaymentApi::log($logMessage);
                }
                return true;
            }else{
                // 添加监控-网络错误
                Monitor::add('YEEPAY_REPAIR_ORDER_FAILED');
            }
        }
        catch(\Exception $e)
        {
            // 添加监控
            Monitor::add('YEEPAY_REPAIR_ORDER_FAILED');
            // 告警
            $errorMsg = sprintf('Yeepay_Query_Order2 is exception|orderId:%d|ExceptionMsg:%s', $orderData['id'], $e->getMessage());
            Alarm::push('yeepay_repair_order', '易宝修复订单事件-异常', $errorMsg);
            PaymentApi::log($errorMsg);
        }
        return false;
    }

    public function alertMails() {
        return array('wangqunqiang@ucfgroup.com', 'guofeng3@ucfgroup.com');
    }
}
