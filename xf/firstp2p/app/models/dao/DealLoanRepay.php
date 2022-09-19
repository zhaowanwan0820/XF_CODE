<?php
/**
 * DealLoanRepay class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace app\models\dao;
use core\dao\DealExtModel;
use core\dao\DealLoanRepayModel;
use core\dao\UserModel;
use core\dao\FinanceQueueModel;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\DealLoanRepayEvent;
use core\dao\DealLoanTypeModel;
use core\service\DealService;

require_once APP_ROOT_PATH . 'system/libs/msgcenter.php';

/**
 * 还款记录,每当满标后进行放款时生成一系列回款记录，也即回款计划
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class DealLoanRepay extends BaseModel {
    const MONEY_PRINCIPAL = 1; // 本金
    const MONEY_INTREST = 2; // 利息
    const MONEY_PREPAY = 3; // 提前还款
    const MONEY_COMPENSATION = 4; // 提前还款补偿金
    const MONEY_IMPOSE = 5; // 逾期罚息
    const MONEY_MANAGE = 6; // 管理费
    const MONEY_PREPAY_INTREST = 7; // 提前还款利息
    const MONEY_COMPOUND_PRINCIPAL = 8; // 利滚利赎回本金
    const MONEY_COMPOUND_INTEREST = 9; // 利滚利赎回利息

    const STATUS_NOTPAYED = 0; // 未还
    const STATUS_ISPAYED = 1; // 已还
    const STATUS_CANCEL = 2; // 因提前还款而取消

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
        $deal_loan_model = new DealLoad();
        $deal_loan_list  = $deal_loan_model->getDealLoanList($deal_repay->deal_id);

        $repay_money = 0;

        $deal = new Deal();
        $deal = $deal->find($deal_repay->deal_id);
        foreach ($deal_loan_list as $deal_loan) {
            // 根据投资总额生成回款金额
            $arr_deal_loan_repay = $deal->getRepayMoney($deal_loan['money'], $is_last, true, $interest_day, $periods_index);
            // 获取还款金额，计算管理费
            $arr_deal_repay = $deal->getRepayMoney($deal_loan['money'], $is_last, false, $interest_day, $periods_index);

            // 生成回款计划移入异步队列
            //$event = new DealLoanRepayEvent($deal_repay, $deal_loan, $arr_deal_loan_repay, $arr_deal_repay, $deal->principal, $is_last);
            //$event->execute();

            $function = '\core\service\DealLoanRepayService::create';
            $param = array($deal_repay->getRow(), $deal_loan->getRow(), $arr_deal_loan_repay, $arr_deal_repay, $deal->principal, $is_last);
            $job_model = new \core\dao\JobsModel();
            $job_model->priority = 50;
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
     * 保存回款计划
     * @param $deal_repay object
     * @param $deal_loan array
     * @param $arr_deal_loan_repay array
     * @param $arr_deal_repay array
     * @param $deal_principal float 借款本金总额
     * @return bool
     */
    private function saveDealLoanRepay($deal_repay, $deal_loan, $arr_deal_loan_repay, $arr_deal_repay, $deal_principal) {
        $this->deal_id        = $deal_repay->deal_id;
        $this->deal_repay_id  = $deal_repay->id;
        $this->deal_loan_id   = $deal_loan['id'];
        $this->loan_user_id   = $deal_loan['user_id'];
        $this->borrow_user_id = $deal_repay->user_id;
        $this->time           = $deal_repay->repay_time;
        $this->status         = self::STATUS_NOTPAYED;
        $this->create_time    = get_gmtime();
        $this->update_time    = get_gmtime();
        // 本金
        $this->type  = self::MONEY_PRINCIPAL;
        $this->money = $arr_deal_loan_repay['principal'];
        if ($this->insert() === false) {
            return false;
        }

        // 利息
        $this->type  = self::MONEY_INTREST;
        $this->money = $arr_deal_loan_repay['interest'];
        if ($this->insert() === false) {
            return false;
        }

        // 管理费
        $this->type  = self::MONEY_MANAGE;
        $this->money = $arr_deal_repay['total'] - $arr_deal_loan_repay['total'];
        if ($this->insert() === false) {
            return false;
        }
        return true;
    }

    /**
     * 根据回款类型获取回款类型文案
     * @param $type int
     * @return $money_type string
     */
    public static function getLoanRepayType($type) {
        switch ($type) {
            case self::MONEY_PRINCIPAL: $money_type = "本金";break;
            case self::MONEY_INTREST: $money_type = "利息";break;
            case self::MONEY_PREPAY: $money_type = "提前还款本金";break;
            case self::MONEY_COMPENSATION: $money_type = "提前还款补偿金";break;
            case self::MONEY_IMPOSE: $money_type = "逾期罚息";break;
            case self::MONEY_MANAGE: $money_type = "投资管理费";break;
            case self::MONEY_PREPAY_INTREST : $money_type = "提前还款利息";break;
            default : $money_type = false;
        }
        return $money_type;
    }

    /**
     * 根据回款状态获取回款状态文案
     * @param $status int
     * @return $money_status string
     */
    public static function getLoanRepayStatus($status) {
        switch ($status) {
            case self::STATUS_NOTPAYED: $money_status = "未还";break;
            case self::STATUS_ISPAYED: $money_status = "已还";break;
            case self::STATUS_CANCEL: $money_status = "因提前还款而取消";break;
            default : $money_status = false;
        }
        return $money_status;
    }

    /**
     * 根据还款id执行正常还款操作
     * @param $deal_repay object
     * @param $next_repay_id int|bool 下次还款id false-若为最后一期
     * @return 返回逾期罚息总额
     */
    public function repayDealLoan($deal_repay, $next_repay_id, $ignore_impose_money = false) {
        $deal_repay_id = $deal_repay->id;
        $deal_id       = $deal_repay->deal_id;
        $deal          = get_deal($deal_id);

        // 变更回款记录状态
        $this->db->query("UPDATE " . $this->tableName() . " SET `status`='" . self::STATUS_ISPAYED . "', `real_time`='" . get_gmtime() . "' WHERE `deal_repay_id` = '{$deal_repay_id}'");

        // 变更出借人账户
        $deal_loan_model = new DealLoad();
        $deal_loan_list  = $deal_loan_model->getDealLoanList($deal_id);

        // 对逾期罚息进行舍余
        $deal_model = new Deal();
        $total_overdue = 0;

        foreach ($deal_loan_list as $deal_loan) {
            $user_model = new User();
            $user       = $user_model->find($deal_loan['user_id']);

            //发生逾期还款
            if($deal_repay->status == 2 && !$ignore_impose_money){
                $fee_of_overdue = $deal_loan->money / $deal["borrow_amount"] * $deal_repay->impose_money;
                $fee_of_overdue = $deal->floorfix($fee_of_overdue);
                $total_overdue += $fee_of_overdue;
          // TODO finance 逾期罚息 | repayment
                $bizToken = [
                    'dealId' => $deal_id,
                    'dealRepayId' => $deal_repay->id,
                    'dealLoadId' => $deal_loan->id,
                ];
                $user->changeMoney($fee_of_overdue, "逾期罚息", "编号{$deal_id} {$deal['name']}");
                $loan_repay = new DealLoanRepay();
                $loan_repay->deal_id = $deal_id;
                $loan_repay->deal_repay_id = $deal_repay->id;
                $loan_repay->deal_loan_id = $deal_loan->id;
                $loan_repay->loan_user_id = $deal_loan->user_id;
                $loan_repay->borrow_user_id = $deal["user_id"];
                $loan_repay->money = $fee_of_overdue;
                $loan_repay->type = self::MONEY_IMPOSE;
                $loan_repay->time = $deal_repay->repay_time;
                $loan_repay->real_time = get_gmtime();
                $loan_repay->status = self::STATUS_ISPAYED;
                $loan_repay->insert();
            }

            $arr_change_money = $this->getChangeMoneyByRepayId($deal_repay_id, $user->id, $deal_loan->id);
            $loan_repay_list  = $this->findAll("`deal_repay_id`= {$deal_repay_id} AND `deal_loan_id` = {$deal_loan->id} AND `loan_user_id`={$user->id}");
            foreach ($loan_repay_list as $loan_repay) {
                switch ($loan_repay['type']) {
                    case self::MONEY_PRINCIPAL :
                        if ($loan_repay['money'] != 0) {
                // TODO finance 偿还本金 | repayment
                            $user->changeMoney($loan_repay['money'], "还本", "编号{$deal_id} {$deal['name']}");
                        }
                        break;
                    case self::MONEY_INTREST :
               // TODO finance 支付利息 | repayment
                        $user->changeMoney($loan_repay['money'], "付息", "编号{$deal_id} {$deal['name']}");
                        break;
                    case self::MONEY_MANAGE :
                        // 出借人平台管理费转入平台账户
                        $platform_user_id = app_conf('MANAGE_FEE_USER_ID');
                        $platform_user  = $user_model->find($platform_user_id);
                        if (!empty($platform_user)) {
                            $log_note = "编号{$deal_id} {$deal['name']} 投资记录ID{$loan_repay['deal_loan_id']}";
                // TODO finance 支付平台管理费 | repayment
                            $platform_user->changeMoney($loan_repay['money'], "平台管理费", $log_note);
                        }
                        break;
                    default:
                        continue;
                }
            }

            // 向出借人发送站内信和邮件
            $this->sendMsg($deal, $deal_loan, $user, $arr_change_money, $next_repay_id);
            // 调用前台还款接口
            DealLoanRepayModel::instance()->repayment($deal_repay_id);

        }

        return $total_overdue;
    }


    /**
     * 还款
     * @param $deal_repay_id 还款计划id
     */
    public function repayment($deal_id, $data = array()){
        $dealService = new \core\service\DealService();
        $deal_id = intval($deal_id);
        if($dealService->isP2pPath($deal_id)){
            return true;
        }

         $loanRepayParam['orders'] = array();
         $dealLoanRepayList = array();
         $condition = '';
        if (empty($data)) {
            $condition = "deal_id = :deal_id AND `status` = 0 ORDER BY id ASC";
            $dealLoanRepayList = $this->findAll($condition, false, '*', array(':deal_id' => $deal_id));
        }
        else {
            $tempdata = array();
            if (is_array($data)) {
                foreach ($data as $k => $batch) {
                    if (is_array($batch)) {
                        foreach ($batch as $item) {
                            $tempdata[] = $item;
                        }
                    }
                }
            }
            $dealLoanRepayList = $tempdata;
        }

        foreach($dealLoanRepayList as $val){
            // 跳过金额为0.00的转账记录
            if (bccomp($val['money'], '0.00', 2) <= 0) {
                continue;
            }
            $temp = array();
            $temp['outOrderId'] = 'PREDEALLOANREPAY|' . $val['id'];
            $temp['payerId'] = $val['borrow_user_id'];//付款人ID
            //如果为管理费，需要将收款人调整为平台ID
            if($val['type'] == 6){
                $temp['receiverId'] = app_conf('MANAGE_FEE_USER_ID');
            }else{
                $temp['receiverId'] = $val['loan_user_id'];//收款人ID
            }
            $temp['repaymentAmount'] = $val['money']*100;//还款金额，单位为分

            $temp['curType'] = 'CNY';//币别，默认CNY
            // 支付转账优化
            // 业务类型
            $temp['bizType'] = '1';
            // 批次号
            $temp['batchId'] = $deal_id;

            $loanRepayParam['orders'][]=$temp;
        }

        // $loanRepayParam['orders'] = json_encode($loanRepayParam['orders']);

        FinanceQueueModel::instance()->push($loanRepayParam,'transfer', FinanceQueueModel::PRIORITY_HIGH);
    }

    /**
     * 提前还款，根据投资人投资记录计算借款人实际还款金额
     * @param int $deal_id
     * @param floot $remain_principal 剩余本金
     * @param int $remain_days 剩余天数
     * @param array $result 投资人实际获得的金额总和，即借款人实际还款金额
     */
    public function getPrepayMoney($deal_id, $remain_principal, $remain_days) {
        $deal_model = new Deal();
        $deal = $deal_model->find($deal_id);
        $rate = $deal['income_fee_rate'];
        $result = array(
            'prepay_money' => 0,
            'principal' => 0,
            'prepay_interest' => 0,
            'loan_fee' => 0,
            'consult_fee' => 0,
            'guarantee_fee' => 0,
            'pay_fee' => 0,
        );

        $deal_service = new DealService();
        if ($deal_service->isDealDTV3($deal_id) === true) {
            $principal = $remain_principal;
            $prepay_interest = $deal_model->floorfix(prepay_money_intrest($principal, $remain_days, $rate));
            $prepay_compensation = $deal_model->floorfix($deal['borrow_amount'] * ($deal['prepay_rate']/100));
            $prepay_money = $principal + $prepay_interest + $prepay_compensation;
            $result['prepay_money'] = bcadd($result['prepay_money'],$deal_model->floorfix($prepay_money),2);
            $result['principal'] = bcadd($result['principal'],$deal_model->floorfix($principal),2);
            $result['prepay_interest'] = bcadd($result['prepay_interest'],$deal_model->floorfix($prepay_interest),2);
        } else {
            // 投资记录
            $deal_loan_model = new DealLoad();
            $deal_loan_list = $deal_loan_model->getDealLoanList($deal_id);

            $deal_loan_repay_model = new DealLoanRepayModel();
            foreach ($deal_loan_list as $deal_loan) {
                // 回款本金
                //$principal = $deal_loan['money'] * $remain_principal / $deal['borrow_amount'];
                $principal = $deal_loan_repay_model->getTotalMoneyByTypeStatusLoanId($deal_loan['id'],DealLoanRepayModel::MONEY_PRINCIPAL,DealLoanRepayModel::STATUS_NOTPAYED);
                // 回款利息
                $prepay_interest = $deal_model->floorfix(prepay_money_intrest($principal, $remain_days, $rate));

                // 提前还款违约金
                $prepay_compensation = $deal_model->floorfix($deal_loan['money'] * ($deal['prepay_rate']/100));
                // 回款实际金额
                //$prepay_money = prepay_money($deal_loan['money'],$principal, $remain_days, $deal['loan_compensation_days'], $rate);
                $prepay_money = $principal + $prepay_interest + $prepay_compensation;
                // 进行舍余后，计算实际回款总额

                $result['prepay_money'] = bcadd($result['prepay_money'],$deal_model->floorfix($prepay_money),2);
                $result['principal'] = bcadd($result['principal'],$deal_model->floorfix($principal),2);
                $result['prepay_interest'] = bcadd($result['prepay_interest'],$deal_model->floorfix($prepay_interest),2);
            }
        }

        $deal_repay_model = new \core\dao\DealRepayModel();
        $deal_repay_list = $deal_repay_model->getDealUnpaiedRepayListByDealId($deal_id);
        foreach ($deal_repay_list as $k => $v) {
            $result['loan_fee'] += $v['loan_fee'];
            $result['consult_fee'] += $v['consult_fee'];
            $result['guarantee_fee'] += $v['guarantee_fee'];
            $result['pay_fee'] += $v['pay_fee'];
        }

        return $result;
    }

    /**
     * @param $deal_id int 标id
     * @param $remain_principal float 剩余本金
     * @param $interest_days  int 利息天数
     * @param $use_days int 总共使用天数
     * @return array
     */
    public function computePrepayMoney($deal_id, $remain_principal, $interest_days, $use_days) {
        $deal_model = new Deal();
        $deal = $deal_model->find($deal_id);

        $deal['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($deal['id'])){
            $deal['isDtb'] = 1;
        }
        $result = array();

        $rate = $deal['rate'];  //年化借款利率

        //CAUTION：利息，计算的是理论值，与实际值不一样。
        $prepay_interest = prepay_money_intrest($remain_principal, $interest_days, $rate);
        $result['prepay_interest'] = $deal_model->floorfix($prepay_interest);

        //计算各种费用
        $deal_ext_model = new DealExtModel();
        $deal_ext = $deal_ext_model->getDealExtByDealId($deal_id);

        //当分期收的时候用到
        $deal_repay_model = new \core\dao\DealRepayModel();
        $deal_repay_list = $deal_repay_model->getPayedRepayListByDealId($deal_id);

        //平台管理费
        if($deal_ext['loan_fee_rate_type'] == 1) {//因为前收已经收取过平台费了，所以不用计算
            $result['loan_fee'] = 0.00;
        }else if($deal_ext['loan_fee_rate_type'] == 2 || $deal_ext['loan_fee_rate_type'] == 3) { //后收，借款金额x手续费率/360x使用天数
            $loan_fee = prepay_money_intrest($deal['borrow_amount'], $use_days, $deal['loan_fee_rate']);
            $result['loan_fee'] = $deal_model->floorfix($loan_fee);
            foreach ($deal_repay_list as $k => $v) {
                $result['loan_fee'] -= $v['loan_fee'];
            }
        }

        //咨询费
        if($deal_ext['consult_fee_rate_type'] == 1) {
            $result['consult_fee'] = 0.00;
        } else if($deal_ext['consult_fee_rate_type'] == 2 || $deal_ext['consult_fee_rate_type'] == 3) {
            $consult_fee = prepay_money_intrest($deal['borrow_amount'], $use_days, $deal['consult_fee_rate']);
            $result['consult_fee'] = $deal_model->floorfix($consult_fee);
            foreach ($deal_repay_list as $k => $v) {
                $result['consult_fee'] -= $v['consult_fee'];
            }
        }

        //担保费
        if($deal_ext['guarantee_fee_rate_type'] == 1) {
            $result['guarantee_fee'] = 0.00;
        } else if($deal_ext['guarantee_fee_rate_type'] == 2 || $deal_ext['guarantee_fee_rate_type'] == 3){
            $guarantee_fee = prepay_money_intrest($deal['borrow_amount'], $use_days, $deal['guarantee_fee_rate']);
            $result['guarantee_fee'] = $deal_model->floorfix($guarantee_fee);
            foreach ($deal_repay_list as $k => $v) {
                $result['guarantee_fee'] -= $v['guarantee_fee'];
            }
        }

        //支付服务费
        if($deal_ext['pay_fee_rate_type'] == 1) {
            $result['pay_fee'] = 0.00;
        } else if($deal_ext['pay_fee_rate_type'] == 2 || $deal_ext['pay_fee_rate_type'] == 3) {
            $pay_fee = prepay_money_intrest($deal['borrow_amount'], $use_days, $deal['pay_fee_rate']);
            $result['pay_fee'] = $deal_model->floorfix($pay_fee);
            foreach ($deal_repay_list as $k => $v) {
                $result['pay_fee'] -= $v['pay_fee'];
            }
        }

        //管理服务费
        if(($deal['isDtb'] == 1) && ($deal_ext['management_fee_rate_type'] == 1)) {
            $result['management_fee'] = 0.00;
        } else if($deal_ext['management_fee_rate_type'] == 2 || $deal_ext['management_fee_rate_type'] == 3) {
            $management_fee = prepay_money_intrest($deal['borrow_amount'], $use_days, $deal['management_fee_rate']);
            $result['management_fee'] = $deal_model->floorfix($management_fee);
            foreach ($deal_repay_list as $k => $v) {
                $result['management_fee'] -= $v['management_fee'];
            }
        }

        //提前还款违约金，取消计算，线下处理。
        $prepay_penalty = bcmul($deal['borrow_amount'], $deal['prepay_rate'] / 100, 2);
        $result['prepay_penalty'] = $deal_model->floorfix($prepay_penalty);
        //本金
        $result['principal'] = $deal_model->floorfix($remain_principal);

        //总额，总额怎么计算待定
//        $result['prepay_money'] = $deal_model->floorfix(
//            $result['principal'] + $result['prepay_interest'] + $result['loan_fee'] + $result['consult_fee'] + $result['guarantee_fee'] + $result['pay_fee']
//        );
        return $result;
    }

    /**
     * 根据还款id执行提前还款操作
     * @param $deal_repay object
     */
    public function prepayDealLoan($deal_repay) {
        // 本金和利息置为取消状态
        $res = \core\dao\DealLoanRepayModel::instance()->cancelDealLoanRepay($deal_repay->deal_id);
        if ($res === false) {
            throw new \Exception("deal loan repay empty error");
        }

        // 私有方法计算提前还款的利息和补偿金、管理费
        $deal = new Deal();
        $deal = $deal->find($deal_repay->deal_id);
        $rate = $deal['income_fee_rate'];

        $user = new User();
        $data = array();

        $deal_loan_model = new DealLoad();
        $deal_loan_list  = $deal_loan_model->getDealLoanList($deal_repay->deal_id);
        foreach ($deal_loan_list as $deal_loan) {
            $principal           = $deal_loan['money'] * $deal_repay->remain_principal / $deal['borrow_amount'];
            $prepay_money        = prepay_money($principal, $deal_repay->remain_days, $deal['loan_compensation_days'], $rate);
            $prepay_intrest      = prepay_money_intrest($principal, $deal_repay->remain_days, $rate);
            $prepay_compensation = $prepay_money - $principal - $prepay_intrest;
            $prepay_manage       = ($deal_repay->prepay_money - $prepay_money) * $deal_loan['money'] / $deal['borrow_amount'];

            // 插入本金、利息、补偿金、管理费
            $data[] = $this->savePrepayDealLoan($deal, $deal_repay, $deal_loan, $principal, $prepay_intrest, $prepay_compensation, $prepay_manage);
        }
        return true;
    }

    private function sendPrepayMsg($deal, $user, $arr_money) {
        // 发送站内信
        $this->sendPrepayMessage($deal, $user, $arr_money);
        // 发送短信
        //$this->sendPrepaySms($deal, $user, $arr_money);
    }

    /**
     * 发送提前还款回款站内信
     * @param $deal
     * @param $user
     * @param $arr_money
     */
    private function sendPrepayMessage($deal, $user, $arr_money) {
        $content = "您在" . app_conf("SHOP_TITLE") . "的投标“{$deal['name']}”发生提前还款，总额{$arr_money['prepay_money']}元，其中提前还款本金{$arr_money['principal']}元，提前还款利息{$arr_money['prepay_intrest']}元，提前还款补偿金{$arr_money['prepay_compensation']}元。本次投标已回款完毕。";
        send_user_msg("", $content, 0, $user->id, get_gmtime(), 0, 1, 9);
    }

    /**
     * 发送提前还款回款短信
     * @param $deal array
     * @param $user object
     * @param $arr_money array
     */
    private function sendPrepaySms($deal, $user, $arr_money) {
        $data      = array(
            'name'               => $deal['name'],
            'money'              => $arr_money['prepay_money'],
            'principal'          => $arr_money['principal'],
            'interest'           => $arr_money['prepay_interest'],
            'compensation_money' => $arr_money['prepay_compensation'],
        );
        \libs\sms\SmsServer::instance()->send($user->mobile, 'TPL_SMS_PREPAY', $data, $user->id);
    }

    /**
     * 向数据库插入本金、利息、补偿金
     * @param $deal array
     * @param $deal_repay object
     * @param $deal_loan array
     * @param $prepay_principal float
     * @param $prepay_intrest float
     * @param $prepay_compensation float
     * @param $prepay_manage float
     */
    public function savePrepayDealLoan($deal, $deal_repay, $deal_loan, $prepay_principal, $prepay_intrest, $prepay_compensation, $prepay_manage) {
        $returndata = array();
        $this->deal_id        = $deal['id'];
        $this->deal_repay_id  = $deal_repay->id;
        $this->deal_loan_id   = $deal_loan['id'];
        $this->loan_user_id   = $deal_loan['user_id'];
        $this->borrow_user_id = $deal['user_id'];
        $this->time           = get_gmtime();
        $this->real_time      = get_gmtime();
        $this->status         = self::STATUS_ISPAYED;
        $this->create_time    = get_gmtime();
        $this->update_time    = get_gmtime();

        $deal_dao = new Deal();
        // 本金
        $this->type  = self::MONEY_PREPAY;
        $this->money = $deal_dao->floorfix($prepay_principal);
        if (!$this->insert()) {
            throw new \Exception("deal_loan_repay insert");
        }

        $returndata[] = $this->getRow();
        // 利息
        $this->type  = self::MONEY_PREPAY_INTREST;
        $this->money = $deal_dao->floorfix($prepay_intrest);
        if (!$this->insert()) {
            throw new \Exception("deal_loan_repay insert");
        }
        $returndata[] = $this->getRow();
        // 补偿金
        $this->type  = self::MONEY_COMPENSATION;
        $this->money = $deal_dao->floorfix($prepay_compensation);
        if (!$this->insert()) {
            throw new \Exception("deal_loan_repay insert");
        }
        $returndata[] = $this->getRow();
        // 管理费
        $this->type  = self::MONEY_MANAGE;
        $this->money = $deal_dao->floorfix($prepay_manage);
        if (!$this->insert()) {
            throw new \Exception("deal_loan_repay insert");
        }
        $returndata[] = $this->getRow();
        return $returndata;
    }

    /**
     * 向出借人发送站内信和邮件
     * @param $deal array
     * @param $deal_loan array
     * @param $user object
     * @param $arr_change_money array
     * @param $next_repay_id int|bool false-若为最后一期
     */
    private function sendMsg($deal, $deal_loan, $user, $arr_change_money, $next_repay_id) {
        if ($next_repay_id) {
            $is_last         = 0;
            $arr_money_extra = $this->getChangeMoneyByRepayId($next_repay_id, $deal_loan['user_id'], $deal_loan->id);
        } else {
            $is_last         = 1;
            $arr_money_extra = array(
                "all_repay_money"  => number_format($this->getTotalRepayMoney($deal_loan['id']), 2),
                "all_impose_money" => number_format($this->getTotalMoneyByTypeLoanId($deal_loan['id'], self::MONEY_IMPOSE), 2),
                "all_income_money" => number_format($this->getTotalMoneyByTypeLoanId($deal_loan['id'], self::MONEY_INTREST), 2),
            );
        }

        // 向出借人发送回款站内信
        $this->sendMessage($deal, $deal_loan, $arr_change_money, $is_last, $arr_money_extra);

        // 向出借人发送回款邮件
        $this->sendEmail($user, $deal, $arr_change_money, $is_last, $arr_money_extra);
    }

    /**
     * 向出借人发回款邮件
     * @param $user object
     * @param $deal array
     * @param $arr_money array
     * @param $is_last int
     * @param $arr_money_extra array
     */
    private function sendEmail($user, $deal, $arr_money, $is_last, $arr_money_extra) {
        if (isset($user['user_type']) && (int)$user['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
            $userName =$user->user_name;
        }else{
            $userName =get_deal_username($user->id);
        }
        $notice = array(
            "user_name"   => $userName,
            "deal_name"   => $deal['name'],
            "deal_url"    => $deal['share_url'],
            "site_name"   => app_conf("SHOP_TITLE"),
            "help_url"    => get_domain() . url("index", "helpcenter"),
            "repay_money" => $arr_money['total'],
        );

        $msgcenter = new \Msgcenter();
        if ($is_last) {
            $notice['all_repay_money']  = $arr_money_extra['all_repay_money'];
            $notice['impose_money']     = $arr_money_extra['all_impose_money'] > 0 ? "其中违约金为:{$arr_money_extra['all_impose_money']}元," : "";
            $notice['all_income_money'] = $arr_money_extra['all_income_money'];

            $msgcenter->setMsg($user->email, $user->id, $notice, 'TPL_DEAL_LOAD_REPAY_EMAIL_LAST', "“{$deal['name']}”回款通知");
        } else {
            $notice['next_repay_time']  = to_date($arr_money_extra['time'], "Y年m月d日");
            $notice['next_repay_money'] = number_format($arr_money_extra['total'], 2);

            $msgcenter->setMsg($user->email, $user->id, $notice, 'TPL_DEAL_LOAD_REPAY_EMAIL', "“{$deal['name']}”回款通知");
        }
        $msgcenter->save();
    }

    /**
     * 向出借人发送回款站内信
     * @param $deal array 订单信息
     * @param $deal_loan array 投资信息
     * @param $arr_money array 回款金额信息
     * @param $next_repay_id int 下次还款id
     */
    private function sendMessage($deal, $deal_loan, $arr_money, $is_last, $arr_money_extra) {
        $repay_money        = number_format($arr_money[self::MONEY_PRINCIPAL] + $arr_money[self::MONEY_INTREST] + $arr_money[self::MONEY_MANAGE], 2);
        $arr_money['total'] = number_format($arr_money['total'], 2);
        $content            = "您好，您在" . app_conf("SHOP_TITLE") . "的投资 “<a href=\"{$deal['share_url']}\">{$deal['name']}</a>”成功回款{$repay_money}元，扣除管理费{$arr_money[self::MONEY_MANAGE]}元，实得{$arr_money['total']}元。";
        if (!$is_last) {
            $content .= "本笔投资的下个回款日为" . to_date($arr_money_extra['time'], "Y年m月d日") . "，需还本息" . number_format($arr_money_extra[self::MONEY_PRINCIPAL] + $arr_money_extra[self::MONEY_INTREST] + $arr_money_extra[self::MONEY_MANAGE], 2) . "元，管理费需缴{$arr_money_extra[self::MONEY_MANAGE]}元";
        } else {
            $content .= "本次投资共回款{$arr_money_extra['all_repay_money']}元，收益:{$arr_money_extra['all_income_money']}元, 其中您获得融资方的违约金为:{$arr_money_extra['all_impose_money']}元,本次投资已回款完毕！";
        }

        send_user_msg("", $content, 0, $deal_loan['user_id'], get_gmtime(), 0, true, 9);
    }

    /**
     * 根据订单id和user_id获取投资回款列表
     * @param $deal_id int
     * @param $user_id int
     * @return array
     */
    public function getDealLoanList($deal_id, $user_id) {
        return $this->findAll("`deal_id`='{$deal_id}' AND `loan_user_id`='{$user_id}' ORDER BY `time`");
    }

    /**
     * 根据日期获取单笔已投资金额
     * @param $deal_loan_id int
     * @param $time int
     * @return array
     */
    public function getDealLoanRepayByTime($deal_loan_id, $time) {
        $total_money  = 0;
        $impose_money = 0;
        $result       = $this->findAll("`deal_loan_id`='{$deal_loan_id}' AND `time`='{$time}' AND `status`='" . self::STATUS_ISPAYED . "'");
        foreach ($result as $val) {
            if ($val['type'] == self::MONEY_MANAGE) {
                continue;
            } else {
                $total_money += $val['money'];
                if ($val['type'] == self::MONEY_IMPOSE) {
                    $impose_money = $val['money'];
                }
            }
        }
        $arr_repay = array(
            "repay_money"  => $total_money,
            "impose_money" => $impose_money,
        );
        return $arr_repay;
    }

    /**
     * 根据还款id和用户id获取各项还款金额
     * @param $deal_repay_id int
     * @param $user_id int
     * @return array
     */
    private function getChangeMoneyByRepayId($deal_repay_id, $user_id, $deal_loan_id) {
        $loan_repay_list = $this->findAll("`deal_repay_id`= {$deal_repay_id} AND `deal_loan_id` = {$deal_loan_id} AND `loan_user_id`={$user_id}");
        $money_total     = 0;
        $arr_money       = array();
        foreach ($loan_repay_list as $val) {
            $arr_money[$val['type']] = $val['money'];
            $arr_money['time']       = $val['time'];
            if ($val['type'] != self::MONEY_MANAGE) {
                $money_total += $val['money'];
            }
        }
        $arr_money['total'] = $money_total;
        return $arr_money;
    }

    /**
     * 根据订单id和用户id获取各项还款金额
     */
    public function getChangeMoneyByDealId($deal_id, $user_id = false) {
        $condition = "`deal_id` = '{$deal_id}'";
        if ($user_id) {
            $condition .= " AND `loan_user_id`='{$user_id}'";
        }
        $loan_repay_list = $this->findAll($condition);
        $money_total     = 0;
        $arr_money       = array();
        foreach ($loan_repay_list as $val) {
            $arr_money[$val['type']] = $val['money'];
            if ($val['type'] != self::MONEY_MANAGE) {
                $money_total += $val['money'];
            }
        }
        $arr_money['total'] = $money_total;
        return $arr_money;
    }

    /**
     * 根据投资id获取回款列表
     * @param $deal_loan_id int
     * @param $p int
     * @param $page_size int
     * @return array("count"=>$count, "list"=>$list)
     */
    public function getLoanRepayListByLoanId($deal_loan_id) {
        $condition = "`deal_loan_id`='{$deal_loan_id}'";
        $count = $this->count($condition);
        //$start = ($p-1) * $page_size ;
        //$list = $this->findAll($condition . "ORDER BY `id` LIMIT {$start}, {$page_size}");
        $list = $this->findAll($condition . "ORDER BY `time`");
        return array("count"=>$count, "list"=>$list);
    }

    /**
     * 获取单笔投资回款总额
     * @param $deal_loan_id int 投资id
     * @return float 回款总额
     */
    public function getTotalRepayMoney($deal_loan_id) {
        $total_money = 0;
        $result      = $this->findAllBySql("SELECT SUM(`money`) AS `m`,`type` FROM " . $this->tableName() . " WHERE `deal_loan_id`='{$deal_loan_id}' AND `status`='" . self::STATUS_ISPAYED . "' GROUP BY `type`");
        foreach ($result as $val) {
            $total_money += $val['m'];
        }
        return $total_money;
    }

    /**
     * 根据user_id获取按月还款总额
     * @param $user_id int
     */
    public function getTotalLoanMoney($user_id) {
        $loan_repay_money = 0;
        $loan_earning     = 0;
        $arr_money        = $this->findAllBySql("SELECT SUM(`money`) AS `money`,`type` FROM " . $this->tableName() . " WHERE `loan_user_id`='{$user_id}' AND `status`='" . self::STATUS_ISPAYED . "' GROUP BY `type`");
        foreach ($arr_money as $val) {
            switch ($val['type']) {
                case self::MONEY_PRINCIPAL :
                    $loan_repay_money += $val['money'];
                    break;
                case self::MONEY_INTREST :
                    $loan_repay_money += $val['money'];
                    $loan_earning += $val['money'];
                    break;
                case self::MONEY_IMPOSE :
                    $loan_earning += $val['money'];
                    break;
                case self::MONEY_MANAGE :
                    break;
                default :
                    break;
            }
        }
        return array("loan_repay_money" => $loan_repay_money, "loan_earning" => $loan_earning);
    }

    /**
     * 获取单个用户赚得的罚息
     * @param $user_id int
     */
    public function getTotalImposeRepay($user_id) {
        return $this->getTotalMoneyByTypeUserId($user_id, self::MONEY_IMPOSE);
    }

    /**
     * 获取单个用户赚得的补偿金
     * @param $user_id int
     */
    public function getTotalCompenstion($user_id) {
        return $this->getTotalMoneyByTypeUserId($user_id, self::MONEY_COMPENSATION);
    }

    /**
     * 根据用户id获取一个类型的总金数
     * @param $user_id
     * @param $type
     */
    private function getTotalMoneyByTypeUserId($user_id, $type) {
        return $this->db->getOne("SELECT SUM(`money`) FROM " . $this->tableName() . " WHERE `loan_user_id`='{$user_id}' AND `type`='{$type}' AND `status`='" . self::STATUS_ISPAYED . "'");
    }

    /**
     * 获取单笔投资收益总额，既违约金+利息+提前还款利息+提前还款补偿金
     * @param $deal_loan_id int 投资id
     * @return float 受益总额
     */
    public function getTotalIncomeMoney($deal_loan_id) {
        return $this->getTotalImposeMoney($deal_loan_id)
        + $this->getTotalMoneyByTypeLoanId($deal_loan_id, self::MONEY_INTREST)
        + $this->getTotalMoneyByTypeLoanId($deal_loan_id, self::MONEY_PREPAY_INTREST)
        + $this->getTotalMoneyByTypeLoanId($deal_loan_id, self::MONEY_COMPENSATION)
        + $this->getTotalMoneyByTypeLoanId($deal_loan_id, self::MONEY_COMPOUND_INTEREST);
    }

    /**
     * 获取单笔投资违约金总额
     * @param $deal_loan_id int 投资id
     * @return float 违约金总额
     */
    public function getTotalImposeMoney($deal_loan_id) {
        return $this->getTotalMoneyByTypeLoanId($deal_loan_id, self::MONEY_IMPOSE);
    }

    /**
     * 获取单笔订单的已经投资的总额
     * @param $deal_id int
     * @return float 已经投资总额
     */
    public function getTotalPrincipalMoney($deal_id) {
        return $this->getTotalMoneyByTypeDealId(self::MONEY_PRINCIPAL, $deal_id);
    }

    /**
     * 根据订单id和用户id获取已经投资的总额
     * @param $deal_id int
     * @param $user_id int
     * @return float
     */
    public function getTotalPrincipalMoneyByUserid($deal_id, $user_id) {
        return $this->db->getOne("SELECT SUM(`money`) FROM " . $this->tableName() . " WHERE `deal_id`='{$deal_id}' AND `loan_user_id`='{$user_id}' AND `type`='" . self::MONEY_PRINCIPAL . "' AND `status`='" . self::STATUS_ISPAYED . "'");
    }

    /**
     * 根据订单id和金额类别获取总金额
     * @param $type int 金额类别
     * @param $deal_id int|bool 订单id
     * @return float 总金额
     */
    private function getTotalMoneyByTypeDealId($type, $deal_id = false) {
        $sql = "SELECT SUM(`money`) FROM " . $this->tableName() . " WHERE `type`='{$type}' AND `status`='" . self::STATUS_ISPAYED . "'";
        if ($deal_id) {
            $sql .= " AND `deal_id`='{$deal_id}'";
        }
        return $this->db->getOne($sql);
    }

    /**
     * 根据投资id和金额类别获取总金额
     * @param $deal_loan_id int 投资id
     * @param $type int 金额类别
     * @param $is_payed int 是否已还
     * @return float 总金额
     */
    public function getTotalMoneyByTypeLoanId($deal_loan_id, $type, $is_payed = 1) {
        $sql = "SELECT SUM(`money`) FROM " . $this->tableName() . " WHERE `deal_loan_id`='{$deal_loan_id}' AND `type`='{$type}'";
        if ($is_payed == 1) {
            $sql .= " AND `status`='" . self::STATUS_ISPAYED . "'";
        }
        return $this->db->get_slave()->getOne($sql);
    }

    /**
     * 获取所有deal_loan_repay表中即将带来的总收益
     * @return float 总金额
     */
    public function getRepayEarnMoney(){
        $sql = "SELECT SUM(money) FROM ".$this->tableName()." WHERE `status` = 0 AND `type` = 2";
        return $this->db->get_slave()->getOne($sql);
    }

    /**
     * 根据投资id和金额类别获取总金额
     * @param $deal_loan_ids array 投资id
     * @param $types array 金额类别
     * @param $is_payed int 是否已还
     * @return array 总金额
     * @author longbo
     */
    public function getTotalMoneyByTypesAndLoanIds($deal_loan_ids = array(), $types = array(), $is_payed = 1) {
        if (!empty($deal_loan_ids) && is_array($deal_loan_ids)) {
            $ids_str = implode(',', $deal_loan_ids);
        } else {
            return array();
        }

        if (!empty($types) && is_array($types)) {
            $type_str = implode(',', $types);
            $type_where = ' AND `type` IN (' . $type_str . ')';
        }
        $sql = "SELECT deal_loan_id, SUM(`money`) as m FROM " . $this->tableName() . " WHERE `deal_loan_id` IN (" . $ids_str . ")" . $type_where;
        if ($is_payed == 1) {
            $sql .= " AND `status`='" . self::STATUS_ISPAYED . "'";
        }

        $sql .= " GROUP BY deal_loan_id ";
        return $this->db->get_slave()->getAll($sql);
    }

    /**
     * 根据投资id和总利息金额
     * @param $deal_loan_ids array 投资id数组
     * @return array 总金额
     * @author longbo
     */
    public function getTotalMoneyTypeForUser($deal_loan_ids) {
        $res = $this->getTotalMoneyByTypesAndLoanIds($deal_loan_ids,
                                                    array(
                                                        self::MONEY_IMPOSE,
                                                        self::MONEY_INTREST,
                                                        self::MONEY_PREPAY_INTREST,
                                                        self::MONEY_COMPENSATION,
                                                        self::MONEY_COMPOUND_INTEREST,
                                                        )
                                                    );
        $data = array();
        if (!empty($res)) {
            foreach ($res as $value) {
                $data[$value['deal_loan_id']] = $value['m'];
            }
        }
        return $data;
    }

} // END class DealLoanRepay extends BaseModel
