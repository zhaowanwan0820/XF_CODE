<?php
/**
 * 智多新存管相关service
 *
 * @author jinhaidong
 * @date 2017-6-20 12:47:58
 */

namespace core\service;

use core\dao\IdempotentModel;
use libs\utils\Logger;
use libs\utils\Rpc;
use libs\utils\Alarm;
use core\dao\JobsModel;
use core\dao\DealModel;
use core\dao\UserModel;
use core\service\P2pIdempotentService;
use core\service\P2pDepositoryService;
use core\service\DtPaymenyService;
use core\service\DealService;
use core\service\DtDealService;
use core\dao\DealAgencyModel;
use core\service\IdempotentService;
use core\service\SupervisionOrderService; // 存管对账

use NCFGroup\Common\Library\Idworker;
use \NCFGroup\Protos\Duotou\RequestCommon;

class DtDepositoryService extends P2pDepositoryService {

    const DT_TRANS_PAGESIZE = 100;// 智多新债转每次最大条数

    private  $rpc;
    private  $request;
    private  $sv;

    public function __construct(){
        $this->rpc = new Rpc('duotouRpc');
        $this->request = new RequestCommon();
        $this->sv = new DtPaymenyService();
    }

    /**
     * 向智多新发送还款请求
     * @param $orderId
     * @param $repayData
     * @return bool
     * @throws \Exception
     */
    public function sendDtRepayRequest($orderId,$repayData) {
        $params = "orderId:{$orderId},repayData:".json_encode($repayData);
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",还款通知智多新 params:" .$params);

        $vars = array(
            'p2pDealId' => $repayData['dealId'],
            'dealRepayId' => $orderId,
            'principal' => $repayData['principal'],
            'interest' => $repayData['interest'],
            'isLast' => $repayData['isLast'],
        );
        $this->request->setVars($vars);

        $response = $this->rpc->go('\NCFGroup\Duotou\Services\DealRepay', "repayDeal", $this->request);

        if(!$response || $response['data'] === false){
            Alarm::push(self::ALARM_DT_DEPOSITORY,'还款通知智多新失败'," orderId:".$orderId);
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",还款通知智多新失败 orderId:" .$orderId." vars:".json_encode($vars));
            throw new \Exception("还款通知智多新失败");
        }
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",还款通知智多新成功 orderId:" .$orderId);

        $data = array(
            'order_id' => $orderId,
            'deal_id' => $repayData['dealId'],
            'repay_id' => isset($repayData['repayId']) ? $repayData['repayId'] : 0,
            'prepay_id' => isset($repayData['prepayId']) ? $repayData['prepayId'] : 0,
            'money' => $repayData['money'],
            'params' => addslashes(json_encode($repayData)),
            'type' => self::IDEMPOTENT_TYPE_DTREPAY,
            'status' => P2pIdempotentService::STATUS_SEND,
            'result' => P2pIdempotentService::RESULT_WAIT,
        );

        $res = P2pIdempotentService::saveOrderInfo($orderId,$data);
        if($res === false){
            throw new \Exception("订单信息保存失败");
        }
        return true;
    }

    /**
     * 智多新还款回调
     * @param $orderId
     * @return bool
     * @throws \Exception
     */
    public function dtRepayCallBack($orderId,$manageId) {
        Logger::info(__CLASS__ . ",". __FUNCTION__ ."," ."智多新还款回调 orderId:{$orderId}");

        try{
            // 判断订单有效性
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if(!$orderInfo) {
                throw new \Exception("order_id不存在");
            }

            // 幂等处理
            if($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK) {
                return true;
            }

            $manageInfo = DealAgencyModel::instance()->getDealAgencyById($manageId);
            if(!$manageInfo || !$manageInfo->user_id) {
                throw new \Exception("管理机构未设置 orderId:{$orderId},manageId:{$manageId}");
            }
        }catch (\Exception $ex) {
            Alarm::push(self::ALARM_DT_DEPOSITORY,'智多新还款回调失败'," orderId:{$orderId}, 错误信息:".$ex->getMessage());
            Logger::error(__CLASS__ . ",". __FUNCTION__ . ",orderId:".$orderId.", errMsg:". $ex->getMessage());
            throw $ex;
        }

        Logger::info(__CLASS__ . ",". __FUNCTION__ . ",智多新还款回调开始事务处理还款逻辑, orderId:".$orderId);
        try {
            $job_model = new JobsModel();

            $GLOBALS['db']->startTrans();
            $function = '\core\service\DtDepositoryService::dtBankRepayRequest';
            $newOrderId = Idworker::instance()->getId();
            $job_model->priority = JobsModel::PRIORITY_DTB_REPAY_BANK;
            $res = $job_model->addJob($function, array($orderId,$newOrderId,$manageInfo->user_id));
            if ($res === false) {
                throw new \Exception("智多新还款通知银行加入jobs失败");
            }

            $orderData = array(
                'status' => P2pIdempotentService::STATUS_CALLBACK,
                'result' => P2pIdempotentService::RESULT_SUCC,
            );

            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($orderId,$orderData,P2pIdempotentService::RESULT_WAIT);
            if($affectedRows == 0){
                throw new \Exception("订单信息保存失败");
            }

            // 保存智多新还款到firstp2p_idempotent 方便后续验证
            $source = IdempotentModel::SOURCE_DTDEPOSITORY_REPAY;
            $orderParams = json_decode($orderInfo['params'],true);
            $data = array(
                'orderId' => $orderId,
                'dealId' => $orderInfo['deal_id'],
                'manageUserId' =>$manageInfo->user_id,
                'repayType' =>$orderParams['repayType'],
            );
            $res = IdempotentService::saveToken($newOrderId,$data,$source);
            if(!$res){
                throw new \Exception("智多新订单信息idempoten保存失败");
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " ". $ex->getMessage());
            Alarm::push(self::ALARM_DT_DEPOSITORY,'智多新还款回调失败'," orderId:{$orderId}, 错误信息:".$ex->getMessage());
            throw $ex;
        }
        return true;
    }

    /**
     * 智多新还款通知银行
     * @param $orderId
     * @param $newOrderid 还款时候的唯一订单ID
     * @return bool
     * @throws \Exception
     */
    public function dtBankRepayRequest($orderId,$newOrderId,$manageUserId){
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if(!$orderInfo){
            throw new \Exception("订单信息不存在 orderId:".$orderId);
        }

        $vars = array(
            'p2pDealId' => $orderInfo['deal_id'],
            'orderId' => $orderId,
            'manageUserId' => $manageUserId,
        );
        $this->request->setVars($vars);
        $response = $this->rpc->go('\NCFGroup\Duotou\Services\DealRepayDetail', "getRepayDetail", $this->request);

        if(!$response || $response['data'] === false){
            throw new \Exception("智多新还款数据拉取异常");
        }

        $totalPrincipal = $response['data']['totalPrincipal'];
        $totalInterest = $response['data']['totalInterest'];
        $repayOrderList = $response['data']['list']; // 智多新还款数据

        foreach($repayOrderList as $key=>$val){
            if($val['type'] == 'ZDXGLF'){
                $repayOrderList[$key]['type'] = 'I';
            }
        }

        $repayOrderCount = count($repayOrderList);

        //$orderInfo['params'] = stripslashes($orderInfo['params']);
        $params = json_decode($orderInfo['params'],true);

        $repayType = $params['repayType'];
        $requestData = $params['requestData'];
        $repayParams = $params['repayParams'];
        $repayFeeOrderList = json_decode($requestData['repayOrderList'],true);
        $repayFeeOrderList = is_array($repayFeeOrderList) ? $repayFeeOrderList : array();

        $totalMoney = bcadd($totalPrincipal,$totalInterest,2);
        $requestData['totalNum']+=$repayOrderCount; // 还款总数量
        $requestData['orderId'] = $newOrderId; // 此处进行orderId替换 因为幂等表orderId是唯一的
        if(bccomp($totalMoney,$orderInfo['money'],2) != 0){
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " 智多新还款金额与实际还款金额不一致 totalMoney:{$totalMoney},orderInfoMoney:".$orderInfo['money'] );
            throw new \Exception("智多新还款金额与实际还款金额不一致");
        }

        $requestData['repayOrderList'] = array_merge($repayFeeOrderList,$repayOrderList);
        $requestData['repayOrderList'] = json_encode($requestData['repayOrderList']);
        $opType = $params['repayOpType'];
        $repayId = !empty($orderInfo['repay_id']) ? $orderInfo['repay_id'] : $orderInfo['prepay_id'];

        $repayService = new \core\service\P2pDealRepayService();
        return $repayService->sendRepayRequest($newOrderId,$orderInfo['deal_id'],$repayType,$opType,$repayId,$requestData,$repayParams);
    }

    /**
     * 智多新赎回
     * @param $orderId
     * @param $userId
     * @param $amount
     * @param $feeAmount
     * @param $feeUserId
     */
    public function dtRedeemRequest($orderId,$userId,$amount,$feeAmount,$feeUserId){
        $logParams="orderId:{$orderId},userId:{$userId},amount:{$amount},feeAmount:{$feeAmount},feeUserId:{$feeUserId}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",智多新赎回通知银行 logParams:".$logParams);
        $params = array(
            'orderId' => $orderId,
            'userId' => $userId,
            'unFreezeType' => '01',
            'amount' => bcmul($amount,100),
        );
        if(bccomp($feeAmount,'0.00',2) == 1){
            $params['feeAmount'] = bcmul($feeAmount,100);
            $params['feeUserId'] = $feeUserId;
        }

        $sendRes = $this->sv->bookfreezeCancel($params);
        if($sendRes['status'] == \core\service\SupervisionBaseService::RESPONSE_SUCCESS) {
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",智多新赎回通知银成功");
            return true;
        }
        Logger::error(__CLASS__ . ",". __FUNCTION__ .",智多新赎回通知银行失败 logParams:".$logParams." errMsg:".$sendRes['respMsg']);
        throw new \Exception("智多新赎回通知银行失败 errMsg:".$sendRes['respMsg']);
    }

    /**
     * 智多新流标
     *  1、通知智多新
     *  2、通知存管行取消投资
     *  3、通知存管行预约冻结
     * @param $dealId
     */
    public function sendDtDealCancelRequest($orderId,$dealId){
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",流标通知智多新 orderId:".$orderId." dealId:{$dealId}");
        $vars = array(
            'p2pDealId' => $dealId,
            'orderId' => $orderId,
        );
        $this->request->setVars($vars);
        $response = $this->rpc->go('\NCFGroup\Duotou\Services\DealFail', "failDeal", $this->request);
        if(!$response || $response['data'] === false){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",流标通知智多新失败 orderId:".$orderId." dealId:{$dealId}");
            throw new \Exception("流标通知智多新失败");
        }
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",流标通知智多新成功 orderId:".$orderId." dealId:{$dealId}");
        return true;
    }


    /**
     * 智多新存管投资接口
     *  -- 目前智多新无法批量发送请求、仅支持单条发送
     * @param $batchId
     * @param $subOrderId
     * @param $userId
     * @param $dealId
     * @param $money
     */
    public function sendDtBidRequest($orderId,$userId,$dealId,$money,$otherBidParams=array()){
        $logParams = "$orderId:{$orderId},userId:{$userId},dealId:{$dealId},money:{$money}";
        $subInvestOrderList[] = array(
            'subInvestOrderId' => $orderId,
            'bidId'=> $dealId,
            'subInvestAmount' => bcmul($money,100)
        );
        $requestData = array(
            'orderId' => $orderId,
            'userId' => $userId,
            'currency' => 'CNY',
            'totalAmount' => bcmul($money,100),
            'subInvestOrderList' => json_encode($subInvestOrderList),
        );

        Logger::info(__CLASS__ . ",". __FUNCTION__ .",知智多投资通知银行 logParams:".$logParams);

        try{
            $sendRes = $this->sv->bookInvestBatchCreate($requestData);
            if($sendRes['status'] !== \core\service\SupervisionBaseService::RESPONSE_SUCCESS){
                throw new \Exception("智多新投资通知银行失败");
            }
            $params = array('dtParams'=>$otherBidParams);
            $data = array(
                'order_id' => $orderId,
                'deal_id' => $dealId,
                'loan_user_id' => $userId,
                'money' => $money,
                'params' => json_encode($params),
                'type' => self::IDEMPOTENT_TYPE_DTP2PBID,
                'status' => P2pIdempotentService::STATUS_SEND,
                'result' => P2pIdempotentService::RESULT_WAIT,
            );
            $res = P2pIdempotentService::addOrderInfo($orderId,$data);
            if($res === false){
                throw new \Exception("订单信息保存失败");
            }
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",logParams".$logParams. " errMsg:".$ex->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 智多新投资银行回调
     * @param $orderId
     * @param $status
     * @return bool
     */
    public function dtBidCallBack($orderId,$status) {
        $logParams = "orderId:{$orderId},status:{$status}";
        Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . $logParams);

        
        $dbStartTrans = false;
        try {

            // 判断订单有效性
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if (!$orderInfo) {
                throw new \Exception("order_id不存在");
            }

            $dealId = $orderInfo['deal_id'];
            $deal = DealModel::instance()->find($dealId);
            if (!$deal) {
                throw new \Exception("标的信息不存在 deal_id:" . $dealId);
            }

            if ($status == self::CALLBACK_STATUS_FAIL) {
                // 智多新底层投资时支持失败，如果失败使用补单脚本resubmit_order.php 处理
                if($orderInfo['status'] == P2pIdempotentService::STATUS_INVALID){
                    return true;
                }
                throw new \Exception("智多新投资回调不接受失败状态");
            }

            // 幂等处理
            if ($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK) {
                return true;
            }

            $dealService = new DealService();

            $GLOBALS['db']->startTrans();
            $dbStartTrans = true;
            $user = UserModel::instance()->find($orderInfo['loan_user_id']);
            $bizToken = array('dealId'=>$dealId);
            $user->changeMoneyDealType = $dealService->getDealType($deal);

            $res = $user->changeMoney(-$orderInfo['money'], '智多鑫-转入本金解冻', "编号 {$dealId}",0, 0,UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
            if(!$res) {
                throw new \Exception("投资底层资产前解冻失败: orderId:{$orderId},user_id:{$user['id']},money:{$orderInfo['money']}");
            }

            // 启动jobs处理理财投资
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsModel::PRIORITY_DTB_CALLBACK_BID;
            $r = $jobs_model->addJob('\core\service\DtDepositoryService::dtBidAfterBankCallBack', array('orderId'=>$orderId));
            if($r === false){
                throw new \Exception("添加JOBS失败");
            }


            $res = P2pIdempotentService::updateStatusByOrderId($orderId,P2pIdempotentService::STATUS_CALLBACK);
            if(!$res){
                throw new \Exception("订单信息更新失败");
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $ex) {
            if($dbStartTrans === true){
                $GLOBALS['db']->rollback();
            }
            Alarm::push(self::ALARM_DT_DEPOSITORY, '智多新投资回调失败', " params:{$logParams}, 错误信息:" . $ex->getMessage());
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",params:" . $logParams . ", errMsg:" . $ex->getMessage());
            throw new \Exception($ex->getMessage());
        }
        return true;
    }


    /**
     * 智多新单独投资底层资产逻辑(略过存管投资) JOBS 方式执行
     * @param $orderId
     * @return bool
     * @throws \Exception
     */
    public function dtBidAfterBankCallBack($orderId){
        $dl_service = new DealLoadService();
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        $userId = $orderInfo['loan_user_id'];
        $money = $orderInfo['money'];
        $deal = DealModel::instance()->find($orderInfo['deal_id']);
        $coupon_id = '';
        $site_id = 1;
        $jforder_id = false;
        $discount_id = '';
        $discount_type = 1;
        $optionParams=array(
            'orderInfo' => $orderInfo,
        );
        $res = $dl_service->bid($userId, $deal, $money, $coupon_id ,\core\dao\DealLoadModel::$SOURCE_TYPE['dtb'],$site_id,$jforder_id,$discount_id,$discount_type,$optionParams);
        if($res['error'] == true) {//投标失败
            throw new \Exception("投标失败 orderId:{$orderId}");
        }
        return true;
    }

    /**
     * 智多新匹配完成回调
     * @param $orderId
     * @param $tableNum
     * @param $date
     */
    public function dtMappingFinishCallBack($orderId,$tableNum,$date){
        $logParams = "orderId:{$orderId},tableNum:{$tableNum},date:{$date}";
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",智多新匹配完成回调 params::" . $logParams);


        $vars = array(
            'date' => $date,
            'isRedeem' => 1,
        );
        // 债转每个表总数量
        $tableIndexArr = array();

        for($i=0;$i<$tableNum;$i++){
            $vars['tableIndex'] = $i;
            $this->request->setVars($vars);
            $response = $this->rpc->go('\NCFGroup\Duotou\Services\LoanMappingContract', "getMappingInvestCount", $this->request);
            if(!$response || $response['data'] === false){
                throw new \Exception("智多新拉取债转分页数量失败");
            }else{
                $tableIndexArr[$i] = $response['data']['totalNum'];
            }
        }
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",智多新获取债转分页数据 tableIndexArr:" . json_encode($tableIndexArr));

        $jobModel = new JobsModel();
        $function = '\core\service\DtDepositoryService::getBatchDtTransBondData';
        $jobModel->priority = JobsModel::PRIORITY_DTB_GET_TRANSDATA;

        try{
            $GLOBALS['db']->startTrans();

            foreach($tableIndexArr as $tableIndex => $tableTotalNum){
                $pageTotal = ceil($tableTotalNum/self::DT_TRANS_PAGESIZE);
                for($pageNum=1;$pageNum<=$pageTotal;$pageNum++){
                    $orderId = Idworker::instance()->getId();
                    $res = $jobModel->addJob($function, array($orderId,$tableIndex,$date,$pageNum,self::DT_TRANS_PAGESIZE));
                    if ($res === false) {
                        throw new \Exception("智多新拉取债转数据加入JOBS失败");
                    }
                }
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",智多新匹配完成回调失败 params::" . $logParams . ", errMsg:" . $ex->getMessage());
            return false;
        }

        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",智多新匹配完成回调拉取jobs生成成功 params::" . $logParams);
        return true;
    }

    /**
     * 批量债转接口 向银行发送债转数据
     * @param $orderId
     * @param $requestData
     * @return bool
     * @throws \Exception
     */
    public function sendDtTransBondRequest($orderId,$requestData,$tableIndex,$date){
        $requestData['currency'] = 'CNY';
        $requestData['orderId'] = $orderId;

        $logParams = "orderId:{$orderId},$requestData:".json_encode($requestData).",tableIndex:{$tableIndex},date:{$date}";
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",智多新向银行发送债转数据 params:" . $logParams);

        if(count($requestData['creditOrderList']) != $requestData['totalNum']){
            Alarm::push(self::ALARM_DT_DEPOSITORY,'智多新债转失败--数据不一致'," orderId:{$orderId}");
            throw new \Exception("债转数据数量与子订单数据不一致");
        }

        if($requestData['dealTotalAmount'] == 0 || $requestData['totalNum'] ==0 ){
            Logger::info(__CLASS__ . ",". __FUNCTION__ ."," ."智多新债转数据为空 orderId:{$orderId}");
            return true;
        }

        $creditOrderList = $requestData['creditOrderList'];
        $requestData['creditOrderList'] = json_encode($requestData['creditOrderList']);

        $sendRes = $this->sv->creditAssignmentBatchGrant($requestData);
        if($sendRes['status'] !== \core\service\SupervisionBaseService::RESPONSE_SUCCESS){
            Alarm::push(self::ALARM_DT_DEPOSITORY,'智多新债转--请求银行失败'," orderId:{$orderId} errMsg:".$sendRes['respMsg']);
            throw new \Exception("智多新债转通知银行失败 orderId:".$orderId." errMsg:".$sendRes['respMsg']);
        }

        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",智多新向银行发送债转数据成功 orderId:" . $orderId);

        try{
            $GLOBALS['db']->startTrans();

            foreach($creditOrderList as $key=>$val){
                $token = $val['subOrderId'];
                //检查订单是否存在，存在情况是产生多个jobs任务，如果存在一个订单id ，说业务做过了
                if(IdempotentService::hasExists($token)){
                    break;
                }
                $data = array(
                    'orderId'=>$orderId,
                    'tableIndex' => $tableIndex,
                    'date' =>$date,
                    'money'=>$val['amount'],
                    'redeemUserId' => $val['assignorUserId'],//出让人
                    'userId' => $val['assigneeUserId'],//受让人
                    'dealId'=>$val['bidId'],
                );

                $source = IdempotentModel::SOURCE_DTDEPOSITORY_REDEEM;
                $res = IdempotentService::saveToken($token,$data,$source);
                if(!$res){
                    throw new \Exception("债转子订单保存失败");
                }
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",orderId:" . $orderId." errMsg:".$ex->getMessage());
            throw $ex;
        }
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",智多新债转数据保存成功 orderId:" . $orderId);
        return true;
    }

    /**
     * 存管债转回调
     *  -- 无法处理失败状态 一旦发现失败报警人工处理
     * @param $orderId
     * @param $status
     * @param $failReason
     */
    public function dtTransBondCallBack($orderId,$amount,$status){
        Logger::info(__CLASS__ . ",". __FUNCTION__ ."," ."智多新债转回调 orderId:{$orderId},amount:{$amount},status:".$status);

        if ($status == self::CALLBACK_STATUS_FAIL) {
            Alarm::push(self::ALARM_DT_DEPOSITORY,'智多新债转回调失败'," orderId:{$orderId}");
            throw new \Exception("智多新债转回调失败 orderId:{$orderId}");
        }

        $orderInfo = IdempotentService::getTokenInfo($orderId);
        if(!$orderInfo){
            // TODO 报警太多，暂时拼比，后期改为先落单
            //Alarm::push(self::ALARM_DT_DEPOSITORY,'智多新债转回调订单不存在'," orderId:{$orderId}");
            throw new \Exception("智多新债转回调订单不存在 orderId:{$orderId}");
        }
        if($orderInfo['status'] == IdempotentModel::STATUS_SUCCESS){
            return true;
        }

        $orderParams = $orderInfo['data'];

        $userId = $orderParams['userId'];
        $redeemUserId = $orderParams['redeemUserId'];
        $p2pDealId = $orderParams['dealId'];
        $money = intval($orderParams['money']);
        $amount = intval($amount);

        if($money !== $amount){
            Alarm::push(self::ALARM_DT_DEPOSITORY,'智多新债转回调金额不匹配'," orderId:{$orderId}");
            Logger::error(__CLASS__ . ",". __FUNCTION__ ."," ."智多新债转回调金额不匹配 orderId:{$orderId},amount:{$amount},status:".$status);
            throw new \Exception("智多新债转回调金额不匹配 orderId:{$orderId}");
        }

        try{
            $dtDealService = new DtDealService();
            $GLOBALS['db']->startTrans();

            // 这种方式会在存管并发调用的时候造成资金记录重复
            //$res = IdempotentService::updateStatusByToken($orderId,IdempotentModel::STATUS_SUCCESS);
            $res = IdempotentService::updateStatusFromOriStatus($orderId,IdempotentModel::STATUS_SUCCESS,IdempotentModel::STATUS_WAIT);
            if(!$res){
                throw new \Exception("订单信息保存失败");
            }
            $money = bcdiv($money,100,2);
            $dtDealService->dealRedeemMoneyLog($orderId,$userId,$redeemUserId,$money,$p2pDealId);
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ ."," ."智多新债转回调失败 orderId:{$orderId} errMsg:".$ex->getMessage());
            Alarm::push(self::ALARM_DT_DEPOSITORY,'智多新债转回调p2p处理是吧'," orderId:{$orderId} errMsg:".$ex->getMessage());
            throw $ex;
        }

        Logger::info(__CLASS__ . ",". __FUNCTION__ ."," ."智多新债转回调处理成功 orderId:{$orderId},amount:{$amount},status:".$status);
        return true;
    }

    /**
     * 拉取智多新债转数据
     * @param $orderId
     * @param $tableIndex
     * @param $date
     * @return bool
     * @throws \Exception
     */
    public function getBatchDtTransBondData($orderId,$tableIndex,$date,$pageNum=1,$pageSize=1000){
        $vars = array(
            'tableIndex' => $tableIndex,
            'date' => $date,
            'isRedeem' => 1,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
        );
        $this->request->setVars($vars);

        $logParams = json_encode($vars);
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",智多新匹配完成开始拉取债转数据 params::" . $logParams);

        $response = $this->rpc->go('\NCFGroup\Duotou\Services\LoanMappingContract', "getMappingInvest", $this->request);
        if(!$response || $response['data'] === false){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",智多新拉取债转数据失败 params:".$logParams);
            throw new \Exception("智多新拉取债转数据失败");
        }

        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",智多新匹配完成拉取债转数据成功 params::" . $logParams);
        return $this->sendDtTransBondRequest($orderId,$response['data'],$tableIndex,$date);
    }

    /**
     * @param $orderId--对应数据拉取时候的subOrderId
     */
    public function getDtOneTransBondData($orderId){
        $vars = array(
            'token' => $orderId,
        );
        $this->request->setVars($vars);
        $response = $this->rpc->go('\NCFGroup\Duotou\Services\LoanMappingContract', "getMappingInvestByToken", $this->request);
        if(!$response || $response['data'] === false){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",智多新拉取债单条债转数据失败 orderId:{$orderId}");
            throw new \Exception("智多新拉取债转数据失败");
        }
        return $response['data'];
    }

    /**
     * 是否完成存管任务
     * 1、债转是否都成功回调
     * 2、智多新还款是否成功回调
     * 3、底层投资是否成功回调
     */
    public function isFinishDtTask(){
        // 是否存在未受理的债转jobs
        $cnt = JobsModel::instance()->count("priority=".JobsModel::PRIORITY_DTB_GET_TRANSDATA." AND `status` !=".JobsModel::JOBS_STATUS_SUCCESS);
        if($cnt > 0){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",智多新存在未完成的债转请求JOBS");
            return false;
        }

        $cnt = IdempotentModel::instance()->getUnFinishCntBySource(IdempotentModel::SOURCE_DTDEPOSITORY_REDEEM);
        if($cnt > 0){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",智多新存在未完成的债转回调");
            return false;
        }
        $cnt = IdempotentModel::instance()->getUnFinishCntBySource(IdempotentModel::SOURCE_DTDEPOSITORY_REPAY);
        if($cnt > 0){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",智多新存在未完成的还款回调");
            return false;
        }

        $cnt = P2pIdempotentService::getOrderCntByTypeAndResult(self::IDEMPOTENT_TYPE_DTP2PBID,P2pIdempotentService::RESULT_WAIT);
        if($cnt > 0){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .",智多新存在未完成的投资回调");
            return false;
        }
        return true;
    }
}
