<?php
namespace core\dao;

use core\dao\UserModel;
use core\dao\DealLoanRepayModel;
use core\dao\DealAgencyModel;
use core\dao\EnterpriseModel;
use core\dao\FinanceQueueModel;
use core\event\DealLoanRepayMsgEvent;
use core\event\DealRepayMsgEvent;
use core\service\DealService;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use libs\utils\Logger;

/**
 * DealLoanPartRepayModel
 * 用户部分回款计划
 **/
class DealLoanPartRepayModel extends BaseModel
{
    const STATUS_NOTPAYED   = 0; // 未还
    const STATUS_ISPAYED    = 1; // 已还
    const STATUS_SAVED      = 2; // 已保存
    const STATUS_ADOPTED    = 3; // 已审核通过

    public function isPartRepayDeal($deal_id,$repay_id=0){
        $cond = '';
        if($repay_id){
            $cond = "AND `deal_repay_id`=".$repay_id;
        }
        $sql = sprintf(" SELECT id FROM %s WHERE `deal_id`= %d {$cond} AND `status` !=1 limit 1", $this->tableName(), $deal_id,$repay_id);
        return $this->findBySql($sql);
    }

    /**
     * 获取标的的部分还款条数
     * @param $deal_id
     * @param $status
     * @return bool|string
     * @throws \Exception
     */
    public function getDealPartRepayCount($deal_id,$status){
        $sql = sprintf("SELECT count(*) AS `all` FROM %s WHERE `deal_id` = '%d' AND `status`!=%d", $this->tableName(), $deal_id,$status);
        $query_ret = $this->db->getOne($sql);
        if ($query_ret === false) {
            throw new \Exception("获取还款数量失败");
        }
        return $query_ret;
    }

    /**
     * 指定还款Id是否是部分还款
     * @param $deal_repay_id
     * @return \libs\db\Model
     */
    public function isPartRepay($deal_repay_id){
        $sql = sprintf(" SELECT id FROM %s WHERE `deal_repay_id`= %d limit 1", $this->tableName(), $deal_repay_id);
        return $this->findBySql($sql);
    }

    public function isPartRepayUser($deal_repay_id,$loan_user_id){
        $sql = sprintf(" SELECT id FROM %s WHERE `deal_repay_id`= %d AND `loan_user_id` = %d limit 1", $this->tableName(), $deal_repay_id,$loan_user_id);
        return $this->findBySql($sql);
    }

    public function isPartNotRepayUser($deal_repay_id,$loan_user_id){
        $sql = sprintf(" SELECT id FROM %s WHERE `deal_repay_id`= %d AND `loan_user_id` = %d AND `status`=0 limit 1", $this->tableName(), $deal_repay_id,$loan_user_id);
        return $this->findBySql($sql);
    }


    public function isPartRepayLoan($deal_repay_id,$deal_loan_id){
        $sql = sprintf(" SELECT id FROM %s WHERE `deal_repay_id`= %d AND `deal_loan_id` = %d limit 1", $this->tableName(), $deal_repay_id,$deal_loan_id);
        return $this->findBySql($sql);
    }



    /**
     * 根据还款Id获取部分还款数据
     * @param $deal_repay_id 还款Id
     * @return \libs\db\Model
     */
    public function getPartRepayListByRepayId($deal_repay_id,$status=''){
        $cond = '';
        if($status !== ''){
            $cond = "AND `status`=".$status;
        }
        $sql = sprintf(' SELECT * FROM %s WHERE `deal_repay_id`= %d %s', $this->tableName(), $deal_repay_id,$cond);
        return $this->findAllBySql($sql,true);
    }

    /**
     * 格式化部分还款数据
     * @param $dealRepay
     * @param $deal_repay_id
     * @return mixed
     * @throws \Exception
     */
    public function formatPartRepay($dealRepay,$deal_repay_id,$status=self::STATUS_ADOPTED) {
        //数据库中该repayId已经还款的条数
        $count = $this->getRepayCountByDealRepayId($deal_repay_id,self::STATUS_ISPAYED);
        if($count > 0){
            //不是第一次部分还款，费用第一次已经收过，这里清空费用数据
            $dealRepay['loan_fee']      = $dealRepay->loan_fee      = 0; //手续费
            $dealRepay['consult_fee']   = $dealRepay->consult_fee   = 0; //咨询费
            $dealRepay['guarantee_fee'] = $dealRepay->guarantee_fee = 0; //担保费
            $dealRepay['pay_fee']       = $dealRepay->pay_fee       = 0; //支付服务费
            $dealRepay['canal_fee']     = $dealRepay->canal_fee     = 0; //渠道服务费
        }

        //计算该次还款的本金、利息、总还款额
        $sql = "SELECT SUM(`repay_money`) as `total_repay_money`, SUM(`principal`) as `total_principal`,SUM(`interest`) as `total_interest` FROM %s WHERE `deal_repay_id`=%d AND status=%d";
        $sql = sprintf($sql,$this->tableName(),$deal_repay_id,$status);
        $totalPartRepay = $this->findBySql($sql, true);

        $totalFee = 0;
        $totalFee = bcadd($totalFee,$dealRepay['loan_fee'],2);
        $totalFee = bcadd($totalFee,$dealRepay['consult_fee'],2);
        $totalFee = bcadd($totalFee,$dealRepay['guarantee_fee'],2);
        $totalFee = bcadd($totalFee,$dealRepay['pay_fee'],2);
        $totalFee = bcadd($totalFee,$dealRepay['canal_fee'],2);

        $dealRepay['repay_money']   = $dealRepay->repay_money   = bcadd($totalPartRepay['total_repay_money'],$totalFee,2); //还款总额
        $dealRepay['principal']     = $dealRepay->principal     = $totalPartRepay['total_principal']; //还款本金
        $dealRepay['interest']      = $dealRepay->interest      = $totalPartRepay['total_interest']; //还款利息

        return $dealRepay;
    }

    /**
     * 获取部分还款汇总数据
     * @param $dealRepay
     * @param $deal_repay_id
     * @return mixed
     * @throws \Exception
     */
    public function getPartRepaySumByStatus($deal_repay_id,$status) {
        //计算该次还款的本金、利息、总还款额
        $sql = "SELECT SUM(`repay_money`) as `total_repay_money`, SUM(`principal`) as `total_principal`,SUM(`interest`) as `total_interest` FROM %s WHERE `deal_repay_id`=%d AND status=%d";
        $sql = sprintf($sql,$this->tableName(),$deal_repay_id,$status);
        $totalPartRepay = $this->findBySql($sql, true);
        $res = array();
        $res['repay_money'] = $totalPartRepay['total_repay_money']; //还款总额
        $res['principal']   =  $totalPartRepay['total_principal']; //还款本金
        $res['interest']    =  $totalPartRepay['total_interest']; //还款利息
        return $res;
    }

    /**
     *获取原始回款计划汇总数据
     * @param $deal_repay_id
     * @return array
     */
    public function getOriginLoanRepayInfos($deal_repay_id) {
            $dealLoanRepayModel = new DealLoanRepayModel();
            $condition = "`deal_repay_id`= '%d' AND status = 0 ORDER BY loan_user_id ASC";
            $condition = sprintf($condition,$deal_repay_id);
            $loan_repay_list = $dealLoanRepayModel->findAll($condition);
            if(empty($loan_repay_list)){
                return array();
            }
            $list = array();
            foreach ($loan_repay_list as $loan_repay) {
                $deal_loan_id = $loan_repay['deal_loan_id'];
                if(empty($list[$deal_loan_id])) {
                    $user = UserModel::instance()->find($loan_repay['loan_user_id']);
                    if (!empty($user)) {
                        $user['user_type_name'] = getUserTypeName($user['id']);
                        // 获取用户企业名称，若不是企业用户，则企业名称为用户真实名称
                        if (UserModel::USER_TYPE_ENTERPRISE == $user['user_type']) {
                            $user['real_name'] = getUserFieldUrl($user, EnterpriseModel::TABLE_FIELD_COMPANY_NAME);
                        } else {
                            $user['real_name'] = getUserFieldUrl($user, UserModel::TABLE_FIELD_REAL_NAME);
                        }
                    }

                    $list[$deal_loan_id] = array(
                        'deal_loan_id'=>$deal_loan_id,
                        'loan_user_id'=>$loan_repay['loan_user_id'],
                        'deal_id'=>$loan_repay['deal_id'],
                        'time'=>$loan_repay['time'],
                        'borrow_user_id'=>$loan_repay['borrow_user_id'],
                        'deal_type'=>$loan_repay['deal_type'],
                        'user_name'=>getUserFieldUrl($user),
                        'real_name'=>$user['real_name'],
                        'repay_money'=>0,
                        'principal'=>0,
                        'interest'=>0,
                        'status'=>0, // DealLoanPartRepay 表的初始状态
                    );
                }
                switch ($loan_repay['type']) {
                    //本金
                    case DealLoanRepayModel::MONEY_PRINCIPAL :
                        $money = $list[$deal_loan_id]['principal'];
                        $money = bcadd($money, $loan_repay['money'], 2);
                        $list[$deal_loan_id]['principal'] = $money;

                        $repay_money = $list[$deal_loan_id]['repay_money'];
                        $repay_money = bcadd($repay_money, $loan_repay['money'], 2);
                        $list[$deal_loan_id]['repay_money'] = $repay_money;
                        break;
                    //利息
                    case DealLoanRepayModel::MONEY_INTREST :
                        $money = $list[$deal_loan_id]['interest'];
                        $money = bcadd($money, $loan_repay['money'], 2);
                        $list[$deal_loan_id]['interest'] = $money;

                        $repay_money = $list[$deal_loan_id]['repay_money'];
                        $repay_money = bcadd($repay_money, $loan_repay['money'], 2);
                        $list[$deal_loan_id]['repay_money'] = $repay_money;
                        break;
                }
        }

        return $list;
    }

    /**
     * 执行还款
     *
     * @param boolean $ignore_impose_money 是否执行逾期罚息
     * @param $negative 0 不可扣负 1可扣负
     * @return void
     **/
    public function repay($dealRepay,$ignore_impose_money = false, &$totalRepayMoney = 0,$negative=1,$repayType=0, $orderId = '') {
        if($dealRepay->status > 0){
            return false;
        }

        $time = get_gmtime();

        $deal = DealModel::instance()->find($dealRepay->deal_id);
        $user_model = new UserModel();
        $dealService = new DealService();

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
            $user = $user_model->find($dealRepay->user_id);
        }elseif($repayType == 0){
            $user = $user_model->find($dealRepay->user_id);
        }elseif($repayType == 4){
            $user = $user_model->find($dealRepay->user_id);
        }elseif($repayType == 5) {//间接代偿
            if($deal['agency_id'] > 0){//担保机构代偿
                $advanceAgencyUserId = $dealService->getRepayUserAccount($deal['id'],2);
                $advanceAgencyUser = $user_model->find($advanceAgencyUserId);
            }else{
                throw new \Exception('还款失败,未设置间接代偿机构!');
            }
            $user = $user_model->find($dealRepay->user_id);
        }

        //如果还款金额不足,提交事物并返回状态,由service通知jobs提交 存管标的不需要进行余额判断
        $rollback = false;

        if($negative === 0 && $repayType == 3){
            //非存管代充值判断
            if($generationRechargeUser['money'] < $dealRepay->repay_money){
                $rollback = true;
            }
        }elseif($negative === 0 && $repayType <> 3){
            //原逻辑,判断账户余额
            if($user['money'] < $dealRepay->repay_money){
                $rollback = true;
            }
        }

        if($rollback === true){
            throw new \Exception('账户余额不足');
        }

        $this->db->startTrans();
        try {
            $sets = array(
                'true_repay_time' => $time,
                'repay_type' => $repayType,
            );
            $condition = " id=".$dealRepay->id." AND status=0";
            //获取未还完的数据
            $unRepayCount = $this->getRepayCountByDealRepayId($dealRepay['id'],self::STATUS_NOTPAYED);
            if($unRepayCount == 0) {
                if(to_date($dealRepay->repay_time, "Y-m-d") >= to_date($time, "Y-m-d")){
                    $dealRepay->status = 1; //准时
                    $sets['status'] = 1;
                }else{
                    $impose_money = $dealRepay->impose_money = $dealRepay->feeOfOverdue();
                    $sets['status'] = $dealRepay->status = 2; //逾期
                    $sets['impose_money'] = $impose_money;
                }
            }

            $dealRepay->true_repay_time = $time;
            $sets['update_time'] = $time;
            $dealRepay->updateAll($sets, $condition);
            $effectRow = $this->db->affected_rows();
            if($effectRow == 1){
                $deal = DealModel::instance()->find($dealRepay->deal_id);
                $deal->repay_money = bcadd($deal->repay_money,$dealRepay->repay_money,2);
                $deal->last_repay_time = $time;
                $deal->update_time = $time;
                if (!$deal->save()) {
                    throw new \Exception('订单还款额修改失败！');
                }
                $repay_user_id = $user['id'];

                $dealType = $dealService->getDealType($deal);
                $repay_money = $dealRepay->principal + $dealRepay->interest;
                $user->changeMoneyDealType = $dealType;

                $bizToken = [
                    'dealId' => $dealRepay->deal_id,
                    'dealRepayId' => $dealRepay->id,
                ];

                if($repayType == 3){ //代充值
                    $generationRechargeUser->changeMoneyDealType = $dealType;
                    $totalMoney = $repay_money + $dealRepay->loan_fee + $dealRepay->consult_fee + $dealRepay->guarantee_fee + $dealRepay->pay_fee;
                    if($dealRepay->status == 2 && !$ignore_impose_money){
                        $totalMoney = bcadd($totalMoney,$dealRepay->impose_money,2);
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
                if($repayType == DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG){ //间接代偿
                    $advanceAgencyUser->changeMoneyDealType = $dealType;
                    $totalMoney = $repay_money + $dealRepay->loan_fee + $dealRepay->consult_fee + $dealRepay->guarantee_fee + $dealRepay->pay_fee;
                    if($dealRepay->status == 2 && !$ignore_impose_money){
                        $totalMoney = bcadd($totalMoney,$dealRepay->impose_money,2);
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

                if ($user->changeMoney(-$repay_money, "偿还本息", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                    throw new \Exception('还款失败');
                }
                if ($dealRepay->loan_fee > 0) {
                    if ($user->changeMoney(-$dealRepay->loan_fee, "平台手续费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款扣除手续费失败');
                    }
                }

                if ($dealRepay->consult_fee > 0) {
                    if($deal['consult_fee_period_rate'] > 0){
                        $consult_fee_period = floorfix($deal['borrow_amount'] * $deal['consult_fee_period_rate'] / 100.0);
                        if($dealRepay->consult_fee < $consult_fee_period){
                            throw new \Exception('分期咨询费大于总咨询费');
                        }

                        if($dealRepay->consult_fee > $consult_fee_period){
                            $consult_fee = bcadd($dealRepay->consult_fee,-$consult_fee_period,2);
                            if ($user->changeMoney(-$consult_fee, "咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                                throw new \Exception('还款扣除咨询费失败');
                            }
                        }

                        if ($user->changeMoney(-$consult_fee_period, "分期咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除分期咨询费失败');
                        }
                    }else{
                        if ($user->changeMoney(-$dealRepay->consult_fee, "咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                            throw new \Exception('还款扣除咨询费失败');
                        }
                }
                }
                if ($dealRepay->guarantee_fee > 0) {
                    if ($user->changeMoney(-$dealRepay->guarantee_fee, "担保费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款扣除担保费失败');
                    }
                }

                if ($dealRepay->pay_fee > 0) {
                    if ($user->changeMoney(-$dealRepay->pay_fee, "支付服务费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款扣除担保费失败');
                    }
                }

                if ($dealRepay->canal_fee > 0) {
                    if ($user->changeMoney(-$dealRepay->canal_fee, "渠道服务费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative,$bizToken) === false) {
                        throw new \Exception('还款扣除渠道费失败');
                    }
                }

                $note = "编号{$deal['id']} {$deal['name']} 借款人ID{$dealRepay->user_id} 借款人姓名{$user['real_name']}";

                // 手续费
                if ($dealRepay->loan_fee > 0) {
                    $loan_user_id = DealAgencyModel::instance()->getLoanAgencyUserId($deal['id']);
                    $user_consult = $user_model->find($loan_user_id);

                    $user_consult->changeMoneyDealType = $dealService->getDealType($deal);
                    $user_consult->changeMoneyAsyn = true;
                    if ($user_consult->changeMoney($dealRepay->loan_fee, '平台手续费', $note, 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('还款支付手续费失败');
                    }
                    if (bccomp($dealRepay->loan_fee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'LOAN_FEE|' . $deal['id'],
                            'payerId' => $user->id,
                            'receiverId' => $loan_user_id,
                            'repaymentAmount' => bcmul($dealRepay->loan_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }
                // 咨询费
                if ($dealRepay->consult_fee > 0) {
                    $advisory_info = DealAgencyModel::instance()->getDealAgencyById($deal['advisory_id']); // 咨询机构
                    $consult_user_id = $advisory_info['user_id']; // 咨询机构账户
                    $user_consult = $user_model->find($consult_user_id);
                    $user_consult->changeMoneyDealType = $dealService->getDealType($deal);
                    $user_consult->changeMoneyAsyn = true;

                    if($deal['consult_fee_period_rate'] > 0){
                        $consult_fee_period = floorfix($deal['borrow_amount'] * $deal['consult_fee_period_rate'] / 100.0);
                        if($dealRepay->consult_fee < $consult_fee_period){
                            throw new \Exception('分期咨询费大于总咨询费');
                        }

                        if($dealRepay->consult_fee > $consult_fee_period){
                            $consult_fee = bcadd($dealRepay->consult_fee,-$consult_fee_period,2);
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
                        if ($user_consult->changeMoney($dealRepay->consult_fee, "咨询费", "编号".$deal['id'].' '.$deal['name'],0,0,0,$negative, $bizToken) === false) {
                            throw new \Exception('还款支付咨询费失败');
                        }

                        if (bccomp($dealRepay->consult_fee, '0.00', 2) > 0) {
                            $syncRemoteData[] = array(
                                'outOrderId' => 'CONSULT_FEE' . $deal['id'],
                                'payerId' => $user->id,
                                'receiverId' => $consult_user_id,
                                'repaymentAmount' => bcmul($dealRepay->consult_fee, 100), // 以分为单位
                                'curType' => 'CNY',
                                'bizType' => 1,
                                'batchId' => $deal['id'],
                            );
                        }
                    }
                }
                // 担保费
                if ($dealRepay->guarantee_fee > 0 ) {
                    $agency_info = DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']); // 咨询机构
                    $guarantee_user_id = $agency_info['user_id']; // 担保机构账户
                    $user_guarantee = $user_model->find($guarantee_user_id);
                    $user_guarantee->changeMoneyDealType = $dealService->getDealType($deal);
                    $user_guarantee->changeMoneyAsyn = true;
                    if ($user_guarantee->changeMoney($dealRepay->guarantee_fee, '担保费', $note, 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('还款支付担保费失败');
                    }
                    if (bccomp($dealRepay->guarantee_fee, '0.00',2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'GUARANTEE_FEE|' . $deal['id'],
                            'payerId' => $user->id,
                            'receiverId' => $guarantee_user_id,
                            'repaymentAmount' => bcmul($dealRepay->guarantee_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                // 支付服务费
                if ($dealRepay->pay_fee > 0) {
                    $pay_user_info = DealAgencyModel::instance()->getDealAgencyById($deal['pay_agency_id']); // 支付机构
                    $pay_user_id = $pay_user_info['user_id']; // 支付机构账户
                    $user_pay = $user_model->find($pay_user_id);
                    $user_pay->changeMoneyDealType = $dealService->getDealType($deal);
                    $user_pay->changeMoneyAsyn = true;
                    if ($user_pay->changeMoney($dealRepay->pay_fee, '支付服务费', $note, 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('还款支付服务费失败');
                    }
                    if (bccomp($dealRepay->pay_fee, '0.00',2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'PAY_SERVICE_FEE|' . $deal['id'],
                            'payerId' => $user->id,
                            'receiverId' => $pay_user_id,
                            'repaymentAmount' => bcmul($dealRepay->pay_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                // 渠道服务费
                if ($dealRepay->canal_fee > 0) {
                    $canal_user_info = DealAgencyModel::instance()->getDealAgencyById($deal['canal_agency_id']); // 支付机构
                    $canal_user_id = $canal_user_info['user_id']; // 渠道机构账户
                    $canal_pay = $user_model->find($canal_user_id);
                    $canal_pay->changeMoneyDealType = $dealService->getDealType($deal);
                    $canal_pay->changeMoneyAsyn = true;
                    if ($canal_pay->changeMoney($dealRepay->canal_fee, '渠道服务费', $note, 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('还款渠道服务费失败');
                    }
                    if (bccomp($dealRepay->canal_fee, '0.00',2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'CANAL_SERVICE_FEE|' . $deal['id'],
                            'payerId' => $user->id,
                            'receiverId' => $canal_user_id,
                            'repaymentAmount' => bcmul($dealRepay->canal_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 1,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                if (!empty($syncRemoteData) && !$dealService->isP2pPath($deal)) {
                    FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH);
                }

                $user = $user_model->find($dealRepay->user_id);
                $content = "您好，您在".app_conf("SHOP_TITLE")."的融资项目“".$deal['name']."”成功还款" . format_price($dealRepay->repay_money, 0) . "元，";
                $next_repay = $dealRepay->getNextRepay();
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

                //标的未还完的数据
                $count = $this->getDealPartRepayCount($dealRepay->deal_id,self::STATUS_ISPAYED);
                //最后一笔
                if($next_repay_id == null && ($count == 0)){
                    $dealRepayRes = $deal->repayCompleted(true);
                    if($dealRepayRes === false){
                        throw new \Exception("还有未完成还款，不能更改标的未已还清状态");
                    }
                }

                $result = DealLoanPartRepayModel::instance()->repayDealLoan($dealRepay->id, $next_repay_id, $ignore_impose_money, $repay_user_id);
                $impose_money = $result['total_overdue'];
                if ($impose_money) {
                    $deal_repay = $dealRepay->find($dealRepay->id);
                    $deal_repay->impose_money = $impose_money;
                    $deal_repay->update_time = get_gmtime();
                    $deal_repay->save();
                    if($dealRepay->status == 2 && !$ignore_impose_money){
                        $user->changeMoneyDealType = $dealService->getDealType($deal);
                        $flag1 = $user->changeMoney(-$impose_money, "逾期罚息", "编号".$deal['id'].' '.$deal['name'], 0, 0, 0, 0, $bizToken);
                        if ($flag1 === false) {
                            throw new \Exception('扣除逾期罚息失败！');
                        }
                    }
                }
                //计算总共还款扣除的钱
                $totalRepayMoney = $dealRepay->principal + $dealRepay->interest + $dealRepay->loan_fee + $dealRepay->guarantee_fee + $dealRepay->consult_fee + $dealRepay->pay_fee + $dealRepay->canal_fee + $impose_money;
                // 加入还款结束检查
                $jobs_model = new JobsModel();
                $function = '\core\dao\DealLoanPartRepayModel::finishRepay';
                $param = array(
                    'deal_id' => $dealRepay->deal_id,
                    'user_id' => $dealRepay->user_id,
                    'deal_repay_id' => $dealRepay->id,
                    'next_repay_id' => $next_repay_id,
                    'repayUserId' => $user->id,
                    'orderId' => $orderId
                );
                $jobs_model->priority = 85;
                $r = $jobs_model->addJob($function, array('param' => $param), false, 90);
                if ($r === false) {
                    throw new \Exception('add \core\dao\DealLoanPartRepayModel::finishRepay error');
                }


                $save_res = $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
                if(!$save_res){
                    throw new \Exception('修改标的还款状态失败！');
                }

                $this->db->commit();
            } else {
                throw new \Exception('还款单状态修改失败！');
            }
        } catch (\Exception $e) {
            \FP::import("libs.utils.logger");
            \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $dealRepay->id, $dealRepay->deal_id, $e->getMessage())));
            $this->db->rollback();
            if ($negative == 0 && $deal) {
                $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
            }
            return false;
        }
        return true;
    }

    /**
     * 根据状态获取部分还款的条数
     * @param $deal_repay_id
     * @param $status
     * @return bool|string
     * @throws \Exception
     */
    public function getRepayCountByDealRepayId($deal_repay_id,$status){
        $sql = sprintf("SELECT count(*) AS `all` FROM %s WHERE `deal_repay_id` = '%d' AND `status`=%d", $this->tableName(), $this->escape($deal_repay_id),$status);
        $query_ret = $this->db->getOne($sql);
        if ($query_ret === false) {
            throw new \Exception("获取还款数量失败");
        }
        return $query_ret;
    }


    /**
     * 还款结束检查
     */
    public function finishRepay($param){
        $deal = DealModel::instance()->find($param['deal_id']);

        //检查这次还款的数量如果还有，那就等着
        $count = $this->getRepayCountByDealRepayId($param['deal_repay_id'],self::STATUS_ADOPTED);
        if($count>0){
            throw new \Exception(JobsModel::ERRORMSG_NEEDDELAY, JobsModel::ERRORCODE_NEEDDELAY);
        }

        $save_res = $deal->changeRepayStatus(DealModel::NOT_DURING_REPAY);
        if(!$save_res){
            throw new \Exception('修改标的还款状态失败！');
        }
        if($deal->deal_status != DealModel::$DEAL_STATUS['repaid']){
            // 查询所有标的回款计划是否已还清
            $res = DealRepayModel::instance()->getDealUnpaiedRepayListByDealId($param['deal_id']);
            if(empty($res)){
                if(!$deal->repayCompleted()){
                    throw new \Exception('修改标的已还清状态失败！');
                }
            }
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, $param['deal_repay_id'],  "finishRepay succ")));

        // JIRA#3102 投资短信整合 PM:lipanpan
        $obj = new GTaskService();
        $event = new DealRepayMsgEvent($param['deal_repay_id'], $param['deal_id']);
        $obj->doBackground($event, 1);

        // 站内信
        $obj_loan_repay = new GTaskService();
        $event_loan_repay = new DealLoanRepayMsgEvent($param['deal_id'], $param['deal_repay_id'], $param['next_repay_id']);
        $obj_loan_repay->doBackground($event_loan_repay, 1);

        \libs\utils\Monitor::add('DEAL_REPAY');
        return true;
    }

    /**
     * 根据还款id执行正常还款操作
     * @param $deal_repay object
     * @param $next_repay_id int|bool 下次还款id false-若为最后一期
     * @param $next_repay_id int|bool 下次还款id false-若为最后一期
     * @return array("total_overdue"=>$totla_overdue) 返回扩展数据，目前只返回逾期罚息金额
     */
    public function repayDealLoan($deal_repay_id, $next_repay_id, $ignore_impose_money = false, $repay_user_id = false){
        $deal_repay_model = new DealRepayModel();
        $deal_repay = $deal_repay_model->find($deal_repay_id);
        $deal_repay = $this->formatPartRepay($deal_repay,$deal_repay_id);
        $deal_repay_id = intval($deal_repay->id);
        $deal_id = intval($deal_repay->deal_id);
        $deal = DealModel::instance()->find($deal_id);
        $dealLoanRepayModel = new \core\dao\DealLoanRepayModel();

        $GLOBALS['db']->startTrans();
        try {
            //从部分用户还款表中获取已审核通过的记录
            $part_repay_list = $this->getPartRepayListByRepayId($deal_repay_id,self::STATUS_ADOPTED);
            $total_overdue = 0;
            foreach ($part_repay_list as $part_repay) {
                //插入队列执行
                $jobs_model = new JobsModel();
                $function = '\core\dao\DealLoanRepayModel::repayDealLoanOne';
                $param = array(
                    'deal_repay_id' => $deal_repay_id,
                    'deal_loan_money' => $part_repay['repay_money'],
                    'deal_loan_id' => $part_repay['deal_loan_id'],
                    'deal_loan_user_id' => $part_repay['loan_user_id'],
                    'ignore_impose_money' => $ignore_impose_money,
                    'next_repay_id' => $next_repay_id,
                );
                $jobs_model->priority = 85;
                $r = $jobs_model->addJob($function, array('param' => $param));
                if ($r === false) {
                    throw new \Exception("add prepay by loan id jobs error");
                }
                // 同步还款记录
                if ($this->repayment($deal_repay_id, $deal->id, $repay_user_id,$part_repay['deal_loan_id']) === false) {
                    throw new \Exception("还款投资人失败");
                }
            }

            $result['total_overdue'] = $total_overdue;

            $rs = $GLOBALS['db']->commit();
        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            throw new \Exception($e->getMessage());
        }
        if ($rs === false) {
            throw new \Exception("事务提交失败");
        }
        return $result;
    }


    /**
     * 还款
     * @param $deal_repay_id 还款计划id
     * @param $deal_id 标的id
     */
    public function repayment($deal_repay_id, $deal_id, $repay_user_id,$deal_loan_id){
        $deal_id = intval($deal_id);
        $dealLoanRepayModel = new DealLoanRepayModel();

        $condition = "`deal_repay_id` = ':deal_repay_id' AND `deal_id` = ':deal_id' AND `deal_loan_id` = ':deal_loan_id' ORDER BY id ASC";
        $dealLoanRepayList = $dealLoanRepayModel->findAll($condition, false, '*', array(':deal_repay_id' => $deal_repay_id, ':deal_id' => $deal_id, ':deal_loan_id' => $deal_loan_id));

        $loanRepayParam['orders'] = array();
        foreach($dealLoanRepayList as $val){
            // 跳过金额为0.00的转账记录
            if (bccomp($val['money'], '0.00', 2) <= 0) {
                continue;
            }
            $temp = array();
            $temp['outOrderId'] = 'DEALLOANREPAY|' . $val['id'];
            $temp['payerId'] = $repay_user_id === false ? $val['borrow_user_id'] : $repay_user_id;//付款人ID
            //如果为管理费，需要将收款人调整为平台ID
            if($val['type'] == 6){
                $temp['receiverId'] = app_conf('MANAGE_FEE_USER_ID');
            }else{
                $temp['receiverId'] = $val['loan_user_id'];//收款人ID
            }
            $temp['repaymentAmount'] = $val['money']*100;//还款金额，单位为分

            $temp['curType'] = 'CNY';//币别，默认CNY
            $temp['bizType'] = 1;
            $temp['batchId'] = $deal_id;//币别，默认CNY
            $loanRepayParam['orders'][]=$temp;
        }

        return FinanceQueueModel::instance()->push($loanRepayParam,'transfer', FinanceQueueModel::PRIORITY_HIGH);
    }

    public function updateLoanRepayStatus($repay_id,$deal_loan_id,$status=1){
        if(!$this->isPartRepayLoan($repay_id,$deal_loan_id)){

            return true;
        }

        $data = array(
            'status' => $status,
            'update_time' => time(),
        );
        $cond = sprintf("deal_repay_id = %d AND deal_loan_id = %d",$repay_id,$deal_loan_id);
        return $this->updateBy($data,$cond);
    }


    /**
     * 获取最近一期的批次号
     * @param $deal_repay_id
     * @return \libs\db\Model
     */
    public function getLatestBatchId($deal_repay_id){
        $sql = sprintf(" SELECT batch_id FROM %s WHERE `deal_repay_id`= %d AND status IN (%s) limit 1", $this->tableName(), $deal_repay_id,implode(',',array(self::STATUS_SAVED,self::STATUS_ADOPTED)));
        $res = $this->findBySql($sql);
        if($res) {
            $batchId = $res['batch_id'];
        } else {
            $batchId = Idworker::instance()->getId();
        }
        return $batchId;
    }

    public function getNotRepayMoney($deal_repay_id){
        $sql = sprintf(" SELECT sum(repay_money) as totalMoney FROM %s WHERE `deal_repay_id`= %d AND status=0", $this->tableName(), $deal_repay_id);
        $res = $this->findBySql($sql);
        return $res['totalMoney'];
    }

}
