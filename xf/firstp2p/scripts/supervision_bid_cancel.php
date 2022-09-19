<?php
/**
 * @desc  对于存管标的投资如果5分钟内未收到回调则取消投资
 * User: jinhaidong
 * Date: 2017-4-19 13:34:22
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\P2pDepositoryService;
use core\service\P2pIdempotentService;
use core\dao\JobsModel;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;
use libs\utils\Alarm;

class SupervisionBidCancel {

    const P2P_BID_EXCEPTION = 'supervision_order_bid_exception';
    const TIME_SENCONDS = 2400; // 处理过去40分钟还未处理订单,存管页面超时时间为35分钟

    public function run() {

        // 取过去x分钟未处理的订单
        $end5 = time() - self::TIME_SENCONDS;
        $begin5 = $end5 - self::TIME_SENCONDS;

        $bidData = $this->untreatedBid($begin5,$end5);

        if(empty($bidData)) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"没有需要处理的订单 startTime:{$begin5},endTime:{$end5}")));
            exit();
        }


        foreach ($bidData as $row) {
            $orderId = $row['order_id'];
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"开始处理超时订单 orderId:".$orderId)));

            try{
                $cancelS = new \core\service\P2pDealBidService();
                $cancRes = $cancelS->dealBidCancelRequest($orderId);

                // 保证一定要通知到
                if(!$cancRes){
                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"通知银行取消订单失败启动jobs继续通知 orderId:".$orderId)));

                    $function = '\core\service\P2pDealBidService::dealBidCancelRequest';
                    $param = array($orderId);
                    $job_model = new \core\dao\JobsModel();
                    $job_model->priority = 99;
                    $add_job = $job_model->addJob($function, $param,false,10);
                    if (!$add_job) {
                        throw new \Exception("投资取消通知银行jobs添加失败 orderId:".$orderId);
                    }
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"通知银行取消订单失加入jobs成功 orderId:".$orderId)));
                }
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"异常订单处理成功 orderId:".$row['order_id'])));
            }catch (\Exception $ex) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$ex->getMessage(),"order_id:".$row['order_id'])));
                Alarm::push(self::P2P_BID_EXCEPTION, '投资取消失败', 'orderId:'.$orderId);
                continue;
            }
        }
    }

    /**
     * 未处理的投资订单
     * @param $startTime
     * @param $endTime
     * @return array
     */
    private function untreatedBid($startTime,$endTime) {
        $bidTypeP2p = P2pDepositoryService::IDEMPOTENT_TYPE_BID; // p2p投资
        $bidTypeDT  = P2pDepositoryService::IDEMPOTENT_TYPE_DTBID; // 智多鑫投资
        $waitResult = P2pIdempotentService::RESULT_WAIT;

        $sql = "SELECT `order_id`,`load_id`,`type`,`result`,`status` FROM `firstp2p_supervision_idempotent`  WHERE `create_time` <=$endTime AND `create_time` >=$startTime AND (`type`={$bidTypeP2p} OR  `type` = {$bidTypeDT}) AND `result` = '{$waitResult}'";
        $rows = $GLOBALS['db']->get_slave()->getAll($sql);
        $orderData = array();

        foreach($rows as $row) {
            if($row['load_id']){
                Alarm::push(self::P2P_BID_EXCEPTION, '投资取消异常', 'orderId:'.$row['order_id']);
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"投资取消异常-已有投资记录ID不能取消 orderId:".$row['order_id'])));
                continue;
            }
            $orderData[] = $row;
        }
        return $orderData;
    }
}

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors' , 1);
set_time_limit(0);
ini_set('memory_limit', '1024M');

$obj = new SupervisionBidCancel();
$obj->run();