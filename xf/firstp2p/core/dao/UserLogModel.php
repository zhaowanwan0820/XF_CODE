<?php
/**
 * UserLogModel class file.
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/

namespace core\dao;

use libs\db\Db;

/**
 * 用户信息
 *
 * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
 **/
class UserLogModel extends ProxyModel {

    const LOG_INFO_SUBSIDY = '平台贴利率';

    public $isSplit = 2;

    public $isBackupDb = false;

    public function __construct($params = array()) {
        parent::__construct();
        foreach($params as $key => $value) {
            $this->$key = $value;
        }
        if($this->isBackupDb) {
            $this->db = Db::getInstance('firstp2p_moved', 'slave');
        }
    }
    /**
     * 资金类型
     */
    private static $LOG_INFO_TYPE = array("身份认证", "投标管理费", "回报本息", "视频认证", "营销补贴", "提现申请", "提现失败",
        "流标返还", "招标成功", "提现成功", "偿还本息", "邀请返利", "逾期还款", "管理员充值", "回报本金", "回报利息",
        "借款额度", "还本", "付息", "充值", "投资放款", "投标冻结", "提前还款申请", "手续费", "咨询费", "担保费", "返利支出",
        "平台管理费", "平台代充值", "逾期罚息", "转出资金", "转入资金", "平台手续费", "工作认证", "投资返利", "取消投标",
        "系统充值", "转账申请", "转账申请失败", "注册返利", "机构返利", "提前还款本金", "提前还款利息", "提前还款补偿金",
        "提前还款", "超出充值", "系统修正", "平台贴利率", "领券收入", "领券支出", "兑券收入", "兑券支出", "领券冻结",
        "兑券冻结", "平台贴息", "超额收益", "返现券返利", "返现券支出","加息券返利", "加息券支出", '余额划转申请','余额划转成功','余额划转失败','网贷余额划转成功','红包充值',
        '买金冻结','买金货款划转','买金手续费冻结','买金手续费','买金流标返还','买金流标手续费返还','提金手续费冻结','提金手续费解冻','提金手续费','黄金变现手续费','黄金变现', '黄金券充值','黄金收益','导流服务费','系统赠金','赠金充值','技术服务费',
        '网信速贷还款剩余金额解冻','网信速贷还款','网信速贷平台服务费','网信速贷还款冻结',
        );

    /**
     * 获取资金类型下拉列表
     */
    public static function getLogInfoTypList(){
       return self::$LOG_INFO_TYPE;
    }

    /**
     * 获取用户可用金额变动的资金记录
     *
     * @param $user_id
     * @param int $offset
     * @param int $page_size
     * @return \libs\db\Model
     */
    public function getUserAvailableMoneyLog($user_id, $offset = 0, $page_size = 20) {
        $condition = "`user_id`=':user_id' AND `money`<>0 AND `is_delete`='0'";
        $order = " ORDER BY `log_time` DESC LIMIT :offset, :size";
        $params = array(
            ':user_id' => $user_id,
            ':offset' => $offset,
            ':size' => $page_size,
        );

        $count = $this->count($condition, $params);
        if ($offset >= $count) {
            $params[':offset'] -= $count;
            $list = $this->getUserAvailableMoneyLogFromBackup($condition . $order, false, "*", $params);
        } else {
            $list = $this->findAll($condition . $order, false, "*", $params);
            //是否存在需要两个库拼装一页
            if (count($list) < $page_size) {
                $params[':offset'] = 0;
                $params[':size'] -= count($list);
                $backup_list = $this->getUserAvailableMoneyLogFromBackup($condition.$order, false, "*", $params);
                $list = array_merge($list, $backup_list);
            }
        }

        return $list;
    }

    public function getUserAvailableMoneyLogFromBackup($condition = "",$is_array=false,$fields="*", $params = array()) {
        $this->db = Db::getInstance('firstp2p_moved', 'slave');
        $list = $this->findAll($condition, false, "*", $params);
        return $list;
    }

    public function getList($user_id, $t, $log_info, $start, $end, $limit, $list_count = 'only_list', $withoutSupervision = false, $excludedLogInfo = []) {
        $condition = "user_id=:user_id";
        if ($t == 'money') {
            $condition .= " AND (:t <> 0 OR lock_money <> 0)";
        }
        elseif ($t == 'money_only') {
            $condition .= " AND (money <> 0)";
        }
        elseif ($t != '') {
            $condition .= " AND (:t <> 0)";
        }

        if ($withoutSupervision) {
            $condition .= ' AND deal_type != '.DealModel::DEAL_TYPE_SUPERVISION;
        }

        if(!empty($log_info)){
            $condition .= " AND log_info = ':log_info'";
        }

        if(!empty($start)){
            $condition .= " AND log_time >= :start";
        }

        if(!empty($end)){
            // TODO 为啥加1天...先注释掉, 等发现有问题的时候再查
            //$end = $end + 86400;
            $condition .= " AND log_time <= :end";
        }

        //排除的log_info
        if (!empty($excludedLogInfo)) {
            $condition .= sprintf(" AND log_info NOT IN ('%s')", implode("','", $excludedLogInfo));
        }

        $condition .= ' AND is_delete = 0';
        $order = " ORDER BY `log_time` DESC ,`id` DESC LIMIT :limit";

        $lim = " %d,%d ";
        $limit = sprintf($lim,$limit[0],$limit[1]);

        $params = array(
                    ':user_id' => $user_id,
                    ':t' => $t,
                    ':log_info' => $log_info,
                    ':start' => $start,
                    ':end' => $end,
                    ':limit' => $limit,
                );

        //由于findAllViaSlave专指firstp2p库的从库，所以需要这么判断一下，TODO:统一sql相关方法
        $list = array();
        $count = 0;
        if($this->isBackupDb === false) {
            if($list_count == 'both' || $list_count == 'only_list') {
                $list = $this->findAllViaSlave($condition . $order, true, '*', $params);
            }
            if($list_count == 'both' || $list_count == 'only_count') {
                $count = $this->countViaSlave($condition, $params);
            }
        }else{
            if($list_count == 'both' || $list_count == 'only_list') {
                $list = $this->findAll($condition . $order, true, '*', $params);
            }
            if($list_count == 'both' || $list_count == 'only_count') {
                $count = $this->count($condition, $params);
            }
        }

        return array("list"=>$list,'count'=>$count);
    }

    /**
     * 方法已经没用被实质的调用了, edited by liaoyebin@ucfgroup.com at 2016.3.9
     * @param $user_id
     * @param $log_info
     * @return array
     */
    public function getLogMoneyInfo($user_id, $log_info) {
        $sql = "SELECT log_time,money FROM `firstp2p_user_log`"
                ." WHERE money IN (SELECT money FROM `firstp2p_user_log` GROUP BY money HAVING COUNT(money) > 1) "
                ." AND user_id = :user_id AND log_time >= log_time-86400 AND log_info =':log_info'";
        $params = array(
                    ':user_id' => $user_id,
                    ':log_info' => $log_info,
                );
        return $this->findAllBySql($sql,true, $params, true);
    }

    /**
     * 根据user_id,log_info 获取 详情   也可以加上时间限制
     * @param int $user_id
     * @param array $log_info
     * @param string $start
     * @param string $end
     */
    public function getDetailByUserIdLogInfo($user_id,$log_info = array(),$start = false,$end = false,$limit = 10) {
        $param = array(':user_id'=>$user_id);
        $sql = 'SELECT id,`log_time`,`note`,`log_info`,`money`,`lock_money`,`remaining_money` FROM '.$this->tableName(true, false, $param)." WHERE `is_delete` = 0 AND `user_id` = ':user_id' ";
        if ($start) {
            $sql .= " AND `log_time`>=':start'";
            $param[':start'] = $start;
        }

        if ($end) {
            $sql .= " AND `log_time`< ':end'";
            $param[':end'] = $end;
        }
        if(count($log_info) > 0) {
            $log_info = implode('\',\'', $log_info);
            $sql .= " AND `log_info` in ('$log_info')";
           // $param[':log_info'] = $log_info;
        }
        $sql .= ' limit  '.$limit;
        $result = $this->findAllBySql($sql,true,$param);

        return $result;
    }

    /**
     * 根据user_id  log_info 以及时间 查询 金额总和
     * @param int $user_id
     * @param array $log_info
     * @param  $start
     * @param  $end
     * @return int $sum
     */
    public function getSumMeneyByUserIdLogInfo($user_id,$log_info=array(),$start = false,$end = false)
    {
        $param = array(':user_id' => $user_id);
        $sql = 'SELECT sum(`money`) as sum FROM ' . $this->tableName(true, false, $param) . " WHERE `is_delete` = 0 AND `user_id` = ':user_id'";

        if ($start) {
            $sql .= " AND `log_time`>=':start'";
            $param[':start'] = $start;
        }

        if ($end) {
            $sql .= " AND `log_time`< ':end'";
            $param[':end'] = $end;
        }
        if (count($log_info) > 0) {
            $log_info = implode('\',\'', $log_info);
            $sql .= " AND `log_info` in ('$log_info')";
        }
        $result = $this->findBySql($sql, $param);    //var_dump(count($result));  die;
        if (count($result) > 0) {
            return $result['sum'];
        }
        return 0;
    }

    public function getSummaryByLogInfo($user_id, $end_time = false) {
        $param = array(
            ':user_id' => $user_id,
        );

        $sql = "SELECT log_info, SUM(money) AS m, SUM(lock_money) AS lm FROM " . $this->tableName(true, false, $param) . " WHERE `user_id` = ':user_id'";

        if ($end_time) {
            $sql .= " AND `log_time` <= ':end_time'";
            $param[':end_time'] = $end_time;
        }

        $sql .= " GROUP BY `log_info`";
        $result = $this->findAllBySql($sql, true, $param, true);
        return $result;
    }

    public function getPrepayDealIds($user_id, $end_time = false) {
        $param = array(
            ':user_id' => $user_id,
        );

        $sql = "SELECT SUBSTRING(`note`, 3) AS `id` FROM " . $this->tableName(true, false, $param) . " WHERE `user_id` = ':user_id' AND `log_info` = '提前还款'";

        if ($end_time) {
            $sql .= " AND `log_time` <= ':end_time'";
            $param[':end_time'] = $end_time;
        }

        $result = $this->findAllBySqlViaSlave($sql, true, $param);
        if ($result) {
            $tmp_arr = array();
            foreach ($result as $v) {
                $tmp_arr[] = intval($v['id']);
            }
            return implode(',', array_unique($tmp_arr));
        }
        return false;
    }


    /**
     * 获取某段时间内的用户资金记录汇总
     * @param $user_id
     * @param $start_time
     * @param $end_time
     * @return array
     */
    public function getSummaryByTime($user_id, $start_time,$end_time) {
        $param = array(
            ':user_id' => $user_id,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
        );

        $sql = "SELECT log_info, SUM(money) AS m, SUM(lock_money) AS lm FROM " . $this->tableName(true, false, $param) . " WHERE `user_id` = ':user_id' AND  `is_delete`='0'";

        if ($start_time) {
            $sql .= " AND `log_time` >= ':start_time'";
        }
        if ($end_time) {
            $sql .= " AND `log_time` <= ':end_time'";
        }

        $sql .= " GROUP BY `log_info`";

        $bool = $this->isBackupDb ? false : true;
        $result = $this->findAllBySql($sql, true, $param, $bool);
        return $result;
    }
    /**
     * 获取某段时间内的用户资金记录汇总
     * @param $user_id
     * @param $start_time
     * @param $end_time
     *  @param $type
     * @return array
     */
    public function getTotalSummaryByTime($user_id, $start_time,$end_time,$type) {
        $param = array(
            ':user_id' => $user_id,
            ':start_time' => $start_time,
            ':end_time' => $end_time,
            ':type' => $type,
        );

        $sql = "SELECT log_info, SUM(money) AS m, SUM(lock_money) AS lm  FROM " . $this->tableName(true, false, $param) . " WHERE `user_id` = ':user_id' AND  `is_delete`='0'";

        if ($start_time) {
            $sql .= " AND `log_time` >= ':start_time'";
        }
        if ($end_time) {
            $sql .= " AND `log_time` <= ':end_time'";
        }

        $sql .= " AND  `log_info` in (:type)";

        $bool = $this->isBackupDb ? false : true;
        $result = $this->findAllBySql($sql, true, $param, $bool);
        return $result;
    }
    public function getUserReaminMoney($user_id,$end_time) {
        $param = array(
            ':user_id' => $user_id,
            ':end_time' => $end_time,
        );

        //先查专享记录
        $sql = "SELECT remaining_total_money FROM " . $this->tableName(true, false, $param) . " WHERE `user_id` = ':user_id' AND `is_delete`='0' AND log_time <= ':end_time' AND deal_type !=4 ORDER BY log_time  DESC LIMIT 1";
        $bool = $this->isBackupDb ? false : true;

        $result = $this->findAllBySql($sql, true, $param, $bool);
        if(!$result) {
            return false;
        }
        $money = isset($result[0]['remaining_total_money']) ? $result[0]['remaining_total_money'] : 0;

        $sql = "SELECT remaining_total_money FROM " . $this->tableName(true, false, $param) . " WHERE `user_id` = ':user_id' AND `is_delete`='0' AND log_time <= ':end_time' AND deal_type=4 ORDER BY log_time  DESC LIMIT 1";

        $result = $this->findAllBySql($sql, true, $param, $bool);
        if(isset($result[0]['remaining_total_money']) && $result[0]['remaining_total_money'] > 0) {
            $money = bcadd($money,$result[0]['remaining_total_money'],2);
        }
        return $money;
    }

    /**
     * 查询用户N天前至今，每天的剩余可用资金
     * @param int $userId 用户ID
     * @param int $startTime 开始时间
     * @param boolean $isSupervision 是否查询网贷记录
     */
    public function getUserRemindMoney($userId, $startTime, $isSupervision = false) {
        $param = array(
            ':user_id' => (int)$userId,
            ':start_time' => (int)$startTime,
        );

        $where = $isSupervision ? ' AND deal_type = ' . \core\dao\DealModel::DEAL_TYPE_SUPERVISION : ' AND deal_type !=' . \core\dao\DealModel::DEAL_TYPE_SUPERVISION;
        // 查询该用户前N天的所有资金记录
        $sql = 'SELECT id,log_time,remaining_money FROM ' . $this->tableName(true, false, $param) . ' WHERE `user_id` = :user_id AND `is_delete`=0 ' . $where . ' AND log_time BETWEEN :start_time AND UNIX_TIMESTAMP() ORDER BY id DESC';
        $result = $this->findAllBySql($sql, true, $param, true);
        if(empty($result)) {
            return false;
        }
        return $result;
    }
} // END class UserLogModel extends BaseModel
