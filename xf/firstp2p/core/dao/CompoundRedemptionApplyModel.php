<?php
/**
 * 利滚利标的赎回申请
 * CompoundRedemptionApplyModel class file.
 **/
namespace core\dao;

class CompoundRedemptionApplyModel extends BaseModel
{

    /**
     * 未到账
     */
    const STATUS_APPLY = 0;

    /**
     * 已到账
     */
    const STATUS_DONE = 1;

    /**
     * 根据deal_id获取用户的通知贷赎回申请
     * @param intval $deal_id
     * @return array
     */
    public function getApplyByDeal($deal_id) {
        $condition = sprintf("`deal_id`='%d'", intval($deal_id));
        $list = $this->findAllViaSlave($condition);
        if (!$list) {
            return array();
        }
        $result = array();
        foreach ($list as $item) {
            $result[$item['deal_load_id']] = $item;
        }
        return $result;
    }

    /**
     * 获取不同赎回状态下的总金额
     * @param unknown $deal_id
     * @param unknown $status
     * @return number
     */
    public function getSumMoneyByDeal($deal_id, $status){
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id` = ':deal_id' AND `status` = ':status'";
        $param = array(':deal_id' => $deal_id, ':status' => $status);
        $sql = sprintf($sql, $this->tableName(), $this->tableName());
        $result = $this->findBySql($sql, $param, true);
        return $result['sum'] > 0 ? $result['sum'] : 0;
    }

    /**
     * 根据投资id获取赎回申请
     * @param int $deal_loan_id
     */
    public function getApplyByDealLoanId($deal_loan_id) {
        $condition = sprintf("`deal_load_id`='%d'", $this->escape($deal_loan_id));
        return $this->findBy($condition);
    }

    /**
     * 根据deal_id获取赎回申请个数
     * @param int $deal_id
     * @param int|false $status
     * @return int
     */
    public function getApplyCountByDealId($deal_id, $status = false) {
        $condition = sprintf("`deal_id`='%d'", $this->escape($deal_id));
        if ($status !== false) {
            $condition .= sprintf(" AND `status`='%d'", intval($status));
        }
        return $this->count($condition);
    }

    /**
     * 保存赎回申请
     *
     * @param $deal_load
     * @param $repay_time
     * @return bool
     */
    public function saveApply($deal_load, $repay_time) {
        $this->user_id = $deal_load['user_id'];
        $this->money = $deal_load['money'];
        $this->deal_id = $deal_load['deal_id'];
        $this->deal_load_id = $deal_load['id'];
        $this->repay_time = $repay_time;
        $this->status = self::STATUS_APPLY;
        $this->create_time = get_gmtime();
        $this->update_time = get_gmtime();
        return $this->insert();
    }

    /**
     * 根据标的ID获取赎回还款预览
     *
     * @param $deal_id
     * @param $repay_day_start
     * @param $repay_day_end
     * @return array
     */
    public function getRepayScheduleByDealId($deal_id, $repay_day_start, $repay_day_end) {
        $sql = "select from_unixtime(time+28800,'%Y-%m-%d') repay_day, sum(money) money from firstp2p_deal_loan_repay ";
        $sql .= "where deal_id=':deal_id' and time>=':repay_day_start' and time<':repay_day_end' and type in ('8','9') and status='0' ";
        $sql .= "group by repay_day";
        $param = array(':deal_id' => $deal_id, ':repay_day_start' => $repay_day_start, ':repay_day_end' => $repay_day_end);
        $list = $this->findAllBySql($sql, true, $param, true);
        $result = array();
        foreach ($list as $item) {
            $result[$item['repay_day']] = $item['money'];
        }
        return $result;
    }

    /**
     * 根据项目ID获取赎回还款预览
     *
     * @param $project_id
     * @param $repay_day_start
     * @param $repay_day_end
     * @return \libs\db\Model
     */
    public function getRepayScheduleByProjectId($project_id, $repay_day_start, $repay_day_end) {
        $sql = "select from_unixtime(r.time+28800,'%Y-%m-%d') repay_day, sum(money) money ";
        $sql .= "from firstp2p_deal_loan_repay r, firstp2p_deal d ";
        $sql .= "where r.deal_id=d.id and d.project_id=':project_id' and r.time>=':repay_day_start' and r.time<':repay_day_end' and r.type in ('8','9') and r.status='0' ";
        $sql .= "group by repay_day";
        $param = array(':project_id' => $project_id, ':repay_day_start' => $repay_day_start, ':repay_day_end' => $repay_day_end);
        $list = $this->findAllBySql($sql, true, $param, true);
        $result = array();
        foreach ($list as $item) {
            $result[$item['repay_day']] = $item['money'];
        }
        return $result;
    }

    /**
     * 根据投资id更新赎回申请状态
     * @param array $arr_deal_loan_id
     * @return bool
     */
    public function updateApplyStatusByDealLoanId($arr_deal_loan_id) {
        $str = implode(',', $arr_deal_loan_id);
        $sql = "UPDATE " . $this->tableName() . " SET `status`='1', `update_time`='" . get_gmtime() . "' WHERE `deal_load_id` IN ({$str})";
        return $this->execute($sql);
    }

    /** 根据用户id某个标赎回数目
     * @param $deal_id int
     * @param $user_id int
     * @return int
     */
    public function getDealApplyNumByUserId($deal_id, $status = '', $user_id = 0){
        $user_id = ($user_id == 0 && !empty($GLOBALS['user_info'])) ? $GLOBALS['user_info']['id'] : intval($user_id);
        if($user_id <= 0){
            return false;
        }
        $condition = "`user_id` = ':user_id' AND `deal_id` = ':deal_id'";
        $params = array(':user_id' => $user_id, ':deal_id' => $deal_id);
        if(is_numeric($status)){
            $condition .= " AND `status` = ':status'";
            $params[':status'] = $status;
        }
        return intval($this->countViaSlave($condition, $params));
    }
}
