<?php
/**
 * PartialRepayService.php
 * @date 2018-06-06
 * @author wangchuanlu <wangchuanlu@ucfgroup.com>
 */

namespace core\service;

use core\dao\DealExtModel;
use core\dao\DealRepayModel;
use core\dao\DealPrepayModel;
use core\dao\DealAgencyModel;
use core\dao\DealLoanRepayModel;
use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\JobsModel;
use core\dao\PartialRepayModel;
use core\service\DealService;
use core\service\P2pIdempotentService;
use core\service\P2pDepositoryService;
use core\service\P2pDealRepayService;
use core\dao\DealLoanTypeModel;
use libs\utils\Alarm;
use libs\utils\Logger;

use NCFGroup\Common\Library\Idworker;

/**
 * 部分还款服务类
 *
 * Class DealRepayService
 * @package core\service
 */
class PartialRepayService extends BaseService {

    const FEE_TYPE_PRINCIPAL    = 1; //本金
    const FEE_TYPE_INTEREST     = 2; //利息
    const FEE_TYPE_SX           = 3; // 平台手续费
    const FEE_TYPE_ZX           = 4; // 借款咨询费
    const FEE_TYPE_DB           = 5; // 借款担保费
    const FEE_TYPE_FW           = 6; // 服务费
    const FEE_TYPE_GL           = 7; // 管理费
    const FEE_TYPE_QD           = 8; // 渠道费
    const FEE_TYPE_YQ           = 9; // 逾期罚息
    const FEE_TYPE_COMPEN       = 10;//提前还款补偿金
    const FEE_TYPE_UGL          = 11; // DTB 收取投资人的管理费

    const RATIO_TYPE_PRINCIPAL    = 'principal'; //比例类型本金
    const RATIO_TYPE_INTEREST     = 'interest'; //比例类型利息
    const RATIO_TYPE_FEE          = 'fee'; //比例类型费用

    const REPAY_EXTRA_MONEY_LIMIT   = 100; //农担贷代偿还款额外金额限制

    /**
     * 获取农担贷还款比例
     * @param $dealId 标的Id
     * @param $userMoney 借款人可用金额
     * @param $compensatoryMoney 代偿担保账户可用金额
     * @param $repayDetail 还款各项金额明细
     * @return mixed 返回false 不允许还款，返回数组表示双方还款比例
     *
     * 假设以下条件：
     * 借款人网贷账户可用余额：a，担保机构网贷账户可用余额：b ,当期待还费用总和：x，利息：y，本金：z
     *
     * 计算逻辑：
     * 对标的进行还款时，首先判断a余额，
     * 如果a≥(x+y+z)，直接抵扣费用，利息和本金按照回款计划分配给投资人
     * 如果a=0，担保机构按照回款计划全额直接代偿
     * 如果0<a<(x+y+z)，则判断a+b之和
     * 当（a+b）<[（x+y+z）+100]时，还款失败，还款批作业终止。
     * 当（a+b）≥[（x+y+z）+100]，则继续判断借款人账户可用余额a
     *
     * 按照出金账户先后顺序，首先对借款人网贷账户可用余额a进行判断：
     * 1) 0<a≤x，直接抵扣费用，如金额不足，后续担保机构账户出金补齐；
     * 2) x<a<(x+y)，直接抵扣费用，利息按照投资权重分配，后续由担保机构账户出金补齐；
     * 3) (x+y)≤a<(x+y+z)，直接抵扣费用，利息按照回款计划分配给投资人，本金按照投资权重分配，后续由担保机构账户出金补齐；
     *
     * 计算过程中遇到除不尽情况，舍余处理
     * 还款完毕后，当期待还变为：x’、y’、z’，对应各项待收费用、出借人待收明细随之更新
     * 如需担保机构进行还款时，对担保机构网贷账户可用余额b进行判断：
     * 担保机构网贷账户可用余额需满足：b≥（x’+y’+z’），直接抵扣剩余费用，利息和本金按照更新
     */
    public function getDealNDRepayRatio($dealId,$userMoney,$compensatoryMoney,$repayDetail) {
        $dealService = new DealService();
        if(!$dealService->isDealND($dealId)) {
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " 还款标的不是农担贷，标的Id{$dealId}");
            return false;
        }

        //借款人全额还款
        if(bccomp($userMoney,$repayDetail['totalMoney'],2) > -1) {
            return $this->_formatRepayRatio(1,1,1);
        }

        //借款人没有钱，担保账户全额代偿
        if(bccomp($userMoney,'0.00',2) < 1) {
            //担保账户全额代偿
            if(bccomp($compensatoryMoney,$repayDetail['totalMoney'],2) > -1) {
                return $this->_formatRepayRatio(0,0,0);
            }
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " 还款标的需要担保账户还款，担保账户金额不足，标的Id{$dealId}");
            return false; //担保账户没钱
        }

        //借款人金额加上担保账户金额不足已支付还款
        if(bccomp(bcadd($userMoney,$compensatoryMoney,2),bcadd($repayDetail['totalMoney'],self::REPAY_EXTRA_MONEY_LIMIT,2),2) < 0) {
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " 还款标的需要担保账户和借款人同时还款，担保账户金额加上借款人金额不足，标的Id{$dealId}");
            return false;
        }

        $userLeftMoney = $userMoney;
        //借款人金额不够支付费用，借款人支付一部分费用，利息、本金由担保账户代偿
        if(bccomp($userLeftMoney,$repayDetail['fee'],2) == -1) {
            return $this->_formatRepayRatio(bcdiv($userLeftMoney,$repayDetail['fee'],9),0,0);
        } else { //借款人金额够支付费用
            $userLeftMoney = bcsub($userLeftMoney,$repayDetail['fee'],2);
            //借款人金额支付费用后，剩余金额不够支付利息
            if(bccomp($userLeftMoney,$repayDetail['interest'],2) == -1) {
                return $this->_formatRepayRatio(1,bcdiv($userLeftMoney,$repayDetail['interest'],9),0);
            } else { //借款人金额支付费用后，剩余金额够支付利息
                $userLeftMoney = bcsub($userLeftMoney,$repayDetail['interest'],2);
                return $this->_formatRepayRatio(1,1,bcdiv($userLeftMoney,$repayDetail['principal'],9));
            }
        }
    }

    /**
     * 格式化还款比例
     * @param $feeRatio 费用比例
     * @param $interestRatio 利息比例
     * @param $principalRatio 本金比例
     * @return array
     */
    private function _formatRepayRatio($feeRatio,$interestRatio,$principalRatio) {
         return array(self::RATIO_TYPE_FEE => $feeRatio, self::RATIO_TYPE_INTEREST => $interestRatio, self::RATIO_TYPE_PRINCIPAL => $principalRatio);
    }

    /**
     * 保存部分还款订单
     * @param array $repayData
     * @param array $repayDetailList
     * @return boolean
     */
    public function savePartialRepayOrder($batchorderId,$repayData,$repayDetailList) {
        Logger::info(sprintf('%s | %s, 保存部分还款订单|业务原始参数,batchorderId:%s, repayData:%s,params:%s', __CLASS__, __FUNCTION__, $batchorderId,json_encode($repayData),json_encode($repayDetailList)));
        $partialRepayModel = new PartialRepayModel();
        return $partialRepayModel->savePartialRepayOrder($batchorderId,$repayData,$repayDetailList);
    }

    /**
     * 发送农担贷还款请求
     * @param $orderId
     * @param $repayData
     * @return bool
     * @throws \Exception
     */
    public function sendNdRepayRequest($orderId,$repayData) {
        $params = "orderId:{$orderId},repayData:".json_encode($repayData);
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",还款通知农担贷 params:" .$params);

        try {
            $GLOBALS['db']->startTrans();

            //添加农担贷还款
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsModel::PRIORITY_ND_REPAY_CALC;
            $res = $jobs_model->addJob('\core\service\PartialRepayService::handleNdRepay', array('orderId'=>$orderId));
            if($res === false){
                throw new \Exception("添加农担贷还款jobs失败");
            }

            $data = array(
                'order_id' => $orderId,
                'deal_id' => $repayData['dealId'],
                'repay_id' => isset($repayData['repayId']) ? $repayData['repayId'] : 0,
                'prepay_id' => isset($repayData['prepayId']) ? $repayData['prepayId'] : 0,
                'money' => $repayData['money'],
                'params' => json_encode($repayData),
                'type' => P2pDepositoryService::IDEMPOTENT_TYPE_NDREPAY,
                'status' => P2pIdempotentService::STATUS_CALLBACK,
                'result' => P2pIdempotentService::RESULT_WAIT,
            );

            $res = P2pIdempotentService::addOrderInfo($orderId,$data);
            if($res === false){
                throw new \Exception("订单信息保存失败");
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " ". $ex->getMessage());
            Alarm::push(P2pDepositoryService::ALARM_BANK_CALLBAK,'发送农担贷还款请求失败'," orderId:{$orderId}, 错误信息:".$ex->getMessage());
            throw $ex;
        }
        return true;
    }

    /**
     * 处理农担贷还款
     * @param $orderId
     * @param $repayData
     */
    public function handleNdRepay($orderId) {
        $transBegin = false;
        try {
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if(!$orderInfo) {
                throw new \Exception("订单Id:{$orderId}信息不存在");
            }

            $repayData = json_decode($orderInfo['params'],true);
            $repayOpType = $repayData['repayOpType'];
            $repayRatio = $repayData['repayRatio'];
            $repayId = $repayData['repayId'];
            $repayUserMoney = $repayData['repayUserMoney'];
            $totalRepayMoney = $orderInfo['money'];

            $repayDetailList = $this->getPartialRepayDetailList($repayOpType,$repayUserMoney,$repayId,$repayRatio);
            if(empty($repayDetailList)) {
                throw new \Exception("获取还款详细信息失败");
            }

            $calcTotalRepayMoney = 0;
            foreach ($repayDetailList as $repayDetail) {
                $calcTotalRepayMoney = bcadd($calcTotalRepayMoney,$repayDetail['amount'],2) ;
            }
            if(bccomp($totalRepayMoney,$calcTotalRepayMoney,2) != 0) {
                throw new \Exception("计算还款金额与真实还款金额不一致！");
            }

            $GLOBALS['db']->startTrans();
            $transBegin = true;
            //更新订单处理状态为处理成功
            $orderData = array(
                'result' => P2pIdempotentService::RESULT_SUCC,
            );
            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($orderId,$orderData,P2pIdempotentService::RESULT_WAIT);
            if($affectedRows == 0){
                throw new \Exception("订单信息更新失败");
            }

            //保存还款详细信息
            $saveRes = $this->savePartialRepayOrder($orderId,$repayData,$repayDetailList);
            if($saveRes === false){
                throw new \Exception("保存还款详细信息失败");
            }

            //在此生成借款人还款、代偿还款的订单号，保证一次还款的两个子任务订单唯一
            $jobsData = array(
                'orderId' => $orderId,
                'borrowerRepayOrderId' => Idworker::instance()->getId(), //用户还款订单号
                'compensatoryRepayOrderId' => Idworker::instance()->getId(), //代偿还款订单号
            );

            //添加农担贷还款请求银行jobs
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsModel::PRIORITY_ND_REPAY_REQUEST;
            $res = $jobs_model->addJob('\core\service\PartialRepayService::ndBankRepayRequest', $jobsData);
            if($res === false){
                throw new \Exception("添加农担贷还款请求银行jobs失败");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            if($transBegin) {
                $GLOBALS['db']->rollback();
            }
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " ". $ex->getMessage());
            Alarm::push(P2pDepositoryService::ALARM_BANK_CALLBAK,'处理农担贷还款失败'," orderId:{$orderId}, 错误信息:".$ex->getMessage());
            throw $ex;
        }
        return true;
    }

    /**
     * 获取部分还款详细还款数据
     * 还款资金清偿顺序依次为：费用、利息、本金
     * 费用清偿顺序依次为：平台手续费、借款咨询费、借款担保费
     * @param $dealRepayId 还款Id
     * @param $repayOpType 还款操作类型
     * @param $repayUserMoney 借款人还款金额
     * @param $repayRatio 还款比例
     * @return array
     */
    public function getPartialRepayDetailList($repayOpType,$repayUserMoney,$dealRepayId,$repayRatio) {
        $repayDetailList = array();
        if($repayOpType == P2pDealRepayService::REPAY_OP_TYPE_PREPAY) { //提前还款(暂时不需要)
            return $repayDetailList;
        }

        $dealRepay = DealRepayModel::instance()->find($dealRepayId);
        if ( empty($dealRepay) || $dealRepay->status != DealRepayModel::STATUS_WAITING) {
            throw new \Exception("获取还款计划失败还款Id：[$dealRepayId]");
        }

        $deal = DealModel::instance()->find($dealRepay->deal_id);
        if(!$deal){
            throw new \Exception("标的信息不存在");
        }

        $agencyInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']);
        if(!isset($agencyInfo['user_id']) || empty($agencyInfo['user_id'])){
            throw new \Exception('担保机构用户不存在');
        }

        $compensatoryUserId = $agencyInfo['user_id']; // 担保机构用户Id
        $repayUserId = $deal['user_id']; //借款人用户Id

        // 费用清偿顺序依次为：平台手续费、借款咨询费、借款担保费
        $leftRepayUserMoney = $repayUserMoney; //还款剩余金额
        // 手续费
        if($dealRepay->loan_fee > 0) {
            $receiveUserId = DealAgencyModel::instance()->getLoanAgencyUserId($dealRepay->deal_id);
            if(!$receiveUserId){
                throw new \Exception('平台手续费用户不存在');
            }
            $feeCompensatorySplit = $this->_getFeeCompensatorySplit($leftRepayUserMoney,$dealRepay->loan_fee,self::FEE_TYPE_SX,$receiveUserId,$repayUserId,$compensatoryUserId) ;
            $repayDetailList = array_merge($repayDetailList,$feeCompensatorySplit);
            $leftRepayUserMoney = bcsub($leftRepayUserMoney,$dealRepay->loan_fee,2);
        }
        // 咨询费
        if($dealRepay->consult_fee > 0) {
            $advisoryInfo = DealAgencyModel::instance()->getDealAgencyById($deal['advisory_id']); // 咨询机构
            if(!isset($advisoryInfo['user_id']) || empty($advisoryInfo['user_id'])){
                throw new \Exception('借款咨询费用户不存在');
            }
            $receiveUserId = $advisoryInfo['user_id']; // 咨询机构账户
            $feeCompensatorySplit = $this->_getFeeCompensatorySplit($leftRepayUserMoney,$dealRepay->consult_fee,self::FEE_TYPE_ZX,$receiveUserId,$repayUserId,$compensatoryUserId) ;
            $repayDetailList = array_merge($repayDetailList,$feeCompensatorySplit);
            $leftRepayUserMoney = bcsub($leftRepayUserMoney,$dealRepay->consult_fee,2);
        }
        // 担保费
        if($dealRepay->guarantee_fee > 0) {
            $agencyInfo = DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']); // 担保机构
            if(!isset($agencyInfo['user_id']) || empty($agencyInfo['user_id'])){
                throw new \Exception('担保机构用户不存在');
            }
            $receiveUserId = $agencyInfo['user_id']; // 担保机构账户
            $feeCompensatorySplit = $this->_getFeeCompensatorySplit($leftRepayUserMoney,$dealRepay->guarantee_fee,self::FEE_TYPE_DB,$receiveUserId,$repayUserId,$compensatoryUserId) ;
            $repayDetailList = array_merge($repayDetailList,$feeCompensatorySplit);
            $leftRepayUserMoney = bcsub($leftRepayUserMoney,$dealRepay->guarantee_fee,2);
        }

        $dealLoanList = DealLoadModel::instance()->getDealLoanList($dealRepay->deal_id);
        foreach ($dealLoanList as $dealLoan) {
            $receiveUserId = $dealLoan->user_id; // 投资人
            $condition = sprintf("`deal_repay_id`= '%d' AND `deal_loan_id` = '%d' AND `loan_user_id`= '%d'", $dealRepayId, $dealLoan->id, $dealLoan->user_id);
            //根据还款记录ID，投标记录ID，投资人ID
            $loanRepayList = DealLoanRepayModel::instance()->findAll($condition);
            foreach ($loanRepayList as $loanRepay) {
                if($loanRepay['money'] !=0) {
                    switch ($loanRepay['type']) {
                        case DealLoanRepayModel::MONEY_PRINCIPAL:
                            $feeCompensatorySplit = $this->_getCompensatorySplit($dealLoan['id'],$repayRatio[self::RATIO_TYPE_PRINCIPAL],$loanRepay['money'],self::FEE_TYPE_PRINCIPAL,$receiveUserId,$repayUserId,$compensatoryUserId) ;
                            $repayDetailList = array_merge($repayDetailList,$feeCompensatorySplit);
                            break;
                        case DealLoanRepayModel::MONEY_INTREST:
                            $feeCompensatorySplit = $this->_getCompensatorySplit($dealLoan['id'],$repayRatio[self::RATIO_TYPE_INTEREST],$loanRepay['money'],self::FEE_TYPE_INTEREST,$receiveUserId,$repayUserId,$compensatoryUserId) ;
                            $repayDetailList = array_merge($repayDetailList,$feeCompensatorySplit);
                            break;
                    }
                }
            }
        }
        return $repayDetailList;
    }

    /**
     * 获取代偿费用分配信息
     * @param $repayUserMoney 借款人可用金额
     * @param $feeAmount 费用总额
     * @param $type 费用类型
     * @param $receiveUserId 收款用户
     * @param $repayUserId 付款用户
     * @param $compensatoryUserId 代偿用户
     */
    private function _getFeeCompensatorySplit($repayUserMoney,$feeAmount,$type,$receiveUserId,$repayUserId,$compensatoryUserId) {
        if(bccomp($repayUserMoney,'0.00',2) == -1) {
            $repayUserMoney = 0;
        }
        $repayDetailList = array();
        $repayUserPayMoney = $compensatoryUserPayMoney = 0;
        if(bccomp($repayUserMoney,$feeAmount,2) > -1) {//借款人全额支付费用
            $repayUserPayMoney = $feeAmount;
        } else {
            $repayUserPayMoney = $repayUserMoney;
            $compensatoryUserPayMoney = bcsub($feeAmount,$repayUserPayMoney,2);
        }

        if(bccomp($repayUserPayMoney,'0.00',2) == 1) {
            $repayDetailList[] = array(
                'orderId' => Idworker::instance()->getId(),
                'amount' => $repayUserPayMoney,
                'receiveUserId' => $receiveUserId,
                'payUserId' => $repayUserId,
                'type' => $type,
                'dealLoanId' => 0,
                'repayType' => PartialRepayModel::REPAY_TYPE_BORROWER,
            );
        }

        if(bccomp($compensatoryUserPayMoney,'0.00',2) == 1) {
            $repayDetailList[] = array(
                'orderId' => Idworker::instance()->getId(),
                'amount' => $compensatoryUserPayMoney,
                'receiveUserId' => $receiveUserId,
                'payUserId' => $compensatoryUserId,
                'type' => $type,
                'dealLoanId' => 0,
                'repayType' => PartialRepayModel::REPAY_TYPE_COMPENSATORY,
            );
        }

        return $repayDetailList;
    }

    /**
     * 获取代偿费用分配信息
     * @param $dealLoanId 投资记录Id
     * @param $ratio 比例
     * @param $amount 还款总金额
     * @param $type 金额类型
     * @param $receiveUserId 收款用户
     * @param $repayUserId 付款用户
     * @param $compensatoryUserId 代偿用户
     */
    private function _getCompensatorySplit($dealLoanId,$ratio,$amount,$type,$receiveUserId,$repayUserId,$compensatoryUserId) {
        $repayDetailList = array();
        $repayUserPayMoney = bcmul($amount,$ratio,2);
        $compensatoryUserPayMoney = bcsub($amount,$repayUserPayMoney,2);
        if(bccomp($repayUserPayMoney,'0.00',2) == 1) {
            $repayDetailList[] = array(
                'orderId' => Idworker::instance()->getId(),
                'amount' => $repayUserPayMoney,
                'receiveUserId' => $receiveUserId,
                'payUserId' => $repayUserId,
                'type' => $type,
                'dealLoanId' => $dealLoanId,
                'repayType' => PartialRepayModel::REPAY_TYPE_BORROWER,
            );
        }

        if(bccomp($compensatoryUserPayMoney,'0.00',2) == 1) {
            $repayDetailList[] = array(
                'orderId' => Idworker::instance()->getId(),
                'amount' => $compensatoryUserPayMoney,
                'receiveUserId' => $receiveUserId,
                'payUserId' => $compensatoryUserId,
                'type' => $type,
                'dealLoanId' => $dealLoanId,
                'repayType' => PartialRepayModel::REPAY_TYPE_COMPENSATORY,
            );
        }

        return $repayDetailList;
    }

    /**
     * 农担贷还款通知银行
     * @param $orderId 还款批次主Id
     * @param $orderId 借款人还款主单Id
     * @param $orderId 代偿用户还款主单Id
     * @return bool
     * @throws \Exception
     */
    public function ndBankRepayRequest($orderId,$borrowerRepayOrderId,$compensatoryRepayOrderId){
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if(!$orderInfo){
            throw new \Exception("订单信息不存在 orderId:".$orderId);
        }

        $repayData = json_decode($orderInfo['params'],true);
        $repayOpType = $repayData['repayOpType'];
        $dealId = $repayData['dealId'];
        $repayId = $repayData['repayId'];
        $repayUserId = $repayData['repayUserId'];
        $compensatoryUserId = $repayData['compensatoryUserId'];
        $repayParams = $repayData['repayParams'];

        $partialRepayModel = new PartialRepayModel();
        $p2pDealRepayService = new P2pDealRepayService();

        try {
            $GLOBALS['db']->startTrans();

            $repayAllBackCheckOrderIds = array();
            $compensatoryRepayOrderList = $partialRepayModel->getPartialRepayOrderList($orderId, PartialRepayModel::REPAY_TYPE_COMPENSATORY);
            if(!empty($compensatoryRepayOrderList)) { //有代偿还款数据
                $compensatoryRepayOrderInfo = $this->_formatBankRepayOrderList($compensatoryRepayOrderList);
                $requestCompensatoryData = array(
                    'orderId' => $compensatoryRepayOrderId,
                    'bidId' => $dealId,
                    'payUserId' => $compensatoryUserId, // 还款人ID
                    'totalNum' => $compensatoryRepayOrderInfo['totalNum'],  // 还款总条数
                    'totalAmount' => $compensatoryRepayOrderInfo['totalAmount'], // 还款总金额 单位分
                    'currency' => 'CNY',
                    'repayOrderList' => json_encode($compensatoryRepayOrderInfo['list']),
                    'originalPayUserId' => $repayUserId,
                );

                $repayAllBackCheckOrderIds[] = $compensatoryRepayOrderId;
                $repayCompensatoryRes =  $p2pDealRepayService->sendRepayRequest($compensatoryRepayOrderId,$dealId,DealRepayModel::DEAL_REPAY_TYPE_DAICHANG,$repayOpType,$repayId,$requestCompensatoryData,$repayParams);
                if(!$repayCompensatoryRes) {
                    throw new \Exception("担保机构代偿还款失败 orderId:".$compensatoryRepayOrderId);
                }
            }

            $borrowerRepayOrderList = $partialRepayModel->getPartialRepayOrderList($orderId,PartialRepayModel::REPAY_TYPE_BORROWER);
            if(!empty($borrowerRepayOrderList)) { //有借款人还款数据
                $borrowerRepayOrderInfo = $this->_formatBankRepayOrderList($borrowerRepayOrderList);
                $requestBorrowerData = array(
                    'orderId' => $borrowerRepayOrderId,
                    'bidId' => $dealId,
                    'payUserId' => $repayUserId, // 还款人ID
                    'totalNum' => $borrowerRepayOrderInfo['totalNum'],  // 还款总条数
                    'totalAmount' => $borrowerRepayOrderInfo['totalAmount'], // 还款总金额 单位分
                    'currency' => 'CNY',
                    'repayOrderList' => json_encode($borrowerRepayOrderInfo['list']),
                    'originalPayUserId' => $repayUserId
                );

                $repayAllBackCheckOrderIds[] = $borrowerRepayOrderId;
                $repayBorrowerRes =  $p2pDealRepayService->sendRepayRequest($borrowerRepayOrderId,$dealId,DealRepayModel::DEAL_REPAY_TYPE_SELF,$repayOpType,$repayId,$requestBorrowerData,$repayParams);
                if(!$repayBorrowerRes) {
                    throw new \Exception("借款人还款失败orderId:".$borrowerRepayOrderId);
                }
            }

            //添加检查还款存管是否都回调成功jobs
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsModel::PRIORITY_ND_REPAY_CALLBACK;
            $jobsData = array(
                'repayOrderId' => $orderId,
                'checkOrderIds' => $repayAllBackCheckOrderIds, //检查还款订单号
            );
            $startTime = get_gmtime()+180;
            $res = $jobs_model->addJob('\core\service\PartialRepayService::ndBankRepayAllCallBack', $jobsData,$startTime,1000);
            if($res === false){
                throw new \Exception("添加农担贷还款请求银行jobs失败");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " ". $ex->getMessage());
            Alarm::push(P2pDepositoryService::ALARM_BANK_CALLBAK,'发送农担贷还款请求失败'," orderId:{$orderId}, 错误信息:".$ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 格式化银行请求还款订单列表
     * @param $repayOrderList 原始订单列表
     * @return array
     */
    private function _formatBankRepayOrderList($repayOrderList) {
        $res = array();
        $orderCount = count($repayOrderList);
        $totalAmount = 0;
        $newList = array();
        foreach ($repayOrderList as $order) {
            $totalAmount = bcadd($totalAmount,$order['money'],2);
            $newList[] = array(
                'subOrderId' => $order['order_id'],
                'amount' => bcmul($order['money'], 100),
                'receiveUserId' => $order['receive_user_id'],
                'type' =>  $this->getP2pMoneyType($order['type']),
                );
        }
        $res['totalNum'] = $orderCount;
        $res['totalAmount'] = bcmul($totalAmount, 100);
        $res['list'] = $newList;
        return $res;
    }

    /**
     * 获取存管资金类型
     * @param $type
     * @return mixed|string
     */
    public function getP2pMoneyType($type) {
        $p2pDepositoryService = new P2pDepositoryService();
        $chineseType = '';
        switch ($type) {
            case self::FEE_TYPE_PRINCIPAL;
                $chineseType = "偿还本金";
                break;
            case self::FEE_TYPE_INTEREST;
                $chineseType = "付息";
                break;
            case self::FEE_TYPE_SX;
                $chineseType = "平台手续费";
                break;
            case self::FEE_TYPE_ZX;
                $chineseType = "咨询费";
                break;
            case self::FEE_TYPE_DB;
                $chineseType = "担保费";
                break;
            case self::FEE_TYPE_FW;
                $chineseType = "支付服务费";
                break;
            case self::FEE_TYPE_QD;
                $chineseType = "渠道服务费";
                break;
        }
        return $p2pDepositoryService->getP2pMoneyType($chineseType);
    }

    /**
     * 检查还款存管是否都回调成功
     * @param $repayOrderId
     * @param $checkOrderIds
     * @return bool
     * @throws \Exception
     */
    public function ndBankRepayAllCallBack($repayOrderId,$checkOrderIds) {
        $canRepay = true;
        foreach ($checkOrderIds as $orderId) {
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if(empty($orderInfo) || ($orderInfo['result'] != P2pIdempotentService::RESULT_SUCC)) {
                $canRepay = false;
                break;
            }
        }
        if(!$canRepay) {
            throw new \Exception("还款结果尚未获取，稍等执行");
        }

        $repayOrderInfo = P2pIdempotentService::getInfoByOrderId($repayOrderId);
        if(empty($repayOrderInfo)) {
            throw new \Exception("还款订单信息不存在");
        }
        $jobs_model = new JobsModel();
        // 正常还款逻辑
        $function = '\core\service\DealRepayService::repay';
        $param = json_decode($repayOrderInfo['params'],true);
        $jobs_model->priority = 90;
        $res = $jobs_model->addJob($function, $param['repayParams']);
        if ($res === false) {
            throw new \Exception("还款加入jobs失败");
        }

        return true;
    }
}
