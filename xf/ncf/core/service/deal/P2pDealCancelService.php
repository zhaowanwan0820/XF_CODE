<?php
/**
 * p2p存管 标的流标
 * 理财在流标时候添加Jobs 通知存管流标
 * 存管在流标处理成功后回调理财，理财继续以前的流标逻辑
 */
namespace core\service\deal;

use core\enum\SupervisionEnum;
use core\service\deal\DealService;
use core\service\deal\P2pDepositoryService;
use core\service\deal\P2pIdempotentService;
use core\service\duotou\DtDepositoryService;
use core\service\supervision\SupervisionDealService;
use core\dao\jobs\JobsModel;
use core\enum\P2pIdempotentEnum;
use core\enum\P2pDepositoryEnum;
use libs\utils\Logger;

class P2pDealCancelService extends P2pDepositoryService {

    /**
     * 标的流标--以JOBS方式启动
     * @param $orderId 订单ID
     * @param $dealId  标的ID
     */
    public function dealCancelRequest($orderId,$dealId) {
        $logParams = "流标通知银行: orderId:{$orderId},deal_id:{$dealId}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ ."," .$logParams);

        $dealService = new DealService();
        $isDT = $dealService->isDealDT($dealId);
        if($isDT){
            $dtCancSservice = new DtDepositoryService();
            $dtCancSservice->sendDtDealCancelRequest($orderId,$dealId);
        }

        $superDealService = new SupervisionDealService();
        $data = array(
            'order_id' => $orderId,
            'deal_id' => $dealId,
            'repay_id' => 0,
            'params' => '',
            'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_CANCEL,
            'status' => P2pIdempotentEnum::STATUS_SEND,
            'result' => P2pIdempotentEnum::RESULT_WAIT,
        );
        $params = array(
            'bidId' =>  $dealId,
            'rpDirect' => '02', // 流标红包到投资人账户
            'noticeUrl' =>app_conf('NOTIFY_DOMAIN').'/supervision/DealCancelNotify',
        );
        $supRes = $superDealService->dealCancel($params);
        if($supRes['status'] == SupervisionEnum::RESPONSE_SUCCESS) {

            Logger::info(__CLASS__ . ",". __FUNCTION__ .",流标通知银行成功 params:".json_encode($params));

            /**
             * 理财和网信之间的交互通过orderId来维护，然而SB支付却要求流标不传订单号
             * 为了保证 supervision_idempotent 表每个标的流标只有一条记录，需要在插入时候进行一次查询
             */

            $cancOrderId = P2pIdempotentService::getCancelOrderByDealId($dealId);
            if($cancOrderId){
                Logger::info(__CLASS__ . ",". __FUNCTION__ .",流标订单已存在 原始订单号 orderId:".$cancOrderId);
                return true;
            }
            $res =  P2pIdempotentService::addOrderInfo($orderId,$data);
            if(!$res){
                throw new \Exception("订单信息保存失败");
            }
            return true;
        }
        throw new \Exception("流标通知银行失败 dealId:{$dealId},errMsg:".$supRes['respMsg']);
    }

    /**
     * 流标回调
     * @param $orderId 订单ID
     * @param $dealId 标的ID
     * @param $status 回调状态 不接受失败
     * @return bool
     * @throws \Exception
     */
    public function dealCancelCallBack($dealId,$status) {
        $logParams = "dealId:{$dealId},status:{$status}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",流标回调," .$logParams);

        try{
            if($status == P2pDepositoryEnum::CALLBACK_STATUS_FAIL) {
                throw new \Exception("流标回调状态不接受失败");
            }

            $orderId = P2pIdempotentService::getCancelOrderByDealId($dealId);
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);

            if(!$orderInfo) {
                throw new \Exception("order_id不存在");
            }
            $dealId = $orderInfo['deal_id'];

            // 幂等处理
            if($orderInfo['status'] == P2pIdempotentEnum::STATUS_CALLBACK) {
                return true;
            }
        }catch (\Exception $ex) {
            Logger::error(__CLASS__ . ",". __FUNCTION__ . ",params:".$logParams.", errMsg:". $ex->getMessage());
            throw $ex;
        }

        try {
            $GLOBALS['db']->startTrans();
            $function = '\core\dao\deal\DealModel::failDeal';
            $param = array('deal_id' => $dealId);
            $res = JobsModel::instance()->addJob($function, $param);
            if(!$res) {
                throw new \Exception("流标jobs添加失败");
            }

            $orderData = array(
                'status' => P2pIdempotentEnum::STATUS_CALLBACK,
                'result' => P2pIdempotentEnum::RESULT_SUCC,
            );
            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($orderId,$orderData,P2pIdempotentEnum::RESULT_WAIT);
            if($affectedRows == 0){
                throw new \Exception("订单信息保存失败");
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . "流标失败,params:".$logParams.", errMsg:". $ex->getMessage());
            throw $ex;
        }
        Logger::error(__CLASS__ . ",". __FUNCTION__ . " 流标成功 dealId:".$dealId);
        return true;
    }
}
