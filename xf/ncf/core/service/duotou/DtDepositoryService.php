<?php
/**
 * 智多鑫存管相关service.
 *
 * @author jinhaidong
 * @date 2017-6-20 12:47:58
 */

namespace core\service\duotou;

use core\dao\deal\IdempotentModel;
use libs\utils\Logger;
use libs\utils\Alarm;
use core\dao\jobs\JobsModel;
use core\dao\deal\DealModel;
use core\service\deal\P2pIdempotentService;
use core\service\deal\P2pDepositoryService;
use core\service\deal\DealService;
use core\dao\deal\DealAgencyModel;
use core\service\deal\IdempotentService;
// 存管对账
use NCFGroup\Common\Library\Idworker;
use core\enum\P2pIdempotentEnum;
use core\enum\JobsEnum;
use core\enum\P2pDepositoryEnum;
use core\service\dealload\DealLoadService;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\enum\UserAccountEnum;
use core\enum\AccountEnum;
use core\service\duotou\DuotouService;
use core\enum\SupervisionEnum;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractTplIdentifierEnum;
use core\enum\MsgbusEnum;
use core\service\msgbus\MsgbusService;

class DtDepositoryService extends P2pDepositoryService
{
    const DT_TRANS_PAGESIZE = 100; // 智多鑫债转每次最大条数

    private $rpc;
    private $request;
    private $sv;

    public function __construct()
    {
        $this->sv = new DtPaymenyService();
    }

    /**
     * 向智多鑫发送还款请求
     *
     * @param $orderId
     * @param $repayData
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function sendDtRepayRequest($orderId, $repayData)
    {
        $params = "orderId:{$orderId},repayData:".json_encode($repayData);
        Logger::info(__CLASS__.','.__FUNCTION__.',还款通知智多新 params:'.$params);

        $request = array(
            'p2pDealId' => $repayData['dealId'],
            'dealRepayId' => $orderId,
            'principal' => $repayData['principal'],
            'interest' => $repayData['interest'],
            'isLast' => $repayData['isLast'],
        );

        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\DealRepay', 'repayDeal', $request));

        if (!$response || false === $response['data']) {
            Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY, '还款通知智多新失败', ' orderId:'.$orderId);
            Logger::error(__CLASS__.','.__FUNCTION__.',还款通知智多新失败 orderId:'.$orderId.' request:'.json_encode($request));
            throw new \Exception('还款通知智多新失败');
        }
        Logger::info(__CLASS__.','.__FUNCTION__.',还款通知智多新成功 orderId:'.$orderId);

        $data = array(
            'order_id' => $orderId,
            'deal_id' => $repayData['dealId'],
            'repay_id' => isset($repayData['repayId']) ? $repayData['repayId'] : 0,
            'prepay_id' => isset($repayData['prepayId']) ? $repayData['prepayId'] : 0,
            'money' => $repayData['money'],
            'params' => addslashes(json_encode($repayData)),
            'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_DTREPAY,
            'status' => P2pIdempotentEnum::STATUS_SEND,
            'result' => P2pIdempotentEnum::RESULT_WAIT,
        );

        $res = P2pIdempotentService::saveOrderInfo($orderId, $data);
        if (false === $res) {
            throw new \Exception('订单信息保存失败');
        }
        return true;
    }

    /**
     * 智多鑫还款回调.
     *
     * @param $orderId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function dtRepayCallBack($orderId, $manageId)
    {
        Logger::info(__CLASS__.','.__FUNCTION__.','."智多新还款回调 orderId:{$orderId}");

        try {
            // 判断订单有效性
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if (!$orderInfo) {
                throw new \Exception('order_id不存在');
            }

            // 幂等处理
            if (P2pIdempotentEnum::STATUS_CALLBACK == $orderInfo['status']) {
                return true;
            }

            $manageInfo = DealAgencyModel::instance()->getDealAgencyById($manageId);
            if (!$manageInfo || !$manageInfo->user_id) {
                throw new \Exception("管理机构未设置 orderId:{$orderId},manageId:{$manageId}");
            }
        } catch (\Exception $ex) {
            Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY, '智多新还款回调失败', " orderId:{$orderId}, 错误信息:".$ex->getMessage());
            Logger::error(__CLASS__.','.__FUNCTION__.',orderId:'.$orderId.', errMsg:'.$ex->getMessage());
            throw $ex;
        }

        Logger::info(__CLASS__.','.__FUNCTION__.',智多新还款回调开始事务处理还款逻辑, orderId:'.$orderId);
        try {

            $job_model = new JobsModel();

            $GLOBALS['db']->startTrans();
            $function = '\core\service\duotou\DtDepositoryService::dtBankRepayRequest';
            $newOrderId = Idworker::instance()->getId();
            $job_model->priority = JobsEnum::PRIORITY_DTB_REPAY_BANK;
            $res = $job_model->addJob($function, array($orderId, $newOrderId, $manageInfo->user_id));
            if (false === $res) {
                throw new \Exception('智多新还款通知银行加入jobs失败');
            }

            $orderData = array(
                'status' => P2pIdempotentEnum::STATUS_CALLBACK,
                'result' => P2pIdempotentEnum::RESULT_SUCC,
            );

            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($orderId, $orderData, P2pIdempotentEnum::RESULT_WAIT);
            if (0 == $affectedRows) {
                throw new \Exception('订单信息保存失败');
            }

            // 保存智多鑫还款到firstp2p_idempotent 方便后续验证
            $source = IdempotentModel::SOURCE_DTDEPOSITORY_REPAY;
            $orderParams = json_decode($orderInfo['params'], true);
            $data = array(
                'orderId' => $orderId,
                'dealId' => $orderInfo['deal_id'],
                'manageUserId' => $manageInfo->user_id,
                'repayType' => $orderParams['repayType'],
            );
            $res = IdempotentService::saveToken($newOrderId, $data, $source);
            if (!$res) {
                throw new \Exception('智多新订单信息idempoten保存失败');
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__.','.__FUNCTION__.' '.$ex->getMessage());
            Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY, '智多新还款回调失败', " orderId:{$orderId}, 错误信息:".$ex->getMessage());
            throw $ex;
        }
        return true;
    }

    /**
     * 智多鑫还款通知银行.
     *
     * @param $orderId
     * @param $newOrderid 还款时候的唯一订单ID
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function dtBankRepayRequest($orderId, $newOrderId, $manageUserId)
    {
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if (!$orderInfo) {
            throw new \Exception('订单信息不存在 orderId:'.$orderId);
        }

        $request = array(
            'p2pDealId' => $orderInfo['deal_id'],
            'orderId' => $orderId,
            'manageUserId' => $manageUserId,
        );
        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\DealRepayDetail', 'getRepayDetail', $request));

        if (!$response || false === $response['data']) {
            throw new \Exception('智多新还款数据拉取异常');
        }

        $totalPrincipal = $response['data']['totalPrincipal'];
        $totalInterest = $response['data']['totalInterest'];
        $repayOrderList = $response['data']['list']['repayOrderList']; // 智多新还款数据
        $chargeOrderList = $response['data']['list']['chargeOrderlist']; // 智多新收费数据

        $repayOrderCount = count($repayOrderList);

        //测试环境默认开了转义，生产环境没开，坑不坑?
        //$orderInfo['params'] = stripslashes($orderInfo['params']);：
        $params = json_decode($orderInfo['params'], true);

        $repayType = $params['repayType'];
        $requestData = $params['requestData'];
        $repayParams = $params['repayParams'];
        $repayFeeOrderList = json_decode($requestData['repayOrderList'], true);
        $repayFeeOrderList = is_array($repayFeeOrderList) ? $repayFeeOrderList : array();

        $totalMoney = bcadd($totalPrincipal, $totalInterest, 2);
        $requestData['totalNum'] += $repayOrderCount; // 还款总数量
        $requestData['orderId'] = $newOrderId; // 此处进行orderId替换 因为幂等表orderId是唯一的
        if (0 != bccomp($totalMoney, $orderInfo['money'], 2)) {
            Logger::error(__CLASS__.','.__FUNCTION__." 智多新还款金额与实际还款金额不一致 totalMoney:{$totalMoney},orderInfoMoney:".$orderInfo['money']);
            throw new \Exception('智多新还款金额与实际还款金额不一致');
        }

        $requestData['repayOrderList'] = array_merge($repayOrderList,$repayFeeOrderList);
        $requestData['repayOrderList'] = json_encode($requestData['repayOrderList']);
        $requestData['chargeOrderList'] = json_encode($chargeOrderList);
        $opType = $params['repayOpType'];
        $repayId = !empty($orderInfo['repay_id']) ? $orderInfo['repay_id'] : $orderInfo['prepay_id'];

        $requestData['bidId'] = $orderInfo['deal_id'];

        $repayService = new \core\service\repay\P2pDealRepayService();
        return $repayService->sendRepayRequest($newOrderId, $orderInfo['deal_id'], $repayType, $opType, $repayId, $requestData, $repayParams);
    }

    /**
     * 智多鑫赎回.
     *
     * @param $orderId
     * @param $userId
     * @param $amount
     * @param $feeAmount
     * @param $feeUserId
     */
    public function dtRedeemRequest($orderId, $userId, $amount, $feeAmount, $feeUserId)
    {
        $logParams = "orderId:{$orderId},userId:{$userId},amount:{$amount},feeAmount:{$feeAmount},feeUserId:{$feeUserId}";
        Logger::info(__CLASS__.','.__FUNCTION__.',智多新转让/退出通知银行 logParams:'.$logParams);
        $params = array(
            'orderId' => $orderId,
            'userId' => $userId,
            'unFreezeType' => '01',
            'amount' => bcmul($amount, 100),
        );
        if (1 == bccomp($feeAmount, '0.00', 2)) {
            $params['feeAmount'] = bcmul($feeAmount, 100);
            $params['feeUserId'] = $feeUserId;
        }

        $sendRes = $this->sv->bookfreezeCancel($params);
        if (SupervisionEnum::RESPONSE_SUCCESS == $sendRes['status']) {
            Logger::info(__CLASS__.','.__FUNCTION__.',智多新转让/退出通知银成功');
            return true;
        }
        Logger::error(__CLASS__.','.__FUNCTION__.',智多新转让/退出通知银行失败 logParams:'.$logParams.' errMsg:'.$sendRes['respMsg']);
        throw new \Exception('智多新转让/退出通知银行失败 errMsg:'.$sendRes['respMsg']);
    }

    /**
     * 智多鑫流标
     *  1、通知智多鑫
     *  2、通知存管行取消投资
     *  3、通知存管行预约冻结.
     *
     * @param $dealId
     */
    public function sendDtDealCancelRequest($orderId, $dealId)
    {
        Logger::info(__CLASS__.','.__FUNCTION__.',流标通知智多新 orderId:'.$orderId." dealId:{$dealId}");
        $request = array(
            'p2pDealId' => $dealId,
            'orderId' => $orderId,
        );
        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\DealFail', 'failDeal', $request));
        if (!$response || false === $response['data']) {
            Logger::error(__CLASS__.','.__FUNCTION__.',流标通知智多新失败 orderId:'.$orderId." dealId:{$dealId}");
            throw new \Exception('流标通知智多新失败');
        }
        Logger::info(__CLASS__.','.__FUNCTION__.',流标通知智多新成功 orderId:'.$orderId." dealId:{$dealId}");
        return true;
    }

    /**
     * 智多鑫存管投资接口
     *  -- 目前智多鑫无法批量发送请求、仅支持单条发送
     *
     * @param $batchId
     * @param $subOrderId
     * @param $userId
     * @param $dealId
     * @param $money
     */
    public function sendDtBidRequest($orderId, $userId, $dealId, $money, $otherBidParams = array())
    {
        $logParams = "$orderId:{$orderId},userId:{$userId},dealId:{$dealId},money:{$money}";
        $subInvestOrderList[] = array(
            'subInvestOrderId' => $orderId,
            'bidId' => $dealId,
            'subInvestAmount' => bcmul($money, 100),
        );

        $requestData = array(
            'orderId' => $orderId,
            'userId' => $userId,
            'currency' => 'CNY',
            'totalAmount' => bcmul($money, 100),
            'subInvestOrderList' => json_encode($subInvestOrderList),
        );

        Logger::info(__CLASS__.','.__FUNCTION__.',知智多通知银行 logParams:'.$logParams);

        try {
            $sendRes = $this->sv->bookInvestBatchCreate($requestData);
            if (SupervisionEnum::RESPONSE_SUCCESS !== $sendRes['status']) {
                throw new \Exception('智多新出借通知银行失败');
            }
            $params = array('dtParams' => $otherBidParams);
            $data = array(
                'order_id' => $orderId,
                'deal_id' => $dealId,
                'loan_user_id' => $userId,
                'money' => $money,
                'params' => json_encode($params),
                'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_DTP2PBID,
                'status' => P2pIdempotentEnum::STATUS_SEND,
                'result' => P2pIdempotentEnum::RESULT_WAIT,
            );
            $res = P2pIdempotentService::addOrderInfo($orderId, $data);
            if (false === $res) {
                throw new \Exception('订单信息保存失败');
            }
        } catch (\Exception $ex) {
            Logger::error(__CLASS__.','.__FUNCTION__.',logParams'.$logParams.' errMsg:'.$ex->getMessage());
            return false;
        }
        return true;
    }

    /**
     * 智多鑫投资银行回调.
     *
     * @param $orderId
     * @param $status
     *
     * @return bool
     */
    public function dtBidCallBack($orderId, $status)
    {
        $logParams = "orderId:{$orderId},status:{$status}";
        Logger::info(__CLASS__.','.__FUNCTION__.','.$logParams);

        $dbStartTrans = false;
        try {
            // 判断订单有效性
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if (!$orderInfo) {
                throw new \Exception('order_id不存在');
            }

            $dealId = $orderInfo['deal_id'];
            $deal = DealModel::instance()->find($dealId);
            if (!$deal) {
                throw new \Exception('标的信息不存在 deal_id:'.$dealId);
            }

            if (P2pDepositoryEnum::CALLBACK_STATUS_FAIL == $status) {
                // 智多鑫底层投资时支持失败，如果失败使用补单脚本resubmit_order.php 处理
                if (P2pIdempotentEnum::STATUS_INVALID == $orderInfo['status']) {
                    return true;
                }
                throw new \Exception('智多新出借回调不接受失败状态');
            }

            // 幂等处理
            if (P2pIdempotentEnum::STATUS_CALLBACK == $orderInfo['status']) {
                return true;
            }

            $dealService = new DealService();

            $GLOBALS['db']->startTrans();
            $dbStartTrans = true;
            $user = UserService::getUserById($orderInfo['loan_user_id']);
            $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!$userIdAccountId) {
                throw new \Exception("未开通出借户");
            }
             //dealId 底层标ID,outOrderId 智多新债转合同记录表（loan_mapping_contract）token 字段
            $bizToken = array('dealId'=>$dealId,'orderId'=>$orderId,'outOrderId'=>$orderId);
            $res = AccountService::changeMoney($userIdAccountId,$orderInfo['money'], "智多鑫-转入本金解冻","编号 {$dealId}", AccountEnum::MONEY_TYPE_UNLOCK,false,true,0,$bizToken);
            if (!$res) {
                throw new \Exception("出借底层资产前解冻失败: orderId:{$orderId},user_id:{$user['id']},money:{$orderInfo['money']}");
            }

            // 启动jobs处理理财投资
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsEnum::PRIORITY_DTB_CALLBACK_BID;
            $r = $jobs_model->addJob('\core\service\duotou\DtDepositoryService::dtBidAfterBankCallBack', array('orderId' => $orderId));
            if (false === $r) {
                throw new \Exception('添加JOBS失败');
            }

            $res = P2pIdempotentService::updateStatusByOrderId($orderId, P2pIdempotentEnum::STATUS_CALLBACK);
            if (!$res) {
                throw new \Exception('订单信息更新失败');
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $ex) {
            if (true === $dbStartTrans) {
                $GLOBALS['db']->rollback();
            }
            Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY, '智多新出借回调失败', " params:{$logParams}, 错误信息:".$ex->getMessage());
            Logger::error(__CLASS__.','.__FUNCTION__.',params:'.$logParams.', errMsg:'.$ex->getMessage());
            throw new \Exception($ex->getMessage());
        }
        return true;
    }

    /**
     * 智多鑫单独投资底层资产逻辑(略过存管投资) JOBS 方式执行.
     *
     * @param $orderId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function dtBidAfterBankCallBack($orderId)
    {
        $dl_service = new DealLoadService();
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        $userId = $orderInfo['loan_user_id'];
        $money = $orderInfo['money'];
        $deal = DealModel::instance()->find($orderInfo['deal_id']);
        $coupon_id = '';
        $site_id = 1;
        $discount_id = '';
        $discount_type = 1;
        $optionParams = array(
            'orderInfo' => $orderInfo,
        );

        $res = $dl_service->bid($userId, $deal, $money, $coupon_id, \core\dao\deal\DealLoadModel::$SOURCE_TYPE['dtb'], $site_id, $discount_id, $discount_type, $optionParams);
        if (true == $res['error']) {//投标失败
            throw new \Exception("投标失败[".$res['msg']."] orderId:{$orderId}");
        }
        return true;
    }

    /**
     * 智多鑫匹配完成回调.
     *
     * @param $orderId
     * @param $tableNum
     * @param $date
     */
    public function dtMappingFinishCallBack($orderId, $tableNum, $date)
    {
        $logParams = "orderId:{$orderId},tableNum:{$tableNum},date:{$date}";
        Logger::info(__CLASS__.','.__FUNCTION__.',智多新匹配完成回调 params::'.$logParams);

        //拉取债转的数据
        $request = array(
            'date' => $date,
            'isRedeem' => 1,
        );
        // 债转每个表总数量
        $tableIndexArr = array();

        for ($i = 0; $i < $tableNum; ++$i) {
            $request['tableIndex'] = $i;

            $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\LoanMappingContract', 'getMappingInvestCount', $request));
            if (!$response || false === $response['data']) {
                throw new \Exception('智多新拉取债转分页数量失败');
            } else {
                $tableIndexArr[$i] = $response['data']['totalNum'];
            }
        }
        Logger::info(__CLASS__.','.__FUNCTION__.',智多新获取债转分页数据 tableIndexArr:'.json_encode($tableIndexArr));

        $jobModel = new JobsModel();
        $function = '\core\service\duotou\DtDepositoryService::getBatchDtTransBondData';
        $jobModel->priority = JobsEnum::PRIORITY_DTB_GET_TRANSDATA;

        try {
            $GLOBALS['db']->startTrans();

            foreach ($tableIndexArr as $tableIndex => $tableTotalNum) {
                $pageTotal = ceil($tableTotalNum / P2pDepositoryEnum::DT_TRANS_PAGESIZE);
                for ($pageNum = 1; $pageNum <= $pageTotal; ++$pageNum) {
                    $orderId = Idworker::instance()->getId();
                    $res = $jobModel->addJob($function, array($orderId, $tableIndex, $date, $pageNum, P2pDepositoryEnum::DT_TRANS_PAGESIZE));
                    if (false === $res) {
                        throw new \Exception('智多新拉取债转数据加入JOBS失败');
                    }
                }
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__.','.__FUNCTION__.',智多新匹配完成回调失败 params::'.$logParams.', errMsg:'.$ex->getMessage());
            return false;
        }

        Logger::info(__CLASS__.','.__FUNCTION__.',智多新匹配完成回调拉取jobs生成成功 params::'.$logParams);
        return true;
    }

    /**
     * 批量债转接口 向银行发送债转数据.
     *
     * @param $orderId
     * @param $requestData
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function sendDtTransBondRequest($orderId, $requestData, $tableIndex, $date)
    {
        $requestData['currency'] = 'CNY';
        $requestData['orderId'] = $orderId;

        $logParams = "orderId:{$orderId},$requestData:".json_encode($requestData).",tableIndex:{$tableIndex},date:{$date}";
        Logger::info(__CLASS__.','.__FUNCTION__.',智多新向银行发送债转数据 params:'.$logParams);

        if (count($requestData['creditOrderList']) != $requestData['totalNum']) {
            Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY, '智多新债转失败--数据不一致', " orderId:{$orderId}");
            throw new \Exception('债转数据数量与子订单数据不一致');
        }

        if (0 == $requestData['dealTotalAmount'] || 0 == $requestData['totalNum']) {
            Logger::info(__CLASS__.','.__FUNCTION__.','."智多新债转数据为空 orderId:{$orderId}");
            return true;
        }

        $creditOrderList = $requestData['creditOrderList'];
        $requestData['creditOrderList'] = json_encode($requestData['creditOrderList']);

        $sendRes = $this->sv->creditAssignmentBatchGrant($requestData);
        if (SupervisionEnum::RESPONSE_SUCCESS !== $sendRes['status']) {
            Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY, '智多新债转--请求银行失败', " orderId:{$orderId} errMsg:".$sendRes['respMsg']);
            throw new \Exception('智多新债转通知银行失败 orderId:'.$orderId.' errMsg:'.$sendRes['respMsg']);
        }

        Logger::info(__CLASS__.','.__FUNCTION__.',智多新向银行发送债转数据成功 orderId:'.$orderId);

        try {
            $GLOBALS['db']->startTrans();

            foreach ($creditOrderList as $key => $val) {
                $token = $val['subOrderId'];
                $data = array(
                    'orderId' => $orderId,
                    'tableIndex' => $tableIndex,
                    'date' => $date,
                    'money' => $val['amount'],
                    'redeemUserId' => $val['assignorUserId'], //出让人
                    'userId' => $val['assigneeUserId'], //受让人
                    'dealId' => $val['bidId'],
                    'projectId' => $val['projectId'],
                    'loanId' => $val['loanId'],
                    'loanMapContractId' => $val['lmcId'],
                    'redemptionLoanId' => $val['redemptionLoanId'],
                    'uniqueId' => str_pad($tableIndex,2,0,STR_PAD_LEFT).str_pad($val['lmcId'],20,0,STR_PAD_LEFT),
                );

                $source = IdempotentModel::SOURCE_DTDEPOSITORY_REDEEM;
                $res = IdempotentService::saveToken($token, $data, $source);
                if (!$res) {
                    throw new \Exception('债转子订单保存失败');
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__.','.__FUNCTION__.',orderId:'.$orderId.' errMsg:'.$ex->getMessage());
            throw $ex;
        }
        Logger::info(__CLASS__.','.__FUNCTION__.',智多新债转数据保存成功 orderId:'.$orderId);
        return true;
    }

    /**
     * 存管债转回调
     *  -- 无法处理失败状态 一旦发现失败报警人工处理.
     *
     * @param $orderId
     * @param $status
     * @param $failReason
     */
    public function dtTransBondCallBack($orderId, $amount, $status)
    {
        Logger::info(__CLASS__.','.__FUNCTION__.','."智多新债转回调 orderId:{$orderId},amount:{$amount},status:".$status);

        if (P2pDepositoryEnum::CALLBACK_STATUS_FAIL == $status) {
            Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY, '智多新债转回调失败', " orderId:{$orderId}");
            throw new \Exception("智多新债转回调失败 orderId:{$orderId}");
        }

        $orderInfo = IdempotentService::getTokenInfo($orderId);
        if (!$orderInfo) {
            // TODO 报警太多，暂时拼比，后期改为先落单
            //Alarm::push(self::ALARM_DT_DEPOSITORY,'智多鑫债转回调订单不存在'," orderId:{$orderId}");
            throw new \Exception("智多新债转回调订单不存在 orderId:{$orderId}");
        }
        if (IdempotentModel::STATUS_SUCCESS == $orderInfo['status']) {
            return true;
        }

        $orderParams = $orderInfo['data'];

        $userId = $orderParams['userId'];
        $redeemUserId = $orderParams['redeemUserId'];
        $p2pDealId = $orderParams['dealId'];
        $money = intval($orderParams['money']);
        $amount = intval($amount);
        $loanId =  $orderParams['loanId'];
        $redemptionLoanId =  $orderParams['redemptionLoanId'];
        $projectId = $orderParams['projectId'];
        $loanMapContractId = $orderParams['loanMapContractId'];
        $uniqueId = $orderParams['uniqueId'];

        if ($money !== $amount) {
            Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY, '智多新债转回调金额不匹配', " orderId:{$orderId}");
            Logger::error(__CLASS__.','.__FUNCTION__.','."智多新债转回调金额不匹配 orderId:{$orderId},amount:{$amount},status:".$status);
            throw new \Exception("智多新债转回调金额不匹配 orderId:{$orderId}");
        }

        try {
            $dtDealService = new DtDealService();
            $GLOBALS['db']->startTrans();

            // 这种方式会在存管并发调用的时候造成资金记录重复
            //$res = IdempotentService::updateStatusByToken($orderId,IdempotentModel::STATUS_SUCCESS);
            $res = IdempotentService::updateStatusFromOriStatus($orderId, IdempotentModel::STATUS_SUCCESS, IdempotentModel::STATUS_WAIT);
            if (!$res) {
                throw new \Exception('订单信息保存失败');
            }
            $money = bcdiv($money, 100, 2);
            $dtDealService->dealRedeemMoneyLog($orderId, $userId, $redeemUserId, $money, $p2pDealId);

            //生产债转协议
             $param = array(
                'dealId' =>  $loanId,
                'borrowUserId' => $userId,
                'projectId' => $projectId,
                'dealLoadId' => $redemptionLoanId,
                'type' => ContractServiceEnum::TYPE_DT,
                'lenderUserId' => $redeemUserId,
                'sourceType' => ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT,
                'createTime' => time(),
                'tplPrefix' =>ContractTplIdentifierEnum::DTB_TRANSFER,
                'loanMapContractId' => $loanMapContractId,
                'uniqueId' => $uniqueId
             );
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsEnum::PRIORITY_DTB_CONTRACT;
            $r = $jobs_model->addJob('\core\service\contract\SendContractService::sendDtContractJob', array('requestData'=>$param));
            if ($r === false) {
                throw new \Exception("添加顾问协议jobs失败");
            }

            $message = array('contractId'=>$loanMapContractId,'orderId'=>$orderId);
            MsgbusService::produce(MsgbusEnum::TOPIC_DT_TRANSFER,$message);

            $GLOBALS['db']->commit();
        } catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__.','.__FUNCTION__.','."智多新债转回调失败 orderId:{$orderId} errMsg:".$ex->getMessage());
            Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY, '智多新债转回调p2p处理是吧', " orderId:{$orderId} errMsg:".$ex->getMessage());
            throw $ex;
        }

        Logger::info(__CLASS__.','.__FUNCTION__.','."智多新债转回调处理成功 orderId:{$orderId},amount:{$amount},status:".$status);
        return true;
    }

    /**
     * 拉取智多鑫债转数据.
     *
     * @param $orderId
     * @param $tableIndex
     * @param $date
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function getBatchDtTransBondData($orderId, $tableIndex, $date, $pageNum = 1, $pageSize = 1000)
    {
        $request = array(
            'tableIndex' => $tableIndex,
            'date' => $date,
            'isRedeem' => 1,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
        );

        $logParams = json_encode($request);
        Logger::info(__CLASS__.','.__FUNCTION__.',智多新匹配完成开始拉取债转数据 params::'.$logParams);


        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\LoanMappingContract', 'getMappingInvest', $request));
        if (!$response || false === $response['data']) {
            Logger::error(__CLASS__.','.__FUNCTION__.',智多新拉取债转数据失败 params:'.$logParams);
            throw new \Exception('智多新拉取债转数据失败');
        }

        Logger::info(__CLASS__.','.__FUNCTION__.',智多新匹配完成拉取债转数据成功 params::'.$logParams);
        return $this->sendDtTransBondRequest($orderId, $response['data'], $tableIndex, $date);
    }

    /**
     * @param $orderId--对应数据拉取时候的subOrderId
     */
    public function getDtOneTransBondData($orderId)
    {
        $request = array(
            'token' => $orderId,
        );
        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\LoanMappingContract', 'getMappingInvestByToken', $request));
        if (!$response || false === $response['data']) {
            Logger::error(__CLASS__.','.__FUNCTION__.",智多新拉取债单条债转数据失败 orderId:{$orderId}");
            throw new \Exception('智多新拉取债转数据失败');
        }
        return $response['data'];
    }

    /**
     * 是否完成存管任务
     * 1、债转是否都成功回调
     * 2、智多鑫还款是否成功回调
     * 3、底层投资是否成功回调.
     */
    public function isFinishDtTask()
    {
        // 是否存在未受理的债转jobs
        $cnt = JobsModel::instance()->count('priority='.JobsEnum::PRIORITY_DTB_GET_TRANSDATA.' AND `status` !='.JobsEnum::JOBS_STATUS_SUCCESS);
        if ($cnt > 0) {
            Logger::error(__CLASS__.','.__FUNCTION__.',智多新存在未完成的债转请求JOBS');
            return false;
        }

        $cnt = IdempotentModel::instance()->getUnFinishCntBySource(IdempotentModel::SOURCE_DTDEPOSITORY_REDEEM);
        if ($cnt > 0) {
            Logger::error(__CLASS__.','.__FUNCTION__.',智多新存在未完成的债转回调');
            return false;
        }
        $cnt = IdempotentModel::instance()->getUnFinishCntBySource(IdempotentModel::SOURCE_DTDEPOSITORY_REPAY);
        if ($cnt > 0) {
            Logger::error(__CLASS__.','.__FUNCTION__.',智多新存在未完成的还款回调');
            return false;
        }

        $cnt = P2pIdempotentService::getOrderCntByTypeAndResult(P2pDepositoryEnum::IDEMPOTENT_TYPE_DTP2PBID, P2pIdempotentEnum::RESULT_WAIT);
        if ($cnt > 0) {
            Logger::error(__CLASS__.','.__FUNCTION__.',智多新存在未完成的出借回调');
            return false;
        }
        return true;
    }
}
