<?php
/**
 * DealRepayModel.php
 *
//* @date 2014-03-20
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

use core\service\CreditLoanService;
use core\service\DealProjectService;
use core\service\DealService;
use core\service\PartialRepayService;
use libs\utils\Finance;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\DealLoanRepayModel;
use core\dao\JobsModel;
use core\dao\DealAgencyModel;
use core\dao\DealProjectModel;
use core\dao\DealExtModel;
use libs\utils\Logger;
use core\service\IncomeExcessService;
use NCFGroup\Task\Services\TaskService AS GTaskService;

use core\event\DealRepayMsgEvent;
use core\event\DealLoanRepayMsgEvent;
use core\event\ReserveDealRepayCacheEvent;

use libs\utils\Aes;

/**
 * 订单还款计划
 *
 * Class DealRepayModel
 * @package core\dao
 */
class DealRepayModel extends BaseModel {

    const STATUS_WAITING = 0;
    const STATUS_PAIED_ONTIME = 1;
    const STATUS_PAIED_DELAYED = 2;
    const STATUS_PAIED_DELAYED_CRITICAL = 3;
    const STATUS_PREPAID = 4;


    const DEAL_REPAY_TYPE_SELF                  = 0; // 借款人还款
    const DEAL_REPAY_TYPE_DAIDIAN               = 1; // 代垫
    const DEAL_REPAY_TYPE_DAICHANG              = 2; // 代偿
    const DEAL_REPAY_TYPE_DAICHONGZHI           = 3; // 代充值
    const DEAL_REPAY_TYPE_DAIKOU                = 4; // 代扣
    const DEAL_REPAY_TYPE_JIANJIE_DAICHANG      = 5; // 间接代偿
    const DEAL_REPAY_TYPE_PART_SELF             = 6; // 部分还款
    const DEAL_REPAY_TYPE_PART_DAICHANG         = 7; // 部分代偿
    const DEAL_REPAY_TYPE_OFFLINE                = 100; //线下还款

    public static $repayTypeMsg = array(
        self::DEAL_REPAY_TYPE_SELF              => '借款人还款',
        self::DEAL_REPAY_TYPE_DAIDIAN           => 'b机构代偿',
        self::DEAL_REPAY_TYPE_DAICHANG          => '直接代偿',
        self::DEAL_REPAY_TYPE_DAICHONGZHI       => 'a机构代偿',
        self::DEAL_REPAY_TYPE_DAIKOU            => '代扣',
        self::DEAL_REPAY_TYPE_JIANJIE_DAICHANG  => '间接代偿',
        self::DEAL_REPAY_TYPE_PART_SELF         => '部分还款',
        self::DEAL_REPAY_TYPE_PART_DAICHANG     => '部分代偿',
        self::DEAL_REPAY_TYPE_OFFLINE => '线下还款',
    );


    /**
     * 还款模式 白名单为节后
     */

    public static $dealRepayModeText = array(
        1 => '节前', // 白名单
        2 => '节后',
    );

    // 节前
    const DEAL_REPAY_MODE_HOLIDAY_BEFORE = 1;
    // 节后
    const DEAL_REPAY_MODE_HOLIDAY_AFTER = 2;

    // 还款模式白名单的key
    const DEAL_REPAY_MODE_WHITE_TYPE_KEY = 'REPAY_MODE_WHITE';

    /**
     * 根据订单id获取订单还款计划列表
     *
     * @param $dealId
     * @return mixed
     */
    public function getDealRepayListByDealId($dealId) {
        $condition = "deal_id=:deal_id ORDER BY id ASC";
        return $this->findAll($condition, false, '*', array(':deal_id' => $dealId));
    }

    /**
     * 根据订单id获取订单未还还款计划列表
     *
     * @param $dealId
     * @return mixed
     */
    public function getDealUnpaiedRepayListByDealId($dealId) {
        $condition = "`deal_id`=:deal_id AND `status`='0' ORDER BY id ASC";
        return $this->findAll($condition, false, '*', array(':deal_id' => $dealId));
    }

    public function getPayedRepayListByDealId($dealId) {
        $condition = "`deal_id`=:deal_id AND `status`='1' ORDER BY id ASC";
        return $this->findAll($condition, false, '*', array(':deal_id' => $dealId));
    }

    /**
     * 执行还款
     *
     * @param boolean $ignore_impose_money 是否执行逾期罚息
     * @param $negative 0 不可扣负 1可扣负
     * @return void
     **/
    public function repay($ignore_impose_money = false, &$totalRepayMoney = 0,$negative=1,$repayType=0, $orderId = '') {
        if($this->status > 0){
            return false;
        }
        $time = get_gmtime();

        $this->db->startTrans();

        $deal = DealModel::instance()->find($this->deal_id);
        $user_model = new UserModel();
        $dealService = new DealService();
        $isP2pPath = $dealService->isP2pPath($deal);
        //是否是农担贷标的
        $isND = $dealService->isDealND($this->deal_id);
        if($isND) {
            $this->ndDeal = $deal;
            $this->ndBorrowerUser = $user_model->find($this->user_id);
            $this->ndBorrowerUser->changeMoneyDealType = $dealService->getDealType($deal);
            if($deal['agency_id'] > 0){//担保机构代偿
                $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],2);
                $this->ndCompensatoryUser = $user_model->find($advanceAgencyUserId);
                $this->ndCompensatoryUser->changeMoneyDealType = $dealService->getDealType($deal);
            }else{
                throw new \Exception('还款失败,农担贷未设置代偿机构!');
            }
            $this->partialRepayModel = new PartialRepayModel();
        }
        $isDealYJ175 = $dealService->isDealYJ175($this->deal_id);

        if($repayType == 1){//代垫
            if($deal['advance_agency_id'] > 0){
                $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],1);
                $user = $user_model->find($advanceAgencyUserId);
            }else{
                throw new \Exception('还款失败,未设置代垫机构!');
            }
        }elseif($repayType == 2) {//代偿
            if($deal['agency_id'] > 0){//担保机构代偿
                $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],2);
                $user = $user_model->find($advanceAgencyUserId);
            }else{
                throw new \Exception('还款失败,未设置代偿机构!');
            }
        }elseif($repayType == 3) {//代充值
            if($deal['generation_recharge_id'] > 0){//担保机构代偿
                $generationRechargeId = $dealService->getRepayUserAccount($deal['id'],3);
                $generationRechargeUser = $user_model->find($generationRechargeId);
            }else{
                throw new \Exception('还款失败,未设置代充值机构!');
            }
            $user = $user_model->find($this->user_id);
        }elseif($repayType == 0){
            $user = $user_model->find($this->user_id);
        }elseif($repayType == 4){
            $user = $user_model->find($this->user_id);
        }elseif($repayType == 5) {//间接代偿
            if($deal['agency_id'] > 0){//担保机构代偿
                $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],2);
                $advanceAgencyUser = $user_model->find($advanceAgencyUserId);
            }else{
                throw new \Exception('还款失败,未设置间接代偿机构!');
            }
            $user = $user_model->find($this->user_id);
        }

        //如果还款金额不足,提交事物并返回状态,由service通知jobs提交 存管标的不需要进行余额判断
        $rollback = false;

        if($negative === 0 && !$dealService->isP2pPath($deal) && $repayType == 3){
            //非存管代充值判断
            if($generationRechargeUser['money'] < $this->repay_money){
                $rollback = true;
            }
       }elseif($negative === 0 && $repayType <> 3 && $isP2pPath == false && $isDealYJ175 == false){
            //原逻辑,判断账户余额
            if($user['money'] < $this->repay_money){
                $rollback = true;
            }
        }

        if($rollback === true){
            $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
            $this->db->commit();
            return 2;
        }

        try {
            $sets = array(
                        'true_repay_time' => $time,
                        'repay_type' => $repayType,
                    );
            $condition = " id=".$this->id." AND status=0";
            if(to_date($this->repay_time, "Y-m-d") >= to_date($time, "Y-m-d")){
                $this->status = 1; //准时
                $sets['status'] = 1;
            }else{
                $impose_money = $this->impose_money = $this->feeOfOverdue();
                $sets['status'] = $this->status = 2; //逾期
                $sets['impose_money'] = $impose_money;
            }
            $this->true_repay_time = $time;
            $sets['update_time'] = $time;
            $this->updateAll($sets, $condition);
            $effectRow = $this->db->affected_rows();
            if($effectRow == 1){
                $deal = DealModel::instance()->find($this->deal_id);
                $deal['isDtb'] = 0;
                if ($dealService->isDealDT($this->deal_id) == true) {
                    $deal['isDtb'] = 1;
                }

                $deal->repay_money = bcadd($deal->repay_money,$this->repay_money,2);
                $deal->last_repay_time = $time;
                $deal->update_time = $time;
                if (!$deal->save()) {
                    throw new \Exception('订单还款额修改失败！');
                }
//                if($repayType === 1){
//                    $user = $user_model->find()
//                }

                $repay_user_id = $user['id'];

                $dealType = $dealService->getDealType($deal);
                // TODO finance 偿还本息 | 还款最后走的repayment()
                $repay_money = $this->principal + $this->interest;
                $user->changeMoneyDealType = $dealType;
                $user->isDoNothing = $isDealYJ175 ? true : false;

                $bizToken = [
                    'dealId' => $this->deal_id,
                    'dealRepayId' => $this->id,
                ];
                if($repayType == 3){ //代充值
                    $generationRechargeUser->changeMoneyDealType = $dealType;
                    $generationRechargeUser->isDoNothing = $isDealYJ175 ? true : false;
                    $totalMoney = $repay_money + $this->loan_fee + $this->consult_fee + $this->guarantee_fee + $this->pay_fee;
                    if($this->status == 2 && !$ignore_impose_money){
                        $totalMoney = bcadd($totalMoney,$this->impose_money,2);
                    }
                    if (($deal['isDtb'] == 1) && ($this->management_fee > 0)) {
                        $totalMoney += $this->management_fee;
                    }
                    if ($generationRechargeUser->changeMoney(-$totalMoney, "代充值扣款", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款失败');
                    }

                    if ($user->changeMoney($totalMoney, "代充值", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款失败');
                    }

                    $syncRemoteData[] = array(
                        'outOrderId' => 'GENERATION_RECHARGE_FEE|' . $deal['id'],
                        'payerId' => $generationRechargeUser->id,
                        'receiverId' => $user->id,
                        'repaymentAmount' => bcmul($totalMoney, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 1,
                        'batchId' => $deal['id'],
                    );

                }
                if($repayType == self::DEAL_REPAY_TYPE_JIANJIE_DAICHANG){ //间接代偿
                    $advanceAgencyUser->changeMoneyDealType = $dealType;
                    $advanceAgencyUser->isDoNothing = $isDealYJ175 ? true : false;
                    $totalMoney = $repay_money + $this->loan_fee + $this->consult_fee + $this->guarantee_fee + $this->pay_fee;
                    if($this->status == 2 && !$ignore_impose_money){
                        $totalMoney = bcadd($totalMoney,$this->impose_money,2);
                    }
                    if (($deal['isDtb'] == 1) && ($this->management_fee > 0)) {
                        $totalMoney += $this->management_fee;
                    }
                    if ($advanceAgencyUser->changeMoney(-$totalMoney, "间接代偿扣款", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('间接代偿还款失败');
                    }

                    if ($user->changeMoney($totalMoney, "间接代偿", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('间接代偿还款失败');
                    }

                    $syncRemoteData[] = array(
                        'outOrderId' => 'GENERATION_RECHARGE_FEE|' . $deal['id'],
                        'payerId' => $advanceAgencyUser->id,
                        'receiverId' => $user->id,
                        'repaymentAmount' => bcmul($totalMoney, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 1,
                        'batchId' => $deal['id'],
                    );

                }

                if($isND === true) {
                    //还本息
                    $this->addNDRepayMoneyLog("偿还本息",array(PartialRepayService::FEE_TYPE_PRINCIPAL,PartialRepayService::FEE_TYPE_INTEREST),$negative,$bizToken) ;
                    //还手续费
                    $this->addNDRepayMoneyLog("平台手续费",array(PartialRepayService::FEE_TYPE_SX),$negative,$bizToken) ;
                } else {
                    if ($user->changeMoney(-$repay_money, "偿还本息", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款失败');
                    }
                    if ($this->loan_fee > 0) {
                        if ($user->changeMoney(-$this->loan_fee, "平台手续费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除手续费失败');
                        }
                    }
                }



                if ($this->consult_fee > 0) {
                    if($isND == true) {
                        //还咨询费
                        $this->addNDRepayMoneyLog("咨询费",PartialRepayService::FEE_TYPE_ZX,$negative,$bizToken) ;
                    }  else {
                        if($deal['consult_fee_period_rate'] > 0){
                            $consult_fee_period = floorfix($deal['borrow_amount'] * $deal['consult_fee_period_rate'] / 100.0);
                            if($this->consult_fee < $consult_fee_period){
                                throw new \Exception('分期咨询费大于总咨询费');
                            }

                            if($this->consult_fee > $consult_fee_period){
                                $consult_fee = bcadd($this->consult_fee,-$consult_fee_period,2);
                                if ($user->changeMoney(-$consult_fee, "咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                                    throw new \Exception('还款扣除咨询费失败');
                                }
                            }

                            if ($user->changeMoney(-$consult_fee_period, "分期咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                                throw new \Exception('还款扣除分期咨询费失败');
                            }
                        }else{
                            if ($user->changeMoney(-$this->consult_fee, "咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                                throw new \Exception('还款扣除咨询费失败');
                            }
                        }
                    }
                }
                if ($this->guarantee_fee > 0) {
                    if($isND == true) {
                        //还担保费
                        $this->addNDRepayMoneyLog("担保费",PartialRepayService::FEE_TYPE_DB,$negative,$bizToken) ;
                    } else {
                        if ($user->changeMoney(-$this->guarantee_fee, "担保费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除担保费失败');
                        }
                    }
                }

                if ($this->pay_fee > 0) {
                    if($isND == true) {
                        //还支付服务费
                        $this->addNDRepayMoneyLog("支付服务费",PartialRepayService::FEE_TYPE_FW,$negative,$bizToken) ;
                    } else {
                        if ($user->changeMoney(-$this->pay_fee, "支付服务费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除担保费失败');
                        }
                    }
                }

                if ($this->canal_fee > 0) {
                    if($isND == true) {
                        //还渠道服务费
                        $this->addNDRepayMoneyLog("渠道服务费",PartialRepayService::FEE_TYPE_QD,$negative,$bizToken) ;
                    } else {
                        if ($user->changeMoney(-$this->canal_fee, "渠道服务费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除渠道费失败');
                        }
                    }

                }

                if (($deal['isDtb'] == 1) && ($this->management_fee > 0)) {
                    if ($user->changeMoney(-$this->management_fee, "管理服务费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款扣除管理服务费失败');
                    }
                }

                $note = "编号{$deal['id']} {$deal['name']} 借款人ID{$this->user_id} 借款人姓名{$user['real_name']}";

                // JIRA#1108 还款时收取服务费
                // 手续费
                if ($this->loan_fee > 0 && !$isND) {
                    $loan_user_id = \core\dao\DealAgencyModel::instance()->getLoanAgencyUserId($deal['id']);
                    $user_consult = $user_model->find($loan_user_id);

                    $user_consult->changeMoneyDealType = $dealService->getDealType($deal);
                    $user_consult->isDoNothing = $isDealYJ175 ? true : false;
                    $user_consult->changeMoneyAsyn = true;
                    if ($user_consult->changeMoney($this->loan_fee, '平台手续费', $note, 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('还款支付手续费失败');
                    }
                    if (bccomp($this->loan_fee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'LOAN_FEE|' . $deal['id'],
                            'payerId' => $user->id,
                            'receiverId' => $loan_user_id,
                            'repaymentAmount' => bcmul($this->loan_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }
                // 咨询费
                if ($this->consult_fee > 0 && !$isND) {
                    $advisory_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['advisory_id']); // 咨询机构
                    $consult_user_id = $advisory_info['user_id']; // 咨询机构账户
                    $user_consult = $user_model->find($consult_user_id);
                    $user_consult->changeMoneyDealType = $dealService->getDealType($deal);
                    $user_consult->isDoNothing = $isDealYJ175 ? true : false;
                    $user_consult->changeMoneyAsyn = true;

                    if($deal['consult_fee_period_rate'] > 0){
                        $consult_fee_period = floorfix($deal['borrow_amount'] * $deal['consult_fee_period_rate'] / 100.0);
                        if($this->consult_fee < $consult_fee_period){
                            throw new \Exception('分期咨询费大于总咨询费');
                        }

                        if($this->consult_fee > $consult_fee_period){
                            $consult_fee = bcadd($this->consult_fee,-$consult_fee_period,2);
                            if ($user_consult->changeMoney($consult_fee, "咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative, $bizToken) === false) {
                                throw new \Exception('还款支付咨询费失败');
                            }
                            if (bccomp($consult_fee, '0.00', 2) > 0) {
                                $syncRemoteData[] = array(
                                    'outOrderId' => 'CONSULT_FEE' . $deal['id'],
                                    'payerId' => $user->id,
                                    'receiverId' => $consult_user_id,
                                    'repaymentAmount' => bcmul($consult_fee, 100), // 以分为单位
                                    'curType' => 'CNY',
                                    'bizType' => 1,
                                    'batchId' => $deal['id'],
                                );
                            }
                        }
                        if ($user_consult->changeMoney($consult_fee_period, "分期咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative, $bizToken) === false) {
                            throw new \Exception('还款支付分期咨询费失败');
                        }

                        if (bccomp($consult_fee_period, '0.00', 2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'CONSULT_PERIOD_FEE' . $deal['id'],
                                'payerId' => $user->id,
                                'receiverId' => $consult_user_id,
                                'repaymentAmount' => bcmul($consult_fee_period, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $deal['id'],
                            );
                        }
                    }else{
                        if ($user_consult->changeMoney($this->consult_fee, "咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative, $bizToken) === false) {
                            throw new \Exception('还款支付咨询费失败');
                        }

                        if (bccomp($this->consult_fee, '0.00', 2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'CONSULT_FEE' . $deal['id'],
                                'payerId' => $user->id,
                                'receiverId' => $consult_user_id,
                                'repaymentAmount' => bcmul($this->consult_fee, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $deal['id'],
                            );
                        }
                    }
                }
                // 担保费
                if ($this->guarantee_fee > 0 && !$isND) {
                    $agency_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']); // 咨询机构
                    $guarantee_user_id = $agency_info['user_id']; // 担保机构账户
                    $user_guarantee = $user_model->find($guarantee_user_id);
                    $user_guarantee->changeMoneyDealType = $dealService->getDealType($deal);
                    $user_guarantee->isDoNothing = $isDealYJ175 ? true : false;
                    $user_guarantee->changeMoneyAsyn = true;
                    if ($user_guarantee->changeMoney($this->guarantee_fee, '担保费', $note, 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('还款支付担保费失败');
                    }
                    if (bccomp($this->guarantee_fee, '0.00',2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'GUARANTEE_FEE|' . $deal['id'],
                            'payerId' => $user->id,
                            'receiverId' => $guarantee_user_id,
                            'repaymentAmount' => bcmul($this->guarantee_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                // 支付服务费
                if ($this->pay_fee > 0 && !$isND) {
                    $pay_user_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['pay_agency_id']); // 支付机构
                    $pay_user_id = $pay_user_info['user_id']; // 支付机构账户
                    $user_pay = $user_model->find($pay_user_id);
                    $user_pay->changeMoneyDealType = $dealService->getDealType($deal);
                    $user_pay->isDoNothing = $isDealYJ175 ? true : false;
                    $user_pay->changeMoneyAsyn = true;
                    if ($user_pay->changeMoney($this->pay_fee, '支付服务费', $note, 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('还款支付服务费失败');
                    }
                    if (bccomp($this->pay_fee, '0.00',2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'PAY_SERVICE_FEE|' . $deal['id'],
                            'payerId' => $user->id,
                            'receiverId' => $pay_user_id,
                            'repaymentAmount' => bcmul($this->pay_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                // 渠道服务费
                if ($this->canal_fee > 0 && !$isND) {
                    $canal_user_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['canal_agency_id']); // 支付机构
                    $canal_user_id = $canal_user_info['user_id']; // 渠道机构账户
                    $canal_pay = $user_model->find($canal_user_id);
                    $canal_pay->changeMoneyDealType = $dealService->getDealType($deal);
                    $canal_pay->changeMoneyAsyn = true;
                    $canal_pay->isDoNothing = $isDealYJ175 ? true : false;
                    if ($canal_pay->changeMoney($this->canal_fee, '渠道服务费', $note, 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('还款渠道服务费失败');
                    }
                    if (bccomp($this->canal_fee, '0.00',2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'CANAL_SERVICE_FEE|' . $deal['id'],
                            'payerId' => $user->id,
                            'receiverId' => $canal_user_id,
                            'repaymentAmount' => bcmul($this->canal_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                // 管理服务费
                 if (($deal['isDtb'] == 1) && ($this->management_fee > 0)) {
                    $management_user_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['management_agency_id']); // 管理机构
                    $management_user_id = $management_user_info['user_id']; // 管理机构账户
                    $user_management = $user_model->find($management_user_id);
                    $user_management->changeMoneyDealType = $dealService->getDealType($deal);
                    $user_management->isDoNothing = $isDealYJ175 ? true : false;
                    $user_management->changeMoneyAsyn = true;
                    if ($user_management->changeMoney($this->management_fee, '管理服务费', $note, 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('还款管理服务费失败');
                    }
                    if (bccomp($this->management_fee, '0.00',2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'MANAGEMENT_SERVICE_FEE|' . $deal['id'],
                            'payerId' => $user->id,
                            'receiverId' => $management_user_id,
                            'repaymentAmount' => bcmul($this->management_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                if (!empty($syncRemoteData) && !$dealService->isP2pPath($deal)) {
                    FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH);
                }

                //add 2014-1-21 caolong
                $user = $user_model->find($this->user_id);
                $content = "您好，您在".app_conf("SHOP_TITLE")."的融资项目“".$deal['name']."”成功还款" . format_price($this->repay_money, 0) . "元，";
                $next_repay = $this->getNextRepay();
                $next_repay_id = null;
                if($next_repay){
                    $next_repay_id = $next_repay->id;
                    $content .= "本融资项目的下个还款日为".to_date($next_repay['repay_time'],"Y年m月d日")."，需要本息". format_price($next_repay['repay_money'], 0) . "元。";
                    $deal->next_repay_time = $next_repay['repay_time'];
                    if (!$deal->save()) {
                        throw new \Exception('修改下个还款日失败！');
                    }
                }
                else{
                    $content .= "本融资项目已还款完毕！";
                }

                $dprm = new DealLoanPartRepayModel();
                $count = $dprm->getDealPartRepayCount($this->deal_id,1);

                //最后一笔
                if($next_repay_id == null && $count == 0){
                    $dealRepayRes = $deal->repayCompleted(true);
                    if($dealRepayRes === false){
                        throw new \Exception("还有未完成还款，不能更改标的未已还清状态");
                    }

                    // 最后一次还款执行
                    // JIRA#3090 定向委托投资标的超额收益功能
                    if ($deal['type_id'] == DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT)) {
                        $incomeExcessService = new IncomeExcessService();
                        $res = $incomeExcessService->pendingRepay($deal['id']);
                        if(!$res) {
                            throw new \Exception("超额收益待还款状态更新失败");
                        }
                    }
                }

                send_user_msg("",$content,0,$user['id'],get_gmtime(),0,true,8);
                //短信通知
                if(app_conf("SMS_ON")==1&&app_conf('SMS_SEND_REPAY')==1){
                    $notice = array(
                        "site_name" => app_conf('SHOP_TITLE'),
                        "real_name" => $user['real_name'],
                        "repay"     => $this->repay_money,
                    );
                    // SMSSend 还款短信
                    $_mobile = $user['mobile'];
                    if ($user['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                    {
                        $_mobile = 'enterprise';
                    }
                    \libs\sms\SmsServer::instance()->send($_mobile, 'TPL_DEAL_LOAD_REPAY_SMS', $notice, $user['id']);
                }

                // add by jinhaidong 还款的时候掉这个感觉没啥乱用 需要详细测试
                //syn_deal_status($this->deal_id);

                $result = DealLoanRepayModel::instance()->repayDealLoan($this->id, $next_repay_id, $ignore_impose_money, $repay_user_id);
                $impose_money = $result['total_overdue'];
                if ($impose_money) {
                    $deal_repay = $this->find($this->id);
                    $deal_repay->impose_money = $impose_money;
                    $deal_repay->update_time = get_gmtime();
                    $deal_repay->save();
                    if($this->status == 2 && !$ignore_impose_money){
                        $user->changeMoneyDealType = $dealService->getDealType($deal);
                        $user->isDoNothing = $isDealYJ175 ? true : false;
                        $flag1 = $user->changeMoney(-$impose_money, "逾期罚息", "编号".$deal['id'].' '.$deal['name'], 0, 0, 0, 0, $bizToken);
                        if ($flag1 === false) {
                            throw new \Exception('扣除逾期罚息失败！');
                        }
                    }
                }
                //计算总共还款扣除的钱
                $totalRepayMoney = $this->principal + $this->interest + $this->loan_fee + $this->guarantee_fee + $this->consult_fee + $this->pay_fee + $this->canal_fee + $impose_money;
                // 加入还款结束检查
                $jobs_model = new JobsModel();
                $function = '\core\dao\DealRepayModel::finishRepay';
                $param = array(
                    'deal_id' => $this->deal_id,
                    'user_id' => $this->user_id,
                    'deal_repay_id' => $this->id,
                    'next_repay_id' => $next_repay_id,
                    'repayUserId' => $user->id,
                    'orderId' => $orderId
                );
                $jobs_model->priority = 85;
                $r = $jobs_model->addJob($function, array('param' => $param), false, 90);
                if ($r === false) {
                    throw new \Exception('add \core\dao\DealRepayModel::finishRepay error');
                }


                $save_res = $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
                if(!$save_res){
                    throw new \Exception('修改标的还款状态失败！');
                }

                $this->db->commit();
            } else {
                //return false;
                throw new \Exception('还款单状态修改失败！');
            }
        } catch (\Exception $e) {
            \FP::import("libs.utils.logger");
            \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $this->id, $this->deal_id, $e->getMessage())));
            $this->db->rollback();

            if ($negative == 0 && $deal) {
                $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
            }

            return false;
        }

        return true;
    }

    /**
     * 记录农担贷资金记录
     */
    public function addNDRepayMoneyLog($logType,$feeTypes,$negative,$bizToken) {
        //扣减借款人的钱
        $borrowerRepayMoney = $this->partialRepayModel->getRepayMoney($this->id,PartialRepayModel::REPAY_TYPE_BORROWER,$feeTypes);
        if(bccomp($borrowerRepayMoney,'0.00',2) == 1) { //借款人还款大于0
            if ($this->ndBorrowerUser->changeMoney(-$borrowerRepayMoney, $logType, "编号".$this->ndDeal['id'].' '.$this->ndDeal['name'],0,0,0,$negative,$bizToken) === false) {
                throw new \Exception("还款扣除{$logType}失败");
            }
        }

        //扣减代偿机构的钱
        $compensatoryRepayMoney = $this->partialRepayModel->getRepayMoney($this->id,PartialRepayModel::REPAY_TYPE_COMPENSATORY,$feeTypes);
        if(bccomp($compensatoryRepayMoney,'0.00',2) == 1) { //代偿还款大于0
            if ($this->ndCompensatoryUser->changeMoney(-$compensatoryRepayMoney, $logType, "编号".$this->ndDeal['id'].' '.$this->ndDeal['name'],0,0,0,$negative,$bizToken) === false) {
                throw new \Exception("还款扣除{$logType}失败");
            }
        }

        if(is_array($feeTypes)) {
            return true;
        }

        //收费机构收钱
        $feeUser = null;
        $user_model = new UserModel();
        $dealService = new DealService();
        switch ($feeTypes) {
            case PartialRepayService::FEE_TYPE_SX:
                $fee_user_id = \core\dao\DealAgencyModel::instance()->getLoanAgencyUserId($this->ndDeal['id']);
                break;
            case PartialRepayService::FEE_TYPE_ZX:
                $advisory_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($this->ndDeal['advisory_id']); // 咨询机构
                $fee_user_id = $advisory_info['user_id']; // 咨询机构账户
                break;
            case PartialRepayService::FEE_TYPE_DB:
                $agency_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($this->ndDeal['agency_id']); // 咨询机构
                $fee_user_id = $agency_info['user_id']; // 担保机构账户
                break;
            case PartialRepayService::FEE_TYPE_FW:
                $pay_user_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($this->ndDeal['pay_agency_id']); // 支付机构
                $fee_user_id = $pay_user_info['user_id']; // 支付机构账户
                break;
            case PartialRepayService::FEE_TYPE_QD:
                $canal_user_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($this->ndDeal['canal_agency_id']); // 渠道机构
                $fee_user_id = $canal_user_info['user_id']; // 渠道机构账户
                break;
        }

        $feeUser = $user_model->find($fee_user_id);
        $feeUser->changeMoneyDealType = $dealService->getDealType($this->ndDeal);
        $feeUser->changeMoneyAsyn = true;

        if(bccomp($borrowerRepayMoney,'0.00',2) == 1) { //借款人还款大于0
            $borrowerNote = "编号{$this->ndDeal['id']} {$this->ndDeal['name']} 借款人ID{$this->user_id} 借款人姓名{$this->ndBorrowerUser['real_name']}";
            if ($feeUser->changeMoney($borrowerRepayMoney, $logType, $borrowerNote, 0, 0, 0, 0, $bizToken) === false) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }

        if(bccomp($compensatoryRepayMoney,'0.00',2) == 1) { //代偿还款大于0
            $compensatoryNote = "编号{$this->ndDeal['id']} {$this->ndDeal['name']} 借款人ID{$this->user_id} 借款人姓名{$this->ndCompensatoryUser['real_name']}";
            if ($feeUser->changeMoney($compensatoryRepayMoney, $logType, $compensatoryNote, 0, 0, 0, 0, $bizToken) === false) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }
    }

    /**
     * JIRA#1108 后台编辑服务费，修改还款计划
     * @param unknown $deal_id 标的ID
     * @param unknown $loan_fee_arr 借款平台手续费
     * @param unknown $consult_fee_arr 借款咨询费
     * @param unknown $guarantee_fee_arr 借款担保费
     * @param unknown $pay_fee_arr 支付服务费
     * @param string $management_fee_arr 管理服务费
     * @return boolean
     */
    public function updateDealRepayServicefee($deal_id, $loan_fee_arr, $consult_fee_arr, $guarantee_fee_arr,$pay_fee_arr,$canal_fee_arr,$management_fee_arr=null) {
        $this->db->startTrans();
        try {
            $result = \core\dao\DealExtModel::instance()->saveDealExtServicefee($deal_id, $loan_fee_arr, $consult_fee_arr, $guarantee_fee_arr, $pay_fee_arr, $canal_fee_arr, $management_fee_arr);

            array_shift($loan_fee_arr);
            array_shift($consult_fee_arr);
            array_shift($guarantee_fee_arr);
            array_shift($pay_fee_arr);
            array_shift($canal_fee_arr);

            if (null != $management_fee_arr) {
                array_shift($management_fee_arr);
            }

            $deal_repay_list = $this->getDealRepayListByDealId($deal_id);
            $i = 0;
            foreach ($deal_repay_list as $deal_repay) {
                if ($deal_repay->status == 0) {
                    $deal_repay->repay_money = $deal_repay->repay_money - $deal_repay->loan_fee - $deal_repay->consult_fee - $deal_repay->guarantee_fee - $deal_repay->pay_fee - $deal_repay->canal_fee - $deal_repay->management_fee + $loan_fee_arr[$i] + $consult_fee_arr[$i] + $guarantee_fee_arr[$i] + $pay_fee_arr[$i]+ $canal_fee_arr[$i]+ $management_fee_arr[$i];
                    $deal_repay->loan_fee = $loan_fee_arr[$i];
                    $deal_repay->consult_fee = $consult_fee_arr[$i];
                    $deal_repay->guarantee_fee = $guarantee_fee_arr[$i];
                    $deal_repay->pay_fee = $pay_fee_arr[$i];
                    $deal_repay->canal_fee = $canal_fee_arr[$i];
                    if (null != $management_fee_arr) {
                        $deal_repay->management_fee = $management_fee_arr[$i];
                    }
                    $deal_repay->update_time = get_gmtime();
                    $deal_repay->save();
                }
                $i++;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
        return true;
    }

    //获取还款url地址
    public function getShareUrl($dealId='') {
        $durl = '';
        if(!empty($dealId)) {
            $durl = \libs\web\Url::gene("d", "", Aes::encryptForDeal($dealId), false, true);
            if($GLOBALS['user_info']){
                $durl .= "?r=".base64_encode(intval($GLOBALS['user_info']['id']));
            }
        }
        return get_domain().$durl;
    }

    /**
     * 获取逾期的借款id
     * @return array
     */
    public function getDelayRepayList() {
        $time = get_gmtime();
        $condition = sprintf("`status`='0' AND `repay_time`<'%d'", $this->escape($time));
        $result = $this->findAll($condition, true, 'deal_id');
        $arr = array();
        foreach ($result as $v) {
            $arr[] = $v['deal_id'];
        }
        return array_unique($arr);
    }

    /**
     * 获取逾期的借款列表
     * @return array
     */
    public function getDelayDealList() {
        $deal_ids = $this->getDelayRepayList();
        if (!$deal_ids) {
            return array();
        }
        $deal_str = implode(",", $deal_ids);
        $condition = sprintf("`deal_status`='4' AND `deal_type` = 0 AND `is_delete`='0' AND `id` IN (%s)", $this->escape($deal_str));
        $result = DealModel::instance()->findAll($condition);
        return $result;
    }

    /**
     * 获取逾期的借款数目
     * @return int
     */
    public function getDelayDealCount() {
        $deal_ids = $this->getDelayRepayList();
        if (!$deal_ids) {
            return 0;
        }
        $deal_str = implode(",", $deal_ids);
        $condition = sprintf("`deal_status`='4' AND `is_delete`='0' AND `id` IN (%s)", $this->escape($deal_str));
        $count = DealModel::instance()->count($condition);
        return $count;
    }

    /**
     * 获取下次还款
     *
     * @return DealRepay or null
     **/
    public function getNextRepay(){
        return $this->findBy("deal_id=$this->deal_id and id>$this->id limit 1");
    }

    public function getNextRepayByRepayId($dealId,$repayId){
        return $this->findBy("deal_id={$dealId} and id>$repayId limit 1");
    }

    /**
     * 根据deal_id获取下次还款
     * @param int deal_id
     * @return obj
     */
    public function getNextRepayByDealId($deal_id) {
        $deal_id = intval($deal_id);
        $condition = sprintf("`deal_id`='%d' AND `status`='0' ORDER BY `id`", $deal_id);
        return $this->findByViaSlave($condition);
    }

    /**
     * 根据deal_id获取上期还款
     * @param int deal_id
     * @return obj
     */
    public function getPrevRepayByDealId($deal_id) {
        $deal_id = intval($deal_id);
        $condition = sprintf("`deal_id`='%d' AND `status`>'0' ORDER BY `id` DESC ", $deal_id);
        return $this->findByViaSlave($condition);
    }

    /**
     * 计算是否能够还款
     *
     * @return boolean
     **/
    public function canRepay()
    {
        $day_of_ahead_repay = $GLOBALS['dict']['DAY_OF_AHEAD_REPAY'];
        if($this->status == 0 && (int)$this->repay_time <= (get_gmtime() + $day_of_ahead_repay * 24 * 3600)){
            return true;
        }
        return false;
    }

    /**
     *  检查是否已经逾期
     *
     * @return void
     **/
    public function isOverdue()
    {
        return to_date(get_gmtime(), "Y-m-d") >= to_date($this->repay_time, "Y-m-d");
    }

    /**
     * 逾期天数
     *
     * @return integer
     **/
    public function daysOfOverdue()
    {
        if($this->status == 0 && $this->isOverdue()){
            return floor((get_gmtime() - $this->repay_time)/(24 * 60 * 60));
        } else {
            return 0;
        }
    }

    /**
     * 逾期费用
     *
     * @return float
     **/
    public function feeOfOverdue()
    {
        return 0;// jira:http://jira.corp.ncfgroup.com/browse/WXPH-9?filter=-1
        $deal = DealModel::instance()->find($this->deal_id);
        $principal = $this->principal;
        //对于按月付息，本金单独计算
        if($deal->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY'] || $deal->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']){
            $repay_times = $deal->getRepayTimes();
            $principal = $deal->borrow_amount / $repay_times; //计算每期正常情况下应还本金
        }
        return Finance::overdue($principal, $this->daysOfOverdue(), floatval($deal->rate)/100, floatval($deal->overdue_rate));
    }

    /**
     * 提前还款时，将未还的还款计划设置为取消
     * @param int $deal_id
     * @param int $prepay_time
     * @return bool
     */
    public function cancelDealRepay($deal_id, $prepay_time) {
        $params = array(":deal_id" => $deal_id);
        $list = $this->findAll("`deal_id`=':deal_id' AND `status`='0'", false, "*", $params);
        if (!$list) {
            return false;
        }

        foreach ($list as $v) {
            $v['status'] = self::STATUS_PREPAID;
            $v['update_time'] = $prepay_time;
            $v['true_repay_time'] = $prepay_time;
            if ($v->save() === false) {
                throw new \Exception("update repay status fail");
            }
        }

        return true;
    }

    /**
    * 还款结束检查
    */
    public function finishRepay($param){
        $deal_service = new DealService();
        $deal = DealModel::instance()->find($param['deal_id']);

        //检查这次还款的数量如果还有，那就等着
        $count = DealLoanRepayModel::instance()->getRepayCountByDealRepayId($param['deal_repay_id']);
        if($count>0 && !$deal_service->isDealPartRepay($deal->id)){
            throw new \Exception(JobsModel::ERRORMSG_NEEDDELAY, JobsModel::ERRORCODE_NEEDDELAY);
        }
        $this->db->startTrans();
        try {
            //修改标状态
            $save_res = $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
            if(!$save_res){
                throw new \Exception('修改标的还款状态失败！');
            }

            $thirdPartyOrder = \core\service\ThirdpartyDkService::getThirdPartyByOrderId($param['orderId']);
            if (!empty($thirdPartyOrder)) {
                $outerOrderRecord = \core\dao\ThirdpartyDkModel::instance()->find($thirdPartyOrder['id']);
                $outerOrderRecord->status = \core\dao\ThirdpartyDkModel::REQUEST_STATUS_SUCCESS;
                $outerOrderRecord->update_time = time();
                $updateOrderRes = $outerOrderRecord->save();
                if (!$updateOrderRes) {
                    throw new \Exception("更新Dk状态失败");
                }
            }
            //接口异步回调通知
            if ($thirdPartyOrder['notify_url'] != '') {
                $orderNotifyInfo = OrderNotifyModel::instance()->findViaOrderId($thirdPartyOrder['client_id'], $thirdPartyOrder['order_id']);
                if (empty($orderNotifyInfo)) {
                    $insertOrderNotifyData = [
                        'client_id'     => $thirdPartyOrder['client_id'],
                        'order_id'      => $thirdPartyOrder['order_id'],
                        'notify_url'    => $thirdPartyOrder['notify_url'],
                        'notify_params' => $thirdPartyOrder['params']
                    ];
                    $orderNotifyRes = OrderNotifyModel::instance()->insertData($insertOrderNotifyData);
                    if (!$orderNotifyRes) {
                        throw new \Exception("插入接口异步通知回调失败");
                    }
                }
            }
            $this->db->commit();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, $param['deal_repay_id'], $thirdPartyOrder['order_id'], "finishRepay succ")));
        } catch(\Exception $e) {
            throw new \Exception("错误信息:".$e->getMessage());
        }


        $is_dtv3 = $deal_service->isDealDTV3($param['deal_id']);
        if ($is_dtv3 === true) {
            \libs\utils\Monitor::add('DEAL_REPAY');
            return true;
        }

        $next_repay_id = $param['next_repay_id'];
        $repayUserId = intval($param['repayUserId']);//还款用户ID

        // 特殊标的逻辑处理
        if($param['deal_id'] != 5438830 && $param['deal_repay_id'] != 8921529){
            // JIRA#3102 投资短信整合 PM:lipanpan
            $obj = new GTaskService();
            $event = new DealRepayMsgEvent($param['deal_repay_id'], $param['deal_id']);
            $obj->doBackground($event, 1);

            // 站内信
            $obj_loan_repay = new GTaskService();
            $event_loan_repay = new DealLoanRepayMsgEvent($param['deal_id'], $param['deal_repay_id'], $param['next_repay_id']);
            $obj_loan_repay->doBackground($event_loan_repay, 1);
        }


        //记录随鑫约回款缓存
        $obj_reserve = new GTaskService();
        $event_reserve = new ReserveDealRepayCacheEvent($param['deal_repay_id'], $param['deal_id']);
        $obj_reserve->doBackground($event_reserve, 1);

        // 判断是否将回款记录同步到即付宝
        $jobs_model = new JobsModel();
        $jfparam = array(
            'deal_id' => $param['deal_id'],
            'prepay_id' => $param['deal_repay_id'],
        );
        $jobs_model->priority = 84;
        $r = $jobs_model->addJob('\core\service\jifu\JfLoanRepayService::syncNormalToJf', $jfparam);
        if ($r === false) {
            throw new \Exception("Add Jobs Fail");
        }

        /** 处理信用贷款逻辑  $credit_loan_service */
        $credit_loan_service = new CreditLoanService();
        if($credit_loan_service->isCreditingDeal($param['deal_id']) && empty($next_repay_id) && $deal_service->isDealYJ175($deal['id']) === false) {
            $jobs_model->priority = 100;
            $creditParam = array(
                'deal_id' => $deal['id'],
                'repay_type' => 2,// 1:网信提前还款2:正常还款3:逾期还款
            );
            $r = $jobs_model->addJob('\core\service\CreditLoanService::dealCreditAfterRepay', $creditParam);
            if ($r === false) {
                throw new \Exception("Add CreditAfterRepay Jobs Fail");
            }
        }

        $mq_job_model = new JobsModel();
        $mq_param = array('repayId'=>$param['deal_repay_id']);
        $mq_job_model->priority = JobsModel::PRIORITY_MESSAGE_QUEUE_REPAY;
        $mq_res = $mq_job_model->addJob('\core\service\mq\MqService::repay', array('param' => $mq_param), false, 90);
        if ($mq_res === false) {
            throw new \Exception("Add MqService repay Jobs Fail");
        }

        if($deal_service->isDealYtsh($param['deal_id'])){
            $XHService = new \core\service\XHService();
            $XHService->repaySuccessNotify($param['deal_id'],$param['deal_repay_id'],\core\service\XHService::REPAY_TYPE_NORMAL);
        }

        \libs\utils\Monitor::add('DEAL_REPAY');
        return true;
    }


    /**
     * 根据标的ids查出所有还款成功的列表
     *
     * @param $begin
     * @param $end
     * @return mixed
     */
    public function getRepayListByDealIds($dealIds) {
        $dealIdsStr = implode(',',$dealIds);
        $condition = sprintf('`status`!=0 AND `deal_id` IN (%s) ORDER BY `create_time` ASC',$dealIdsStr);
        $ret = $this->findAll($condition, false, '`deal_id`,`status`,`impose_money`,`interest`');
        return $ret;
    }


    /**
    * 获取标的所有正常还款和逾期还款的标
    */
    public function getRepaysByTime($start,$end){
        $condition = sprintf('`status`!=0 AND `status`!=4 AND `true_repay_time`>=%s AND `true_repay_time`<%s GROUP BY `deal_id`,`status` ORDER BY `deal_id`, `status` ASC',$start,$end);
        $ret = $this->findAllViaSlave($condition, false, 'deal_id,status');
        if (is_array($ret) && count($ret) > 0) {
            return $ret;
        } else {
            return array();
        }
    }


    /**
     * 取得标的的最大还款时间
     * @param $deal_id
     * @param array $status
     * @return \libs\db\Model
     */
    public function getMaxRepayTimeByDealId($deal_id, $status = array(1,2)) {
        $str_status = implode(',', $status);
        $res = $this->findBy("deal_id={$deal_id} and status in ({$str_status})","max(repay_time) as repay_time");
        return $res;
    }

    /**
     * 取得标的的最后一次还款时间
     * @param $deal_id
     * @return \libs\db\Model
     */
    public function getLastRepayTimeByDealId($deal_id) {
        $res = $this->findBy("deal_id={$deal_id}","max(repay_time) as repay_time");
        return $res;
    }

    /**
     * 取得预期还款汇总清单
     * @param $deal_id 标ID
     */
    public function getExpectRepayStat($deal_id) {
        $sql = sprintf('SELECT sum(repay_money) as repay_money,sum(loan_fee) as loan_fee,sum(consult_fee) as consult_fee,
                sum(guarantee_fee) as guarantee_fee,sum(pay_fee) as pay_fee,sum(management_fee) as management_fee,
                max(repay_time) as last_repay_time,sum(principal) as principal,sum(interest) as interest
                FROM `firstp2p_deal_repay` where deal_id=%s and status=%s',$deal_id,0);
        $res = $this->findBySql($sql);
        return $res;
    }

    /**
     * 根据标ID获取最后一期还款时间
     * @param $deal_id 标ID
     */
    public function getFinalRepayTimeByDealId($deal_id) {
        $sql = 'SELECT MAX(repay_time) as final_repay_time FROM `firstp2p_deal_repay` WHERE deal_id = '.$deal_id;
        $res = $this->findBySql($sql);
        if($res) {
            return $res->final_repay_time;
        }
        return 0;
    }

    /**
     * 提前还款情况下 取得标的截止某天的未收取费用
     * 手续费,咨询费,担保费,支付服务费
     * 每项费用计算公式 = 费用天数 * 借款金额(borrow_amount) * 费率 / 360 - 已收费用
     * @param $deal
     * @param $day 还款日期
     */
    public function getNoPayFees($deal,$deal_ext,$day) {
        $return = array('loan_fee'=>0,'consult_fee'=>0,'guarantee_fee'=>0,'pay_fee'=>0,'management_fee'=>0,'canal_fee'=>0);

        $has_pay_fees = 0; // 已经收取费用

        //续费后收 对于后收的标的，已收费用为零 对于分期收的标的，已收费用为已收各期费用之和
        $fee_days = ceil((to_timespan($day) - $deal->repay_start_time)/86400);
        $management_fee_column = "";
        if ($deal['isDtb'] == 1) {
            $management_fee_column = ",sum(management_fee) as management_fee " ;
        }

        // 已收取费用
        $sql = sprintf('SELECT `status`,sum(loan_fee) as loan_fee,sum(consult_fee) as consult_fee,sum(guarantee_fee) as guarantee_fee,sum(pay_fee) as pay_fee,sum(canal_fee) as canal_fee %s FROM `firstp2p_deal_repay` where deal_id = %s and `status` = 1',$management_fee_column,$deal['id']);
        $ret = $this->findBySql($sql);

        $totalMoney = $fee_days * $deal->borrow_amount;

        $return['loan_fee'] = 0;
        $has_pay_loan_fee = $ret['loan_fee'] ? $ret['loan_fee'] : 0;
        if(DealExtModel::FEE_RATE_TYPE_PROXY == $deal_ext['loan_fee_rate_type']) { // 手续费代销分期,足额收取最后一期的手续费
            $loan_fee_arr = json_decode($deal_ext['loan_fee_ext'], true);
            $return['loan_fee'] = array_pop($loan_fee_arr);
        } elseif (DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE == $deal_ext['loan_fee_rate_type']) { // 固定比例分期收，足额收取剩下的平台手续费
            $loan_fee_arr = json_decode($deal_ext['loan_fee_ext'], true);
            $return['loan_fee'] = array_sum($loan_fee_arr) - $has_pay_loan_fee;
        } else {
            if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtModel::FEE_RATE_TYPE_BEFORE, DealExtModel::FEE_RATE_TYPE_FIXED_BEFORE))) { // 如果是前收  或者 固定比例前收
                $return['loan_fee'] = 0;
            } else {
                if (DealExtModel::FEE_RATE_TYPE_FIXED_BEHIND == $deal_ext['loan_fee_rate_type']) { // 固定比例后收
                    $loan_fee_rate = $deal->loan_fee_rate / 100.0;
                    $loan_money = $deal->borrow_amount;
                } else { // 正常后收
                    $loan_fee_rate = $deal->loan_fee_rate /(100 * 360);
                    $loan_money = $totalMoney;
                }
                $loan_fee = $deal->floorfix($loan_money * $loan_fee_rate);
                $return['loan_fee'] = $loan_fee - $has_pay_loan_fee;
            }
        }

        if($deal_ext['consult_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
            $return['consult_fee'] = 0;
        } else {
            $consult_fee = $deal->floorfix($totalMoney * $deal->consult_fee_rate /(100 * 360));
            $has_pay_consult_fee = $ret['consult_fee'] ? $ret['consult_fee'] : 0;
            $return['consult_fee'] = $consult_fee - $has_pay_consult_fee;
        }

        if($deal_ext['guarantee_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
            $return['guarantee_fee'] = 0;
        } else {
            $guarantee_fee = $deal->floorfix($totalMoney * $deal->guarantee_fee_rate /(100 * 360));
            $has_pay_guarantee_fee = $ret['guarantee_fee'] ? $ret['guarantee_fee'] : 0;
            $return['guarantee_fee'] = $guarantee_fee - $has_pay_guarantee_fee;
        }

        if($deal_ext['pay_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
            $return['pay_fee'] = 0;
        } else {
            $pay_fee = $deal->floorfix($totalMoney * $deal->pay_fee_rate /(100 * 360));
            $has_pay_pay_fee = $ret['pay_fee'] ? $ret['pay_fee'] : 0;
            $return['pay_fee'] = $pay_fee - $has_pay_pay_fee;
        }

        if($deal_ext['canal_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
            $return['canal_fee'] = 0;
        } else {
            $canal_fee = $deal->floorfix($totalMoney * $deal->canal_fee_rate /(100 * 360));
            $has_pay_canal_fee = $ret['canal_fee'] ? $ret['canal_fee'] : 0;
            $return['canal_fee'] = $canal_fee - $has_pay_canal_fee;
        }

        if ($deal['isDtb'] == 1) {
            if($deal_ext['management_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
                $return['management_fee'] = 0;
            } else {
                $management_fee = $deal->floorfix($totalMoney * $deal->management_fee_rate /(100 * 360));
                $has_pay_management_fee = $ret['management_fee'] ? $ret['management_fee'] : 0;
                $return['management_fee'] = $management_fee - $has_pay_management_fee;
            }
        }

        return $return;
    }

   /**
    * 根据status统计标的利息
    * @param $status
    * @string $deal_types 标的类型
    */
   public function getRepayDealInterestByStatus($status = 0 ,$deal_types = '') {
       $condition = "WHERE `status` = $status";
       if($status != 0){
          $condition = sprintf("WHERE `status` IN (%s)", $status);
       }

       $deal_type_cond = '';
       if(!empty($deal_types)) {
           $deal_type_cond = ' AND deal_type IN ('. $deal_types .') ';
       }

       $sql = sprintf("SELECT SUM(`interest`+`impose_money`) as `sum` FROM %s %s %s ",$this->tableName(),$condition,$deal_type_cond);
       $result = $this->findAllBySqlViaSlave($sql,true);
       return $result['0']['sum'];
   }

    /**
     * 根据deal_id获取等额本息还款方式的最后一期本金
     * @param array $deal
     * @param int $deal_repay_id
     * @return float
     */
    public function getFixPrincipalByDeal($deal, $deal_repay_id) {
        $sql = "SELECT SUM(`principal`) AS `m` FROM %s WHERE `deal_id` = '%d' AND `id` != '%d'";
        $sql = sprintf($sql, $this->tableName(), $deal['id'], $deal_repay_id);

        $row = $this->findBySql($sql);

        if (!$row) {
            return false;
        } else {
            return bcsub($deal['borrow_amount'], $row['m'], 2);
        }
    }

    /**
     * 根据deal_id数据获取最后一期还款日
     * @param array $deal_ids
     * @return array
     */
    public function getMaxRepayTimeByDealIds($deal_ids) {
        $sql = 'SELECT `deal_id`, MAX(`repay_time`) AS `final_repay_time` FROM %s WHERE `deal_id` IN (%s) GROUP BY `deal_id`';
        $sql = sprintf($sql, $this->tableName(), $deal_ids);

        $res = $this->findAllBySql($sql, true, array(), true);

        $result = array();
        foreach ($res as $row) {
            $result[$row['deal_id']] = $row['final_repay_time'];
        }

        return $result;
    }

    /**
     * 专享标的按项目还款
     * @param $projectId 项目ID
     * @param $ignoreImposeMoney
     * @param $admin 管理员信息
     * @param $negative 是否可以扣负
     * @param $repayType 还款类型(0:借款人还款,1:代垫还款)
     * @param $submitUid 提交人ID
     * @param $auditType
     * @return mixed
     * @throws \Exception
     */
    public function projectRepay($projectId,$ignoreImposeMoney,$admin,$negative,$repayType,$submitUid,$auditType){
        $ps = new DealProjectService();
        $project = DealProjectModel::instance()->find(intval($projectId));

        if(empty($project)){
            throw new \Exception('未查询到此项目!');
        }
        $dealType = $project['deal_type'];
        $isYJ175 = $ps->isProjectYJ175($projectId);

        $dealsRepay = $this->getProjectDealRepay($projectId);
        $repayMoney = 0;
        $loanFee = 0;
        $consultFee = 0;
        $guaranteeFee = 0;
        $payFee = 0;
        $canalFee = 0;
        $managementFee = 0;
        $principal = 0;
        $interest = 0;

        $dealService = new DealService();
        $userModel = new UserModel();

        $repayRecords = array();
        $repayIds = array();
        $this->db->startTrans();

        $agencyDealId = 0;
        if($dealsRepay){
            foreach($dealsRepay as $dealRepay){
                $deal = DealModel::instance()->find($dealRepay['deal_id']);
                if(empty($agencyDealId)){
                    $agencyDealId = $dealRepay['deal_id'];
                }
                if(empty($user)){
                    if($repayType == 1){//代垫
                        if($deal['advance_agency_id'] > 0){
                            $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],1);
                            $user = $userModel->find($advanceAgencyUserId);
                        }else{
                            throw new \Exception('还款失败,未设置代垫机构!');
                        }
                    }elseif($repayType == 2) {//代偿
                        if($deal['agency_id'] > 0){//担保机构代偿
                            $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],2);
                            $user = $userModel->find($advanceAgencyUserId);
                        }else{
                            throw new \Exception('还款失败,未设置代偿机构!');
                        }
                    }elseif($repayType == 0){
                        $user = $userModel->find($dealRepay['user_id']);
                    }

                }
                $deal->changeRepayStatus(DealModel::DURING_REPAY);

                $repayMoney = bcadd($repayMoney,$dealRepay['repay_money'],2);
                $loanFee = bcadd($loanFee,$dealRepay['loan_fee'],2);
                $consultFee = bcadd($consultFee,$dealRepay['consult_fee'],2);
                $guaranteeFee = bcadd($guaranteeFee,$dealRepay['guarantee_fee'],2);
                $payFee = bcadd($payFee,$dealRepay['pay_fee'],2);
                $canalFee = bcadd($canalFee,$dealRepay['canal_fee'],2);
                $managementFee = bcadd($managementFee,$dealRepay['management_fee'],2);
                $principal = bcadd($principal,$dealRepay['principal'],2);
                $interest = bcadd($interest,$dealRepay['interest'],2);

                $repayRecords[] = array('repay_id'=>$dealRepay['id'],'deal_id'=>$dealRepay['deal_id'], 'repay_money' => $dealRepay['repay_money']);
                $repayIds[] = $dealRepay['id'];

            }

            if(count($repayRecords) == 0){
                return false;
            }


            $agencyDeal = DealModel::instance()->find($agencyDealId);

            $loanUserId = \core\dao\DealAgencyModel::instance()->getLoanAgencyUserId($agencyDeal['id']); // 平台机构
            $advisoryInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($agencyDeal['advisory_id']); // 咨询机构
            $agencyInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($agencyDeal['agency_id']); // 担保机构
            $entrustInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($agencyDeal['entrust_agency_id']);
            $payUserInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($agencyDeal['pay_agency_id']); // 支付机构
            $canalUserInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($agencyDeal['canal_agency_id']); // 支付机构
            $managementUserInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($agencyDeal['management_agency_id']); // 管理机构


            try {
                $effectRow = 0;
                $imposeMoneyTotal = 0;

                foreach($repayIds as $repayId){
                    $repayUpdateRecord = $this->find($repayId);
                    //由借款人/代偿,代垫机构还款至委托人账户
                    $time = get_gmtime();
                    $sets = array(
                        'true_repay_time' => $time,
                        'repay_type' => $repayType,
                    );
                    $condition = " id=".$repayUpdateRecord->id." AND status=0";

                    if(to_date($repayUpdateRecord->repay_time, "Y-m-d") >= to_date($time, "Y-m-d")){
                        $repayUpdateRecord->status = 1; //准时
                        $sets['status'] = 1;
                    }else{
                        $imposeMoney = $repayUpdateRecord->impose_money = $repayUpdateRecord->feeOfOverdue();
                        $sets['status'] = $repayUpdateRecord->status = 2; //逾期
                        $sets['impose_money'] = $imposeMoney;

                        $imposeMoneyTotal += $imposeMoney;
                    }

                    $repayUpdateRecord->true_repay_time = $time;
                    $sets['update_time'] = $time;
                    $this->updateAll($sets, $condition);
                    $effectRow += $this->db->affected_rows();
                }


                //如果还款金额不够,则更新所有标未处于还款中的状态,更新项目还款中状态
                if($negative === 0 && $isYJ175 === false){
                    if($user['money'] < ($repayMoney + $imposeMoneyTotal)){
                        foreach($dealsRepay as $dealRepay) {
                            $deal = DealModel::instance()->find($dealRepay['deal_id']);
                            $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);

                        }
                        DealProjectModel::instance()->changeProjectStatus($projectId,5);
                        $this->db->commit();
                        return true;
                    }
                }

                if($effectRow == count($repayIds)){
                    foreach($repayRecords as $repayRecord){
                        $deal = DealModel::instance()->find($repayRecord['deal_id']);

                        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
                        $deal['isDtb'] = 0;
                        if($deal['type_id'] == $dtbTypeId){
                            $deal['isDtb'] = 1;
                        }

                        $deal->repay_money = bcadd($deal->repay_money,$repayRecord['repay_money'],2);
                        $deal->last_repay_time = $time;
                        $deal->update_time = $time;
                        if (!$deal->save()) {
                            throw new \Exception('订单还款额修改失败！');
                        }
                    }

                    $bizToken = [
                        'projectId' => $projectId,
                        'dealRepayId' => $repayRecord['repay_id'],
                    ];

                    $repayUserId = $user['id'];
                    $repayMoney = $principal + $interest;
                    $user->changeMoneyDealType = $dealType;
                    $user->isDoNothing = $isYJ175 ? true : false;
                    if ($user->changeMoney(-$repayMoney, "偿还本息", $project['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款失败');
                    }

                    //给委托机构还本付息
                    $entrustUser = $userModel->find($entrustInfo['user_id']);
                    $entrustUser->changeMoneyDealType = $dealType;
                    $entrustUser->isDoNothing = $isYJ175 ? true : false;
                    if ($entrustUser->changeMoney($repayMoney, "偿还本息", $project['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款失败');
                    }else{
                        $syncRemoteData[] = array(
                            'outOrderId' =>  $projectId,
                            'payerId' => $user->id,
                            'receiverId' => $entrustInfo['user_id'],
                            'repaymentAmount' => bcmul($repayMoney, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $projectId,
                        );
                    }

                    //委托机构扣除本息
                    if ($entrustUser->changeMoney(-$repayMoney, "偿还本息", $project['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款失败');
                    }

                    if ($loanFee > 0) {
                        if ($user->changeMoney(-$loanFee, "平台手续费", $project['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除手续费失败');
                        }
                    }
                    if ($consultFee > 0) {
                        if ($user->changeMoney(-$consultFee, "咨询费", $project['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除咨询费失败');
                        }
                    }
                    if ($guaranteeFee > 0) {
                        if ($user->changeMoney(-$guaranteeFee, "担保费", $project['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除担保费失败');
                        }
                    }

                    if ($payFee > 0) {
                        if ($user->changeMoney(-$payFee, "支付服务费", $project['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除担保费失败');
                        }
                    }

                    if ($canalFee > 0) {
                        if ($user->changeMoney(-$canalFee, "渠道服务费", $project['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除渠道费失败');
                        }
                    }

                    if (($deal['isDtb'] == 1) && ($managementFee > 0)) {
                        if ($user->changeMoney(-$managementFee, "管理服务费", $project['name'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除管理服务费失败');
                        }
                    }

                    $note = "{$project['name']}";

                    if ($loanFee > 0) {
                        $userConsult = $userModel->find($loanUserId);
                        $userConsult->changeMoneyDealType = $dealType;
                        $userConsult->isDoNothing = $isYJ175 ? true : false;
                        if ($userConsult->changeMoney($loanFee, '平台手续费', $note, 0, 0, 0, 0, $bizToken) === false) {
                            throw new \Exception('还款支付手续费失败');
                        }
                        if (bccomp($loanFee, '0.00', 2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'LOAN_FEE|' . $projectId,
                                'payerId' => $user->id,
                                'receiverId' => $loanUserId,
                                'repaymentAmount' => bcmul($loanFee, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $projectId,
                            );
                        }
                    }
                    // 咨询费
                    if ($consultFee > 0) {
                        $consultUserId = $advisoryInfo['user_id']; // 咨询机构账户
                        $userConsult = $userModel->find($consultUserId);
                        $userConsult->changeMoneyDealType = $dealType;
                        $userConsult->isDoNothing = $isYJ175 ? true : false;
                        if ($userConsult->changeMoney($consultFee, '咨询费', $note, 0, 0, 0, 0, $bizToken) === false) {
                            throw new \Exception('还款支付咨询费失败');
                        }
                        if (bccomp($consultFee, '0.00', 2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'CONSULT_FEE' . $projectId,
                                'payerId' => $user->id,
                                'receiverId' => $consultUserId,
                                'repaymentAmount' => bcmul($consultFee, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $projectId,
                            );
                        }
                    }
                    // 担保费
                    if ($guaranteeFee > 0) {
                        $guaranteeUserId = $agencyInfo['user_id']; // 担保机构账户
                        $userGuarantee = $userModel->find($guaranteeUserId);
                        $userGuarantee->changeMoneyDealType = $dealType;
                        $userGuarantee->isDoNothing = $isYJ175 ? true : false;
                        if ($userGuarantee->changeMoney($guaranteeFee, '担保费', $note, 0, 0, 0, 0, $bizToken) === false) {
                            throw new \Exception('还款支付担保费失败');
                        }
                        if (bccomp($guaranteeFee, '0.00',2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'GUARANTEE_FEE|' . $projectId,
                                'payerId' => $user->id,
                                'receiverId' => $guaranteeUserId,
                                'repaymentAmount' => bcmul($guaranteeFee, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $projectId,
                            );
                        }
                    }

                    // 支付服务费
                    if ($payFee > 0) {
                        $payUserId = $payUserInfo['user_id']; // 支付机构账户
                        $userPay = $userModel->find($payUserId);
                        $userPay->changeMoneyDealType = $dealType;
                        $userPay->isDoNothing = $isYJ175 ? true : false;
                        if ($userPay->changeMoney($payFee, '支付服务费', $note, 0, 0, 0, 0, $bizToken) === false) {
                            throw new \Exception('还款支付服务费失败');
                        }
                        if (bccomp($payFee, '0.00',2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'PAY_SERVICE_FEE|' . $projectId,
                                'payerId' => $user->id,
                                'receiverId' => $payUserId,
                                'repaymentAmount' => bcmul($payFee, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $projectId,
                            );
                        }
                    }

                    // 渠道服务费
                    if ($canalFee > 0) {
                        $canalUserId = $canalUserInfo['user_id']; // 渠道机构账户
                        $userCanal = $userModel->find($canalUserId);
                        $userCanal->changeMoneyDealType = $dealType;
                        $userCanal->isDoNothing = $isYJ175 ? true : false;
                        if ($userCanal->changeMoney($canalFee, '渠道服务费', $note, 0, 0, 0, 0, $bizToken) === false) {
                            throw new \Exception('还款支付渠道费失败');
                        }
                        if (bccomp($canalFee, '0.00',2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'CANAL_SERVICE_FEE|' . $projectId,
                                'payerId' => $user->id,
                                'receiverId' => $canalUserId,
                                'repaymentAmount' => bcmul($canalFee, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $projectId,
                            );
                        }
                    }

                    // 管理服务费
                    if (($deal['isDtb'] == 1) && ($managementFee > 0)) {
                        $managementUserId = $managementUserInfo['user_id']; // 管理机构账户
                        $userManagement = $userModel->find($managementUserId);
                        $userManagement->changeMoneyDealType = $dealType;
                        $userManagement->isDoNothing = $isYJ175 ? true : false;

                        if ($userManagement->changeMoney($managementFee, '管理服务费', $note, 0, 0, 0, 0, $bizToken) === false) {
                            throw new \Exception('还款管理服务费失败');
                        }
                        if (bccomp($managementFee, '0.00',2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'MANAGEMENT_SERVICE_FEE|' . $projectId,
                                'payerId' => $user->id,
                                'receiverId' => $managementUserId,
                                'repaymentAmount' => bcmul($managementFee, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $projectId,
                            );
                        }
                    }

                    $overdueMoneyTotal = 0;

                    foreach($dealsRepay as $dealRepay){
                        //add 2014-1-21 caolong

                        $dealRepayRecord = DealRepayModel::instance()->find($dealRepay['id']);
                        $deal = DealModel::instance()->find($dealRepay['deal_id']);
                        $user = $userModel->find($dealRepayRecord['user_id']);
                        $content = "您好，您在".app_conf("SHOP_TITLE")."的融资项目“".$deal->name."”成功还款" . format_price($dealRepayRecord->repay_money, 0) . "元，";
                        $next_repay = $dealRepayRecord->getNextRepay();
                        $next_repay_id = null;
                        if($next_repay){
                            $next_repay_id = $next_repay->id;
                            $content .= "本融资项目的下个还款日为".to_date($next_repay['repay_time'],"Y年m月d日")."，需要本息". format_price($next_repay['repay_money'], 0) . "元。";
                            $deal->next_repay_time = $next_repay['repay_time'];
                            if (!$deal->save()) {
                                throw new \Exception('修改下个还款日失败！');
                            }
                        } else{
                            $content .= "本融资项目已还款完毕！";
                        }
                        //最后一笔
                        if($next_repay_id == null){
                            $dealRepayRes = $deal->repayCompleted();
                            if($dealRepayRes === false){
                                throw new \Exception("还有未完成还款，不能更改标的未已还清状态");
                            }

                            // 最后一次还款执行
                            // JIRA#3090 定向委托投资标的超额收益功能
                            if ($deal['type_id'] == DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT)) {
                                $incomeExcessService = new IncomeExcessService();
                                $res = $incomeExcessService->pendingRepay($deal['id']);
                                if(!$res) {
                                    throw new \Exception("超额收益待还款状态更新失败");
                                }
                            }
                        }else{
                            $next_repay_tag = true;
                        }

                        send_user_msg("",$content,0,$user['id'],get_gmtime(),0,true,8);
                        //短信通知
                        if(app_conf("SMS_ON")==1&&app_conf('SMS_SEND_REPAY')==1){
                            $notice = array(
                                "site_name" => app_conf('SHOP_TITLE'),
                                "real_name" => $user['real_name'],
                                "repay"     => $dealRepayRecord->repay_money,
                            );
                            // SMSSend 还款短信
                            $_mobile = $user['mobile'];
                            if ($user['user_type'] == \core\dao\UserModel::USER_TYPE_ENTERPRISE)
                            {
                                $_mobile = 'enterprise';
                            }
                            \libs\sms\SmsServer::instance()->send($_mobile, 'TPL_DEAL_LOAD_REPAY_SMS', $notice, $user['id']);
                        }

                        $result = DealLoanRepayModel::instance()->repayDealLoan($dealRepay['id'], $next_repay_id, $ignoreImposeMoney, $entrustInfo['user_id']);
                        $impose_money = $result['total_overdue'];
                        if ($impose_money) {
                            $deal_repay = $this->find($dealRepay['id']);
                            $deal_repay->impose_money = $impose_money;
                            $deal_repay->update_time = get_gmtime();
                            $deal_repay->save();
                            if($deal_repay->status == 2 && !$ignoreImposeMoney){
                                $overdueMoneyTotal += $impose_money;
                            }
                        }
                        //计算总共还款扣除的钱
                        $totalRepayMoney = $dealRepayRecord->principal + $dealRepayRecord->interest + $dealRepayRecord->loan_fee + $dealRepayRecord->guarantee_fee + $dealRepayRecord->consult_fee + $dealRepayRecord->pay_fee + $dealRepayRecord->canal_fee + $impose_money;
                        // 加入还款结束检查
                        $jobs_model = new JobsModel();
                        $function = '\core\dao\DealRepayModel::finishRepay';
                        $param = array(
                            'deal_id' => $deal['id'],
                            'user_id' => $dealRepayRecord->user_id,
                            'deal_repay_id' => $dealRepayRecord->id,
                            'next_repay_id' => $next_repay_id,
                            'repayUserId' => $user->id,
                        );
                        $jobs_model->priority = 85;
                        $r = $jobs_model->addJob($function, array('param' => $param), false, 90);
                        if ($r === false) {
                            throw new \Exception('add \core\dao\DealRepayModel::finishRepay error');
                        }

                        $save_res = $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
                        if(!$save_res){
                            throw new \Exception('修改标的还款状态失败！');
                        }

                        $repayOpLog = new DealRepayOplogModel();
                        $repayOpLog->operation_type = $isYJ175 ? DealRepayOplogModel::REPAY_TYPE_DAIFA : DealRepayOplogModel::REPAY_TYPE_NORMAL;//正常还款
                        $repayOpLog->operation_time = get_gmtime();
                        $repayOpLog->operation_status = 1;
                        $repayOpLog->operator = $admin['adm_name'];
                        $repayOpLog->operator_id = $admin['adm_id'];
                        //标的信息
                        $repayOpLog->deal_id = $deal['id'];
                        $repayOpLog->deal_name = $deal['name'];
                        $repayOpLog->borrow_amount = $deal['borrow_amount'];
                        $repayOpLog->rate = $deal['rate'];
                        $repayOpLog->loantype = $deal['loantype'];
                        $repayOpLog->repay_period = $deal['repay_time'];
                        $repayOpLog->user_id = $deal['user_id'];

                        //存管&&还款方式
                        $repayOpLog->repay_type = $repayType;
                        $repayOpLog->report_status = $deal['report_status'];

                        //还款的信息
                        $repayOpLog->deal_repay_id = $deal_repay['id'];
                        $repayOpLog->repay_money = $totalRepayMoney;
                        $repayOpLog->real_repay_time = get_gmtime();
                        $repayOpLog->submit_uid = intval($submitUid);
                        $repayOpLog->audit_type= intval($auditType);
                        $repayOpLog->save();

                    }

                    if(($overdueMoneyTotal > 0) && (!$ignoreImposeMoney) && $isYJ175 === false){
                        $borrowerFlag = $user->changeMoney(-$overdueMoneyTotal, "逾期罚息", $project['name'], 0, 0, 0, 0, $bizToken);
                        $entrustFlag = $entrustUser->changeMoney($overdueMoneyTotal, "逾期罚息", $project['name'], 0, 0, 0, 0, $bizToken);
                        $entrustFlag2 = $entrustUser->changeMoney(-$overdueMoneyTotal, "逾期罚息", $project['name'], 0, 0, 0, 0, $bizToken);

                        if (bccomp($overdueMoneyTotal, '0.00', 2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'OVERDUE_FEE|' . $project['id'],
                                'payerId' => $user->id,
                                'receiverId' => $entrustInfo['user_id'],
                                'repaymentAmount' => bcmul($overdueMoneyTotal, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $project['id'],
                            );
                        }

                        if (($borrowerFlag === false)||($entrustFlag === false)||($entrustFlag2 === false)) {
                            throw new \Exception('扣除逾期罚息失败！');
                        }
                    }

                    if (!empty($syncRemoteData) && $isYJ175 === false) {
                        FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH);
                    }

                    if($next_repay_tag){
                        DealProjectModel::instance()->changeProjectStatus($projectId,DealProjectModel::$PROJECT_BUSINESS_STATUS['repaying']);
                    }else{
                        DealProjectModel::instance()->changeProjectStatus($projectId,DealProjectModel::$PROJECT_BUSINESS_STATUS['repaid']);
                    }

                    //更新项目还款列表状态jobs添加
                    $function = '\core\dao\DealProjectModel::changeProjectRepayList';
                    $param = array(
                        'project_id' => $projectId,
                        'repay_ids' => $repayIds,
                    );
                    $jobs_model->priority = JobsModel::PRIORITY_PROJECT_REPAY;
                    $r = $jobs_model->addJob($function, $param, false, 90);
                    if ($r === false) {
                        throw new \Exception('add \core\dao\DealProjectModel::changeProjectRepayList error');
                    }

                }else{
                    throw new \Exception('还款单状态修改失败！');
                }

                $this->db->commit();
            } catch (\Exception $e) {
                \FP::import("libs.utils.logger");
                \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,  $projectId, $e->getMessage())));
                $this->db->rollback();
                if ($negative == 0 && $deal) {
                    foreach($dealsRepay as $dealRepay) {
                        $deal = DealModel::instance()->find($dealRepay['deal_id']);
                        $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
                    }
                }
                DealProjectModel::instance()->changeProjectStatus($projectId,4);
                return false;
            }
        }else{
            return false;
        }

        return true;
    }

    public function getProjectDealRepay($projectId,$startTime = 0,$endTime = 0){
        if(empty($projectId)){
            return false;
        }

        $deals = DealModel::instance()->getDealByProId(intval($projectId),array(4));
        //获取该项目所有还款中的标的
        $repayDeal = array();
        foreach($deals as $deal){
            if($deal['deal_status'] == DealModel::$DEAL_STATUS['repaying']){
                $repayDeal[] = $deal['id'];
            }
        }
        if(count($repayDeal) > 0){
            $repayDealCond = implode(',',$repayDeal);
        }else{
            return false;
        }

        //获取还款列表中最近一起的还款时间
        $repayTimeSql = "SELECT * from firstp2p_deal_repay WHERE deal_id in (".$repayDealCond.") AND status = 0 ORDER BY repay_time ASC limit 1";
        $record = $this->findBySql($repayTimeSql,array(),true);

        $startTime = $startTime != 0 ? $startTime : to_timespan(date("Y-m-d") . "00:00:00");
        $endTime = $endTime != 0 ? $endTime : to_timespan(date("Y-m-d") . " 23:59:59");

        $sql = "SELECT t1.`id`,t1.`repay_time`, t1.`repay_money`, t1.`user_id`,t1.deal_id,t1.loan_fee,t1.consult_fee,t1.guarantee_fee,t1.pay_fee,t1.canal_fee,t1.management_fee,t1.principal,t1.interest
                 FROM firstp2p_deal_repay t1
                 LEFT JOIN firstp2p_deal t2
                 ON t1.`deal_id` = t2.`id`
                 AND t1.`repay_time` = {$record['repay_time']} AND t1.`status` = 0 WHERE t2.`is_delete` = 0 AND t2.`publish_wait` = 0 AND t2.`deal_status` = 4 AND t2.`is_during_repay` = 0 AND t1.deal_id in(".$repayDealCond.") ORDER by t2.`id` desc";

        $rows = $this->findAllBySqlViaSlave($sql,true);
        if(empty($rows)){
            return false;
        }

        return $rows;
    }

    /**
     * 获取标的总还款期数
     * @param int $deal_id
     * @param int $user_id
     * @return int $periods_sum
     */
    public function getDealRepayPeriodsSumByUserId($deal_id, $user_id)
    {
        $condition = sprintf('`deal_id` = %d AND `user_id` = %d', $deal_id, $user_id);
        return $this->count($condition);
    }

    /**
     * 本次还款所属期数
     * @param int $deal_id
     * @param int $user_id
     * @return int $periods_order
     */
    public function getDealRepayPeriodsOrderByUserId($deal_id, $user_id)
    {
        $condition = sprintf('`deal_id` = %d AND `user_id` = %d AND `status` != %d', $deal_id, $user_id, self::STATUS_WAITING);
        return $this->count($condition);
    }
}
