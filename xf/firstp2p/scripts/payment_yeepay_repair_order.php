<?php
/**
 * 易宝-充值补单脚本
 *
 * 通过易宝交易订单查询接口，核对订单状态并更新
 * 易宝订单状态(0:失败1:成功2:未处理3:处理中4:已撤销)
 * 
 * @example 5 * * * * /apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/payment_yeepay_repair_order.php >/dev/null 2>&1
 * 
 * @package     scripts
 * @author      guofeng3
 * @copyright   (c) 2016, Wxlc Corporation. All rights reserved.
 * @History:
 *     1.0.0 | guofeng3 | 2016-03-24 15:50:00 | initialization
 ********************************** 80 Columns *********************************
*/

ini_set('memory_limit', '2048M');
set_time_limit(0);
require_once(dirname(__FILE__) . '/../app/init.php');
use libs\utils\PaymentApi;
use core\service\PtpTaskClient;
use core\service\YeepayPaymentService;
use core\dao\PaymentNoticeModel;
use core\event\YeepayRepairOrderEvent;;

class PaymentYeepayRepairOrder {

    public function __construct()
    {
        // 支付方式ID
        $this->paymentId = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_YEEPAY)->getGateway()->getConfig('common', 'PAYMENT_ID');
    }

    public function run()
    {
        $yeepayPaymentService = new YeepayPaymentService();
        // 获取易宝补单重试列表中最小的订单ID
        $repairOrderResult = $yeepayPaymentService->getMinRepairOrderId();
        $minId = $repairOrderResult['minId'];
        // 查询[支付方式=易宝]、[支付状态=待支付]、[订单时间=指定时间内]的充值订单列表
        $sql = sprintf('SELECT id FROM firstp2p_payment_notice WHERE id >= %d AND payment_id = %d AND is_paid = %d', $minId, $this->paymentId, PaymentNoticeModel::IS_PAID_ING);
        $list = $GLOBALS['db']->get_slave()->getCol($sql);
        if (is_array($list) && !empty($list))
        {
            // 当前重试列表里面的订单ID
            $repairOrderRetryList = $repairOrderResult['repairOrderRetryList'];
            $repairList = array_unique(array_merge($list, $repairOrderRetryList));
            $loop = 0;
            $listCnt = count($repairList);
            PaymentApi::log('Yeepay_Repair_Order|Start_Insert_Into_Queue');
            foreach ($repairList as $orderId)
            {
                // 把需要补单的订单ID，放入重试列表
                $yeepayPaymentService->addMinRepairOrderId($orderId);

                // 加入通知队列通知业务方
                $event = new YeepayRepairOrderEvent($orderId);
                $taskObj = new PtpTaskClient();
                $taskId = $taskObj->register($event);
                if (empty($taskId))
                {
                    $eventData = get_object_vars($event);
                    PaymentApi::log('PaymentYeepayRepairOrder['.($loop+1).'/'.$listCnt.'] add-task failed.execute event:'.json_encode($eventData));
                    throw new \Exception('Yeepay_Repair_Order|Insert_Into_Queue is failed');
                }
                ++$loop;
                $taskObj->notify($taskId, 'domq_yeepay_repair_order');
                unset($event, $taskObj);
                PaymentApi::log('Yeepay_Repair_Order|Insert_Into_Queue_Success|TaskId:' . $taskId . '|orderId:' . $orderId);
            }
            unset($list, $yeepayPaymentService);
            PaymentApi::log('Yeepay_Repair_Order|End_Insert_Into_Queue');
        }else{
            PaymentApi::log('Yeepay_Repair_Order is empty');
        }
    }
}

// 同时仅允许一个脚本运行
$cmd = sprintf('ps aux | grep \'%s\' | grep -v grep | grep -v vim | grep -v %d', basename(__FILE__), posix_getpid());
$handle = popen($cmd, 'r');
$scriptCmd = fread($handle, 1024);
if ($scriptCmd) {
    exit("yeepay_repair_order is running!\n");
}

// 通过易宝交易订单查询接口，核对订单状态并更新
$obj = new PaymentYeepayRepairOrder();
$obj->run();
