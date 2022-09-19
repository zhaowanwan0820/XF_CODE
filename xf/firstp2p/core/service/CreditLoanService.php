<?php

/**
 * 信用贷service
 */

namespace core\service;

use core\dao\DealPrepayModel;
use core\dao\UserModel;
use core\service\DealService;
use core\dao\CreditLoanModel;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\DealLoadModel;
use core\dao\DealLoanTypeModel;
use core\dao\FinanceQueueModel;
use libs\utils\Finance;
use core\service\UniteBankPaymentService;
use core\service\AccountService;
use libs\utils\Alarm;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use libs\utils\Rpc;
use NCFGroup\Common\Library\Idworker;
use web\controllers\oauth\User;
use core\dao\JobsModel;
use \msgcenter;
use libs\utils\Site;
use core\dao\DealExtModel;
use core\service\ZxDealRepayService;

use core\service\CreditLoanConfigService;
use NCFGroup\Protos\Creditloan\RequestTriggerRepay;
use NCFGroup\Protos\Creditloan\RequestBackendRepayApply;

class CreditLoanService extends BaseService {
    const ALARM_KEY = 'CREDITLOAN_APPLY';
    const DEAL_INVEST_MONEY = 7500; //单个标的投资金额（大于此值时,显示信用贷）
    const MIN_LOAD_MONEY = 10000; //账户最小投资额（大于此值时,显示变现通)）
    const CREDIT_LOAN_CONT_TEMPLATE = 'CREDIT_LOAN_PROTOCOL';

    static $status_mark = array(
        CreditLoanModel::STATUS_APPLY           => "申请中",
        CreditLoanModel::STATUS_USING           => "使用中",
        CreditLoanModel::STATUS_FAIL            => "已取消",
        CreditLoanModel::STATUS_PAYMENT         => "还款中",
        CreditLoanModel::STATUS_REPAY           => "还款中",
        CreditLoanModel::STATUS_REPAY_HANDLE    => "还款中",
        CreditLoanModel::STATUS_FINISH          => "已还清",
    );

    const BANK_ACCEPT = 1; //申请已受理
    const BANK_LOAN_SUCCESS = 2; // 银行放款成功
    const BANK_LOAN_FAIL = 4 ;// 银行放款失败
    const BANK_REPAY_SUCCESS = 3; // 还款成功
    const BANK_REPAY_FAIL = 6; // 还款失败
    const BANK_REFUSE = 8; // 受理失败

    /**
     * 是否是正在借贷中的用户
     *
     * @param $userId
     * @param $dealId
     */
    public function isCreditingUser($userId,$dealId) {
        $row = CreditLoanModel::instance()->getCreditLoan($userId,$dealId);
        return $row && !in_array($row->status,array(CreditLoanModel::STATUS_FAIL,CreditLoanModel::STATUS_FINISH));
    }

    public function isShowCreditEntrance($userId)
    {
        return CreditLoanModel::instance()->isShowCreditEntrance($userId);
    }

    public function isCreditingDeal($dealId) {
        return CreditLoanModel::instance()->isCreditingDeal($dealId);
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
                $function = '\core\service\CreditLoanService::dealCreditAfterRepayOne';
                $param = array(
                    'credit_loan_id' => $row->id,
                    'repay_type' => $repayType,
                );
                $jobs_model->priority = 90;
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
            if($creditLoan->status == CreditLoanModel::STATUS_APPLY) {
                Alarm::push(self::ALARM_KEY,"标的ID:{$creditLoan->deal_id}发生提前还款,而贷款申请ID:{$creditLoanId} 还未放款");
                return true;
            }

            // 已经是还款中状态(操作过) 不在操作
            if($creditLoan->status == CreditLoanModel::STATUS_REPAY) {
                return true;
            }

            if($creditLoan->status != CreditLoanModel::STATUS_USING) {
                throw new \Exception("申请记录状态值错误不能进行还款 id:".$creditLoanId);
            }

            $deal = DealModel::instance()->find($creditLoan->deal_id);
            if(!$deal) {
                throw new \Exception("标的信息不存在 deal_id:".$creditLoan->deal_id);
            }

            $user = UserModel::instance()->find($creditLoan->user_id);
            $dealService = new \core\service\DealService();
            $isYJ175 = $dealService->isDealYJ175($creditLoan->deal_id);

            $GLOBALS['db']->startTrans();
            $hasTrans = true;

            // 1、解冻本金
            $user->changeMoneyDealType = $dealService->getDealType($deal);
            $user->isDoNothing = $isYJ175 ? true : false;
            $user->changeMoney(-$creditLoan->deal_loan_money, '贷款解冻', '取消冻结"'.$deal->name.'"投资本金',0,0,UserModel::TYPE_LOCK_MONEY);

            // 2、冻结本金利息及管理费
            $lockMoney = $creditLoan->money + $creditLoan->interest + $creditLoan->service_fee;
            $user->changeMoney($lockMoney, '银信通还款冻结','冻结"银信通"还款金额',0,0,UserModel::TYPE_LOCK_MONEY);

            // 3、更改为还款中状态 因为实际测试中发现被重复修改(jobs重复添加)，所以加了改用此方法进行判断
            $sql = " UPDATE `firstp2p_credit_loan` SET  `repay_time`='".time()."', `status`=".CreditLoanModel::STATUS_REPAY." WHERE id=".$creditLoan->id." and status=".CreditLoanModel::STATUS_USING;
            $affectRows = CreditLoanModel::instance()->updateRows($sql);
            if($affectRows == 0) {
                throw new \Exception("申请记录状态必须为使用中状态才可以更改为还款中");
            }
//            $creditLoan->status = CreditLoanModel::STATUS_REPAY;
//            $creditLoan->repay_time = time();
//            $saveRes = $creditLoan->save();
//            if(!$saveRes) {
//                throw new \Exception("申请记录状态修改失败");
//            }

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
     * 撤销信用贷申请或 放款失败
     * @param $creditLoanId
     */
    public function revokeCreditLoanByLoanId($creditLoanId) {
        try {
            $GLOBALS['db']->startTrans();
            $creditLoan = CreditLoanModel::instance()->find($creditLoanId);
            if(!$creditLoan) {
                throw new \Exception("申请记录不存在 id:".$creditLoanId);
            }
            $deal = DealModel::instance()->find($creditLoan->deal_id);
            if(!$deal) {
                throw new \Exception("标的信息不存在 deal_id:".$creditLoan->deal_id);
            }
            if($creditLoan->status != CreditLoanModel::STATUS_APPLY) {
                throw new \Exception("当前状态不允许撤销申请 id:".$creditLoanId);
            }

            /**
             * TODO: 风险控制
             * 如果在撤销时候已经发生了提前还款那么需要将用户冻结的本金解冻
             * 有一种异常情况,就是理财已经提前还款了，但是这时候银行的申请受理还没有回调过来
             * 这时候理财还款是没有冻结的。如果银行在回调放款失败接口，那么此时无法判断
             * 用户是否有贷款冻结，所以如果此时依旧取消冻结的话那么用户的现金余额会变多
             * 这是一个风险点
             *
             * 解决方案
             * 银行在受理回调的时候如果我们已经还款完成直接将改比贷款置为失败状态并报警
             * 后续如果银行再次放款回调或其他回调都不做处理了
             */
            if($deal->deal_status == DealModel::$DEAL_STATUS['repaid']) {
                $user = UserModel::instance()->find($creditLoan->user_id);
                if(!$user) {
                    throw new \Exception("贷款用户ID不存在 user_id:".$creditLoan->user_id);
                }
                $user->changeMoneyDealType = (new \core\service\DealService())->getDealType($deal);
                $user->changeMoney(-$creditLoan->deal_loan_money, '贷款撤销', '取消冻结"'.$deal->name.'"投资本金',0,0,UserModel::TYPE_LOCK_MONEY);
            }
            $creditLoan->status = CreditLoanModel::STATUS_FAIL;
            $creditLoan->update_time = time();
            $saveRes = $creditLoan->save();
            if(!$saveRes) {
                throw new \Exception("申请记录撤销失败");
            }
            $GLOBALS['db']->commit();
            return true;
        }catch (\Exception $ex) {
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",".$ex->getMessage());
            $GLOBALS['db']->rollback();
            return false;
        }
    }
    /**
     * 银行撤销信用贷申请回调
     * @param $creditLoanId
     */
//    public function revokeCreditLoanCallBack($request) {
//        try {
//            $creditLoanId = $request['credit_loan_id'];
//            $GLOBALS['db']->startTrans();
//            $creditLoan = CreditLoanModel::instance()->find($creditLoanId);
//            if(!$creditLoan) {
//                throw new \Exception("申请记录不存在 id:".$creditLoanId);
//            }
//            $deal = DealModel::instance()->find($creditLoan->deal_id);
//            if(!$deal) {
//                throw new \Exception("标的信息不存在 deal_id:".$creditLoan->deal_id);
//            }
//            if($creditLoan->status != CreditLoanModel::STATUS_APPLY) {
//                throw new \Exception("当前状态不允许撤销申请 id:".$creditLoanId);
//            }
//            if($request['status'] == 0) {//撤销成功
//                // 更改状态为申请撤销成功状态
//                $creditLoan->status = CreditLoanModel::STATUS_REVOKE_SUCCESS;
//                $saveRes = $creditLoan->save();
//                if(!$saveRes) {
//                    throw new \Exception("申请记录状态修改失败");
//                }
//            }
//
//            $GLOBALS['db']->commit();
//            return true;
//        }catch (\Exception $ex) {
//            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",".$ex->getMessage());
//            $GLOBALS['db']->rollback();
//            return false;
//        }
//    }

    /**
     * 申请信用贷款的方法
     * 以银行返回结果为准 网信只做校验不做阻止
     * @param int $user_id
     * @param int $deal_id
     * @param boole $isPassed
     * @param float $money 贷款金额
     * @param float $rate 贷款利率
     * @return bool
     */
    public function applyCreditLoan($uid, $dealId, $isPassed,$money,$bankRate) {
        $paramsStr = " uid:{$uid},dealId:{$dealId},money:{$money},isPassed:{$isPassed},bankRate:{$bankRate}";
        $alarmInfo = array(); // 报警信息

        // 首先获取用户的投资记录
        $loadMoney = DealLoadModel::instance()->getSumByDealUserId($uid, $dealId);
        $proportionLoanRate = $this->getCreditLoanProportionLoanRate();
        if ($loadMoney < 0 || 1 == bccomp($money, ceilfix($loadMoney * $proportionLoanRate), 2)) {
            $alarmInfo[] = '借款金额不正确';
        }

        // 获取标的信息
        $ds = new DealService();
        $deal = $ds->getDeal($dealId);

        // 仅还款中的标的允许借款
        if ($deal['deal_status'] != DealModel::$DEAL_STATUS['repaying']) {
            $alarmInfo[] = '仅还款中标的允许发起借款,此贷款直接置为撤销';
            $isPassed = false; // 如果受理时候已经还款了，直接将状态置为失败
            Alarm::push(self::ALARM_KEY,"标的：{$dealId} 已经还款完成 请及时通知银行。该笔贷款将自动置为失败状态");
        }
        $loanRepayTypes = explode(CreditLoanConfigService::$configDelimiter, app_conf('CREDIT_LOAN_DEAL_REPAY_TYPE'));
        if ($deal['deal_type'] == DealModel::DEAL_TYPE_COMPOUND || !in_array($deal['loantype'], $loanRepayTypes)) {
            $alarmInfo[] = '标的类型不正确';
        }

        $type_tag = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal['type_id']);
        if ($type_tag == DealLoanTypeModel::TYPE_BXT) {
            $alarmInfo[] = '变现通不允许申请贷款';
        }

        //哈哈农庄化肥标不允许申请贷款
        if($ds->isDealHF($dealId)) {
            $alarmInfo[] = '哈哈农庄化肥标不允许申请贷款';
        }

        // 借款期限及持有期限
        $repay_periods = $deal['loantype'] == 5 ? $deal['repay_time'] : $deal['repay_time'] * DealModel::DAY_OF_MONTH;
        $flag = true;
        if ($repay_periods > 36*DealModel::DAY_OF_MONTH) {
            // 标的总借款时间为36个月以内（含36个月）
            $flag = false;
        } elseif ($repay_periods <= 92 && (to_timespan(date('Y-m-d')) - $deal['repay_start_time']) < app_conf('CREDIT_LOAN_HOLD_TERM_LT_3')*86400 ) {
            // 3个月（含）以内的标的：客户已经投资并持续持有≥n天
            $flag = false;
        } elseif ($repay_periods > 92 && (to_timespan(date('Y-m-d')) - $deal['repay_start_time']) < app_conf('CREDIT_LOAN_HOLD_TERM_GE_3')*86400) {
            // 3个月以上的标的：客户已经投资并持续持有≥n天
            $flag = false;
        }

        $max_repay = DealRepayModel::instance()->getMaxRepayTimeByDealId($dealId, array(0,1,2,3,4));
        if ( ($max_repay['repay_time'] - to_timespan(date('Y-m-d')) < app_conf('CREDIT_LOAN_REMAINNING_DAYS')*86400) ) {
            // 标的到期日距离贷款申请日≥n天
            $flag = false;
        }

        if ($flag === false) {
            $alarmInfo[] = '持有期限不正确';
        }

        // 计算借款利率
        $period_apply = ceil(($max_repay['repay_time'] - to_timespan(date('Y-m-d'))) / 86400 ); // 预计借款期限
        $rate = $this->getLoanRate($period_apply);

        if ($rate === false || $rate != $bankRate) {
            $alarmInfo[] = '存续期限不正确或与银行返回不符';
        }

        $serviceFee = 0;
        $status = $isPassed ? CreditLoanModel::STATUS_APPLY : CreditLoanModel::STATUS_FAIL;

        if(!empty($alarmInfo)) {
            $alarmInfoStr = $paramsStr ."|". implode(",",$alarmInfo);
            Alarm::push(self::ALARM_KEY,$alarmInfoStr);
        }
        return CreditLoanModel::instance()->createCreditLoan($uid, $dealId, $money, $period_apply, $bankRate, strtotime(to_date($max_repay['repay_time'])), $loadMoney,$serviceFee,$status);
    }

    /**
     * 供回调使用，申请成功或失败
     * @param int $creditLoanId
     * @param bool $is_passed
     */
    public function verifyCreditLoan($uid,$dealId, $isPassed,$money,$rate) {
        $creditLoan = CreditLoanModel::instance()->findBy("user_id=".$uid." AND deal_id=".$dealId." AND status !=".CreditLoanModel::STATUS_FAIL);
        if(!$creditLoan) {
            return $this->applyCreditLoan($uid,$dealId,$isPassed,$money,$rate);
        }

        // 已受理 幂等
        if($creditLoan && $isPassed && $creditLoan->status == CreditLoanModel::STATUS_APPLY) {
            return true;
        }
        // 受理失败 幂等
        if($creditLoan && !$isPassed && $creditLoan->status == CreditLoanModel::STATUS_FAIL) {
            return true;
        }
        throw new \Exception("当前状态不允许对申请记录状态进行回调修改");
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
            ':status' => CreditLoanModel::STATUS_FAIL,
        );
        $startTrans = false;

        try {
            if($state == self::BANK_REFUSE) {
                Alarm::push(self::ALARM_KEY,'还款申请不接受拒绝状态 uid:'.$uid.' deal_id:'.$dealId);
                throw new \Exception("还款申请不接受拒绝状态");
            }
            $creditLoan = CreditLoanModel::instance()->findBy("`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `status` !=':status'", '*', $param);
            if (!$creditLoan) {
                throw new \Exception("申请记录不存在 uid:" . $uid . " dealId:" . $dealId);
            }

            if ($creditLoan->status == CreditLoanModel::STATUS_FINISH) {
                throw new \Exception("申请记录状态不允许修改");
            }

            // 银行已受理 幂等处理
            if ($creditLoan->status == CreditLoanModel::STATUS_REPAY_HANDLE && $state == self::BANK_ACCEPT) {
                return true;
            }

            // 银行还款受理的时候状态值必须是还款中状态
            if($creditLoan->status != CreditLoanModel::STATUS_REPAY && $state == self::BANK_ACCEPT) {
                throw new \Exception("还款受理时状态值需要在还款中状态");
            }

            $data = array(
                'repay_time' => time(),
                'status' => CreditLoanModel::STATUS_REPAY_HANDLE,
            );

            $dealService = new \core\service\DealService();
            $isP2pPath = $dealService->isP2pPath(intval($dealId));
            $isYjDeal = $dealService->isDealYJ175($dealId);

            $GLOBALS['db']->startTrans();
            $startTrans = true;
            $res = CreditLoanModel::instance()->updateCreditLoanByUidDealId($uid,$dealId,$data);
            if(!$res) {
                throw new \Exception("申请记录状态更新失败修改");
            }

            // 盈嘉1.75标的不行也要在通知支付提现
            if($isYjDeal === false){
                if($isP2pPath){
                    $this->repayCreditLoanSupervision($creditLoan);
                }else{

                    // 通知支付提现到银行
                    $bankParams = array(
                        'userId' => $creditLoan->user_id,
                        'amount' => bcadd($creditLoan->money,$creditLoan->interest,2) * 100,
                        'outOrderId' => $this->genUniqueWJnlNo($creditLoan->user_id,$creditLoan->deal_id),
                    );
                    \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",通知支付提现 params:".json_encode($bankParams));

                    $bankService = new UniteBankPaymentService();
                    $bankRes = $bankService->withdrawTrustBank($bankParams);
                    if(!$bankRes) {
                        throw new \Exception("银行处理失败");
                    }
                }
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",".$ex->getMessage());
            if($startTrans) {
                $GLOBALS['db']->rollback();
            }
            throw $ex;
        }

        // 盈嘉1.75标的不存在支付回调，所以在还款回调时候自动进行支付回调
        if($isYjDeal === true){
            \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",盈嘉还款模拟支付进行回调 uid:{$uid},dealId:{$dealId}");
            $this->LoanCreditLoanForPay($uid,$dealId);
        }
        return true;
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
            ':status' => CreditLoanModel::STATUS_FAIL,
        );
        if($state == self::BANK_REPAY_FAIL) {
            Alarm::push(self::ALARM_KEY,'银行还款回调状态值为失败 系统无法处理');
            return false;
        }

        try {
            $creditLoan = CreditLoanModel::instance()->findBy("`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `status` !=':status'", '*', $param);
            if(!$creditLoan) {
                throw new \Exception("申请记录不存在 uid:".$uid." dealId:".$dealId);
            }
            // 银行已处理完成
            if($creditLoan->status == CreditLoanModel::STATUS_FINISH && $state == self::BANK_REPAY_SUCCESS) {
                return true;
            }

            if($state == self::BANK_REPAY_SUCCESS &&  $creditLoan->status != CreditLoanModel::STATUS_PAYMENT) {
                throw new \Exception("申请记录状态错误id:".$creditLoan->id);
            }

            $deal = DealModel::instance()->find($creditLoan->deal_id);
            if(!$deal) {
                throw new \Exception("标的信息不存在deal_id:".$creditLoan->deal_id);
            }

            //更改为还款状态
            $creditLoan->status = CreditLoanModel::STATUS_FINISH;
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
     * 银行放款回调使用
     * @param $uid
     * @param $dealId
     * @param $money 放款金额
     * @param $timestap 放款时间
     * @param $state 放款状态 2 已放款  4 放款失败
     */
    public function LendCreditLoanSuccess($uid,$dealId,$money,$timestap,$state) {
        $param = array(
            ':user_id' => $uid,
            ':deal_id' => $dealId,
            ':status' => CreditLoanModel::STATUS_FAIL,
        );
        $startTrans = false;
        try{
            $creditLoan = CreditLoanModel::instance()->findBy("`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `status` !=':status'", '*', $param);
            // 幂等处理
            if($state == self::BANK_LOAN_FAIL && !$creditLoan) {
                return true;
            }
            if(!$creditLoan) {
                throw new \Exception("申请记录不存在 uid:".$uid." dealId:".$dealId);
            }

            if($creditLoan->status == CreditLoanModel::STATUS_USING && $state == self::BANK_LOAN_SUCCESS) {
                return true;
            }

            if($creditLoan->status != CreditLoanModel::STATUS_APPLY) {
                throw new \Exception("当前状态不允许修改 uid:".$uid." dealId:".$dealId);
            }

            $deal = DealModel::instance()->find($creditLoan->deal_id);
            if(!$deal) {
                throw new \Exception("标的信息不存在deal_id:".$creditLoan->deal_id);
            }

            //$max_repay = DealRepayModel::instance()->getMaxRepayTimeByDealId($dealId, array(0,1,2,3,4));
            //$max_repay_time = strtotime(to_date($max_repay['repay_time']));

            $period_apply = $creditLoan->plan_time ? ceil(($creditLoan->plan_time - $timestap) / 86400 ) : 0;

            if($state == self::BANK_LOAN_SUCCESS) {
                $GLOBALS['db']->startTrans();
                $startTrans = true;

                // 放款成功计算服务费 如果放款时已经发生提前还款 则服务费按0收取
                if($deal->deal_status == DealModel::$DEAL_STATUS['repaid']) {
                    $serviceFee = 0;
                }else {
                    $serviceFeeRate = $this->getCreditLoanServiceRate();
                    $serviceFee = DealModel::instance()->floorfix($money * $serviceFeeRate * $period_apply / DealModel::DAY_OF_YEAR, 2);
                }
                $creditLoan->loan_time = $timestap;
                $creditLoan->money = $money;
                $data = array(
                    'money' => $money,
                    'loan_time' => $timestap,
                    'service_fee' => $serviceFee,
                    'period_apply' => $period_apply, // 预计贷款期限
                    'interest' => $this->getLoanInterest($creditLoan,$creditLoan->plan_time),
                    'status' => CreditLoanModel::STATUS_USING,
                );
                $updateRes = CreditLoanModel::instance()->updateCreditLoanByUidDealId($uid,$dealId,$data);

                if(!$updateRes) {
                    throw new \Exception("更新贷款申请状态失败 ID:".$creditLoan->id);
                }
                // 如果放款的时候已经还款了 需要将还款未完成逻辑走完
                if($deal->deal_status == DealModel::$DEAL_STATUS['repaid']) {
                    $this->dealCreditAfterRepayOne($creditLoan->id,1);
                }

                if (app_conf("SMS_ON")==1){
                    $sms_content = array(
                        'time' => date('m月d日',$creditLoan->loan_time),
                        'money' => $creditLoan->money,
                    );

                    $user = UserModel::instance()->find($creditLoan->user_id);
                    \libs\sms\SmsServer::instance()->send($user['mobile'], 'TPL_SMS_CREDIT_LOAN_SUCCESS', $sms_content, $user['id']);

                }

                $GLOBALS['db']->commit();
                return true;
            }else {
                return $this->revokeCreditLoanByLoanId($creditLoan->id);
            }
        }catch (\Exception $ex) {
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",".$ex->getMessage());
            if($startTrans) {
                $GLOBALS['db']->rollback();
            }
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
            ':status' => CreditLoanModel::STATUS_FAIL,
        );
        $startTrans = false;
        $dealService = new \core\service\DealService();
        $dealId = intval($dealId);
        $isP2pPath = $dealService->isP2pPath($dealId);
        $isYj175 = $dealService->isDealYJ175($dealId);
        try {
            $creditLoan = CreditLoanModel::instance()->findBy("`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `status` !=':status'", '*', $param);
            if(!$creditLoan) {
                throw new \Exception("申请记录不存在 uid:".$userId." dealId:".$dealId);
            }
            if($creditLoan->status == CreditLoanModel::STATUS_PAYMENT) {
                return true;
            }

            if($creditLoan->status != CreditLoanModel::STATUS_REPAY_HANDLE) {
                throw new \Exception("支付需要在状态为还款受理中时才能发生");
            }

            $user = UserModel::instance()->find($creditLoan->user_id);
            if(!$user) {
                throw new \Exception("用户ID不存在 uid:".$creditLoan->user_id);
            }

            $GLOBALS['db']->startTrans();
            $startTrans = true;

            $creditLoan->status = CreditLoanModel::STATUS_PAYMENT;
            $saveRes = $creditLoan->save();
            if(!$saveRes) {
                throw new \Exception("申请记录状态修改失败");
            }
            // 扣除本金、利息
            $p2pInterest = $creditLoan->interest; // 网信扣除利息和银行实际利息可能不一样，此处扣除的是网信自己计算的利息
            $totalMoney = $creditLoan->money + $creditLoan->interest;
            $user->changeMoneyDealType = $dealService->getDealType($dealId);
            $user->isDoNothing = $isYj175 ? true : false;
            $user->changeMoney($totalMoney, '银信通还款','银信通还款成功',0,0,UserModel::TYPE_DEDUCT_LOCK_MONEY);

            if($creditLoan->service_fee > 0) {
                $mangeUid = app_conf('CREDIT_LOAN_SERVICE_FEE_UID');
                if(!$mangeUid) {
                    throw new \Exception("服务费机构账户未配置");
                }
                $mangeUser = UserModel::instance()->find($mangeUid);
                if(!$mangeUser) {
                    throw new \Exception("服务费机构账户不存在");
                }
                // 重新计算管理费
                $nowTimestamp = time();
                $period_apply = ceil(($nowTimestamp - $creditLoan->loan_time) / 86400 );
                // 实际使用天数和预期使用天数之间取最小值
                $period_apply = min($period_apply, ceil($creditLoan->period_apply));
                $serviceFeeRate = $this->getCreditLoanServiceRate();
                $serviceFee = DealModel::instance()->floorfix($creditLoan->money * $serviceFeeRate * $period_apply / DealModel::DAY_OF_YEAR, 2);
                // 计算多冻结的管理费
                $remainServiceFee = bcsub($creditLoan->service_fee, $serviceFee, 2);
                // 更新用户实际的管理费
                $creditLoan->service_fee = $serviceFee;
                $creditLoan->period_apply = $period_apply;
                $creditLoan->save();

                // 扣除管理费， 允许扣负
                $user->changeMoney($creditLoan->service_fee, '业务信息服务费','银信通业务信息服务费',0,0,UserModel::TYPE_DEDUCT_LOCK_MONEY, 1);
                // 返还剩余冻结的管理费用
                if (bccomp($remainServiceFee, '0.00', 2) > 0) {
                    $user->changeMoney(-$remainServiceFee, '业务信息服务费解冻','解冻银信通剩余业务信息服务费',0,0,UserModel::TYPE_LOCK_MONEY);
                }

                $mangeUser->changeMoneyDealType = $dealService->getDealType($dealId);
                $mangeUser->isDoNothing = $isYj175 ? true : false;;
                $mangeUser->changeMoney($creditLoan->service_fee, '业务信息服务费','银信通业务信息服务费',0,0,UserModel::TYPE_MONEY);

                $syncRemoteData[] = array(
                    'outOrderId' => 'PREPAYINTEREST|' . $creditLoan->id,
                    'payerId' => $user->id,
                    'receiverId' => $mangeUid,
                    'repaymentAmount' => bcmul($creditLoan->service_fee, 100),
                    'curType' => 'CNY',
                    'bizType' => 1,
                    'batchId' => $creditLoan->id,
                );
                // 支付转账
                if ($isYj175 === false && !$isP2pPath && !FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH)) {
                    throw new \Exception("FinanceQueueModel push error");
                }
            }
            if($isYj175 === true){
                $yjService = new ZxDealRepayService();
                $yjService->genYXTDFRecord($user->id,$dealId,$creditLoan->money,$creditLoan->interest,$creditLoan->service_fee);
            }


            if (app_conf("SMS_ON")==1){
                $sms_content = array(
                    'repay_time' => date('m月d日 H时i分'),
                    'money' => bcadd($totalMoney,$creditLoan->service_fee,2),
                    'principal' => $creditLoan->money,
                    'interest' => $p2pInterest,
                    'service_fee' => $creditLoan->service_fee,
                );
                \libs\sms\SmsServer::instance()->send($user['mobile'], 'TPL_SMS_CREDIT_REPAY_SUCCESS', $sms_content, $user['id']);
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
     * 根据借款期限获取借款利率
     * @param int $period_apply 单位为天
     * @return float
     */
    public function getLoanRate($period_apply) {
        $config = new CreditLoanConfigService();
        if ($period_apply <= 90) {
            return $config->getLoanRate(0);
        } elseif ($period_apply <= 180) {
            return $config->getLoanRate(1);
        } elseif ($period_apply <= 360) {
            return $config->getLoanRate(2);
        } elseif ($period_apply <= 720) {
            return $config->getLoanRate(3);
        } elseif ($period_apply <= 1080) {
            return $config->getLoanRate(4);
        } else {
            return false;
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
     * 根据用户id获取可贷款标的信息
     * @param $user_id
     * @return mixed
     */
    public function getCreditDealsByUserId ($user_id) {
        //查询用户可以申请的标的
        $dealsMoneyRes = CreditLoanModel::instance()->getCreditDealsMoneyByUserId($user_id);
        $dealmoneys = array();
        foreach ($dealsMoneyRes as $deal) {
            $dealmoneys[$deal['deal_id']] = $deal['total'];
        }
        if (empty($dealmoneys)) {
            return array();
        }

        $dealIds = implode(',',array_keys($dealmoneys));

        $dealRepayList = DealRepayModel::instance()->getMaxRepayTimeByDealIds($dealIds);

        $creditDeals = CreditLoanModel::instance()->getCreditDealsMoneyByDealIds($dealIds);
        $ds = new DealService();
        // 记录符合条件的科技款资产的总额
        $dealTotalMoney = 0.00;
        foreach ($creditDeals as $k => $creditDeal) {
            $creditDeal['pass_periods']     = floor((to_timespan(date("Y-m-d H:i:s")) - $creditDeal['repay_start_time']) / 86400); // 现在 - 开始时间
            $creditDeal['repay_periods']    = floor(($dealRepayList[$creditDeal['id']] - $creditDeal['repay_start_time']) / 86400); // 最后还款期限 - 开始时间

            //哈哈农庄化肥标不允许申请贷款
            if ($ds->isDealHF($creditDeal['id']) || ($this->checkCanLoan($creditDeal) == false)) {
                unset($creditDeals[$k]);
            }elseif($ds->isDealYtsh($creditDeal['id'])){
                // 云图享花标不允许申请贷款
                unset($creditDeals[$k]);
            } else {
                $creditDeal['totalmoney_num']   = DealModel::instance()->floorfix($dealmoneys[$creditDeal['id']]);//投资总金额 数值型，排序用
                $creditDeal['totalmoney']   = format_price(DealModel::instance()->floorfix($dealmoneys[$creditDeal['id']]), false);//投资总金额
                $creditDeal['duration']     = $creditDeal['repay_periods'] - $creditDeal['pass_periods']; // 存续期限
                $platform_service_fee_rate  = $this->getCreditLoanServiceRate()*100; // 平台手续费%
                $creditDeal['rate']         = format_rate_for_show($this->getLoanRate($creditDeal['duration']) + $platform_service_fee_rate); //借款利率

                // 标的收益率
                $dealExt = DealExtModel::instance()->getDealExtByDealId($creditDeal['id']);
                $creditDeal['income_rate'] = format_rate_for_show($dealExt['income_base_rate']);

                $creditDeals[$k] = $creditDeal;

                // 合计用户持有资产总额
                $dealTotalMoney = bcadd($dealTotalMoney, $creditDeal['totalmoney_num'], 2);
            }
        }

        // 判断符合条件的标的的总金额
        $loanMoneyLimit = floatval(app_conf('CREDIT_LOAN_SUMMARY'));
        // 增加借款记录中的申请中和使用中的借款的标的投资金额
        $creditLoanSummary = $this->caculateUserCreditLoanSummary($user_id);
        $dealTotalMoney = bcadd($dealTotalMoney, $creditLoanSummary, 2);
        // 如果小于配置值， 则不返回记录
        if (bccomp($dealTotalMoney, $loanMoneyLimit, 2) < 0) {
            return array();
        }

        //排序 金额大的在前，金额相同时，id大的在前
        if (!empty($creditDeals)) {
            usort($creditDeals, function ($a, $b) {
                if($a['totalmoney_num'] > $b['totalmoney_num']) {
                    return -1;
                }elseif($a['totalmoney_num'] == $b['totalmoney_num']) {
                    if($a['id'] > $b['id']) {
                        return -1;
                    }
                }
                return 1;
            });
        }

        return $creditDeals;
    }

    /**
     * 检查是否可以作为信用贷
     */
    public function checkCanLoan($dealInfo) {
        // 在投标的最小的持有时间
        $minRemainingDays = intval(app_conf('CREDIT_LOAN_REMAINNING_DAYS'));
        if ($dealInfo['repay_periods'] - $dealInfo['pass_periods'] < $minRemainingDays) {
            PaymentApi::log($dealInfo['id'].'最小持有时间'.$minRemainingDays.'不满足条件');
            return false;
        }
        if ($dealInfo['repay_periods'] > 92) {
            // 借款期限大于3个月,持有时间小于60天的，不能借款
            $holdGt3 = intval(app_conf('CREDIT_LOAN_HOLD_TERM_GE_3'));
            if ($dealInfo['pass_periods'] < $holdGt3) {
                PaymentApi::log($dealInfo['id'].'借款期限大于3个月,持有时间小于'.$holdGt3.'天的，不能借');
                return false;
            }
        } else {
            // 如果借款期限小于三个月的，持有时间小于30天的 不能借款
            $holdLt3 = intval(app_conf('CREDIT_LOAN_HOLD_TERM_LT_3'));
            if ($dealInfo['pass_periods'] < $holdLt3) {
                PaymentApi::log($dealInfo['id'].'如果借款期限小于三个月的，持有时间小于'.$holdLt3.'天的 不能借款');
                return false;
            }
        }

        // 标的还款方式


        return true;
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

    /**
     * 根据标的id，获取可贷款标的信息
     * @author <fanjingwen>
     * @param int $deal_id
     * @param int $user_id
     * @return mixed || array
     */
    public function getCreditDealInfoForUser($deal_id, $user_id)
    {
        $credit_deal = array();
        $credit_deals   = CreditLoanModel::instance()->getCreditDealsMoneyByDealIds(intval($deal_id));
        if (!empty($credit_deals)) {
            $credit_deal  = $credit_deals[0];
            $deal_repay  = DealRepayModel::instance()->getMaxRepayTimeByDealIds(intval($deal_id));
            $credit_deal['pass_periods']    = floor((to_timespan(date("Y-m-d H:i:s")) - $credit_deal['repay_start_time']) / 86400); // 现在 - 开始时间
            $credit_deal['repay_periods']   = floor(($deal_repay[$credit_deal['id']] - $credit_deal['repay_start_time']) / 86400); // 最后还款期限 - 开始时间
            $credit_deal['totalmoney']  = DealModel::instance()->floorfix(DealLoadModel::instance()->getUserLoadMoneyByDealid($user_id, $deal_id));   // 投资总金额
            $credit_deal['duration']    = $credit_deal['repay_periods'] - $credit_deal['pass_periods'];   // 存续期限
            $credit_deal['service_fee_rate'] = format_rate_for_show($this->getCreditLoanServiceRate()*100); // 平台手续费%
            $credit_deal['rate']        = format_rate_for_show($this->getLoanRate($credit_deal['duration']) + $credit_deal['service_fee_rate']); // 借款利率
            $credit_deal['day_cost']    = DealModel::instance()->floorfix(10000*$credit_deal['rate']/100/360); // 每日成本
        }

        return $credit_deal;
    }

    /**
     * 根据用户id，获取申请记录
     * @author <fanjingwen>
     * @param int $user_id
     * @return mixed || array
     */
    public function getCreditLoanRecordListByUserId($user_id,$offset=0,$count=20)
    {
        $credit_loan_record_list = CreditLoanModel::instance()->getCreditLoanListByUserId($user_id,$offset,$count);

        // add key-value and classify
        foreach ($credit_loan_record_list as $key => $credit_loan_record) {
            $credit_loan_record['status_mark'] = static::$status_mark[$credit_loan_record['status']];
            if (in_array($credit_loan_record['status'], array(CreditLoanModel::STATUS_APPLY, CreditLoanModel::STATUS_FAIL))) {
                $credit_loan_record['time_show'] = '';
                $credit_loan_record['time_describe'] = '-';
            } else {
                $credit_loan_record['time_show'] = format_date($credit_loan_record['loan_time'], "Y-m-d");
                $credit_loan_record['time_describe'] = '借';
            }

            $credit_loan_record['money'] = format_price($credit_loan_record['money'], false);
            $credit_loan_record_list[$key] = $credit_loan_record;
        }

        return $credit_loan_record_list;
    }

    /**
     * 根据用户id，获取已申请的项目详情
     * @author <fanjingwen>
     * @param int $credit_loan_id creditLoan表对应的id
     * @return object
     */
    public function getCreditLoanRecordByCreditLoanId($credit_loan_id)
    {
        $credit_loan_record = CreditLoanModel::instance()->getCreditLoanById($credit_loan_id);
        if (CreditLoanModel::STATUS_FINISH == $credit_loan_record['status']) {
            $credit_loan_record['total_money'] = $credit_loan_record['money'] + $credit_loan_record['service_fee'] + $credit_loan_record['interest'];
        } else {
            $credit_loan_record['use_days'] = ceil(($credit_loan_record['plan_time'] - $credit_loan_record['loan_time'])/86400);
            $credit_loan_record['interest']     = DealModel::instance()->floorfix($credit_loan_record['money']*$credit_loan_record['rate']/100/360*$credit_loan_record['period_apply']);
            $credit_loan_record['total_money']  = $credit_loan_record['money'];
        }
        $credit_loan_record['create_time']  = format_date($credit_loan_record['create_time'], "Y-m-d");
        $credit_loan_record['loan_time']    = format_date($credit_loan_record['loan_time'], "Y-m-d");
        $credit_loan_record['repay_time']   = format_date($credit_loan_record['repay_time'], "Y-m-d");
        $credit_loan_record['finish_time']  = format_date($credit_loan_record['finish_time'], "Y-m-d");
        $credit_loan_record['plan_time']    = format_date($credit_loan_record['plan_time'], "Y-m-d");
        $credit_loan_record['update_time']    = format_date($credit_loan_record['update_time'], "Y-m-d");

        return $credit_loan_record;
    }

    /**
     * 根据用户ID,判断用户是否可以使用标的信用贷
     */
    public function isCreditLoanUser($user_id){
        $config = new CreditLoanConfigService();
        $user_id = intval($user_id);
        //判断用户是否通过四要素验证
//        $unite_bank_payment = new UniteBankPaymentService();
//        if(!$unite_bank_payment->isFastPayVerify($user_id)){
//            return false;
//        }

        //查询用户的单个标的投资额度是否大于
        $minInvestMoney = 0.00;
        $minBorrowMoney = app_conf('CREDIT_LOAN_MIN_BORROW_AMOUNT');
        $borrowRate = app_conf('CREDIT_LOAN_PROPORTION_LOAN_RATE');
        $minInvestMoney = bcdiv($minBorrowMoney, $borrowRate, 2); // 单笔最小投资金额=单笔起借金额/质押率
        $sql = "SELECT COUNT(*) AS record FROM firstp2p_deal_load WHERE user_id = ".$user_id." GROUP BY deal_id HAVING SUM(money)>=".$minInvestMoney;
        $record = $GLOBALS['db']->get_slave()->getAll($sql);
        if (empty($record)) {
            return false;
        }

        // 是否启用黑名单功能
        if (app_conf('CREDIT_LOAN_BLACKLIST_SWITCH')) {
            //判断用户是否在黑名单
            $black_list = $config->getBlackList();
            if(in_array($user_id,$black_list)){
                return false;
            }
        }

        return true;
    }

    /**
     * 根据用户ID,判断用户是否可以使用变现通
     */
    public function isBXTUser($user_id){
        $user_id = intval($user_id);
        $account_service = new AccountService();
        $account_data = $account_service->getUserStaicsInfo($user_id);

        $account_pending_principal = $account_service->getUserStaicsInfo($user_id);
        if($account_data['load_money'] >= self::MIN_LOAD_MONEY){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 根据用户ID,判断用户是否可以使用职易贷
     */
    public function isJobLoanUser($user_id){
        $user_id = intval($user_id);
        $user_model = new UserModel();
        $user_info = $user_model->find($user_id);
        $job_user_group = explode(";",app_conf('JOB_USER_GROUPLIST'));
        if(in_array($user_info['group_id'],$job_user_group)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 判断是否有历史申请记录
     * @param int $user_id
     * @param boolen
     */
    public function isExistRecord($user_id)
    {
        $record_list = CreditLoanModel::instance()->getCreditLoanListByUserId($user_id);

        return empty($record_list) ? false : true;
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
        $status = CreditLoanModel::STATUS_USING;
        $res = CreditLoanModel::instance()->getCreditLoanByUserIdAndStatus($user_id,$status);
        return empty($res) ? array() : $res;
    }

    /**
     * 先下完成还款后 手动更新贷款记录及状态
     * 风险：如果标的还款，此时将资金冻结。这时候发生提前还款那么被冻结的资金将无法解冻
     * @param $credit_loan_id  借款ID
     * @param $interest 还款利息
     * @param $service_fee 还款手续费
     * @param $finish_time 完成还款时间
     */
    public function offlinePrepay($credit_loan_id,$interest,$service_fee,$finish_time) {

        $creditLoanInfo = CreditLoanModel::instance()->getCreditLoanById($credit_loan_id);
        if(!$creditLoanInfo['status']){
            throw new \Exception("借款信息不存在");
        }
        if($creditLoanInfo->status != CreditLoanModel::STATUS_USING) {
            throw new \Exception("当前状态不允许手动还款");
        }
        $dealModel = new DealModel();
        $deal = $dealModel->find($creditLoanInfo->deal_id);

        if($deal['is_during_repay'] == DealModel::DURING_REPAY){
            throw new \Exception("标的正在还款中不能进行手动还款");
        }
        $creditLoanInfo->id = $credit_loan_id;
        $creditLoanInfo->interest = $interest;
        $creditLoanInfo->service_fee = $service_fee;
        $creditLoanInfo->finish_time = $finish_time;
        $creditLoanInfo->repay_time = $finish_time;
        $creditLoanInfo->period_repay = ceil(($finish_time - $creditLoanInfo->loan_time) / 86400 ); // 实际借款期限
        $creditLoanInfo->status = CreditLoanModel::STATUS_FINISH;
        $creditLoanInfo->update_time = time();
        $res = $creditLoanInfo->save();
        if(!$res) {
            throw new \Exception("提前还款修改失败");
        }
        return true;
    }

    public function caculateUserCreditLoanSummary($userId) {
        return CreditLoanModel::instance()->caculateUserCreditLoanSummary($userId);
    }

    public function getNotFinishCreditCount($userId){
        $param = array(
            ':user_id' => $userId,
            ':status' => CreditLoanModel::STATUS_FAIL . "," . CreditLoanModel::STATUS_FINISH,
        );
        return CreditLoanModel::instance()->count("`user_id` = ':user_id' AND `status`  NOT in (':status')",$param);
    }

    /**
     * 存管行银信通还款通知提现
     */
    public function repayCreditLoanSupervision($creditLoan){
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",通知存管行提现 creditId:".$creditLoan->id);
        $p2pInterest = $creditLoan->interest;
        //$totalMoney = bcadd($creditLoan->money,$creditLoan->interest,2);
        $totalMoney = $creditLoan->deal_loan_money; // 总金额 = 用户投标本金
        $serviceFee = 0;
        if($creditLoan->service_fee > 0) {
            $mangeUid = app_conf('CREDIT_LOAN_SERVICE_FEE_UID');
            if(!$mangeUid) {
                throw new \Exception("服务费构账户未配置");
            }
            $mangeUser = UserModel::instance()->find($mangeUid);
            if(!$mangeUser) {
                throw new \Exception("服务费构账户不存在");
            }
            $serviceFee = $creditLoan->service_fee;
        }
        //$repayAmount = bcsub($totalMoney,$serviceFee,2);
        $repayAmount = bcadd($creditLoan->money,$creditLoan->interest,2); // 还款金额 = 借款本金+借款预计利息


        $userBankcardObj = new \core\service\UserBankcardService();
        $userBankCardData = $userBankcardObj->getBankcard($creditLoan->user_id);
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
        if($bankRes['status'] === \core\service\SupervisionBaseService::RESPONSE_SUCCESS){
            $data = array(
                'order_id' => $orderId,
                'deal_id' => $creditLoan->deal_id,
                'loan_user_id' => $creditLoan->user_id,
                'money' => $repayAmount,
                'type' => \core\service\P2pDepositoryService::IDEMPOTENT_TYPE_YXT,
                'status' => \core\service\P2pIdempotentService::STATUS_SEND,
                'result' => \core\service\P2pIdempotentService::RESULT_SUCC
            );
            $res =  \core\service\P2pIdempotentService::addOrderInfo($orderId,$data);
            if(!$res){
                throw new \Exception("订单信息保存失败");
            }
            \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",通知存管行提现成功 orderId:".$orderId);
            return true;
        }
        throw new \Exception("存管行处理失败");
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
        if($status == \core\service\P2pDepositoryService::CALLBACK_STATUS_FAIL){
            throw new \Exception('存管回调不支持失败状态');
        }
        $orderService = new \core\service\P2pIdempotentService();
        $orderInfo = $orderService->getInfoByOrderId($orderId);
        $uid = $orderInfo['loan_user_id'];
        $dealId = $orderInfo['deal_id'];

        $res =  \core\service\P2pIdempotentService::updateStatusByOrderId($orderId,\core\service\P2pIdempotentService::STATUS_CALLBACK);
        if(!$res){
            throw new \Exception("订单信息保存失败");
        }
        return $this->LoanCreditLoanForPay($uid,$dealId);
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
        $tagInfo = (new \core\service\DealTagService())->getTagByDealId($deal['id']);
        $request = new  RequestTriggerRepay();
        $request->setUserId(intval($repayUserId));
        $request->setDealType(intval($deal['deal_type']));
        $request->setDealProductType(intval($deal['type_id']));
        $request->setDealLoanType(intval($deal['loantype']));
        $request->setDealId(intval($deal['id']));
        $request->setDealRepayId(intval($repayId));
        $request->setDealTag($tagInfo);
        $request->setDealRepayType($repayType);
        $rpc = new Rpc('creditloanRpc');
        $response = $rpc->go('\NCFGroup\Creditloan\Services\CreditRepay', "triggerRepay", $request);

        // 网络请求失败 为了不影响还款流程同样冻结回款本金
        if($response === false){
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",网信速贷请求失败 dealId:".$deal['id']);
            Alarm::push(self::ALARM_KEY,"速贷请求失败 deal_id:".$deal['id'].",repay_id:{$repayId},repayUserId:{$repayUserId}");
            throw new \Exception("网信速贷请求失败 deal_id:".$deal['id']." repay_id:{$repayId},repayUserId:{$repayUserId}");
        }
        return (isset($response['data']['repay']) && $response['data']['repay'] === true) ? true : false;
    }

    /**
     * 回款冻结通知速贷
     * @param $userId
     * @param $dealId
     */
    public function freezeNotifyCreditloan($userId,$dealId,$repayId,$repayType,$batchOrderId=0,$merchantId=0){
        $jobs_model = new JobsModel();
        $function = '\core\service\CreditLoanService::freezeNotifyCreditloanJob';
        $param = array(
            'userId' => $userId,
            'dealId' => $dealId,
            'repayId' => $repayId,
            'repayType' => $repayType,
            'batchOrderId' => $batchOrderId,
            'merchantId' => $merchantId,
        );
        $jobs_model->priority = JobsModel::REPAY_FREEZE_NOTIFY_SUDAI;
        $r = $jobs_model->addJob($function, $param);
        if ($r === false) {
            throw new \Exception("add freezeNotifyCreditloanJob jobs error");
        }
        return true;
    }

    public function freezeNotifyCreditloanJob($userId,$dealId,$repayId,$repayType,$batchOrderId=0,$merchantId='0'){
        $deal = DealModel::instance()->find($dealId);
        $dealService = new DealService();
        $isYJ175 = $dealService->isDealYJ175($dealId);

        $request = new RequestBackendRepayApply();
        $request->setUserId(intval($userId));
        $request->setDealId(intval($dealId));
        $request->setDealRepayId(intval($repayId));
        $request->setDealRepayType($repayType);
        $request->setDealType(intval($deal['deal_type']));

        if($isYJ175){
            $merchantId = strval($merchantId); //商户号
            $request->setMerchantBatchNo($batchOrderId);
            $request->setMerchantId($merchantId);
        }

        $rpc = new Rpc('creditloanRpc');
        $response = $rpc->go('\NCFGroup\Creditloan\Services\CreditRepay', "backendRepayApply", $request);
        if($response === false){
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",网信速贷请求失败 dealId:".$dealId);
            throw new \Exception("网络请求失败");
        }
        return (isset($response['data']['apply']) && $response['data']['apply'] === true) ? true : false;
    }
}
