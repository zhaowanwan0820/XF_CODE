<?php
/**
 * 投资记录
 **/
namespace core\dao\deal;

use core\dao\BaseModel;
use core\service\duotou\DtDealService;
use core\dao\deal\DealModel;
use core\enum\DealEnum;
use libs\db\Db;
use core\dao\repay\DealLoanRepayModel;

class DealLoadModel extends BaseModel {

    // 投资来源
    public static $SOURCE_TYPE = array(
        'general'     => 0, //前台正常投标
        'appointment' => 1, //后台预约投标
        'ios'         => 3, //ios客户端
        'android'     => 4, //安卓客户端
        'reservation' => 5, //前台预约投标
        'openapi'     => 6, //openAPI 目前支持即付使用
        'wap'         => 8, //WAP站投资
        'dtb'         => 9, //智多鑫投资
    );



    /**
     *
     * 检查用户是否第一次投资
     * @return bool
     */
    public function checkFirstBid($userId){

        $ret = $GLOBALS['db']->getOne("select id from ".$this->tableName()." where user_id = '$userId' limit 1");

        if (!empty($ret)){
            return false;
        }
        return true;
    }

    /**
     * 已成功投资数目(流标、标被删除、无效均不算)
     *
     * @param $user_id int
     * @param $source_type array    来源 3:ios 4:android
     * @param  $source_allow 是否包含source_type
     * @return integer
     */
    public function getCountByUserIdInSuccess($user_id, $source_type = array(),$source_allow=true) {
        $sql = "SELECT count(id) FROM %s WHERE `user_id` = ':user_id'";
        if ( !empty($source_type)) {
            $source_type_str = implode(',', $source_type);
            if($source_allow){
                $sql .= " AND `source_type` in ({$source_type_str})";
            }else{
                $sql .= " AND `source_type` not in ({$source_type_str})";
            }
        }
        $sql .= " AND `deal_id` IN (SELECT `id` FROM %s WHERE `deal_status` IN (1,2,4,5) AND is_delete = '0' AND is_effect = '1')";
        $sql = sprintf($sql, $this->tableName(), DealModel::instance()->tableName());
        $param[':user_id'] = $user_id;
        $cnt = $this->countBySql($sql,$param,true);
        return intval($cnt);
    }
    /**
     * 获取项目用户已投资金额总数
     * @param $uid
     * @param $deal_id
     */
    function getUserLoadMoneyByDealid($uid,$deal_id){
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s  WHERE user_id = ':user_id' AND deal_id =':deal_id' ";
        $param = array(':user_id'=>$uid,':deal_id'=>$deal_id);
        $sql = sprintf($sql, $this->tableName());
        $result = $this->findBySql($sql,$param, true);
        return $result['sum'];
    }

    /**
     * 获取用户可以赎回的利滚利投资列表
     * @param $uid 用户 id
     * @param $time 赎回 时间
     * @return bool|int
     */
    public function getUserCompoundList($uid){
        if(!$uid){
            return 0;
        }
        $sql = " SELECT d.`id` AS deal_load_id ,d.`deal_id` AS deal_id , d.`money`,d.`user_id` AS user_id
        FROM `firstp2p_deal_load` AS d LEFT JOIN `firstp2p_compound_redemption_apply` AS c  ON c.`deal_load_id` = d.id
        WHERE d.user_id = '%s' AND d.deal_type = 1 AND c.`deal_id` IS NULL ";
        $sql = sprintf($sql, $uid);
        $list = $this->findAllBySql($sql, true, array(),true);
        return $list;
    }


    /**
     * 根据用户id获取投资列表
     * @param $user_id int
     * @param $offset int
     * @param $page_size int
     * @param bool|int $status int 多个状态逗号隔开
     * @param bool $date_start string|false
     * @param bool $date_end string|false
     * @return array('count'=>$count, 'list'=>$list)
     */
    public function getUserLoadList($user_id, $offset, $page_size, $status=0, $date_start=false, $date_end=false, $type = '', $exclude_loantype = 0, $deal_type_id = 0)
    {
        /*
         * 暂时不迁移
         * if ($status == DealEnum::DEAL_STATUS_REPAID){
            return $this->getRepaidLoadListByUserId($user_id, $offset, $page_size, $status, $date_start, $date_end, $type, $exclude_loantype, $deal_type_id);
        }*/
        $user_id = intval($user_id);
        $offset = intval($offset);
        $page_size = intval($page_size);
        //$status = intval($status); //会传入多个status字符串
        $deal_status = ($status == 0) ? "1,2,4,5" : $status;
        $deal_condition_str = '';
        if ($type !== '' && $type !== null) {
            $deal_condition_str .= ' AND d.deal_type IN(' . $type . ')';
        }
        if (intval($exclude_loantype) > 0) {
            $deal_condition_str .= ' AND d.loantype <> ' . intval($exclude_loantype);
        }

        if (!empty($deal_type_id)) {
            $deal_condition_str .= ' AND d.type_id=' . intval($deal_type_id);
        }

        //需要过滤掉多投的标的
        $dt_tag = \core\dao\tag\TagModel::instance()->getInfoByTagName(DtDealService::TAG_DT);
        $dt_tag_v3 = \core\dao\tag\TagModel::instance()->getInfoByTagName(DtDealService::TAG_DT_V3);
        $dt_tag_arr = array();
        if (!empty($dt_tag)) {
            $dt_tag_arr[] = intval($dt_tag['id']);
        }
        if (!empty($dt_tag_v3)) {
            $dt_tag_arr[] = intval($dt_tag_v3['id']);
        }
        if (!empty($dt_tag_arr)) {
            $deal_condition_str .= sprintf(" AND d.id NOT IN (SELECT `deal_id` FROM %s WHERE `tag_id` IN (%s))", \core\dao\deal\DealTagModel::instance()->tableName(), implode(',', $dt_tag_arr));
        }

        $condition = "FROM %s l WHERE l.`user_id`='%d' AND EXISTS (SELECT 1 FROM %s d WHERE l.`deal_id`=d.`id` AND d.`deal_status` IN (%s) AND d.`parent_id` != '0' AND d.`is_effect`='1' AND d.`is_delete`='0' AND d.`publish_wait` = 0 {$deal_condition_str})";
        $condition = sprintf($condition, $this->tableName(), $this->escape($user_id), DealModel::instance()->tableName(), $this->escape(preg_replace("/'|\"/", "", $deal_status)));

        if ($date_start) {
            $condition .= sprintf(" AND l.`create_time`>='%d'", strtotime($this->escape($date_start)));
        }
        if ($date_end) {
            $condition .= sprintf(" AND l.`create_time`<'%d'", strtotime($this->escape($date_end)) + 3600 * 24);
        }
        $count_sql = "SELECT COUNT(*) " . $condition;
        $sql = "SELECT l.* %s ORDER BY l.`id` DESC LIMIT %d, %d";
        $sql = sprintf($sql, $condition, $this->escape($offset), $this->escape($page_size));
        $count = $this->countBySql($count_sql, array(), true);
        $result = $this->findAllBySql($sql, true, array(), true);

        return array("count" => $count, "list" => $result);
    }

    public function getRepaidLoadListByUserId($user_id, $offset, $page_size, $status=0, $date_start=false, $date_end=false, $type = '', $exclude_loantype = 0, $deal_type_id = 0){

        $user_id = intval($user_id);
        $offset = intval($offset);
        $page_size = intval($page_size);
        //$status = intval($status); //会传入多个status字符串
        $deal_status = 5;
        $deal_condition_str = '';
        if ($type !== '' && $type !== null) {
            $deal_condition_str .= ' AND d.deal_type IN(0)';
        }
        if (intval($exclude_loantype) > 0) {
            $deal_condition_str .= ' AND d.loantype <> ' . intval($exclude_loantype);
        }

        if (!empty($deal_type_id)) {
            $deal_condition_str .= ' AND d.type_id=' . intval($deal_type_id);
        }
        // 先从正常库查出deal_id
        $user_deal_ids = $this->getDealIdsByUserId($user_id, $date_start, $date_end);
        if (empty($user_deal_ids)){
            return array("count" => 0, "list" => array());
        }
        // 先从备份库中查出已经还清的标的
        $deal_condition_str = "SELECT id FROM %s d WHERE  d.`deal_status` IN (%s) AND d.`parent_id` != '0'
                      AND d.`is_effect`='1' AND d.`is_delete`='0' AND d.`publish_wait` = 0 {$deal_condition_str} AND id IN ($user_deal_ids)";
        $deal_sql = sprintf($deal_condition_str, DealModel::instance()->tableName(), DealEnum::DEAL_STATUS_REPAID);
        $vardb = Db::getInstance(DealEnum::DEAL_MOVED_DB_NAME, 'slave');
        $result = $vardb->getAll($deal_sql);
        if (empty($result)){
            return array("count" => 0, "list" => array());
        }
        $deal_id_where = '';
        if (!empty($result)){
            $deal_ids = array();
            foreach($result as $key =>$v){
                $deal_ids[$key] = $v['id'];
            }
            $deal_id_where = implode(',',$deal_ids);
        }

        $deal_condition_str = '';
        //需要过滤掉多投的标的
        $dt_tag = \core\dao\tag\TagModel::instance()->getInfoByTagName(DtDealService::TAG_DT);
        $dt_tag_v3 = \core\dao\tag\TagModel::instance()->getInfoByTagName(DtDealService::TAG_DT_V3);
        $dt_tag_arr = array();
        if (!empty($dt_tag)) {
            $dt_tag_arr[] = intval($dt_tag['id']);
        }
        if (!empty($dt_tag_v3)) {
            $dt_tag_arr[] = intval($dt_tag_v3['id']);
        }
        if (!empty($dt_tag_arr)) {
            $deal_condition_str .= sprintf(" AND l.deal_id NOT IN (SELECT `deal_id` FROM %s WHERE `tag_id` IN (%s)) ", \core\dao\deal\DealTagModel::instance()->tableName(), implode(',', $dt_tag_arr));
        }
        $condition = "FROM %s l WHERE l.`user_id`='%d' {$deal_condition_str}";
        $condition = sprintf($condition, $this->tableName(), $this->escape($user_id));

        if ($date_start) {
            $condition .= sprintf(" AND l.`create_time`>='%d'", strtotime($this->escape($date_start)));
        }
        if ($date_end) {
            $condition .= sprintf(" AND l.`create_time`<'%d'", strtotime($this->escape($date_end)) + 3600 * 24);
        }
        $condition .= " AND l.deal_id IN ($deal_id_where)";
        $count_sql = "SELECT COUNT(*) " . $condition;
        $sql = "SELECT l.* %s ORDER BY l.`id` DESC LIMIT %d, %d";
        $sql = sprintf($sql, $condition, $this->escape($offset), $this->escape($page_size));
        $count = $this->countBySql($count_sql, array(), true);
        $result = $this->findAllBySql($sql, true, array(), true);

        return array("count" => $count, "list" => $result);
    }

    /**
     * 方便从备份库查询
     * @param $user_id
     * @param bool $date_start
     * @param bool $date_end
     * @return string
     */
    public function getDealIdsByUserId($user_id, $date_start=false, $date_end=false){

        $deal_condition_str = '';

        $condition = "FROM %s l WHERE l.`user_id`='%d' {$deal_condition_str}";
        $condition = sprintf($condition, $this->tableName(), $this->escape($user_id));

        if ($date_start) {
            $condition .= sprintf(" AND l.`create_time`>='%d'", strtotime($this->escape($date_start)));
        }
        if ($date_end) {
            $condition .= sprintf(" AND l.`create_time`<'%d'", strtotime($this->escape($date_end)) + 3600 * 24);
        }
        $sql = "SELECT l.deal_id %s";
        $sql = sprintf($sql, $condition);
        $result = $this->findAllBySql($sql, true, array(), true);
        if (empty($result)){
            return '';
        }

        $user_deal_ids = array();
        foreach($result as $key =>$v){
            $user_deal_ids[$key] = $v['deal_id'];
        }

        return implode(',',$user_deal_ids);
    }
    /**
     *  从备份库查询已还清的标的投资记录
     * @param $user_id
     * @param $offset
     * @param $page_size
     * @param int $status
     * @param bool $date_start
     * @param bool $date_end
     * @param string $type
     * @param int $exclude_loantype
     * @param int $deal_type_id
     */
    public function getUserLoadRepaidList($user_id, $offset, $page_size, $status=5, $date_start=false, $date_end=false, $type = '', $exclude_loantype = 0, $deal_type_id = 0){

        $ret = $this->getUserLoadList($user_id, $offset, $page_size, DealEnum::DEAL_STATUS_REPAID, $date_start, $date_end, $type = '', $exclude_loantype, $deal_type_id);

        return $ret;
    }

    /**
     * 根据订单id获取投资数目 * @param int $deal_id
     * @return array
     */
    public function getLoadCount($deal_id)
    {
        $deal_id = intval($deal_id);
        $sql = "SELECT COUNT(*) AS `buy_count`, SUM(money) AS `load_money` FROM %s WHERE `deal_id`='%d'";
        $sql = sprintf($sql, $this->tableName(), $this->escape($deal_id));
        return $this->findBySql($sql, null, false);
    }

    /**
     * 获取用户的第一次投标记录
     */
    public function getFirstDealByUser($userId, $slave = false) {
        $userId = intval($userId);
        if (!$userId) {
            return false;
        }
        $sql_first = "SELECT id, deal_id, money FROM `firstp2p_deal_load` WHERE user_id = '{$userId}' ORDER BY id ASC LIMIT 1";
        $load_first = $this->findBySql($sql_first, null, $slave);
        return $load_first;
    }

    /**
     * 用户今天是否有投资
     * @param $userId
     * @return bool
     */
    public function isTodayLoadByUserId($userId){

        $startTime = strtotime(date('Ymd')) - date('Z');
        $sql = "SELECT id FROM firstp2p_deal_load WHERE user_id = '{$userId}' AND create_time >= $startTime";
        $ret = $this->findBySql($sql, array(), true);

        if (!empty($ret['id'])) {
            return true;
        }

        return false;
    }

     /*
     * 根据订单id获取投资列表
     * @param $deal_id int 订单id
     * @return array
     */
    public function getDealLoanList($deal_id) {
        $deal_id = intval($deal_id);
        $condition = "`deal_id`='%d' ORDER BY `id` ASC";
        $condition = sprintf($condition, $this->escape($deal_id));
        return $this->findAllViaSlave($condition, false, '*');
    }

    public function setIsrepayByDealId($deal_id, $is_repay = 1) {
        $sql = "UPDATE " . $this->tableName() . " SET `is_repay`='1', `update_time`='".get_gmtime()."' WHERE `deal_id`='{$deal_id}'";
        return $this->db->query($sql);
    }

    /**
      * 根据时间获取投资人数
      * @param $startTime
      * @return integer
      */
    public function getLoadUsersNumByTime($startTime){
        $sql = "SELECT count(DISTINCT loan_user_id) as cnt  FROM `firstp2p_supervision_idempotent` WHERE type=1 AND create_time >=".$startTime;
        $cnt = $this->countBySql($sql,array(),true);
        return $cnt;
    }

    /**
     * 获取投资记录及用户信息
     *
     * @param $deal_id int 借款id
     * @param $load_id int 投资id
     * @return array
     */
    public function getLoadDetailInfo($deal_id, $load_id) {
        $deal_id = intval($deal_id);
        $load_id = intval($load_id);
        $loan_sql = "SELECT d.id as deal_load_id,d.deal_id,d.money as loan_money,d.create_time as jia_sign_time
                FROM %s as d
                WHERE d.deal_id = '%d'  AND d.id = '%d'";
        $sql = sprintf($loan_sql, $this->tableName(), $this->escape($deal_id), $this->escape($load_id));
        return $this->findBySql($sql);
    }

    /**
     * 根据user_id获取投资人某段时间内累计投资总额
     * @param user_id|int $user_id int
     * @param bool $date_start int|false
     * @param bool $date_end int|false
     * @return float
     * @author zhanglei5@ucfgroup.com
     */
    public function getTotalLoanMoneyByUserId($user_id,$date_start = false, $date_end = false,$deal_status=array(4)) {
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s as dl WHERE dl.user_id = ':user_id' AND `deal_id` IN (SELECT `id` FROM %s WHERE `deal_status` IN (".implode(',',$deal_status)."))";
        $param = array(':user_id'=>$user_id);

        if ($date_start) {
            $sql .= " AND dl.`create_time`>=':date_start'";
            $param[':date_start'] = $date_start;
        }

        if ($date_end) {
            $sql .= " AND dl.`create_time`< ':date_end'";
            $param[':date_end'] = $date_end;
        }

        //strtotime($this->escape($date_end))+3600*24

        $sql = sprintf($sql, $this->tableName(), DealModel::instance()->tableName());
        $result = $this->findBySql($sql, $param, true);
        return $result['sum'];
    }
    public function getDealInfoByLoadId($load_id) {
        $dealinfo = array();
        $dealLoadInfo = $this->find(intval($load_id));
        $dealinfo['money']= $dealLoadInfo->money;
        $dealinfo['deal_id']= $dealLoadInfo->deal_id;
        $deal_model = new DealModel();
        $deal = $deal_model ->getDealInfo( $dealLoadInfo['deal_id']);
        $dealinfo['name']= $deal->name;;
        return $dealinfo;
    }

    /**
     * getO2ODealLoadInfo o2o后台查询普惠交易信息接口
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-10-09
     * @param mixed $load_id
     * @access public
     * @return void
     */
    public function getO2ODealLoadInfo($load_id) {
        $dealinfo = array();
        $dealLoadInfo = $this->find(intval($load_id));
        if ($dealLoadInfo) {
            $deal_model = new DealModel();
            $deal = $deal_model->getDealInfo($dealLoadInfo->deal_id);
            $dealinfo['site_id'] = $dealLoadInfo->site_id;
            $dealinfo['money']= $dealLoadInfo->money;
            $dealinfo['deal_id']= $dealLoadInfo->deal_id;
            $dealinfo['repay_time']= $deal->repay_time;
            $dealinfo['name']= $deal->name;
            $dealinfo['loantype']= $deal->loantype;
            $dealinfo['source_type']= $dealLoadInfo->source_type;
            $dealinfo['short_alias']= $dealLoadInfo->short_alias;
        }
        return $dealinfo;
    }

    /**
     * 获取用户从11月1日起大于等于10000元的投资
     */
    public function getUserLoadMoreTenThousand($userId, $slave =false)
    {
        $startTime = strtotime("2018-10-30") - date('Z');
        $sql = "SELECT money FROM firstp2p_deal_load WHERE user_id = {$userId} AND money >= 10000 AND create_time >= {$startTime} LIMIT 1";

        return $this->findBySql($sql, null, $slave);
    }

    /**
     * 获取非预约标的投资用户 id 集合
     * @param $deal_id int
     * @return array
     */
    public function getDealLoanUserIdsExReservation($deal_id) {
        $cond = sprintf(' `deal_id` = %d AND `source_type` != %d GROUP BY `user_id` ', $deal_id, self::$SOURCE_TYPE['reservation']);
        $res =  $this->findAll($cond, true, '`user_id`');
        $loan_user_id_collection = array();
        foreach ($res as $user_id) {
            $loan_user_id_collection[] = $user_id['user_id'];
        }
        return $loan_user_id_collection;
    }

    /**
     * 根据 deal_id 获取预约投标的 load_id 集合
     * @param int $deal_id
     * @return array
     */
    public function getReserveDealLoadIdsByDealId($deal_id)
    {
        if ($deal_id <= 0) {
            return array();
        }

        $cond = sprintf(' deal_id = %d AND source_type = %d ', $deal_id, self::$SOURCE_TYPE['reservation']);
        $deal_load_arr = $this->findAllViaSlave($cond, true, 'id');

        // 回调，获取 id 集合
        $func_get_load_ids = function ($deal_load) {
            return $deal_load['id'];
        };
        return empty($deal_load_arr) ? array() : array_map($func_get_load_ids, $deal_load_arr);
    }

    /**
     * 已投资数目(包括流标等任意情况)
     *
     * @param $user_id int
     * @return integer
     * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
     **/
    public function countByUserId($user_id, $is_slave = true) {
        $user_id = intval($user_id);
        $condition = "`user_id` = '%d'";
        $condition = sprintf($condition, $this->escape($user_id));
        if ($is_slave) {
            return $this->countViaSlave($condition);
        } else {
            return $this->count($condition);
        }
    }

    /*
 * 获取用户在此标的的投资笔数
 * @param int $deal_id
 * @param int $user_id
 * @param boolen $exclude_reservation 是否排除前台预约投标
 * @return int
 */
    public function getDealLoadCountsByUserId($deal_id, $user_id, $exclude_reservation = false) {
        $condition = sprintf('`deal_id` = %d AND `user_id` = %d', $deal_id, $user_id);
        $condition .= $exclude_reservation ? sprintf(' AND source_type != %d ', self::$SOURCE_TYPE['reservation']) : '';
        return $this->count($condition);
    }


    /**
     * 根据用户id整合投资记录，排除预约投资
     * @param $deal_id int
     * @return array
     */
    public function getNonReserveDealLoanUserList($deal_id) {
        $deal_id = intval($deal_id);
        $sql = "SELECT SUM(`money`) as `m`, `user_id`, `site_id`, `create_time`, COUNT(`id`) AS `c` FROM " . $this->tableName() . " WHERE `deal_id`='{$deal_id}' AND `source_type` != '" . self::$SOURCE_TYPE['reservation'] . "' GROUP BY `user_id`";
        $result = $this->findAllBySql($sql, true, array(), true);
        return $result;
    }

    /**
     * 获取用户当天总投资额，和总投资年化额
     * @param $userId
     *
     * @return array array('money' ,'moneyRate')
     */
    public function getUserTodayLoadMoneyStat($userId)
    {

        $ret = array('money' => 0, 'moneyRate' => 0);

        $startTime = strtotime(date("Ymd")) - date('Z');
        $sql = "SELECT deal_id, money FROM firstp2p_deal_load WHERE create_time >= $startTime AND user_id = '$userId'";
        $userDealLoads = $this->findAllBySql($sql, true);
        if (empty($userDealLoads)) {
            return $ret;
        }

        foreach ($userDealLoads as $dealLoad) {

            $deal = DealModel::instance()->findViaSlave($dealLoad['deal_id'], 'loantype,repay_time');
            if ($deal['loantype'] != 5) {
                $deal['repay_time'] = $deal['repay_time'] * 30;
            }

            $moneyRate = $dealLoad['money'] * $deal['repay_time'] / 360;
            $ret['moneyRate'] += $moneyRate;
            $ret['money'] += $dealLoad['money'];
        }

        return $ret;
    }
    /**
     * 获取用户当天总投资额，和总投资年化额
     * @param $userId
     *
     * @return array array('money' ,'moneyRate')
     */
    public function getUserLoadMoneyStat($userId, $startTime = 0)
    {

        $ret = array('money' => 0, 'moneyRate' => 0);

        if (empty($startTime)){
            return $ret;
        }
        $startTime = $startTime - date('Z');
        $sql = "SELECT deal_id, money FROM firstp2p_deal_load WHERE create_time >= $startTime AND user_id = '$userId'";
        $userDealLoads = $this->findAllBySql($sql, true);
        if (empty($userDealLoads)) {
            return $ret;
        }

        foreach ($userDealLoads as $dealLoad) {

            $deal = DealModel::instance()->findViaSlave($dealLoad['deal_id'], 'loantype,repay_time');
            if ($deal['loantype'] != 5) {
                $deal['repay_time'] = $deal['repay_time'] * 30;
            }

            $moneyRate = $dealLoad['money'] * $deal['repay_time'] / 360;
            $ret['moneyRate'] += $moneyRate;
            $ret['money'] += $dealLoad['money'];
        }

        return $ret;
    }

}
