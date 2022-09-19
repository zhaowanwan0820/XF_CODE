<?php

namespace core\dao\makeloans;

use core\dao\BaseModel;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealModel;
use core\dao\deal\DealExtModel;
use core\dao\jobs\JobsModel;
use core\enum\AccountEnum;
use core\enum\DealExtEnum;
use core\enum\UserAccountEnum;
use core\service\user\UserService;
use core\service\repay\DealRepayService;
use core\enum\DealEnum;
use core\enum\JobsEnum;
use libs\utils\Finance;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;
use core\dao\repay\DealRepayModel;
use core\service\account\AccountService;
use core\service\deal\DealService;
use core\service\deal\FeeAfterGrantService;

class MakeLoansModel extends BaseModel {

    /**
     * 处理放款逻辑，增加事务处理
     * @param int $deal_id
     * @param int $advisoryFeeAccountId 咨询机构id
     * @param int $agencyFeeAccountId 担保机构id
     * @param int $loanFeeAccountId 平台机构id
     * @param int $payAgencyAccountId 支付机构id
     * @param int $managementAgencyAccountId 管理结构
     * @param int $canalAgencyAccountId 渠道结构
     */
    public function makeDealLoans($deal_data, $advisoryFeeAccountId, $agencyFeeAccountId, $loanFeeAccountId, $payAgencyAccountId, $managementAgencyAccountId,$canalAgencyAccountId, $adm_info = array())
    {
        $deal_id = intval($deal_data['id']);
        $dealService = new DealService();
        $this->db->startTrans();
        try {
            //更新为已打款状态
            $deal = DealModel::instance()->find(intval($deal_id));
            $dealType =  DealEnum::DEAL_TYPE_SUPERVISION;
            if ($deal->is_has_loans != DealEnum::DEAL_IS_HAS_LOANS_ING) {
                throw new \Exception("标不是放款中的状态");
            }

            $accountService = new AccountService();
            $user_id = $deal->user_id;
            $user = UserService::getUserByCondition("id=".$user_id);
            $userAccountId = $accountService::getUserAccountId($user_id,UserAccountEnum::ACCOUNT_FINANCE);
            $note = "编号{$deal_id} {$deal['name']} 借款人ID{$user_id} 借款人姓名{$user['real_name']}";

            // 放款 及 手续费收取
            $feeAccountIds = array(
                'loanFeeAccountId' => $loanFeeAccountId,
                'advisoryFeeAccountId' => $advisoryFeeAccountId,
                'agencyFeeAccountId' => $agencyFeeAccountId,
                'payAgencyAccountId' => $payAgencyAccountId,
                'managementAgencyAccountId' => $managementAgencyAccountId,
                'canalAgencyAccountId' => $canalAgencyAccountId,
            );
            $deal_ext_info = DealExtModel::instance()->getInfoByDeal(intval($deal_id));
            $bizToken = array('dealId' => $deal->id);
            $adm_info['adm_id'] = isset($adm_info['adm_id']) ? $adm_info['adm_id'] : 0;

            // 放款提现后再收费
            if($dealService->isAfterGrantFee($deal_id)){
                $afterGrantFees = $this->getAfterGrantFees(intval($deal_id), $feeAccountIds, $deal_data['isDtb']);
                $feeAmount = $afterGrantFees['feeAmount'];
                $feeDetailList = $afterGrantFees['feeDetailList'];

                $feeParams = array(
                    'deal_id' => intval($deal_id),
                    'deal_name' => $deal['name'],
                    'grant_time' => time(),
                    'deal_user_name' => $user['real_name'],
                    'deal_user_id' => $user_id,
                    'adm_id' => $adm_info['adm_id'],
                    'fee_amount' => $feeAmount,
                    'fee_detail_list' => $feeDetailList
                );
                $feeAfterGrantService = new FeeAfterGrantService();
                $createRes = $feeAfterGrantService->createFeeOrder($feeParams);
                if(!$createRes) {
                    throw new \Exception('创建代扣收费单据失败 feeParams:'.json_encode($feeParams));
                }
                $services_fee = 0; // 因为是放款提现后再收费，提现时，不扣除手续费
                $accountService->changeMoney($userAccountId, $deal->borrow_amount, '招标成功', $note, AccountEnum::MONEY_TYPE_INCR, false, true,$adm_info['adm_id'],$bizToken);
            }else{
                if ($deal_ext_info->loan_type == DealExtEnum::LOAN_AFTER_CHARGE) {
                    $services_fee = $this->changeFeeMoney($deal->id, $user, $feeAccountIds, $note, $adm_info, $deal_data['isDtb']);
                    $services_fee = 0; // 因为是收费后放款，提现时，不再扣除手续费
                    $accountService->changeMoney($userAccountId, $deal->borrow_amount, '招标成功', $note, AccountEnum::MONEY_TYPE_INCR, false, true,$adm_info['adm_id'],$bizToken);
                } else {
                    $accountService->changeMoney($userAccountId, $deal->borrow_amount, '招标成功', $note, AccountEnum::MONEY_TYPE_INCR, false, true,$adm_info['adm_id'],$bizToken);
                    $services_fee = $this->changeFeeMoney($deal->id, $user, $feeAccountIds, $note, $adm_info, $deal_data['isDtb']);
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            \libs\utils\Logger::error($e->getMessage());
            $this->db->rollback();
            return false;
        }
        return array("ret"=>true, "data"=>array("services_fee"=>$services_fee));
    }

    /**
     * 创建还款计划
     * @param $dealId 标的Id
     * @return bool
     */
    public function createDealRepayList($dealId) {
        try {
            $this->createDealRepayListSub($dealId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 创建子标以及正常标的还款回款计划表
     * @param $dealId 标的Id
     * @return void
     **/
    private function createDealRepayListSub($dealId) {
        $deal_info = DealModel::instance()->find(intval($dealId));
        $repay_time = $deal_info->repay_start_time; //中间变量，保存各期还款时间
        if (!$repay_time) {
            throw new \Exception("repay start time error");
        }

        $dealService = new DealService();
        $isDtb = $dealService->isDealDT($dealId);

        $repay_cycle = $deal_info->getRepayCycle();
        $repay_times = $deal_info->getRepayTimes();
        $deal_ext = DealExtModel::instance()->getInfoByDeal($dealId, false);
        $first_repay_day = $deal_ext['first_repay_interest_day'];

        $loan_fee_arr = $deal_ext['loan_fee_ext'] ? json_decode($deal_ext['loan_fee_ext'], true) : array();
        $consult_fee_arr = $deal_ext['consult_fee_ext'] ? json_decode($deal_ext['consult_fee_ext'], true) : array();
        $guarantee_fee_arr = $deal_ext['guarantee_fee_ext'] ? json_decode($deal_ext['guarantee_fee_ext'], true) : array();
        $pay_fee_arr = $deal_ext['pay_fee_ext'] ? json_decode($deal_ext['pay_fee_ext'], true) : array();
        $canal_fee_arr = $deal_ext['canal_fee_ext'] ? json_decode($deal_ext['canal_fee_ext'], true) : array();
        $consult_fee_period = $deal_info->floorfix($deal_info['borrow_amount'] * $deal_info['consult_fee_period_rate'] / 100.0);

        if($isDtb) {
            $management_fee_arr = $deal_ext['management_fee_ext'] ? json_decode($deal_ext['management_fee_ext'], true) : array();//管理服务费
        }

        if ($first_repay_day) {
            if($deal_info['loantype'] == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_FIXED_DATE']) {//消费分期固定日还款
                for ($i=0; $i<$repay_times; $i++) {
                    if ($i == 0) {
                        $interest_day = ($first_repay_day - $repay_time) / 86400;
                        $repay_time = $first_repay_day;
                    } else {
                        $interest_day = 0;
                        $repay_time = $deal_info->getRepayDay($first_repay_day, $repay_cycle, $deal_info['loantype'], $i);
                    }
                    $is_last = (($i + 1) == $repay_times);
                    $repay_money = $deal_info->getRepayMoney($deal_info['borrow_amount'],$is_last,false,$interest_day,$i+1);
                    $loan_fee = $consult_fee = $guarantee_fee = $pay_fee = $management_fee = $canal_fee = 0;
                    $k = $i+1;
                    if(isset($loan_fee_arr[$k])) {
                        $loan_fee = $loan_fee_arr[$k];
                    }
                    if(isset($consult_fee_arr[$k])) {
                        $consult_fee = $consult_fee_arr[$k];
                    }
                    if(isset($guarantee_fee_arr[$k])) {
                        $guarantee_fee = $guarantee_fee_arr[$k];
                    }
                    if(isset($pay_fee_arr[$k])) {
                        $pay_fee = $pay_fee_arr[$k];
                    }
                    if(isset($management_fee_arr) && (isset($management_fee_arr[$k]))) {
                        $management_fee = $management_fee_arr[$k];
                    }
                    if(isset($canal_fee_arr[$k])) {
                        $canal_fee = $canal_fee_arr[$k];
                    }
                    $this->insertDealRepayList($repay_time, $repay_money, $is_last,  $loan_fee, ($consult_fee + $consult_fee_period), $guarantee_fee, $pay_fee, $management_fee ,$canal_fee, $interest_day,$i+1);
                }
            } else {// 如果后收服务费且借款需要按日计息，则第一期不收手续费，所有手续费向后延续一期
                $repay_times += 1;
                for ($i=0; $i<$repay_times; $i++) {
                    if ($i == 0) {
                        $interest_day = ($first_repay_day - $repay_time) / 86400;
                        $repay_time = $first_repay_day;
                        $repay_money = $deal_info->getRepayMoney($deal_info->borrow_amount, 0, false, $interest_day);
                        $management_fee_val = 0;
                        $this->insertDealRepayList($deal_info,$repay_time, $repay_money, 0, 0, 0, 0, 0, $management_fee_val,0, $interest_day);
                    } elseif ( $i+1 == $repay_times) {
                        $last_repay_time = $deal_info->getLastRepayDay();
                        $interest_day = ($last_repay_time - $repay_time) / 86400;
                        $repay_money = $deal_info->getRepayMoney($deal_info->borrow_amount, 1, false, $interest_day);
                        $management_fee_val = 0;
                        if($isDtb) {
                            $management_fee_val = $management_fee_arr[$i];//管理服务费
                        }
                        $this->insertDealRepayList($deal_info,$last_repay_time, $repay_money, 1, $loan_fee_arr[$i], $consult_fee_arr[$i], $guarantee_fee_arr[$i], $pay_fee_arr[$i], $management_fee_val,$canal_fee_arr[$i], $interest_day);
                    } else {
                        $repay_time = $deal_info->getRepayDay($first_repay_day, $repay_cycle, $deal_info->loantype, $i);
                        $repay_money = $deal_info->getRepayMoney($deal_info->borrow_amount, 0);
                        if($isDtb) {
                            $management_fee_val = $management_fee_arr[$i];//管理服务费
                        }
                        $this->insertDealRepayList($deal_info,$repay_time, $repay_money, 0, $loan_fee_arr[$i], ($consult_fee_arr[$i] + $consult_fee_period), $guarantee_fee_arr[$i], $pay_fee_arr[$i], $management_fee_val,$canal_fee_arr[$i]);
                    }
                }
            }
            // 修改下次还款时间为第一期付息日
            $deal_info->next_repay_time = $first_repay_day;
            $deal_info->save();
        } else {
            if ($repay_times) {
                for($i = 0; $i < $repay_times; $i++) {
                    $repay_time = $deal_info->getRepayDay($deal_info->repay_start_time, $repay_cycle, $deal_info->loantype, $i+1);
                    $is_last = (($i + 1) == $repay_times);
                    $repay_money = $deal_info->getRepayMoney($deal_info->borrow_amount, $is_last, false, false, $i+1);
                    $management_fee_val = 0;
                    if($isDtb) {
                        $management_fee_val = $management_fee_arr[$i+1];//管理服务费
                    }
                    $loan_fee_arr[$i+1] = isset($loan_fee_arr[$i+1])?$loan_fee_arr[$i+1] : 0;
                    $consult_fee_arr[$i+1] = isset($consult_fee_arr[$i+1])?$consult_fee_arr[$i+1] : 0;
                    $guarantee_fee_arr[$i+1] = isset($guarantee_fee_arr[$i+1])?$guarantee_fee_arr[$i+1] : 0;
                    $pay_fee_arr[$i+1] = isset($pay_fee_arr[$i+1])?$pay_fee_arr[$i+1] : 0;
                    $canal_fee_arr[$i+1] = isset($canal_fee_arr[$i+1])?$canal_fee_arr[$i+1] : 0;
                    $this->insertDealRepayList($deal_info,$repay_time, $repay_money, $is_last, $loan_fee_arr[$i+1], ($consult_fee_arr[$i+1] + $consult_fee_period), $guarantee_fee_arr[$i+1], $pay_fee_arr[$i+1], $management_fee_val,$canal_fee_arr[$i+1], false, $i+1);
                }
            }
        }
    }

    /**
     * 向还款计划表插入数据，并生成回款计划
     * @param  $deal_info 标的信息
     * @param int $repay_time 还款日期
     * @param array $repay_money
     * @param bool $is_last
     * @param int|bool $interest_day 计息天数
     */
    public function insertDealRepayList($deal_info,$repay_time, $repay_money, $is_last, $loan_fee, $consult_fee, $guarantee_fee, $pay_fee, $management_fee,$canal_fee, $interest_day=false, $periods_index=0) {
        if (DealExtEnum::LOAN_AFTER_CHARGE === DealExtModel::instance()->getDealExtLoanType($deal_info['id'])) { // 放款类型为：收费后放款，还款计划不生成手续费，因为已统一视为前收收取
            $loan_fee = $consult_fee = $guarantee_fee = $pay_fee = $management_fee = 0;
        }

        $deal_repay = new DealRepayModel();
        $deal_repay->deal_id = $deal_info->id;
        $deal_repay->user_id = $deal_info->user_id;
        $deal_repay->repay_money = $repay_money['total'];
        $deal_repay->principal = $repay_money['principal'];
        $deal_repay->interest = $repay_money['interest'];
        $deal_repay->repay_time = $repay_time;
        $deal_repay->loan_fee = $loan_fee;
        $deal_repay->consult_fee = $consult_fee;
        $deal_repay->guarantee_fee = $guarantee_fee;
        $deal_repay->pay_fee = $pay_fee;
        $deal_repay->management_fee = $management_fee;
        $deal_repay->canal_fee = $canal_fee;
        $deal_repay->create_time = get_gmtime();
        $deal_repay->update_time = get_gmtime();
        $deal_repay->deal_type = $deal_info->deal_type;

        // 保存还款类型
        $dealRepayService = new DealRepayService();
        $deal_repay->repay_type = $dealRepayService->getRepayTypeByDeal($deal_info->id);

        // 生成回款计划
        if ($deal_repay->save() === false) {
            throw new \Exception("insert deal repay list error");
        }
        //生成本期回款计划表
        $result = $this->createLoanRepayPlan($deal_repay, $is_last, $interest_day, $periods_index);
        if ($result === false) {
            throw new \Exception("create loan repay plan error");
        } else {
            $deal_repay = $deal_repay->find($deal_repay->id);
            $deal_repay->repay_money = $result + $loan_fee + $consult_fee + $guarantee_fee + $pay_fee + $management_fee + $canal_fee;
            $deal_repay->interest = $result - $repay_money['principal'];
            $deal_repay->update_time = get_gmtime();

            if ($is_last == true && in_array($deal_info->loantype, array($GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'], $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH']))) {
                $principal_fix = DealRepayModel::instance()->getFixPrincipalByDeal($deal_info->getRow(), $deal_repay->id);
                if ($principal_fix === false) {
                    throw new \Exception("get fix principal fail");
                } else {
                    $deal_repay->principal = $principal_fix;
                }
            } else {
                $deal_repay->principal = $repay_money['principal'];
            }

            if ($deal_repay->save() === false) {
                throw new \Exception("update deal repay list error");
            }
        }
    }

    /**
     * 收取各项手续费
     * @params int $deal_id
     * @params object $user
     * @params array $feeAccountIds 收费机构id
     * @params string $note
     * @params array $adm_info
     * @params boolean $is_dtb
     * @return list ($sync_remote_data, $service_fee)
     */
    public function changeFeeMoney($deal_id, $user, $feeAccountIds, $note, $adm_info, $is_dtb = false)
    {
        $deal_info = DealModel::instance()->find(intval($deal_id));
        $deal_ext_info = DealExtModel::instance()->getInfoByDeal(intval($deal_id), false);
        $fee = array();
        $fee['loan_fee'] = $this->changeOneFeeMoney($deal_info, $deal_ext_info, $feeAccountIds['loanFeeAccountId'], $user, 'loan_fee', '平台手续费', $note, $adm_info);
        $fee['consult_fee'] = $this->changeOneFeeMoney($deal_info, $deal_ext_info, $feeAccountIds['advisoryFeeAccountId'], $user, 'consult_fee', '咨询费', $note, $adm_info);
        $fee['guarantee_fee'] = $this->changeOneFeeMoney($deal_info, $deal_ext_info, $feeAccountIds['agencyFeeAccountId'], $user, 'guarantee_fee', '担保费', $note, $adm_info);
        $fee['pay_fee'] = $this->changeOneFeeMoney($deal_info, $deal_ext_info, $feeAccountIds['payAgencyAccountId'], $user, 'pay_fee', '支付服务费', $note, $adm_info);
        $fee['canal_fee'] = $this->changeOneFeeMoney($deal_info, $deal_ext_info, $feeAccountIds['canalAgencyAccountId'], $user, 'canal_fee', '渠道服务费', $note, $adm_info);

        if ($is_dtb) { // 多投才收管理费
            $fee['management_fee'] = $this->changeOneFeeMoney($deal_info, $deal_ext_info, $feeAccountIds['managementAgencyAccountId'], $user, 'management_fee', '管理服务费', $note, $adm_info);
        }
        Logger::info(sprintf('change fee,deal_id:%d, adm_info:%s, fee_account_ids:%s, fee-detail:%s [%s:%s]', $deal_id, json_encode($adm_info), json_encode($feeAccountIds), json_encode($fee), __FILE__, __LINE__));
        return array_sum($fee);
    }

    /**
     * 提现后收费
     * @param $deal_id 标的Id
     * @param $user_id 借款人Id
     * @param $fee_detail_list 费用明细
     * @param int $adm_id 管理员Id
     * @return bool
     * @throws \Exception
     */
    public function changeAfterGrantFees($deal_id,$user_id,$fee_detail_list,$adm_id = 0) {
        $deal = DealModel::instance()->find(intval($deal_id));
        $user = UserService::getUserByCondition("id=".$user_id);

        $accountService = new AccountService();
        $userAccountId = $accountService::getUserAccountId($user_id,UserAccountEnum::ACCOUNT_FINANCE);

        $note = "编号{$deal_id} {$deal['name']} 借款人ID{$user_id} 借款人姓名{$user['real_name']}";
        $bizToken = array('dealId' => $deal_id);

        foreach ($fee_detail_list as $feeName => $feeDetail) {
            $feeAccountId = $feeDetail['receiveUserId'];
            $fee = $feeDetail['amount'];
            $message = $feeDetail['remark'];
            // 扣除费用
            if ($feeAccountId && bccomp($fee, '0.00', 2) > 0) {
                $accountService->changeMoney($userAccountId, $fee, $message, $note, AccountEnum::MONEY_TYPE_REDUCE,false, true,$adm_id,$bizToken);
                // 相关有用户手续费所得
                $accountService->changeMoney($feeAccountId, $fee, $message, $note,AccountEnum::MONEY_TYPE_INCR,true, true,$adm_id,$bizToken);
            }
        }
        return true;
    }


    /**
     * 收取指定项手续费
     * @params object $deal 对应 deal 表
     * @params object $deal_ext 对应 deal_ext 表
     * @params int $feeAccountId 收取费用的用户id
     * @params object $user 对应 user 表
     * @params string $fee_name eg. loan_fee consult_fee ..
     * @params string $message 扣费类型
     * @params string $note 备注
     * @params array $adm_info 后台操作用户 [adm_id]
     * @return list ($sync_remote_data, $services_fee)
     */
    public function changeOneFeeMoney($deal, $deal_ext, $feeAccountId, $user, $fee_name, $message, $note, $adm_info)
    {

        $bizToken = array('dealId' => $deal->id);
        // 获取费用金额
        $fee = $this->getOneFee($deal, $deal_ext, $fee_name);
        $accountService = new AccountService();
        $userAccountId = AccountService::getUserAccountId($deal->user_id,UserAccountEnum::ACCOUNT_FINANCE);
        // 扣除费用
        $adm_info['adm_id'] = isset($adm_info['adm_id']) ? $adm_info['adm_id'] : 0;
        if ($feeAccountId && bccomp($fee, '0.00', 2) > 0) {
            $accountService->changeMoney($userAccountId, $fee, $message, $note, AccountEnum::MONEY_TYPE_REDUCE,false, true,$adm_info['adm_id'],$bizToken);
            // 相关有用户手续费所得
            $accountService->changeMoney($feeAccountId, $fee, $message, $note,AccountEnum::MONEY_TYPE_INCR,true, true,$adm_info['adm_id'],$bizToken);
        }

        $logArgs = func_get_args();
        $logArgs = empty($logArgs) ? "" : json_encode($logArgs);
        Logger::info(sprintf('change one-fee,deal_id:%d, user_account_id:%d, fee_account_id:%d, params:%s [%s:%s]', $deal->id,$userAccountId,$feeAccountId, $logArgs, __FILE__, __LINE__));
        return $fee;
    }

    /**
     * 此项手续费应收金额
     * @params object $deal 对应 deal 表
     * @params object $deal_ext 对应 deal_ext 表
     * @params string $fee_name eg. loan_fee consult_fee ..
     * @return float $fee
     */
    public function getOneFee($deal, $deal_ext, $fee_name)
    {
        $dealModel = new DealModel();
        // 获取各字段名
        $fee_rate_field = sprintf('%s_rate', $fee_name);
        $fee_rate_type_field = sprintf('%s_rate_type', $fee_name);
        $fee_ext_field = sprintf('%s_ext', $fee_name);

        // 计算费用
        if ($deal_ext->loan_type == DealExtEnum::LOAN_AFTER_CHARGE || !$deal_ext->$fee_ext_field) { // 收费后放款，统一按前收处理
            $fee_rate = Finance::convertToPeriodRate($deal->loantype, $deal->$fee_rate_field, $deal->repay_time, false);
            $fee = $dealModel->floorfix($deal->borrow_amount * $fee_rate / 100.0);
        } else {
            $fee_arr = json_decode($deal_ext->$fee_ext_field, true);
            $fee = $fee_arr[0];
        }
        return $fee;
    }

    /**
     * 根据还款id生成一期的回款计划
     * @param $deal_repay object
     * @param $is_last int 是否是最后一期
     * @param $interest_day int|bool
     * @param $periods_index int 期数
     * @return bool
     */
    public function createLoanRepayPlan($deal_repay, $is_last, $interest_day, $periods_index=0) {
        // 根据deal_id获取投资列表
        $deal_loan_model = new DealLoadModel();
        $deal_loan_list  = $deal_loan_model->getDealLoanList($deal_repay->deal_id);

        $repay_money = 0;
        $deal = DealModel::instance()->find($deal_repay->deal_id);
        foreach ($deal_loan_list as $deal_loan) {
            // 根据投资总额生成回款金额
            $arr_deal_loan_repay = $deal->getRepayMoney($deal_loan['money'], $is_last, true, $interest_day, $periods_index);
            // 获取还款金额，计算管理费
            $arr_deal_repay = $deal->getRepayMoney($deal_loan['money'], $is_last, false, $interest_day, $periods_index);

            $function = '\core\service\makeloans\MakeLoansService::createDealLoanRepay';
            $param = array($deal_repay->getRow(), $deal_loan->getRow(), $arr_deal_loan_repay, $arr_deal_repay, $deal->principal, $is_last);
            $job_model = new JobsModel();
            $job_model->priority = JobsEnum::PRIORITY_DEALLOANS_REPAY_CREATE;
            $add_job = $job_model->addJob($function, $param, false, 30);
            if (!$add_job) {
                throw new \Exception('回款计划子任务添加失败');
            }

            // JIRA#1062 将每笔还款金额相加，计算实际的还款金额
            $repay_money += bcadd($arr_deal_repay['principal'], $arr_deal_repay['interest'], 2);
        }
        return $repay_money;
    }

    /**
     * 获取放款后收的费用信息
     * @param $dealId
     * @return bool
     * @throws \Exception
     */
    public function getAfterGrantFees($deal_id,$feeAccountIds,$is_dtb = false) {

        $deal_info = DealModel::instance()->find($deal_id);
        $deal_ext_info = DealExtModel::instance()->getInfoByDeal($deal_id, false);

        $feeAmount = 0;
        $feeDetailList = array();
        $feeNames = array('loan_fee','consult_fee','guarantee_fee','pay_fee','canal_fee');
        if ($is_dtb) { array_push($feeNames,'management_fee'); }
        foreach ($feeNames as $feeName) {
            $amount = $this->getOneFee($deal_info, $deal_ext_info, $feeName);
            if (bccomp($amount, 0, 2) < 1) {
                continue;
            }
            $amount = bcmul($amount,100,0); //金额转化成分
            $feeAmount += $amount;

            switch ($feeName) {
                case 'loan_fee':
                    $receiveUserId = $feeAccountIds['loanFeeAccountId'];
                    $remark = '平台手续费';
                    break;
                case 'consult_fee':
                    $receiveUserId = $feeAccountIds['advisoryFeeAccountId'];
                    $remark = '咨询费';
                    break;
                case 'guarantee_fee':
                    $receiveUserId = $feeAccountIds['agencyFeeAccountId'];
                    $remark = '担保费';
                    break;
                case 'pay_fee':
                    $receiveUserId = $feeAccountIds['payAgencyAccountId'];
                    $remark = '支付服务费';
                    break;
                case 'canal_fee':
                    $receiveUserId = $feeAccountIds['canalAgencyAccountId'];
                    $remark = '渠道服务费';
                    break;
                case 'management_fee':
                    $receiveUserId = $feeAccountIds['managementAgencyAccountId'];
                    $remark = '管理服务费';
                    break;
            }
            $feeDetailList[$feeName] = array('amount'=>$amount,'receiveUserId'=>$receiveUserId,'remark'=>$remark);
        }
        return array('feeAmount'=>$feeAmount,'feeDetailList'=>$feeDetailList);
    }
}
