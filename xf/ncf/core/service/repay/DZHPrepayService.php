<?php

namespace core\service\repay;

use libs\utils\Alarm;
use libs\utils\Logger;
use libs\utils\Finance;
use core\enum\JobsEnum;
use core\enum\UserAccountEnum;
use core\dao\deal\DealModel;
use core\dao\jobs\JobsModel;
use core\service\BaseService;
use core\dao\deal\DealLoadModel;
use core\enum\P2pDepositoryEnum;
use core\enum\P2pIdempotentEnum;
use core\enum\DealRepayEnum;
use core\enum\PartialRepayEnum;
use core\enum\DealLoanRepayEnum;
use core\dao\repay\DealPrepayModel;
use core\dao\deal\DealAgencyModel;
use core\dao\repay\PartialRepayModel;
use core\service\account\AccountService;
use core\dao\repay\DealLoanRepayModel;
use NCFGroup\Common\Library\Idworker;
use core\service\deal\P2pIdempotentService;
use core\service\deal\P2pDepositoryService;
use core\service\repay\P2pDealRepayService;

class DZHPrepayService extends BaseService
{

    /**
     * 获取多账户贷还款比例
     * @param $dealId 标的Id
     * @param $userMoney 借款人可用金额
     * @param $rechargeMoney 代充值户可用金额
     * @param $repayDetail 还款各项金额明细
     * @return mixed 返回false 不允许还款，返回数组表示双方还款比例
     *
     * 假设以下条件：
     * 借款人网贷账户可用余额：a，代充值机构网贷账户可用余额：b ,当期待还费用总和：x，利息：y，本金：z
     *
     * 计算逻辑：
     * 对标的进行还款时，首先判断a余额，
     * 如果a≥(x+y+z)，直接抵扣费用，利息和本金按照回款计划分配给投资人
     * 如果a=0，代充值机构按照回款计划全额直接代偿
     * 如果0<a<(x+y+z)，则判断a+b之和
     * 当（a+b）<[（x+y+z）+20]时，还款失败，还款批作业终止。
     * 当（a+b）≥[（x+y+z）+20]，则继续判断借款人账户可用余额a
     *
     * 按照出金账户先后顺序，首先对借款人网贷账户可用余额a进行判断：
     * 1) 0<a≤x，直接抵扣费用，如金额不足，后续代充值机构账户出金补齐；
     * 2) x<a<(x+y)，直接抵扣费用，利息按照投资权重分配，后续由代充值机构账户出金补齐；
     * 3) (x+y)≤a<(x+y+z)，直接抵扣费用，利息按照回款计划分配给投资人，本金按照投资权重分配，后续由代充值机构账户出金补齐；
     *
     * 计算过程中遇到除不尽情况，舍余处理
     * 还款完毕后，当期待还变为：x’、y’、z’，对应各项待收费用、出借人待收明细随之更新
     * 如需担保机构进行还款时，对担保机构网贷账户可用余额b进行判断：
     * 代充值网贷账户可用余额需满足：b≥（x’+y’+z’），直接抵扣剩余费用，利息和本金按照更新后的回款计划分配给投资人
     */

    public function getDealDZHPrepayRatio($dealId, $userMoney, $rechargeMoney, $repayDetail)
    {
        //借款人全额还款
        if(bccomp($userMoney, $repayDetail['totalMoney'], 2) > -1) {
            return $this->_formatRepayRatio(1,1,1);
        }

        //借款人没有钱，担保账户全额代偿
        if(bccomp($userMoney,'0.00',2) < 1) {
            //担保账户全额代偿
            if(bccomp($rechargeMoney, $repayDetail['totalMoney'], 2) > -1) {
                return $this->_formatRepayRatio(0, 0, 0);
            }
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " 还款标的需要代充值账户还款，担保账户金额不足，标的Id{$dealId}");
            return false; //代充值账户没钱
        }

        //借款人金额加上担保账户金额不足已支付还款
        if(bccomp(bcadd($userMoney,$rechargeMoney,2),bcadd($repayDetail['totalMoney'],PartialRepayEnum::REPAY_EXTRA_MONEY_LIMIT_DZH,2),2) < 0) {
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " 还款标的需要担保账户和借款人同时还款，担保账户金额加上借款人金额不足，标的Id{$dealId}");
            return false;
        }

        $userLeftMoney = $userMoney;
        //借款人金额不够支付费用，借款人支付一部分费用，利息、本金由担保账户代偿
        if(bccomp($userLeftMoney, $repayDetail['fee'], 2) == -1) {
            return $this->_formatRepayRatio(bcdiv($userLeftMoney, $repayDetail['fee'], 9), 0, 0);
        } else { //借款人金额够支付费用
            $userLeftMoney = bcsub($userLeftMoney, $repayDetail['fee'], 2);
            //借款人金额支付费用后，剩余金额不够支付利息
            if(bccomp($userLeftMoney,$repayDetail['interest'], 2) == -1) {
                return $this->_formatRepayRatio(1, bcdiv($userLeftMoney, $repayDetail['interest'], 9), 0);
            } else { //借款人金额支付费用后，剩余金额够支付利息
                $userLeftMoney = bcsub($userLeftMoney,$repayDetail['interest'],2);
                return $this->_formatRepayRatio(1, 1, bcdiv($userLeftMoney, $repayDetail['principal'], 9));
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
         return [
            PartialRepayEnum::RATIO_TYPE_FEE       => $feeRatio, // 费用
            PartialRepayEnum::RATIO_TYPE_INTEREST  => $interestRatio, // 利息
            PartialRepayEnum::RATIO_TYPE_PRINCIPAL => $principalRatio // 本金
        ];
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
        return $partialRepayModel->savePartialRepayOrder($batchorderId,$repayData,$repayDetailList, PartialRepayEnum::REPAY_BIZ_TYPE_DZH_PREPAY);
    }

    /**
     * 发送多账户提前结清还款请求
     * @param $orderId
     * @param $repayData
     * @return bool
     * @throws \Exception
     */
    public function sendPrepayRequest($orderId, $repayData)
    {
        $params = "orderId:{$orderId},repayData:".json_encode($repayData);
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",多账户提前结清 params:" .$params);

        try {
            $GLOBALS['db']->startTrans();

            //添加多账户提前结清
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsEnum::PRIORITY_ND_REPAY_CALC;
            $res = $jobs_model->addJob('\core\service\repay\DZHPrepayService::handleRepay', ['orderId'=> $orderId]);
            if($res === false){
                throw new \Exception("添加多账户提前结清jobs失败");
            }

            $data = [
                'order_id'  => $orderId,
                'deal_id'   => $repayData['dealId'],
                'repay_id'  => isset($repayData['repayId']) ? $repayData['repayId'] : 0,
                'prepay_id' => isset($repayData['prepayId']) ? $repayData['prepayId'] : 0,
                'money'     => $repayData['money'],
                'params'    => json_encode($repayData),
                'type'      => P2pDepositoryEnum::IDEMPOTENT_TYPE_NDREPAY,
                'status'    => P2pIdempotentEnum::STATUS_CALLBACK,
                'result'    => P2pIdempotentEnum::RESULT_WAIT,
            ];

            $res = P2pIdempotentService::addOrderInfo($orderId, $data);
            if($res === false){
                throw new \Exception("订单信息保存失败");
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " ". $ex->getMessage());
            Alarm::push(P2pDepositoryService::ALARM_BANK_CALLBAK,'发送多账户提前结清请求失败'," orderId:{$orderId}, 错误信息:".$ex->getMessage());
            throw $ex;
        }
        return true;
    }

    /**
     * 处理多账户提前结清
     * @param $orderId
     * @param $repayData
     */
    public function handleRepay($orderId)
    {
        $transBegin = false;
        try {
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if(!$orderInfo) {
                throw new \Exception("订单Id:{$orderId}信息不存在");
            }

            $repayData       = json_decode($orderInfo['params'],true);
            $repayOpType     = $repayData['repayOpType'];
            $repayRatio      = $repayData['repayRatio'];
            $repayId         = $repayData['repayId'];
            $prepayId        = $repayData['prepayId'];
            $repayUserMoney  = $repayData['repayUserMoney'];
            $totalRepayMoney = $orderInfo['money'];

            $repayDetailList = $this->getPartialRepayDetailList($repayUserMoney, $prepayId, $repayRatio);
            if(empty($repayDetailList)) {
                throw new \Exception("获取还款详细信息失败");
            }

            $calcTotalRepayMoney = 0;
            foreach ($repayDetailList as $repayDetail) {
                $calcTotalRepayMoney = bcadd($calcTotalRepayMoney, $repayDetail['amount'], 2);
            }
            if(bccomp($totalRepayMoney, $calcTotalRepayMoney, 2) != 0) {
                throw new \Exception("计算还款金额与真实还款金额不一致！");
            }

            $GLOBALS['db']->startTrans();
            $transBegin = true;
            //更新订单处理状态为处理成功
            $orderData = [
                'result' => P2pIdempotentEnum::RESULT_SUCC,
            ];
            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($orderId, $orderData, P2pIdempotentEnum::RESULT_WAIT);
            if($affectedRows == 0) {
                throw new \Exception("订单信息更新失败");
            }

            //保存还款详细信息
            $saveRes = $this->savePartialRepayOrder($orderId, $repayData, $repayDetailList);
            if($saveRes === false){
                throw new \Exception("保存还款详细信息失败");
            }

            //在此生成借款人还款、代充值还款的订单号，保证一次还款的两个子任务订单唯一
            $jobsData = [
                'orderId' => $orderId,
                'borrowerRepayOrderId' => Idworker::instance()->getId(), //用户还款订单号
                'RechargeRepayOrderId' => Idworker::instance()->getId(), //代偿还款订单号
            ];

            //添加多账户提前结清请求银行jobs
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsEnum::PRIORITY_ND_REPAY_REQUEST;
            $res = $jobs_model->addJob('\core\service\repay\DZHPrepayService::bankRepayRequest', $jobsData);
            if($res === false) {
                throw new \Exception("添加多账户提前结清请求银行jobs失败");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            if($transBegin) {
                $GLOBALS['db']->rollback();
            }
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " ". $ex->getMessage());
            Alarm::push(P2pDepositoryEnum::ALARM_BANK_CALLBAK,'处理多账户提前结清失败'," orderId:{$orderId}, 错误信息:".$ex->getMessage());
            throw $ex;
        }
        return true;
    }

    /**
     * 获取部分还款详细还款数据
     * 还款资金清偿顺序依次为：费用、利息、本金
     * 费用清偿顺序依次为：平台手续费、借款咨询费、借款担保费
     * @param $dealPrepayId 提前还款Id
     * @param $repayUserMoney 借款人还款金额
     * @param $repayRatio 还款比例
     * @return array
     */
    public function getPartialRepayDetailList($repayUserMoney, $dealPrepayId, $repayRatio)
    {
        $repayDetailList = [];

        $dealPrepay = DealPrepayModel::instance()->find($dealPrepayId);
        if ( empty($dealPrepay)) {
            throw new \Exception("获取还款计划失败还款Id：[$dealPrepayId]");
        }

        $deal = DealModel::instance()->find($dealPrepay->deal_id);
        if(!$deal){
            throw new \Exception("标的信息不存在");
        }

        //TODO
        $agencyInfo = DealAgencyModel::instance()->getDealAgencyById($deal['generation_recharge_id']);
        if(!isset($agencyInfo['user_id']) || empty($agencyInfo['user_id'])){
            throw new \Exception('代充值机构用户不存在');
        }

        $repayUserId    = $deal['user_id']; //借款人用户Id
        $rechargeUserId = $agencyInfo['user_id']; // 代充值机构用户Id

        // 费用清偿顺序依次为：平台手续费、借款咨询费、借款担保费
        $leftRepayUserMoney = $repayUserMoney; //还款剩余金额

        // 手续费
        if($dealPrepay->loan_fee > 0) {
            $receiveUserId = DealAgencyModel::instance()->getLoanAgencyUserId($dealPrepay->deal_id);
            if(!$receiveUserId){
                throw new \Exception('平台手续费用户不存在');
            }
            $feeRechargeSplit = $this->_getFeeRechargeSplit($leftRepayUserMoney, $dealPrepay->loan_fee, PartialRepayEnum::FEE_TYPE_SX, $receiveUserId, $repayUserId, $rechargeUserId);
            $repayDetailList = array_merge($repayDetailList, $feeRechargeSplit);
            $leftRepayUserMoney = bcsub($leftRepayUserMoney, $dealPrepay->loan_fee, 2);
        }

        // 咨询费
        if($dealPrepay->consult_fee > 0) {
            $advisoryInfo = DealAgencyModel::instance()->getDealAgencyById($deal['advisory_id']); // 咨询机构
            if(!isset($advisoryInfo['user_id']) || empty($advisoryInfo['user_id'])){
                throw new \Exception('借款咨询费用户不存在');
            }
            $receiveUserId = $advisoryInfo['user_id']; // 咨询机构账户
            $feeRechargeSplit = $this->_getFeeRechargeSplit($leftRepayUserMoney, $dealPrepay->consult_fee, PartialRepayEnum::FEE_TYPE_ZX, $receiveUserId, $repayUserId, $rechargeUserId);
            $repayDetailList = array_merge($repayDetailList, $feeRechargeSplit);
            $leftRepayUserMoney = bcsub($leftRepayUserMoney, $dealPrepay->consult_fee, 2);
        }

        // 担保费
        if($dealPrepay->guarantee_fee > 0) {
            $agencyInfo = DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']); // 担保机构
            if(!isset($agencyInfo['user_id']) || empty($agencyInfo['user_id'])){
                throw new \Exception('担保机构用户不存在');
            }
            $receiveUserId = $agencyInfo['user_id']; // 担保机构账户
            $feeRechargeSplit = $this->_getFeeRechargeSplit($leftRepayUserMoney, $dealPrepay->guarantee_fee, PartialRepayEnum::FEE_TYPE_DB,$receiveUserId, $repayUserId, $rechargeUserId);
            $repayDetailList = array_merge($repayDetailList, $feeRechargeSplit);
            $leftRepayUserMoney = bcsub($leftRepayUserMoney, $dealPrepay->guarantee_fee, 2);
        }

        // 支付服务费
        if($dealPrepay->pay_fee > 0) {
            $payUserInfo = DealAgencyModel::instance()->getDealAgencyById($deal['pay_agency_id']); // 支付机构
            if(!isset($payUserInfo['user_id']) || empty($payUserInfo['user_id'])){
                throw new \Exception('支付机构用户不存在');
            }
            $receiveUserId = $payUserInfo['user_id']; // 支付机构账户
            $feeRechargeSplit = $this->_getFeeRechargeSplit($leftRepayUserMoney, $dealPrepay->pay_fee, PartialRepayEnum::FEE_TYPE_FW,$receiveUserId, $repayUserId, $rechargeUserId);
            $repayDetailList = array_merge($repayDetailList, $feeRechargeSplit);
            $leftRepayUserMoney = bcsub($leftRepayUserMoney, $dealPrepay->pay_fee, 2);
        }

        // 渠道服务费
        if($dealPrepay->canal_fee > 0) {
            $canalUserInfo = DealAgencyModel::instance()->getDealAgencyById($deal['canal_agency_id']); // 渠道服务费
            if(!isset($canalUserInfo['user_id']) || empty($canalUserInfo['user_id'])){
                throw new \Exception('渠道服务费用户不存在');
            }
            $receiveUserId = $canalUserInfo['user_id']; // 支付机构账户
            $feeRechargeSplit = $this->_getFeeRechargeSplit($leftRepayUserMoney, $dealPrepay->canal_fee, PartialRepayEnum::FEE_TYPE_QD,$receiveUserId, $repayUserId, $rechargeUserId);
            $repayDetailList = array_merge($repayDetailList, $feeRechargeSplit);
            $leftRepayUserMoney = bcsub($leftRepayUserMoney, $dealPrepay->canal_fee, 2);
        }

        // 管理服务费
        // if ($prepay->management_fee > 0) {
        //     $managementagencyInfo = DealAgencyModel::instance()->getDealAgencyById($deal['management_agency_id']); // 管理机构
        //     if(!isset($managementagencyInfo['user_id']) || empty($managementagencyInfo['user_id'])){
        //         throw new \Exception('管理机构用户不存在');
        //     }
        //     $receiveUserId = $managementagencyInfo['user_id']; // 支付机构账户
        //     $feeRechargeSplit = $this->_getFeeRechargeSplit($leftRepayUserMoney, $dealPrepay->management_fee, PartialRepayEnum::FEE_TYPE_UGL,$receiveUserId, $repayUserId, $rechargeUserId);
        //     $repayDetailList = array_merge($repayDetailList, $feeRechargeSplit);
        //     $leftRepayUserMoney = bcsub($leftRepayUserMoney, $dealPrepay->management_fee, 2);
        // }

        $dealLoadList = DealLoadModel::instance()->getDealLoanList($dealPrepay->deal_id);
        foreach ($dealLoadList as $dealLoad) {
            $receiveUserId = $dealLoad->user_id; // 投资人
            $principal = DealLoanRepayModel::instance()->getTotalMoneyByTypeStatusLoanId($dealLoad->id,DealLoanRepayEnum::MONEY_PRINCIPAL,DealLoanRepayEnum::STATUS_NOTPAYED);

            // 提前还款利息
            $prepayInterest = Finance::prepay_money_intrest($principal, $dealPrepay->remain_days, $deal['income_fee_rate']);

            // 提前还款违约金  此处需要保留两位小数，因为数据库字段是保留两位小数，如果此处大于2位导致数据库四舍五入
            $prepayCompensation = floorfix($dealLoad->money * ($deal['prepay_rate']/100),2);

            // 中间值计算完成，将数据进行两位舍余
            $principal = floorfix($principal);
            $prepayInterest = floorfix($prepayInterest);

            if($principal > 0) { //本金
                $feeRechargeSplit = $this->_getRechargeSplit($dealLoad->id, $repayRatio[PartialRepayEnum::RATIO_TYPE_PRINCIPAL], $principal, PartialRepayEnum::FEE_TYPE_PRINCIPAL, $receiveUserId, $repayUserId, $rechargeUserId);
                $repayDetailList = array_merge($repayDetailList, $feeRechargeSplit);
            }
            if($prepayInterest > 0) { //利息
                $feeRechargeSplit = $this->_getRechargeSplit($dealLoad->id, $repayRatio[PartialRepayEnum::RATIO_TYPE_INTEREST], $prepayInterest, PartialRepayEnum::FEE_TYPE_INTEREST, $receiveUserId, $repayUserId, $rechargeUserId);
                $repayDetailList = array_merge($repayDetailList, $feeRechargeSplit);
            }
            if($prepayCompensation > 0) { //提前还款补偿金
                $feeRechargeSplit = $this->_getRechargeSplit($dealLoad->id, $repayRatio[PartialRepayEnum::RATIO_TYPE_INTEREST], $prepayCompensation, PartialRepayEnum::FEE_TYPE_COMPEN, $receiveUserId, $repayUserId, $rechargeUserId);
                $repayDetailList = array_merge($repayDetailList, $feeRechargeSplit);
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
     * @param $rechargeUserId 代充值用户
     */
    private function _getFeeRechargeSplit($repayUserMoney, $feeAmount, $type, $receiveUserId, $repayUserId, $rechargeUserId)
    {
        if(bccomp($repayUserMoney, '0.00', 2) == -1) {
            $repayUserMoney = 0;
        }
        $repayDetailList = [];
        $repayUserPayMoney = $rechargeUserPayMoney = 0;
        if(bccomp($repayUserMoney, $feeAmount,2) > -1) {//借款人全额支付费用
            $repayUserPayMoney = $feeAmount;
        } else {
            $repayUserPayMoney    = $repayUserMoney;
            $rechargeUserPayMoney = bcsub($feeAmount, $repayUserPayMoney, 2);
        }

        if(bccomp($repayUserPayMoney, '0.00', 2) == 1) {
            $repayDetailList[] = [
                'orderId'       => Idworker::instance()->getId(),
                'amount'        => $repayUserPayMoney,
                'receiveUserId' => $receiveUserId,
                'payUserId'     => $repayUserId,
                'type'          => $type,
                'dealLoanId'    => 0,
                'repayType'     => PartialRepayEnum::REPAY_TYPE_BORROWER,
            ];
        }

        if(bccomp($rechargeUserPayMoney, '0.00', 2) == 1) {
            $repayDetailList[] = [
                'orderId'       => Idworker::instance()->getId(),
                'amount'        => $rechargeUserPayMoney,
                'receiveUserId' => $receiveUserId,
                'payUserId'     => $rechargeUserId,
                'type'          => $type,
                'dealLoanId'    => 0,
                'repayType'     => PartialRepayEnum::REPAY_TYPE_COMPENSATORY,
            ];
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
     * @param $rechargeUserId 代充值用户
     */
    private function _getRechargeSplit($dealLoanId, $ratio, $amount, $type, $receiveUserId, $repayUserId, $rechargeUserId)
    {
        $repayDetailList = [];
        $repayUserPayMoney = bcmul($amount, $ratio, 2);
        $rechargeUserPayMoney = bcsub($amount, $repayUserPayMoney, 2);
        if(bccomp($repayUserPayMoney, '0.00', 2) == 1) {
            $repayDetailList[] = [
                'orderId'       => Idworker::instance()->getId(),
                'amount'        => $repayUserPayMoney,
                'receiveUserId' => $receiveUserId,
                'payUserId'     => $repayUserId,
                'type'          => $type,
                'dealLoanId'    => $dealLoanId,
                'repayType'     => PartialRepayEnum::REPAY_TYPE_BORROWER,
            ];
        }

        if(bccomp($rechargeUserPayMoney, '0.00', 2) == 1) {
            $repayDetailList[] = [
                'orderId'       => Idworker::instance()->getId(),
                'amount'        => $rechargeUserPayMoney,
                'receiveUserId' => $receiveUserId,
                'payUserId'     => $rechargeUserId,
                'type'          => $type,
                'dealLoanId'    => $dealLoanId,
                'repayType'     => PartialRepayEnum::REPAY_TYPE_COMPENSATORY,
            ];
        }

        return $repayDetailList;
    }

    /**
     * 多账户提前结清通知银行
     * @param $orderId 还款批次主Id
     * @param $orderId 借款人还款主单Id
     * @param $orderId 代偿用户还款主单Id
     * @return bool
     * @throws \Exception
     */
    public function bankRepayRequest($orderId, $borrowerRepayOrderId, $rechargeRepayOrderId)
    {
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if(!$orderInfo){
            throw new \Exception("订单信息不存在 orderId:".$orderId);
        }

        $repayData = json_decode($orderInfo['params'],true);
        $repayOpType = $repayData['repayOpType'];
        $dealId = $repayData['dealId'];
        $repayId = $repayData['prepayId'];
        $repayUserId = $repayData['repayUserId'];
        $rechargeUserId = $repayData['rechargeUserId'];

        $repayAccountId = AccountService::getUserAccountId($repayUserId, UserAccountEnum::ACCOUNT_FINANCE);
        if(!$repayAccountId) {
            throw new \Exception("未获取到借款人账户ID userId:{$repayUserId}");
        }

        $rechargeUserId = $repayData['rechargeUserId'];
        $rechargeAccountId = AccountService::getUserAccountId($rechargeUserId, UserAccountEnum::ACCOUNT_RECHARGE);
        if(!$rechargeAccountId) {
            throw new \Exception("未获取到代充值账户ID userId:{$rechargeUserId}");
        }

        $repayParams = $repayData['repayParams'];
        $repayParams['repayParams']['repayAccountType'] = DealRepayEnum::DEAL_REPAY_TYPE_PREPAY_DZH;
        $repayParams['borrowerRepayOrderId'] = $borrowerRepayOrderId;
        $repayParams['rechargeRepayOrderId'] = $rechargeRepayOrderId;

        $partialRepayModel = new PartialRepayModel();
        $p2pDealRepayService = new P2pDealRepayService();

        try {
            $GLOBALS['db']->startTrans();

            $repayAllBackCheckOrderIds = [];
            $rechargeRepayOrderList = $partialRepayModel->getPartialRepayOrderList($orderId, PartialRepayEnum::REPAY_TYPE_COMPENSATORY);
            if(!empty($rechargeRepayOrderList)) { //有代偿还款数据
                $rechargeRepayOrderInfo = $this->_formatBankRepayOrderList($rechargeRepayOrderList);
                $requestRechargeData = [
                    'orderId'           => $rechargeRepayOrderId,
                    'bidId'             => $dealId,
                    'payUserId'         => $rechargeUserId, // 还款人ID
                    'totalNum'          => $rechargeRepayOrderInfo['totalNum'],  // 还款总条数
                    'totalAmount'       => $rechargeRepayOrderInfo['totalAmount'], // 还款总金额 单位分
                    'currency'          => 'CNY',
                    'repayOrderList'    => json_encode($rechargeRepayOrderInfo['list']),
                    'originalPayUserId' => $repayUserId,
                ];

                $repayAllBackCheckOrderIds[] = $rechargeRepayOrderId;
                $repayRechargeRes =  $p2pDealRepayService->sendRepayRequest($rechargeRepayOrderId, $dealId, DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI, $repayOpType, $repayId, $requestRechargeData, $repayParams);
                if(!$repayRechargeRes) {
                    throw new \Exception("代充值还款失败 orderId:".$rechargeRepayOrderId);
                }
            }

            $borrowerRepayOrderList = $partialRepayModel->getPartialRepayOrderList($orderId, PartialRepayEnum::REPAY_TYPE_BORROWER);
            if(!empty($borrowerRepayOrderList)) { //有借款人还款数据
                $borrowerRepayOrderInfo = $this->_formatBankRepayOrderList($borrowerRepayOrderList);
                $requestBorrowerData = [
                    'orderId'           => $borrowerRepayOrderId,
                    'bidId'             => $dealId,
                    'payUserId'         => $repayUserId, // 还款人ID
                    'totalNum'          => $borrowerRepayOrderInfo['totalNum'],  // 还款总条数
                    'totalAmount'       => $borrowerRepayOrderInfo['totalAmount'], // 还款总金额 单位分
                    'currency'          => 'CNY',
                    'repayOrderList'    => json_encode($borrowerRepayOrderInfo['list']),
                    'originalPayUserId' => $repayUserId
                ];

                $repayAllBackCheckOrderIds[] = $borrowerRepayOrderId;
                $repayBorrowerRes =  $p2pDealRepayService->sendRepayRequest($borrowerRepayOrderId, $dealId, DealRepayEnum::DEAL_REPAY_TYPE_SELF, $repayOpType, $repayId, $requestBorrowerData, $repayParams);
                if(!$repayBorrowerRes) {
                    throw new \Exception("借款人还款失败orderId:".$borrowerRepayOrderId);
                }
            }

            //添加检查还款存管是否都回调成功jobs
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsEnum::PRIORITY_ND_REPAY_CALLBACK;
            $jobsData = [
                'repayOrderId' => $orderId,
                'checkOrderIds' => $repayAllBackCheckOrderIds, //检查还款订单号
            ];
            $startTime = get_gmtime()+180;
            $res = $jobs_model->addJob('\core\service\repay\DZHPrepayService::bankRepayAllCallBack', $jobsData,$startTime,1000);
            if($res === false){
                throw new \Exception("添加多账户提前结清请求银行jobs失败");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " ". $ex->getMessage());
            Alarm::push(P2pDepositoryEnum::ALARM_BANK_CALLBAK,'发送多账户提前结清请求失败'," orderId:{$orderId}, 错误信息:".$ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 格式化银行请求还款订单列表
     * @param $repayOrderList 原始订单列表
     * @return array
     */
    private function _formatBankRepayOrderList($repayOrderList)
    {
        $res = [];
        $orderCount = count($repayOrderList);
        $totalAmount = 0;
        $newList = [];
        foreach ($repayOrderList as $order) {
            $totalAmount = bcadd($totalAmount,$order['money'],2);
            $newList[] = [
                'subOrderId'    => $order['order_id'],
                'amount'        => bcmul($order['money'], 100),
                'receiveUserId' => $this->getAccountId($order['receive_user_id'],$order['type']),
                'type'          =>  $this->getP2pMoneyType($order['type']),
            ];
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
    public function getP2pMoneyType($type)
    {
        $p2pDepositoryService = new P2pDepositoryService();
        $chineseType = '';
        switch ($type) {
            case PartialRepayEnum::FEE_TYPE_PRINCIPAL:
                $chineseType = "偿还本金";
                break;
            case PartialRepayEnum::FEE_TYPE_INTEREST:
                $chineseType = "付息";
                break;
            case PartialRepayEnum::FEE_TYPE_SX:
                $chineseType = "平台手续费";
                break;
            case PartialRepayEnum::FEE_TYPE_ZX:
                $chineseType = "咨询费";
                break;
            case PartialRepayEnum::FEE_TYPE_DB:
                $chineseType = "担保费";
                break;
            case PartialRepayEnum::FEE_TYPE_FW:
                $chineseType = "支付服务费";
                break;
            case PartialRepayEnum::FEE_TYPE_QD:
                $chineseType = "渠道服务费";
                break;
            case PartialRepayEnum::FEE_TYPE_COMPEN:
                $chineseType = "提前还款补偿金";
                break;
        }
        return $p2pDepositoryService->getP2pMoneyType($chineseType);
    }
    /**
     * 获取存管资金类型
     * @param $type
     * @return mixed|string
     */
    public function getAccountId($userId, $type)
    {
        switch ($type) {
            case PartialRepayEnum::FEE_TYPE_PRINCIPAL:
                $accountId = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_INVESTMENT);
                break;
            case PartialRepayEnum::FEE_TYPE_INTEREST:
                $accountId = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_INVESTMENT);
                break;
            case PartialRepayEnum::FEE_TYPE_COMPEN:
                $accountId = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_INVESTMENT);
                break;
            case PartialRepayEnum::FEE_TYPE_SX:
                $accountId = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_PLATFORM);
                break;
            case PartialRepayEnum::FEE_TYPE_ZX:
                $accountId = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_ADVISORY);
                break;
            case PartialRepayEnum::FEE_TYPE_DB:
                $accountId = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_GUARANTEE);
                break;
            case PartialRepayEnum::FEE_TYPE_FW:
                $accountId = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_PAY);
                break;
            case PartialRepayEnum::FEE_TYPE_QD:
                $accountId = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_CHANNEL);
                break;
        }
        if(empty($accountId)) {
            throw new \Exception("手续费账户未设置");
        }
        return $accountId;
    }

    /**
     * 检查还款存管是否都回调成功
     * @param $repayOrderId
     * @param $checkOrderIds
     * @return bool
     * @throws \Exception
     */
    public function bankRepayAllCallBack($repayOrderId, $checkOrderIds)
    {
        $canRepay = true;
        foreach ($checkOrderIds as $orderId) {
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if(empty($orderInfo) || ($orderInfo['result'] != P2pIdempotentEnum::RESULT_SUCC)) {
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
        $function = '\core\service\repay\DealPrepayService::prepay';
        $param = json_decode($repayOrderInfo['params'],true);
        $repayParams = $param['repayParams'];
        $jobs_model->priority = 90;
        $res = $jobs_model->addJob($function, ['param' => $repayParams]);
        if ($res === false) {
            throw new \Exception("还款加入jobs失败");
        }

        return true;
    }
}
