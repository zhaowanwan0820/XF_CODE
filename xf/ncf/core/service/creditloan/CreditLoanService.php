<?php

/**
 * 信用贷service
 */

namespace core\service\creditloan;


use libs\utils\Alarm;
use core\enum\JobsEnum;
use core\dao\creditloan;
use core\enum\AccountEnum;
use core\dao\deal\DealModel;
use core\dao\jobs\JobsModel;
use core\service\BaseService;
use core\enum\CreditLoanEnum;
use core\enum\SupervisionEnum;
use core\enum\UserAccountEnum;
use core\enum\P2pDepositoryEnum;
use core\enum\P2pIdempotentEnum;
use core\service\user\BankService;
use core\service\user\UserService;
use core\service\deal\DealService;
use NCFGroup\Common\Library\Idworker;
use core\service\account\AccountService;
use core\service\deal\P2pIdempotentService;
use core\service\speedloan\SpeedloanService;
use core\dao\creditloan\CreditLoanModel;
use core\service\payment\UniteBankPaymentService;
use core\service\supervision\SupervisionFinanceService;


class CreditLoanService extends BaseService {

    /**
     * 是否是正在借贷中的用户
     *
     * @param $userId
     * @param $dealId
     */
    public function isCreditingUser($userId,$dealId) {
        $row = CreditLoanModel::instance()->getCreditLoan($userId,$dealId);
        return $row && !in_array($row->status,array(CreditLoanEnum::STATUS_FAIL,CreditLoanEnum::STATUS_FINISH));
    }


    /**
     * 通过速贷系统查询标的是否需要冻结
     * @param $deal
     * @param $repayType 1 正常还款  3提前还款
     */
    public function isNeedFreeze($deal,$repayUserId,$repayId,$repayType){
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",网信速贷请求询问冻结 dealId:".$deal['id']." ,user_id:".$repayUserId.",repayId:".$repayId);

        if(app_conf('CREDIT_LOAN_FREEZE_SWITCH') == 2){
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",网信速贷冻结开关关闭-不走速贷请求 dealId:".$deal['id'].",repayId:{$repayId},repayUserId:{$repayUserId}");
            return false;
        }

        if(app_conf('CREDIT_LOAN_FREEZE_SWITCH') == 1){
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",网信速贷冻结开关打开-不走速贷请求默认冻结 dealId:".$deal['id'].",repayId:{$repayId},repayUserId:{$repayUserId}");
            return true;
        }

        $tagInfo = (new \core\service\deal\DealTagService())->getTagByDealId($deal['id']);
        $response = SpeedloanService::triggerRepay($repayUserId, $deal['id'], $repayId,$deal['deal_type'], $deal['loantype'], $deal['type_id'], $tagInfo, $repayType);


        // 网络请求失败 为了不影响还款流程同样冻结回款本金
        if($response['data'] === false){
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",网信速贷请求失败 dealId:".$deal['id']);
            Alarm::push(CreditLoanEnum::ALARM_KEY,"速贷请求失败 deal_id:".$deal['id'].",repay_id:{$repayId},repayUserId:{$repayUserId}");
            throw new \Exception("网信速贷请求失败 deal_id:".$deal['id']." repay_id:{$repayId},repayUserId:{$repayUserId}");
        }
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",网信速贷请求成功 dealId:".$deal['id']." response:".json_encode($response['data']));
        return (isset($response['data']['repay']) && $response['data']['repay'] === true) ? true : false;
    }


    /**
     * 还款|提前还款 对银行借款处理
     * @param $dealId
     * @param $repayType 1:网信提前还款2:正常还款3:逾期还款
     * @return bool
     */
    public function dealCreditAfterRepay($dealId,$repayType) {
        try {
            $GLOBALS['db']->startTrans();
            $creditLoans = CreditLoanModel::instance()->getCreditNotRepayByDealId($dealId);
            foreach($creditLoans as $key=>$row) {
                $jobs_model = new JobsModel();
                $function = '\core\service\creditloan\CreditLoanService::dealCreditAfterRepayOne';
                $param = array(
                    'credit_loan_id' => $row->id,
                    'repay_type' => $repayType,
                );
                $jobs_model->priority = JobsEnum::PRIORITY_DEAL_REPAY;
                $r = $jobs_model->addJob($function, $param,false,10);
                if ($r === false) {
                    throw new \Exception("add dealCreditAfterRepayOne jobs error");
                }
            }
            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            \libs\utils\Logger::error($e->getMessage());
            $GLOBALS['db']->rollback();
            return false;
        }
    }

    /**
     * 还款(提前还款)完成后处理信用贷逻辑
     * 1、解冻本金
     * 2、冻结本息及管理费
     * 3、更改状态
     * 4、通知银行接口
     * @param $creditLoanId
     * @param $repayType  1:网信提前还款2:正常还款3:逾期还款
     */
    public function dealCreditAfterRepayOne($creditLoanId,$repayType) {
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .", 银信通还款请求 creditLoanId:{$creditLoanId}");
        try {
            $hasTrans = false; // 是否已经开启了事务

            $creditLoan = CreditLoanModel::instance()->find($creditLoanId);

            if(!$creditLoan) {
                throw new \Exception("申请记录不存在 id:".$creditLoanId);
            }

            /** 如果借款状态为申请中 不进行任何操作(因为银行有可能放款失败或者取消此笔贷款) 资金保持冻结状态 等待银行回调 */
            if($creditLoan->status == CreditLoanEnum::STATUS_APPLY) {
                Alarm::push(CreditLoanEnum::ALARM_KEY,"标的ID:{$creditLoan->deal_id}发生提前还款,而贷款申请ID:{$creditLoanId} 还未放款");
                return true;
            }

            // 已经是还款中状态(操作过) 不在操作
            if($creditLoan->status == CreditLoanEnum::STATUS_REPAY) {
                return true;
            }

            if($creditLoan->status != CreditLoanEnum::STATUS_USING) {
                throw new \Exception("申请记录状态值错误不能进行还款 id:".$creditLoanId);
            }

            $deal = DealModel::instance()->find($creditLoan->deal_id);
            if(!$deal) {
                throw new \Exception("标的信息不存在 deal_id:".$creditLoan->deal_id);
            }

            $user = UserService::getUserById($creditLoan->user_id);
            $accountId = AccountService::getUserAccountId($creditLoan->user_id,UserAccountEnum::ACCOUNT_INVESTMENT);
            if(!$accountId){
                throw new \Exception("未获取到账户ID userId:{$creditLoan->user_id}");
            }
            $dealService = new DealService();


            $GLOBALS['db']->startTrans();
            $hasTrans = true;

            // 1、解冻本金
            if (!AccountService::changeMoney($accountId,$creditLoan->deal_loan_money, '贷款解冻','取消冻结"'.$deal->name.'"投资本金',AccountEnum::MONEY_TYPE_UNLOCK)) {
                throw new \Exception("银信通还款解冻失败 userId:".$creditLoan->user_id);
            }

            // 2、冻结本金利息及管理费
            $lockMoney = $creditLoan->money + $creditLoan->interest + $creditLoan->service_fee;
            if (!AccountService::changeMoney($accountId,$creditLoan->deal_loan_money, '银信通还款冻结','冻结"银信通"还款金额',AccountEnum::MONEY_TYPE_LOCK)) {
                throw new \Exception("银信通还款冻结失败 userId:".$creditLoan->user_id);
            }

            // 3、更改为还款中状态 因为实际测试中发现被重复修改(jobs重复添加)，所以加了改用此方法进行判断
            $sql = " UPDATE `firstp2p_credit_loan` SET  `repay_time`='".time()."', `status`=".CreditLoanEnum::STATUS_REPAY." WHERE id=".$creditLoan->id." and status=".CreditLoanEnum::STATUS_USING;
            $affectRows = CreditLoanModel::instance()->updateRows($sql);
            if($affectRows == 0) {
                throw new \Exception("申请记录状态必须为使用中状态才可以更改为还款中");
            }

            // 4、通知银行接口还款
            $bankService = new UniteBankPaymentService();
            $params = array(
                'userId' => $creditLoan->user_id,
                'WJnlNo' => $this->genUniqueWJnlNo($creditLoan->user_id,$creditLoan->deal_id),
                'Amount' => bcadd($creditLoan->money,$creditLoan->interest,2),
                'PTime' => date('Y-m-d'),
                'PState' => $repayType,//1:网信提前还款2:正常还款3:逾期还款
                'PRate' => $creditLoan->rate,
            );

            $bankRes = $bankService->loanRepayEarlyWX($params);
            if($bankRes['respCode'] != '00') {
                throw new \Exception("还款通知银行失败 params:{".json_encode($params)."} err:".$bankRes['respMsg']);
            }
            $GLOBALS['db']->commit();
            return true;
        }catch (\Exception $ex) {
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",".$ex->getMessage());
            if($hasTrans) {
                $GLOBALS['db']->rollback();
            }
            throw $ex;
        }
    }

    /**
     * 从流水号获取用户ID及标的ID
     * @param $WJnlNo
     * @return array|bool
     */
    public static function getDetailFromWJnlNo($WJnlNo) {
        $uidDealId = explode("_",$WJnlNo);
        if(!is_array($uidDealId) || count($uidDealId) !=2) {
            return false;
        }
        return $uidDealId;
    }

    /**
     * 生成流水号
     * @param $uid
     * @param $dealId
     * @return string
     */
    public static function genUniqueWJnlNo($uid,$dealId) {
        return $uid."_".$dealId;
    }

    public function isCreditingDeal($dealId) {
        return CreditLoanModel::instance()->isCreditingDeal($dealId);
    }

    /**
     * 银行还款申请回调 如果银行还款失败 之报警不做处理
     * @param $uid
     * @param $dealId
     * @param $money 申请还款金额
     * @param $state 1 申请已受理 8 受理失败
     */
    public function repayCreditLoanApply($uid,$dealId,$money,$state) {
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",还款申请受理回调 uid:{$uid},dealId:{$dealId},money:{$money},state:{$state}");
        $param = array(
            ':user_id' => $uid,
            ':deal_id' => $dealId,
            ':status' => CreditLoanEnum::STATUS_FAIL,
        );
        $startTrans = false;

        try {
            if($state == CreditLoanEnum::BANK_REFUSE) {
                Alarm::push(CreditLoanEnum::ALARM_KEY,'还款申请不接受拒绝状态 uid:'.$uid.' deal_id:'.$dealId);
                throw new \Exception("还款申请不接受拒绝状态");
            }
            $creditLoan = CreditLoanModel::instance()->findBy("`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `status` !=':status'", '*', $param);
            if (!$creditLoan) {
                throw new \Exception("申请记录不存在 uid:" . $uid . " dealId:" . $dealId);
            }

            if ($creditLoan->status == CreditLoanEnum::STATUS_FINISH) {
                throw new \Exception("申请记录状态不允许修改");
            }

            // 银行已受理 幂等处理
            if ($creditLoan->status == CreditLoanEnum::STATUS_REPAY_HANDLE && $state == CreditLoanEnum::BANK_ACCEPT) {
                return true;
            }

            // 银行还款受理的时候状态值必须是还款中状态
            if($creditLoan->status != CreditLoanEnum::STATUS_REPAY && $state == CreditLoanEnum::BANK_ACCEPT) {
                throw new \Exception("还款受理时状态值需要在还款中状态");
            }

            $data = array(
                'repay_time' => time(),
                'status' => CreditLoanEnum::STATUS_REPAY_HANDLE,
            );

            $dealService = new DealService();

            $GLOBALS['db']->startTrans();
            $startTrans = true;
            $res = CreditLoanModel::instance()->updateCreditLoanByUidDealId($uid,$dealId,$data);
            if(!$res) {
                throw new \Exception("申请记录状态更新失败修改");
            }
            $this->repayCreditLoanSupervision($creditLoan);
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",".$ex->getMessage());
            if($startTrans) {
                $GLOBALS['db']->rollback();
            }
            throw $ex;
        }
        return true;
    }


    /**
     * 存管行银信通还款通知提现
     */
    public function repayCreditLoanSupervision($creditLoan){
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",通知存管行提现 creditId:".$creditLoan->id);
        $p2pInterest = $creditLoan->interest;
        $totalMoney = $creditLoan->deal_loan_money; // 总金额 = 用户投标本金
        $serviceFee = 0;
        if($creditLoan->service_fee > 0) {
            $mangeUid = app_conf('CREDIT_LOAN_SERVICE_FEE_ACCOUNT_ID');
            if(!$mangeUid) {
                throw new \Exception("服务费构账户未配置");
            }
            $serviceFee = $creditLoan->service_fee;
        }
        //$repayAmount = bcsub($totalMoney,$serviceFee,2);
        $repayAmount = bcadd($creditLoan->money,$creditLoan->interest,2); // 还款金额 = 借款本金+借款预计利息

        $userBankCardData = BankService::getNewCardByUserId($creditLoan->user_id);
        if(empty($userBankCardData['p_account'])){
            throw new \Exception("海口联合农商银行电子账号未设置");
        }

        $bankCardNo = $userBankCardData['p_account'];

        $orderId = Idworker::instance()->getId();
        $params = array(
            'bidId' => $creditLoan->deal_id,
            'orderId' => $orderId,
            'userId' => $creditLoan->user_id,
            'totalAmount' => bcmul($totalMoney,100),
            'repayAmount' => bcmul($repayAmount,100),
            'bankCardNo' => $bankCardNo,
        );

        if(bccomp($serviceFee,0) == 1){
            $params['chargeAmount'] = bcmul($serviceFee,100); // 收费总金额
            $params['chargeOrderList'] = json_encode(array(array(
                'subOrderId' => Idworker::instance()->getId(),
                'receiveUserId' => $mangeUid,
                'amount' => bcmul($serviceFee, 100),
            )));
        }


        $sfs = new SupervisionFinanceService();
        $bankRes = $sfs->bidElecWithdraw($params);
        if($bankRes['status'] === SupervisionEnum::RESPONSE_SUCCESS){
            $data = array(
                'order_id' => $orderId,
                'deal_id' => $creditLoan->deal_id,
                'loan_user_id' => $creditLoan->user_id,
                'money' => $repayAmount,
                'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_YXT,
                'status' => P2pIdempotentEnum::STATUS_SEND,
                'result' => P2pIdempotentEnum::RESULT_SUCC
            );
            $res =  P2pIdempotentService::addOrderInfo($orderId,$data);
            if(!$res){
                throw new \Exception("订单信息保存失败");
            }
            \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",通知存管行提现成功 orderId:".$orderId);
            return true;
        }
        throw new \Exception("存管行处理失败");
    }
    /**
     * 银行还款还款成功(失败)回调 如果银行还款失败 报警不做处理
     * @param $uid
     * @param $dealId
     * @param $state  3 还款完成 6 还款失败
     * @param $time 还款时间
     * @return bool
     * @throws \Exception
     */
    public function repayCreditLoanSuccess($uid,$dealId,$money,$state,$time) {
        $param = array(
            ':user_id' => $uid,
            ':deal_id' => $dealId,
            ':status' => CreditLoanEnum::STATUS_FAIL,
        );
        if($state == CreditLoanEnum::BANK_REPAY_FAIL) {
            Alarm::push(CreditLoanEnum::ALARM_KEY,'银行还款回调状态值为失败 系统无法处理');
            return false;
        }

        try {
            $creditLoan = CreditLoanModel::instance()->findBy("`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `status` !=':status'", '*', $param);
            if(!$creditLoan) {
                throw new \Exception("申请记录不存在 uid:".$uid." dealId:".$dealId);
            }
            // 银行已处理完成
            if($creditLoan->status == CreditLoanEnum::STATUS_FINISH && $state == CreditLoanEnum::BANK_REPAY_SUCCESS) {
                return true;
            }

            if($state == CreditLoanEnum::BANK_REPAY_SUCCESS &&  $creditLoan->status != CreditLoanEnum::STATUS_PAYMENT) {
                throw new \Exception("申请记录状态错误id:".$creditLoan->id);
            }

            $deal = DealModel::instance()->getDealInfo($creditLoan->deal_id);
            if(!$deal) {
                throw new \Exception("标的信息不存在deal_id:".$creditLoan->deal_id);
            }

            //更改为还款状态
            $creditLoan->status = CreditLoanEnum::STATUS_FINISH;
            $creditLoan->period_repay = ceil(($time - $creditLoan->loan_time) / 86400 ); // 实际借款期限
            $creditLoan->finish_time = $time;
            $creditLoan->interest = bcsub($money,$creditLoan->money,2); // 保存实际利息 = 银行还款金额-本金
            $saveRes = $creditLoan->save();
            if(!$saveRes) {
                throw new \Exception("申请记录状态修改失败");
            }
            return true;
        }catch (\Exception $ex) {
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",".$ex->getMessage());
            throw $ex;
        }
    }

    /**
     * 支付回调处理
     * @param $userId
     * @param $dealId
     */
    public function LoanCreditLoanForPay($userId,$dealId) {
        $param = array(
            ':user_id' => $userId,
            ':deal_id' => $dealId,
            ':status' => CreditLoanEnum::STATUS_FAIL,
        );
        $startTrans = false;
        $dealService = new DealService();
        $dealId = intval($dealId);

        try {
            $creditLoan = CreditLoanModel::instance()->findBy("`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `status` !=':status'", '*', $param);
            if(!$creditLoan) {
                throw new \Exception("申请记录不存在 uid:".$userId." dealId:".$dealId);
            }
            if($creditLoan->status == CreditLoanEnum::STATUS_PAYMENT) {
                return true;
            }

            if($creditLoan->status != CreditLoanEnum::STATUS_REPAY_HANDLE) {
                throw new \Exception("支付需要在状态为还款受理中时才能发生");
            }

            $accountId = AccountService::getUserAccountId($creditLoan->user_id,UserAccountEnum::ACCOUNT_INVESTMENT);
            if(!$accountId){
                throw new \Exception("未获取到账户ID userId:{$creditLoan->user_id}");
            }

            $GLOBALS['db']->startTrans();
            $startTrans = true;

            $creditLoan->status = CreditLoanEnum::STATUS_PAYMENT;
            $saveRes = $creditLoan->save();
            if(!$saveRes) {
                throw new \Exception("申请记录状态修改失败");
            }
            // 扣除本金、利息
            $p2pInterest = $creditLoan->interest; // 网信扣除利息和银行实际利息可能不一样，此处扣除的是网信自己计算的利息
            $totalMoney = $creditLoan->money + $creditLoan->interest;


            if (!AccountService::changeMoney($accountId,$totalMoney, '银信通还款','银信通还款成功',AccountEnum::MONEY_TYPE_LOCK_REDUCE)) {
                throw new \Exception("银信通还款解冻失败 userId:".$creditLoan->user_id);
            }

            if($creditLoan->service_fee > 0) {
                $mangeUid = app_conf('CREDIT_LOAN_SERVICE_FEE_ACCOUNT_ID');
                if(!$mangeUid) {
                    throw new \Exception("服务费机构账户未配置");
                }

                // 重新计算管理费
                $nowTimestamp = time();
                $period_apply = ceil(($nowTimestamp - $creditLoan->loan_time) / 86400 );
                // 实际使用天数和预期使用天数之间取最小值
                $period_apply = min($period_apply, ceil($creditLoan->period_apply));
                $serviceFeeRate = $this->getCreditLoanServiceRate();
                $serviceFee = floorfix($creditLoan->money * $serviceFeeRate * $period_apply / DealModel::DAY_OF_YEAR, 2);
                // 计算多冻结的管理费
                $remainServiceFee = bcsub($creditLoan->service_fee, $serviceFee, 2);
                // 更新用户实际的管理费
                $creditLoan->service_fee = $serviceFee;
                $creditLoan->period_apply = $period_apply;
                $creditLoan->save();

                // 扣除管理费， 允许扣负

                if (!AccountService::changeMoney($accountId,$creditLoan->service_fee, '业务信息服务费','银信通业务信息服务费',AccountEnum::MONEY_TYPE_LOCK_REDUCE)) {
                    throw new \Exception("银信通业务信息服务费扣减冻结失败 userId:".$creditLoan->user_id);
                }

                // 返还剩余冻结的管理费用
                if (bccomp($remainServiceFee, '0.00', 2) > 0) {
                    if (!AccountService::changeMoney($accountId,$creditLoan->service_fee, '业务信息服务费解冻','解冻银信通剩余业务信息服务费',AccountEnum::MONEY_TYPE_UNLOCK)) {
                        throw new \Exception("业务信息服务费解冻 userId:".$creditLoan->user_id);
                    }
                }

                if (!AccountService::changeMoney($mangeUid,$creditLoan->service_fee, '业务信息服务费','银信通业务信息服务费',AccountEnum::MONEY_TYPE_INCR)) {
                    throw new \Exception("业务信息服务费 userId:".$creditLoan->user_id);
                }
            }
            $GLOBALS['db']->commit();
            return true;
        }catch (\Exception $ex) {
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",".$ex->getMessage());
            if($startTrans) {
                $GLOBALS['db']->rollback();
            }
            throw $ex;
        }
    }


    /**
     * 使用天数 = 标的还款时间 - 银行放款时间
     * 本金/360 x (可使用的天数+n) x 可使用天数对应的利率
     * @param int $repayTime 标的还款时间 无8小时差别
     */
    public function getLoanInterest($creditLoan,$repayTime=false) {
        if(!$repayTime) {
            $repayTime = DealRepayModel::instance()->getMaxRepayTimeByDealId($creditLoan->deal_id,array(0,1,2,3,4));
            $repayTime = strtotime(to_date($repayTime['repay_time']));
        }
        $endTime = $creditLoan->loan_time ? $creditLoan->loan_time : $creditLoan->create_time;
        if($endTime > $repayTime ) { // 如果放款的时候已经还款了 那么放款时间是大于标的还款时间的 此时使用天数应该是 0
            $useDays = 0;
        }else{
            $useDays = ceil($repayTime - $endTime)/86400;
        }
        $n = app_conf('CREDIT_LOAN_EXTRA_DAY') ? app_conf('CREDIT_LOAN_EXTRA_DAY') : 15;
        return DealModel::instance()->floorfix($creditLoan->money /360 * ($useDays + $n) * $creditLoan->rate/100,2);
    }

    /**
     * @param int $record_count
     */
    public function getCreditLoanCountByUserId($user_id)
    {
        $record_count = CreditLoanModel::instance()->getCreditLoanCountByUserId($user_id);

        return $record_count;
    }


    /**
     * 获取银行借款-平台服务费
     * @return int 平台服务费
     */
    public function getCreditLoanServiceRate()
    {
        return (app_conf('CREDIT_LOAN_SERVICE_RATE') !== '') ? floatval(app_conf('CREDIT_LOAN_SERVICE_RATE')) / 100 : 0.01;
    }

    /**
     * 获取银信通-质押率
     * @return float 银信通-质押率
     */
    public function getCreditLoanProportionLoanRate()
    {
        return (app_conf('CREDIT_LOAN_PROPORTION_LOAN_RATE') !== '') ? floatval(app_conf('CREDIT_LOAN_PROPORTION_LOAN_RATE')) : 0.9;
    }

    /**
     * 获取用户使用中的借款信息
     * 用户只能有一笔进行中的借款，这个在业务上有控制（程序上并没有控制）
     * 所以此处指返回一条就可以了
     */
    public function getCreditingLoanByUserId($user_id) {
        $status = CreditLoanEnum::STATUS_USING;
        $res = CreditLoanModel::instance()->getCreditLoanByUserIdAndStatus($user_id,$status);
        return empty($res) ? array() : $res;
    }

    public function caculateUserCreditLoanSummary($userId) {
        return CreditLoanModel::instance()->caculateUserCreditLoanSummary($userId);
    }

    public function getNotFinishCreditCount($userId){
        $param = array(
            ':user_id' => $userId,
            ':status' => CreditLoanEnum::STATUS_FAIL . "," . CreditLoanEnum::STATUS_FINISH,
        );
        return CreditLoanModel::instance()->count("`user_id` = ':user_id' AND `status`  NOT in (':status')",$param);
    }

    /**
     * 存管行支付提现回调
     * @param $orderId
     * @param $status
     * @return bool
     * @throws \Exception
     */
    public function repaySupervisionCallBack($orderId,$status){
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",存管行提现回调 orderId:".$orderId);
        if($status == P2pDepositoryEnum::CALLBACK_STATUS_FAIL){
            throw new \Exception('存管回调不支持失败状态');
        }
        $orderService = new P2pIdempotentService();
        $orderInfo = $orderService->getInfoByOrderId($orderId);
        $uid = $orderInfo['loan_user_id'];
        $dealId = $orderInfo['deal_id'];

        $res = P2pIdempotentService::updateStatusByOrderId($orderId,P2pIdempotentEnum::STATUS_CALLBACK);
        if(!$res){
            throw new \Exception("订单信息保存失败");
        }
        return $this->LoanCreditLoanForPay($uid,$dealId);
    }

    /**
     * 回款冻结通知速贷
     * @param $userId
     * @param $dealId
     */
    public function freezeNotifyCreditloan($userId,$dealId,$repayId,$repayType,$batchOrderId=0,$merchantId=0){
        $jobs_model = new JobsModel();
        $function = '\core\service\creditloan\CreditLoanService::freezeNotifyCreditloanJob';
        $param = array(
            'userId' => $userId,
            'dealId' => $dealId,
            'repayId' => $repayId,
            'repayType' => $repayType,
            'batchOrderId' => $batchOrderId,
            'merchantId' => $merchantId,
        );
        $jobs_model->priority = JobsEnum::REPAY_FREEZE_NOTIFY_SUDAI;
        $r = $jobs_model->addJob($function, $param);
        if ($r === false) {
            throw new \Exception("add freezeNotifyCreditloanJob jobs error");
        }
        return true;
    }

    public function freezeNotifyCreditloanJob($userId,$dealId,$repayId,$repayType,$batchOrderId=0,$merchantId='0'){
        $deal = DealModel::instance()->getDealInfo($dealId);
        $dealService = new DealService();
        $response = SpeedloanService::backendRepayApply($userId,$dealId,$repayId,0,$repayType);

        if($response === false){
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",网信速贷请求失败 dealId:".$dealId);
            throw new \Exception("网络请求失败");
        }
        return (isset($response['data']['apply']) && $response['data']['apply'] === true) ? true : false;
    }

    public function isShowCreditEntrance($userId)
    {
        return CreditLoanModel::instance()->isShowCreditEntrance($userId);
    }
}
