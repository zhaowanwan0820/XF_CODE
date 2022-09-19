<?php


namespace core\dao\creditloan;

use core\enum\CreditLoanEnum;
use libs\utils\Logger;
use core\dao\BaseModel;


class CreditLoanModel extends BaseModel {

    /**
     * 获取有效的申请记录
     * @param $user_id
     * @param $deal_id
     */
    public function getCreditLoan($user_id,$deal_id) {
        $param = array(
            ':user_id' => $user_id,
            ':deal_id' => $deal_id,
            ':status' => CreditLoanEnum::STATUS_FAIL,
        );
        return $this->findBy("`user_id` = ':user_id' AND `deal_id` = ':deal_id' AND `status` != ':status'", '*', $param);
    }

    public function isCreditingDeal($deal_id) {
        $param = array(
            ':deal_id' => $deal_id,
            ':status' => CreditLoanEnum::STATUS_FAIL . "," . CreditLoanEnum::STATUS_FINISH,
        );
        return $this->count("`deal_id` = ':deal_id' AND `status`  NOT in (':status')",$param);
    }

    /**
     * 取得未完成还款的贷款标的
     * @param $deal_id
     * @return \libs\db\Model
     */
    public function getCreditNotRepayByDealId($deal_id) {
        $param = array(
            ':deal_id' => $deal_id,
        );
        $allowStatus = implode(",",array(CreditLoanEnum::STATUS_APPLY,CreditLoanEnum::STATUS_USING));
        return $this->findAll("`deal_id` = ':deal_id' AND `status`  IN (".$allowStatus.")", false, '*', $param);
    }

    /**
     * 申请记录更新
     * @param $uid
     * @param $dealId
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function updateCreditLoanByUidDealId($uid,$dealId,$data) {
        $param = array(
            ':user_id' => $uid,
            ':deal_id' => $dealId,
            ':status' => CreditLoanEnum::STATUS_FAIL,
        );

        $creditLoan = $this->findBy("`user_id` = ':user_id' AND  `deal_id` = ':deal_id' AND `status` != ':status'", '*', $param);
        if(!$creditLoan) {
            throw new \Exception("credit loan record does not exist");
        }

        $creditLoan->deal_loan_money = isset($data['deal_loan_money']) ? $data['deal_loan_money'] : $creditLoan->deal_loan_money;
        $creditLoan->money = isset($data['money']) ? $data['money'] : $creditLoan->money;
        $creditLoan->period_apply = isset($data['period_apply']) ? $data['period_apply'] : $creditLoan->period_apply;
        $creditLoan->rate = isset($data['rate']) ? $data['rate'] : $creditLoan->rate;
        $creditLoan->loan_time = isset($data['loan_time']) ? $data['loan_time'] : $creditLoan->loan_time;
        $creditLoan->plan_time = isset($data['plan_time']) ? $data['plan_time'] : $creditLoan->plan_time;
        $creditLoan->repay_time = isset($data['repay_time']) ? $data['repay_time'] : $creditLoan->repay_time;
        $creditLoan->finish_time = isset($data['finish_time']) ? $data['finish_time'] : $creditLoan->finish_time;
        $creditLoan->period_repay = isset($data['period_repay']) ? $data['period_repay'] : $creditLoan->period_repay;
        $creditLoan->interest = isset($data['interest']) ? $data['interest'] : $creditLoan->interest;
        $creditLoan->service_fee = isset($data['service_fee']) ? $data['service_fee'] : $creditLoan->service_fee;
        $creditLoan->status = isset($data['status']) ? $data['status'] : $creditLoan->status;
        $creditLoan->update_time = time();
        return $creditLoan->save();
    }




    public function isShowCreditEntrance($userId)
    {
        $userId = intval($userId);
        if (empty($userId)) {
            return array();
        }
        $count = $this->db->getOne("SELECT COUNT(*) FROM firstp2p_credit_loan WHERE user_id = '{$userId}' AND `status` NOT IN (".implode(',',[CreditLoanEnum::STATUS_FAIL, CreditLoanEnum::STATUS_FINISH]).")");
        return  $count > 0 ? true : false;

    }



    /**
     * 通过deal_id 获取申请列表
     * @param $deal_id
     * @return \libs\db\Model
     */
    public function getCreditLoansByDealId($deal_id) {
        $param = array(
            ':deal_id' => $deal_id,
            ':status' => CreditLoanEnum::STATUS_FAIL,
        );
        return $this->findAll("`deal_id` = ':deal_id' AND `status` != ':status'", false, '*', $param);
    }

    /**
     * 通过标的id获取未完成的申请列表
     * @param $deal_id
     * @return \libs\db\Model
     */
    public function getCreditingLoansByDealId($deal_id) {
        $param = array(
            ':deal_id' => $deal_id,
        );
        return $this->findAll("`deal_id` = ':deal_id' AND `status`  NOT IN (".implode(",",array(CreditLoanEnum::STATUS_FAIL,CreditLoanEnum::STATUS_FINISH)).")", false, '*', $param);
    }



    /**
     * 获取用户满足条件的投资标的id以及投资标的汇总金额
     * @param $user_id
     * @return array
     */
    public function getCreditDealsMoneyByUserId($user_id) {
        $now = get_gmtime();
        // 最长可借款期限36个月
        $begin_time = $now - 1096*86400;
        // 最短可借款标的期限
        $holdLt3 = intval(app_conf('CREDIT_LOAN_HOLD_TERM_LT_3'));
        $holdGt3 = intval(app_conf('CREDIT_LOAN_HOLD_TERM_GT_3'));
        $end_time = $now - min($holdLt3,$holdGt3) * 86400;

        // 单笔最小投资额
        $minBorrowMoney = floatval(app_conf('CREDIT_LOAN_MIN_BORROW_AMOUNT'));
        $proportionLoanRate = floatval(app_conf('CREDIT_LOAN_PROPORTION_LOAN_RATE'));
        if (bccomp($proportionLoanRate, '0.00', 2) == 0) {
            $proportionLoanRate = 0.9;
        }
        $minInvestMoney = bcdiv($minBorrowMoney, $proportionLoanRate, 2);

        // 可重复借款状态
        $canApplyStatus = array(
            CreditLoanEnum::STATUS_FAIL,
        );
        $creditLoanStatusCondition = ' AND status NOT IN ('.implode(',', $canApplyStatus).') ';

        // 统计用户所有再投标的数据 同时过滤银信通配置中的标的类型
        $dealTypeFilter = app_conf('CREDIT_LOAN_DEAL_TYPE') !== '' ? app_conf('CREDIT_LOAN_DEAL_TYPE') : '';
        if ($dealTypeFilter !== '') {
            $dealTypeFilter = str_replace(CreditLoanConfigService::$configDelimiter, ',', $dealTypeFilter);
            $dealTypeFilter = " AND deal_type IN ($dealTypeFilter) ";
        }
        $sql = "SELECT * FROM (SELECT deal_id,SUM(money) AS total FROM firstp2p_deal_load WHERE user_id = '{$user_id}'
             AND deal_id NOT IN ( SELECT deal_id FROM firstp2p_credit_loan WHERE user_id = '{$user_id}' {$creditLoanStatusCondition} )
             AND create_time >= '{$begin_time}' AND create_time <= '{$end_time}' {$dealTypeFilter} GROUP BY deal_id ) dl WHERE dl.total >= '{$minInvestMoney}'";
        return $this->findAllBySql($sql, true, array(),true);
    }

    /**
     * 根据标id筛选符合条件的标信息
     * @param $deal_ids
     * @return array
     */
    public function getCreditDealsMoneyByDealIds($deal_ids) {
        $excludeTypes = array();
        $excludeTypes[] = intval(DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT));
        $excludeTypes[] = intval(DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_XFFQ));

        //$now = to_timespan(date("Y-m-d H:i:s"));
        //$sql = 'SELECT * FROM ( SELECT id,`name`, floor((next_repay_time-repay_start_time)/86400)  AS repay_periods,floor((' .$now. '-repay_start_time)/86400) AS pass_periods FROM firstp2p_deal WHERE id in (' . $deal_ids . ') AND  deal_status = 4 AND loantype IN(3,4,5,6) AND type_id NOT IN(' .implode(',',$excludeTypes). ')) fd  WHERE repay_periods >= 60  AND repay_periods <= 1080 AND repay_periods-pass_periods >= 30 AND pass_periods >= IF (repay_periods > 90, 60,30)';

        // 取得标的还款方式
        $loanTypeCondition = '';
        $loanType = str_replace(CreditLoanConfigService::$configDelimiter, ',', app_conf('CREDIT_LOAN_DEAL_REPAY_TYPE'));
        if ($loanType !== '') {
            $loanTypeCondition = " AND `loantype` IN ({$loanType}) ";
        }
        $sql = 'SELECT * FROM ' . DealModel::instance()->tableName() . " WHERE `id` IN ({$deal_ids}) AND `deal_status` = 4 {$loanTypeCondition} AND `type_id` NOT IN (" .implode(',',$excludeTypes). ")";

        return $this->findAllBySql($sql, true, array(),true);
    }

    /**
     * 根据用户id，获取申请记录
     * @author <fanjingwen>
     * @param int $user_id
     * @return mixed || array
     */
    public function getCreditLoanListByUserId($user_id, $offset=0, $count=20)
    {
        $limit = sprintf(" %d,%d ",$offset,$count);
        $param = array(
            ':user_id'          => intval($user_id),
            ':limit'            => $limit,
            ':apply'            => CreditLoanEnum::STATUS_APPLY,
            ':using'            => CreditLoanEnum::STATUS_USING,
            ':payment'          => CreditLoanEnum::STATUS_PAYMENT,
            ':repay'            => CreditLoanEnum::STATUS_REPAY,
            ':repay_handle'     => CreditLoanEnum::STATUS_REPAY_HANDLE,
            ':finish'           => CreditLoanEnum::STATUS_FINISH,
            ':fail'             => CreditLoanEnum::STATUS_FAIL,
        );
        $order_by = " order by field(`status`, :apply, :using, :payment, :repay, :repay_handle, :finish, :fail), `id` DESC"; // 根据状态，id排序
        return $this->findAll("`user_id` = :user_id  {$order_by} LIMIT :limit ", true, '*', $param);
    }

    /**
     * 根据id，获取CreditLoan信息
     * @param int $id
     * @return object
     */
    public function getCreditLoanById($id)
    {
        return $this->find(intval($id));
    }

    /**
     * 根据用户id，获取申请记录条数
     * @param int $user_id
     * @return int $count
     */
    public function getCreditLoanCountByUserId($user_id)
    {
        $param = array(
            ":user_id" => intval($user_id),
        );

        return $this->count("`user_id` = :user_id", $param);
    }

    /**
     * 根据用户id和状态 获取CreditLoan信息
     * @param $user_id
     * @param $status
     * @return \libs\db\Model
     */
    public function getCreditLoanByUserIdAndStatus($user_id,$status) {
        $param = array(
            ":user_id" => intval($user_id),
            ":status" => $status,
        );
        return $this->findAllViaSlave("`user_id` = :user_id  AND `status` = :status ", true, '*',$param);
    }

    public function caculateUserCreditLoanSummary($userId) {
        $condition = ' `status` IN ('.CreditLoanEnum::STATUS_APPLY.','.CreditLoanEnum::STATUS_USING.") AND user_id = '{$userId}' ";
        $rows = $this->findAllViaSlave($condition, true, 'deal_loan_money', array());
        $totalBorrowAmount = 0;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $totalBorrowAmount = bcadd($totalBorrowAmount, $row['deal_loan_money'], 2);
            }
        }
        return $totalBorrowAmount;
    }

    /**
     * 查询用户是否有【银信通借款】记录
     * @param int $userId
     * @return \libs\db\model
     */
    public function hasExistByUserId($userId) {
        $data = $this->findByViaSlave("user_id=':user_id' LIMIT 1", 'id', array(':user_id'=>(int)$userId));
        return !empty($data['id']) ? true : false;
    }

    public function getCreditLoanInfo($dealId,$userId){
        $param = array(
            ':deal_id' => $dealId,
            ':user_id' => $userId,
        );
        $allowStatus = implode(",",array(CreditLoanEnum::STATUS_APPLY,CreditLoanEnum::STATUS_USING));
        return $this->findBy("`deal_id` = ':deal_id'  AND `user_id` = ':user_id' AND `status`  IN (".$allowStatus.")", '*', $param);
    }
}
