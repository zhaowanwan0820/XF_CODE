<?php
/**
 * CouponLogModel.php
 *
 * @date 2014-02-28 13:46
 * @author liangqiang@ucfgroup.com
 */

namespace core\dao;

use core\service\CouponService;
use core\dao\UserModel;
use core\dao\UserBankcardModel;
use core\service\CouponLogService;
use libs\utils\Logger;


/**
 * 邀请码消费记录dao
 * @package app\models\dao
 */

class CouponLogModel extends CouponBaseModel {
    //过期时间 默认为21天
    public $expire_time = 1814400;//86400 * 21
    /**
     * 更新订单状态
     *
     * 此处有大坑！放款时，把邀请码记录更新。此时有可能因放款异步处理前用户发起赎回，已存在回款到账时间，则该方法不再更新成默认的满期限的回款到账时间；否则初始化为标的满期限回款到账时间。20150723修复
     * 回款到账时间deal_repay_time ，如果标的期限计算是按按月份数*30天计算，那么标起息时间+标期限 算出来的回款时间有可能比实际的回款日期不等，不过目前只影响通知贷还款方式（非 按天一次性还款）的标，但是目前显示没有这类型的标。
     *
     * @param $deal_id 订单id
     * @param $deal_status
     */
    public function updateLogStatusByDealId($deal_id, $deal_status) {
        $sql = "UPDATE " . DB_PREFIX . "coupon_log l, " . DB_PREFIX . "deal d ";
        //有坑，d.repay_time*86400 按月的标不对 确认旧数据影响 20160227
        //$sql .= "SET l.deal_status='%d', l.update_time='%s', l.deal_repay_time=IF(l.deal_repay_time, l.deal_repay_time, (d.repay_time*86400+d.repay_start_time)) ";
        $sql .= "SET l.deal_status='%d', l.update_time='%s', l.deal_repay_time=IF(l.deal_repay_time, l.deal_repay_time, (l.deal_repay_days*86400+d.repay_start_time)) ";
        $sql .= ", l.rebate_days_update_time=IF(l.rebate_days_update_time, l.rebate_days_update_time, d.repay_start_time) ";
        $sql .= "WHERE l.deal_id=d.id AND l.deal_id='%d' ";
        $sql = sprintf($sql, $this->escape($deal_status), get_gmtime(), $this->escape($deal_id));
        return $this->db->query($sql);
    }

    /**
        修改couponLog时间 
    */
    public function updateLogByDealId($deal_id,$deal_repay_time,$repay_start_time){
        $sql = "update " .$this->tableName() . " set deal_status=1,deal_repay_time='%d',rebate_days_update_time ='%d',update_time='%s' where deal_id='%d'";
        $sql = sprintf($sql, $deal_repay_time,$repay_start_time, get_gmtime(), $this->escape($deal_id));
        return $this->db->query($sql);
    }

    /**
     * 根据订单id获取消费记录
     *
     * @param $deal_id 订单id
     * @return \libs\db\Model
     */
    public function findByDealId($deal_id, $status = false, $fields = '*') {
        if (empty($deal_id)) {
            return false;
        }
        $sql = "deal_id='%d' ";
        $sql = sprintf($sql, $this->escape($deal_id));
        if ($status !== false) {
            if (is_array($status)) {
                $status = implode(',', $status);
                $sql .= " AND pay_status in (%s)";
                $sql = sprintf($sql, $this->escape($status));
            } else {
                $sql .= " AND pay_status='%d'";
                $sql = sprintf($sql, $this->escape($status));
            }
        }
        return $this->findAll($sql, false, $fields);
    }

    /**
     * 根据订单id获取没有处理的投资邀请记录
     *
     * @param $deal_id
     * @return array
     */
    public function findNotExistsByDealId($deal_id) {
        $sql = "select d.id from " . DB_PREFIX . "deal_load d left join " . DB_PREFIX . "coupon_log l  on d.id=l.deal_load_id where d.deal_id='%d' and l.id is null";
        $sql = sprintf($sql, $this->escape($deal_id));
        $list = $this->findAllBySql($sql, true, array());
        return $list;
    }

    /**
     * 根据id更新一条状态
     *
     * @param $id 自增id
     * @param $admin_id 管理员id
     * @param $pay_time 支付成功时间
     * @param $status
     * @return bool
     */
    public function updateLogStatus($id, $status, $admin_id) {
        if (empty($id) || !in_array($status, array(-1, -2, 1, 2, 3, 4, 5, 6))) {
            return false;
        }
        $where = '';
        // 根据不同状态，加条件限制
        switch ($status) {
            case 2:
                $where = ' AND pay_status in (0,3,4,5)';
                break;
            case 3:
                $where = ' AND (pay_status=0 or pay_status=4)';
                break;
            case 4:
                $where = ' AND pay_status=3';
                break;
            case 5:
                $where = ' AND pay_status=0';
                break;
            case 6:
                $where = ' AND pay_status=0';
                break;
            case -1:
                $where = " AND pay_status='-2' AND type=1 ";
                break;
            case 1:
                $where = " AND pay_status in ('-1','-2') AND type=1 ";
                break;
            default:
                break;
        }
        // 手工结算，返利支出
        if (in_array($status, array(2, 5, 6))) {
            $pay_time = get_gmtime();
            //$sql = "UPDATE " . $this->tableName() . " SET `pay_status`='%d',`pay_time`='%s',`update_time`='%s',`admin_id` ='%s' WHERE id='%d' AND deal_status=1 $where";
            $sql = "UPDATE " . $this->tableName() . " SET `pay_status`='%d',`pay_time`='%s',`update_time`='%s',`admin_id` ='%s' WHERE id='%d' $where";
            $sql = sprintf($sql, $this->escape($status), $this->escape($pay_time), get_gmtime(), $this->escape($admin_id), $this->escape($id));
        } elseif (in_array($status, array(0, 3, 4))) {
            // 0,3,4的情况
            //$sql = "UPDATE " . $this->tableName() . " SET pay_status='%d',`update_time`='%s',`admin_id` ='%s' WHERE id='%d' AND deal_status=1 $where";
            $sql = "UPDATE " . $this->tableName() . " SET pay_status='%d',`update_time`='%s',`admin_id` ='%s' WHERE id='%d' $where";
            $sql = sprintf($sql, $this->escape($status), get_gmtime(), $this->escape($admin_id), $this->escape($id));
        } else {
            // 注册返利自动结算
            if ($status == 1) {
                // 更新状态同时会更新deal_status 为1
                $pay_time = get_gmtime();
                //$sql = "UPDATE " . $this->tableName() . " SET pay_status='%d',`pay_time`='%s',`update_time`='%s',`admin_id` ='%s' WHERE id='%d' AND deal_status=0 $where";
                $sql = "UPDATE " . $this->tableName() . " SET pay_status='%d',`pay_time`='%s',`update_time`='%s',`admin_id` ='%s' WHERE id='%d' $where";
                $sql = sprintf($sql, $this->escape($status), $this->escape($pay_time), get_gmtime(), $this->escape($admin_id), $this->escape($id));
            } else {
                // 完成实名认证
                $sql = "UPDATE " . $this->tableName() . " SET pay_status='%d',`update_time`='%s',`admin_id` ='%s' WHERE id='%d' $where";
                $sql = sprintf($sql, $this->escape($status), get_gmtime(), $this->escape($admin_id), $this->escape($id));
            }
        }
        return $this->updateRows($sql);
    }

    /**
     * 根据订单id获取消费记录，支持deal_load_id=0的注册邀请码查询
     *
     * @param $deal_load_id 订单id
     * @param $consume_user_id 投资或注册用户id，当查注册用户时，必填
     * @return \libs\db\Model
     */
    public function findByDealLoadId($deal_load_id, $consume_user_id = false) {
        if (empty($deal_load_id) && empty($consume_user_id)) {
            return false;
        }
        if (!empty($consume_user_id)) {
            $sql = "deal_load_id='%d' AND consume_user_id='%d'";
            $sql = sprintf($sql, $this->escape($deal_load_id), $this->escape($consume_user_id));
        } else {
            $sql = "deal_load_id='%d'";
            $sql = sprintf($sql, $this->escape($deal_load_id));
        }
        return $this->findBy($sql);
    }

    /**
     * 根据订单id获取消费记录，支持deal_load_id=0的注册邀请码查询
     *
     * @param $deal_load_id 订单id
     * @param $consume_user_id 投资或注册用户id，当查注册用户时，必填
     * @return \libs\db\Model
     */
    public function findAllByDealLoadId($deal_load_id, $consume_user_id = false) {
        if (empty($deal_load_id) && empty($consume_user_id)) {
            return false;
        }
        if (!empty($consume_user_id)) {
            $sql = "deal_load_id='%d' AND consume_user_id='%d'";
            $sql = sprintf($sql, $this->escape($deal_load_id), $this->escape($consume_user_id));
        } else {
            $sql = "deal_load_id='%d'";
            $sql = sprintf($sql, $this->escape($deal_load_id));
        }
        return $this->findAll($sql);
    }

    /**
     * 根据订单id获取消费记录，支持deal_load_id=0的注册邀请码查询
     *
     * @param $deal_load_id 订单id
     * @param $consume_user_id 投资或注册用户id，当查注册用户时，必填
     * @return \libs\db\Model
     */
    public function findAllByConsumeUserId($consume_user_id) {
        if (empty($consume_user_id)) {
            return false;
        }
        $sql = "consume_user_id='%d'";
        $sql = sprintf($sql, $this->escape($consume_user_id));
        return $this->findAll($sql);
    }


    /**
     * 根据推荐用户id获取已经结算的消费记录
     *
     * @param $refer_user_id 推荐用户id
     * @param $firstRow 起始行数
     * @param $pageSize 列表每页显示行数
     * @return array
     */
    public function  getLogPaid($type, $refer_user_id, $firstRow = false, $pageSize = false, $short_alias = '', $userIds = array(),$siteId =null ) {
        $data = array('count' => 0, 'data' => array(
            'consume_user_count' => 0,
            'referer_rebate_amount' => 0.00,
            'referer_rebate_amount_no' => 0.00,
            'referer_rebate_result_amount' => 0.00,
            'referer_rebate_result_amount_no' => 0.00,
            'list' => false));

        $field_coupon_log = " type,deal_id,deal_load_id,deal_type,deal_load_money,short_alias,pay_status,pay_time,create_time,consume_user_id,refer_user_id,referer_rebate_amount,referer_rebate_ratio_amount,deal_status ";
        $field_coupon_pay_log = " referer_rebate_amount,referer_rebate_ratio_amount,refer_user_id,consume_user_id ";

        $table_coupon_log = " (select {$field_coupon_log} from firstp2p_coupon_log WHERE type=2 and refer_user_id='{$refer_user_id}' union select {$field_coupon_log} from firstp2p_coupon_log_reg where refer_user_id='{$refer_user_id}') l ";
        $table_coupon_pay_log = " (select {$field_coupon_pay_log} from firstp2p_coupon_pay_log where refer_user_id='{$refer_user_id}') l ";

        //邀请人数
        $sql_consume_user_count = "SELECT COUNT(DISTINCT consume_user_id)  FROM  " . $table_coupon_log . "
        WHERE refer_user_id='%d' AND `deal_status` != 2  ";
        $sql_consume_user_count = sprintf($sql_consume_user_count, $this->escape($refer_user_id));
        $consume_user_count = $this->countBySql($sql_consume_user_count, array(), self::$_is_use_slave);
        $data['data']['consume_user_count'] = $consume_user_count;
        if ($data['data']['consume_user_count'] == 0) {
            return $data;
        }

        //总体已返利
        $referer_rebate_amount_sql = 'SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount  FROM ' . $table_coupon_log . "
        WHERE refer_user_id='%d' AND pay_status in (1,2) AND deal_type!='" . CouponLogService::DEAL_TYPE_COMPOUND . "' ";
        $referer_rebate_amount_sql = sprintf($referer_rebate_amount_sql, $this->escape($refer_user_id));
        $referer_rebate_amount = $this->countBySql($referer_rebate_amount_sql, array(), self::$_is_use_slave);
        $data['data']['referer_rebate_amount'] = $referer_rebate_amount;

        //总体待返
        $referer_rebate_amount_no_sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount  FROM " . $table_coupon_log . "
         WHERE refer_user_id='%d' AND pay_status in (0,3,4) AND deal_type!='" . CouponLogService::DEAL_TYPE_COMPOUND . "' AND l.deal_status !=2 ";
        $referer_rebate_amount_no_sql = sprintf($referer_rebate_amount_no_sql, $this->escape($refer_user_id));
        $referer_rebate_amount_no = $this->countBySql($referer_rebate_amount_no_sql, array(), self::$_is_use_slave);
        $data['data']['referer_rebate_amount_no'] = $referer_rebate_amount_no;

        //通知贷已返
        $referer_rebate_amount_compound_sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount " .
            "FROM " . $table_coupon_pay_log . " WHERE refer_user_id='%d' ";
        $referer_rebate_amount_compound_sql = sprintf($referer_rebate_amount_compound_sql, $this->escape($refer_user_id));
        $referer_rebate_amount_compound = $this->countBySql($referer_rebate_amount_compound_sql, array(), self::$_is_use_slave);
        $data['data']['referer_rebate_amount'] = $data['data']['referer_rebate_amount'] + $referer_rebate_amount_compound;

        //查询列表
        $sql = "SELECT type,deal_id,deal_load_id,deal_type,deal_load_money,short_alias,pay_status,pay_time,create_time,";
        $sql .= "consume_user_id,refer_user_id,(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount_2part ";
        $sql_count = "SELECT count(*) ";

        $sql_type_str_list = array("1" => " AND type = '1' ", "2" => " AND type in ('2','3') ");
        $sql_type_str = isset($sql_type_str_list[$type]) ? $sql_type_str_list[$type] : ' ';
        $sql_where = " FROM   " . $table_coupon_log . "
         WHERE refer_user_id='%d' AND `deal_status` != 2  {$sql_type_str} ";
        if ($short_alias) {
            $sql_where .= " AND short_alias = '%s' ";
        }
        if (!empty($userIds)) {
            $sql_where .= sprintf(" AND consume_user_id in (%s) ", $this->escape(implode(',', $userIds)));
        }
        //查询记录数
        $sql = sprintf($sql . $sql_where, $this->escape($refer_user_id), $this->escape($short_alias));
        $sql_count = sprintf($sql_count . $sql_where, $this->escape($refer_user_id), $this->escape($short_alias));

        $count = $this->countBySql($sql_count, array(), self::$_is_use_slave);
        $data['count'] = $count;
        if ($data['count'] == 0) {
            return $data;
        }

        if ($firstRow !== false && $pageSize !== false) {
            $sql .= " ORDER BY create_time DESC, pay_time ASC LIMIT " . $this->escape($firstRow) . ", " . $this->escape($pageSize);
        }

        //查询列表记录
        $list = $this->findAllBySql($sql, true, array(), self::$_is_use_slave);
        $data['data']['list'] = $list;

        //查询结果已返利
        $referer_rebate_result_amount_sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount  FROM " . $table_coupon_log . "
        WHERE refer_user_id='%d' AND pay_status in (1,2) AND deal_type!='" . CouponLogService::DEAL_TYPE_COMPOUND . "' {$sql_type_str}  ";
        $referer_rebate_result_amount_sql = sprintf($referer_rebate_result_amount_sql, $this->escape($refer_user_id));
        if (!empty($userIds)) {
            $referer_rebate_result_amount_sql .= sprintf(" AND consume_user_id in (%s) ", $this->escape(implode(',', $userIds)));
        }
        $referer_rebate_result_amount = $this->countBySql($referer_rebate_result_amount_sql, array(), self::$_is_use_slave);
        $data['data']['referer_rebate_result_amount'] = $referer_rebate_result_amount;

        //查询结果待返
        $referer_rebate_result_amount_no_sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount  FROM " . $table_coupon_log . "
         WHERE refer_user_id='%d' AND pay_status in (0,3,4) AND deal_type!='" . CouponLogService::DEAL_TYPE_COMPOUND . "' {$sql_type_str} AND l.deal_status !=2 ";
        $referer_rebate_result_amount_no_sql = sprintf($referer_rebate_result_amount_no_sql, $this->escape($refer_user_id));
        if (!empty($userIds)) {
            $referer_rebate_result_amount_no_sql .= sprintf(" AND consume_user_id in (%s) ", $this->escape(implode(',', $userIds)));
        }
        $referer_rebate_result_amount_no = $this->countBySql($referer_rebate_result_amount_no_sql, array(), self::$_is_use_slave);
        $data['data']['referer_rebate_result_amount_no'] = $referer_rebate_result_amount_no;

        if ($type == 'all' || $type == 2) { //返利类型为邀请投资，有通知贷
            //查询结果通知贷已返
            $referer_rebate_result_amount_compound_sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount " .
                "FROM " . $table_coupon_pay_log . " WHERE refer_user_id='%d' ";
            $referer_rebate_result_amount_compound_sql = sprintf($referer_rebate_result_amount_compound_sql, $this->escape($refer_user_id));
            if (!empty($userIds)) {
                $referer_rebate_result_amount_compound_sql .= sprintf(" AND consume_user_id in (%s) ", $this->escape(implode(',', $userIds)));
            }
            $referer_rebate_result_amount_compound = $this->countBySql($referer_rebate_result_amount_compound_sql, array(), self::$_is_use_slave);
            $data['data']['referer_rebate_result_amount'] = $data['data']['referer_rebate_result_amount'] + $referer_rebate_result_amount_compound;
        }

        return $data;
    }

    /**
     * 根据投资人id获取投资人已返待反利息
     * @param int $refer_user_id 邀请人id
     * @param array $consume_user_ids 投资人id
     * @return array
     */
    public function getRefererRebateAmount($refer_user_id, $consume_user_ids = array(),$siteId=null) {

        if (!empty($consume_user_ids)) {
            $consume_user_ids = array_map('intval', $consume_user_ids);
        }

        $data = array('referer_rebate_amount' => 0, 'referer_rebate_amount_no' => 0);

        //查询结果已返利
        $sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount  FROM " . $this->tableName() . " WHERE refer_user_id='%d' AND pay_status in (1,2) AND deal_type!='" . CouponLogService::DEAL_TYPE_COMPOUND . "' AND type=2";
        if (!empty($siteId)){
            $sql .= sprintf(" AND site_id = '%d' ", intval($this->escape($siteId)));
        }
        $sql = sprintf($sql, $this->escape($refer_user_id));
        if (!empty($consume_user_ids)) {
            $sql .= sprintf(" AND consume_user_id in (%s) ", $this->escape(implode(',', $consume_user_ids)));
        }

        $sql .= $this->setDealLoadIdCond($this->dataType,self::$module_name);
        $data['referer_rebate_amount'] = $this->countBySql($sql, array(), self::$_is_use_slave);

        //查询结果待返
        $sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount  FROM " . $this->tableName() . " AS l WHERE refer_user_id='%d' AND pay_status in (0,3,4) AND deal_type!='" . CouponLogService::DEAL_TYPE_COMPOUND . "' AND l.deal_status != 2";
        $sql = sprintf($sql, $this->escape($refer_user_id));
        if (!empty($siteId)){
            $sql .= sprintf(" AND site_id = '%d' ", intval($this->escape($siteId)));
        }
        if (!empty($consume_user_ids)) {
            $sql .= sprintf(" AND consume_user_id in (%s) ", $this->escape(implode(',', $consume_user_ids)));
        }

        $sql .= $this->setDealLoadIdCond($this->dataType,self::$module_name);
        $data['referer_rebate_amount_no'] = $this->countBySql($sql, array(), self::$_is_use_slave);

        return $data;
    }

    /**
     * 获取投资人返利list
     */
    public function getList($refer_user_id, $consume_user_ids = array(), $short_alias = '', $firstRow = false, $pageSize = false,$siteId = null, $pay_status = false, $pay_time_start = false, $pay_time_end = false) {
        $data = array('count' => 0, 'list' => false);
        //查询列表
        $sql = "SELECT type,deal_id,deal_load_id,deal_type,deal_load_money,short_alias,pay_status,pay_time,create_time,";
        if (stripos($this->tableName(), "coupon_log_duotou") !== false){
            $sql .= 'repay_start_time,';
        }
        if (stripos($this->tableName(), "coupon_log_reg") !== true){
            $sql .= 'site_id ,';
        }
        if (stripos($this->tableName(), "coupon_log_third") == true){
            $sql .= 'client_id ,';
        }
        $sql .= "consume_user_id,refer_user_id,(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount_2part ";
        $sql_count = "SELECT count(*) ";

        $table_name = " FROM   " . $this->tableName();

        $sql_where = " WHERE refer_user_id='%d' AND `deal_status` != 2 ";

        if ($short_alias) {
            $sql_where .= " AND short_alias = '%s' ";
        }
        if (stripos($this->tableName(), "coupon_log_reg") === false) {
            $sql_where .= ' AND type=2 AND (referer_rebate_amount+referer_rebate_ratio_amount) > 0 ';
        }
        if (!empty($consume_user_ids)) {
            $consume_user_ids = array_map('intval', $consume_user_ids);
            $sql_where .= sprintf(" AND consume_user_id in (%s) ", $this->escape(implode(',', $consume_user_ids)));
        }
        if ($pay_status !== false){
            $sql_where .= sprintf(" AND pay_status = '%d' ", intval($this->escape($pay_status)));
        }
        if (!empty($pay_time_start)){
            $sql_where .= sprintf(" AND pay_time >= '%d' ", intval($this->escape($pay_time_start)));
        }
        if (!empty($pay_time_end)){
            $sql_where .= sprintf(" AND pay_time <= '%d' ", intval($this->escape($pay_time_end)));
        }
        if (!empty($siteId)){
            $sql_where .= sprintf(" AND site_id = '%d' ", intval($this->escape($siteId)));
        }

        $sql_where = sprintf($sql_where, $this->escape($refer_user_id), $this->escape($short_alias));

        if ($this->tableName()=="firstp2p_coupon_log"){
            $sql1 = "SELECT id,type,deal_id,deal_load_id,deal_type,deal_load_money,short_alias,pay_status,pay_time,create_time,repay_start_time,site_id,consume_user_id,refer_user_id,referer_rebate_amount,referer_rebate_ratio_amount ".$table_name.$sql_where . $this->setDealLoadIdCond($this->dataType,'p2p');
            $sql2 = "SELECT id,type,deal_id,deal_load_id,deal_type,deal_load_money,short_alias,pay_status,pay_time,create_time,repay_start_time,site_id,consume_user_id,refer_user_id,referer_rebate_amount,referer_rebate_ratio_amount ".$table_name.'_ncfph '.$sql_where . $this->setDealLoadIdCond($this->dataType,'ncfph');
            $sql = $sql . " FROM " ."(( ".$sql1.") UNION ALL (".$sql2.")) cl";
            $sql_count = $sql_count . " FROM " ."(( ".$sql1.") UNION ALL (".$sql2.")) cl";
        }else{
            $sql = $sql.$table_name.$sql_where . $this->setDealLoadIdCond($this->dataType,self::$module_name);
            $sql_count = $sql_count .$table_name. $sql_where .$this->setDealLoadIdCond($this->dataType,self::$module_name);
        }

        //查询记录数
        $count = $this->countBySql($sql_count, array(), self::$_is_use_slave);
        $data['count'] = $count;
        if ($data['count'] == 0 || $firstRow === false) {
            return $data;
        }
        if ($firstRow !== false && $pageSize !== false) {
            $sql .= " ORDER BY create_time DESC, pay_time ASC, id DESC LIMIT " . $this->escape($firstRow) . ", " . $this->escape($pageSize);
        }
        //查询列表记录
        $list = $this->findAllBySql($sql, true, array(), self::$_is_use_slave);
        $data['list'] = $list;
        return $data;
    }

    /**
     * 根据时间段获取推荐用户id获取已经结算的已返消费记录
     * @param int $refer_user_id
     * @param int $start_time
     * @param int $end_time
     * @return float
     */
    public function getLogPaidHavedPeriodOfTime($refer_user_id, $start_time, $end_time) {
        if (empty($refer_user_id) || empty($start_time) || empty($end_time)) {
            return false;
        }
        //已返利
        $referer_rebate_amount_sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount  FROM `firstp2p_coupon_log`
        WHERE refer_user_id='%d' AND pay_status in (1,2) AND deal_type!='" . CouponLogService::DEAL_TYPE_COMPOUND . "' AND pay_time>='%s' AND pay_time<'%s'";
        $referer_rebate_amount_sql = sprintf($referer_rebate_amount_sql, $this->escape($refer_user_id), $this->escape($start_time), $this->escape($end_time));
        $referer_rebate_amount = $this->countBySql($referer_rebate_amount_sql, array(), self::$_is_use_slave);
        // 通知贷已返
        $referer_rebate_amount_compound_sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount " .
            "FROM `firstp2p_coupon_pay_log` WHERE refer_user_id='%d' AND pay_time>='%s' AND pay_time<'%s'";
        $referer_rebate_amount_compound_sql = sprintf($referer_rebate_amount_compound_sql, $this->escape($refer_user_id), $this->escape($start_time), $this->escape($end_time));
        $referer_rebate_amount_compound = $this->countBySql($referer_rebate_amount_compound_sql, array(), self::$_is_use_slave);
        $referer_rebate_amount = $referer_rebate_amount + $referer_rebate_amount_compound;

        return $referer_rebate_amount;
    }

    /**
     * 单个标里同个用户是否已经获得一次返点金额
     *
     * @param $consume_user_id 投标会员ID
     * @param $deal_id 订单ID
     * @return \libs\db\Model
     */
    public function isExistOneConsumeOneRebate($consume_user_id, $deal_id, $exclude_id = false) {
        if (empty($consume_user_id)) {
            return false;
        }
        $sql = "consume_user_id='%d' AND deal_id='%d' AND rebate_amount>0";
        $sql = sprintf($sql, $this->escape($consume_user_id), $this->escape($deal_id));
        if ($exclude_id) {
            $sql .= " AND id<>" . $this->escape($exclude_id);
        }
        return $this->findAll($sql);
    }

    /**
     * 获取用户最近使用过的邀请码
     * 只判断最近两条邀请码使用记录，如果有绑定要求的邀请码，则返回绑定邀请码，否则返回最近使用的邀请码
     *
     * @param $consume_user_id 投标会员ID
     * @return \libs\db\Model
     */
    public function getCouponLatestByUserId($consume_user_id) {
        //所有邀请码使用记录
        $sql = "select id, consume_user_id, refer_user_id, refer_user_name, short_alias,type, create_time from " . $this->tableName() . "
         where consume_user_id='%d' and short_alias!='" . CouponService::SHORT_ALIAS_DEFAULT . "' order by id desc limit 1";
        $sql = sprintf($sql, $this->escape($consume_user_id));
        return $this->findBySql($sql, false, true);
    }

    /**
     * 取某个借款金额列的金额汇总
     *
     * @param $deal_id intval 借款id
     * @return float
     */
    public function getDealSumAmount($deal_id, $field) {
        $deal_id = intval($deal_id);
        $res = '0';
        if ($deal_id > 0 && $field) {
            $sql = "SELECT SUM(`%s`) FROM " . $this->tableName() . " WHERE `deal_id` = '%d'";
            $sql = sprintf($sql, $this->escape($field), $this->escape($deal_id));
            $res = $this->db->getOne($sql);
        }
        return $res;
    }

    /**
     * 取某个借款邀请码金额相关汇总
     *
     * @param $deal_id intval 借款id
     * @return array
     */
    public function getDealCouponSumAmount($deal_id) {
        $deal_id = intval($deal_id);
        $res = array();
        if ($deal_id > 0) {
            $sql = "SELECT
                    SUM(`rebate_amount`) as rebate_amount_sum,
                    SUM(`rebate_ratio_amount`) as rebate_ratio_amount_sum,
                    SUM(`referer_rebate_amount`) as referer_rebate_amount_sum,
                    SUM(`referer_rebate_ratio_amount`) as referer_rebate_ratio_amount_sum,
                    SUM(`agency_rebate_ratio_amount`) as agency_rebate_ratio_amount_sum
                    FROM " . $this->tableName() . " WHERE `deal_id` = '%d'";
            $sql = sprintf($sql, $this->escape($deal_id));
            $res = $this->db->getRow($sql);
        }
        return $res;
    }

    /**
     * 获取所有需要更新用户等级的用户列表
     *
     * @param int $user_id 会员ID，为空则统计所有
     * @return \libs\db\Model
     */
    public function getUserLevelListForUpdate($user_id) {
        $sql = " id=%d and is_delete=0 and is_effect = 1 ";
        $sql = sprintf($sql, $this->escape($user_id));
        $fields = "id, user_name, group_id, coupon_level_id, coupon_level_valid_end";
        return UserModel::instance()->findAll($sql, false, $fields);
    }

    /**
     * 获取days天内有投资用户的相应累计投资金额
     *
     * @param int $days 统计时间区间
     * @param int $user_id 会员ID，为空则统计所有
     * @return array
     */
    public function getLevelUpdateStat($days = 30, $user_id = false) {
        $days = intval($this->escape($days));
        $time_begin = get_gmtime() - 3600 * 24 * $days;
        $sql = "select user_id, sum(money) from (";
        $sql .= "select user_id, money from " . DB_PREFIX . "deal_load where create_time>%d ";
        if ($user_id) {
            $sql .= sprintf(" and user_id=%d ", $this->escape($user_id));
        }
        $sql .= "UNION ALL select refer_user_id user_id, deal_load_money money from " . DB_PREFIX . "coupon_log ";
        $sql .= "where consume_user_id<>refer_user_id and create_time>%d";
        if ($user_id) {
            $sql .= sprintf(" and refer_user_id=%d ", $this->escape($user_id));
        }
        $sql .= ") a group by a.user_id";
        $sql = sprintf($sql, $this->escape($time_begin), $this->escape($time_begin));
        $result = $this->findAllBySql($sql, true);
        $stat_data = array();
        foreach ($result as $item) {
            $stat_data[$item['user_id']] = $item['sum(money)'];
        }
        return $stat_data;
    }

    /**
     * 是否已经使用过 邀请码
     *
     * @param $user_id
     * @return bool|int
     */
    public function isCouponUsed($user_id) {
        $user_id = intval($user_id);
        if (!$user_id) {
            return false;
        }
        $where = " `refer_user_id` = '{$user_id}' ";
        return $this->countViaSlave($where);
    }

    /**
     * 使用过用户的邀请码
     * @param $refer_user_id
     * @param string $fields
     */
    public function getShortAliasUsed($refer_user_id, $fields = "short_alias") {
        $sql = "SELECT DISTINCT {$fields} FROM `firstp2p_coupon_log` where refer_user_id = '%d' AND deal_status != 2 ";
        $sql = sprintf($sql, $this->escape($refer_user_id));
        $list = $this->findAllBySql($sql, true, array(), true);
        return $list;
    }


    /**
     * 每日更新邀请码记录的返利天数
     * @param $id
     * @return bool|resource
     */
    public function updateRebateDays($id, $rebate_days_add, $rebate_days_update_time) {
        $log_info = array(__CLASS__, __FUNCTION__, $id, $rebate_days_add);
        if ($rebate_days_add <= 0) {
           Logger::info(implode(" | ", array_merge($log_info, array('empty rebate_days_add)'))));
           return true;
        }
        $update_time = get_gmtime();
        //$rebate_days_update_time = to_timespan(date('Y-m-d'));
        $sql = "update " . $this->tableName() . " set rebate_days=rebate_days+{$rebate_days_add}, rebate_days_update_time='{$rebate_days_update_time}',update_time='{$update_time}' ";
        $sql .= "where id='{$id}' and deal_status='1' and pay_status in ('0', '5') ";
        $sql .= "and rebate_days_update_time<'{$rebate_days_update_time}' and deal_repay_time>='{$rebate_days_update_time}' ";
        $rs = $this->execute($sql);
        Logger::info(implode(" | ", array_merge($log_info, array($rs))));
        return $rs;
    }

    /**
     * 根据单个标更新返利天数
     * @param int $deal_id 标id
     * @param int $rebate_days_add 返利天数
     */
    public function updateRebateDaysAndAmount($deal_id, $rebate_days_add) {
        $log_info = array(__CLASS__, __FUNCTION__, $deal_id, $rebate_days_add);
        if ($rebate_days_add < 0 || !is_numeric($deal_id)) {
            Logger::info(implode(" | ", array_merge($log_info, array('rebate_days_add param error)'))));
            return false;
        }
        $update_time = get_gmtime();
        $couponService = new CouponService();
        $pay_status_not_pay = $couponService::PAY_STATUS_NOT_PAY;
        $pay_status_finance_audit = $couponService::PAY_STATUS_FINANCE_AUDIT;
        $deal_type = \core\service\CouponLogService::DEAL_TYPE_GENERAL;
        $sql = 'UPDATE ' . $this->tableName() . " SET rebate_days='{$rebate_days_add}',update_time='$update_time',";
        $sql .= "rebate_ratio_amount = round((`deal_load_money` * `rebate_ratio` * `referer_rebate_ratio_factor` * 0.01 * $rebate_days_add / 360) ,2),";
        $sql .= "referer_rebate_ratio_amount = round((`deal_load_money` * `referer_rebate_ratio` * `referer_rebate_ratio_factor` * 0.01 * $rebate_days_add / 360) ,2),";
        $sql .= "agency_rebate_ratio_amount = round((`deal_load_money` * `agency_rebate_ratio` * `referer_rebate_ratio_factor` * 0.01 * $rebate_days_add / 360) ,2) ";
        $sql .= " WHERE deal_id='$deal_id' AND deal_type!='" . CouponLogService::DEAL_TYPE_COMPOUND . "' AND pay_status IN ($pay_status_not_pay,$pay_status_finance_audit) ";
        //$sql .= " WHERE deal_id='$deal_id' AND deal_type='$deal_type'AND pay_status IN ($pay_status_not_pay,$pay_status_finance_audit) "; // 有问题
        $stime = microtime(true);
        $rs = $this->updateRows($sql);
        $run_time = microtime(true) - $stime;
        Logger::info(implode(" | ", array_merge($log_info, array($rs, 'succ', 'run time ' . $run_time . ' seconds'))));
        return $rs;
    }

    /**
     * 获取用户的待返返利
     */
    public function getUserPendingRebate($refer_user_id) {
        $referer_rebate_amount_no_sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount  FROM `firstp2p_coupon_log` AS l
         WHERE refer_user_id='%d' AND pay_status in (0,3,4) AND deal_type!='" . CouponLogService::DEAL_TYPE_COMPOUND . "' AND l.deal_status !=2 ";
        $referer_rebate_amount_no_sql = sprintf($referer_rebate_amount_no_sql, $this->escape($refer_user_id));
        return $this->countBySql($referer_rebate_amount_no_sql, array(), true);
    }

    /**
     * 根据查询条件获取返利记录列表
     */
    public function getListByParams($params){
        $whereSql = $this->setWhere($params);
        $sql = "select * from {$this->tableName()} where {$whereSql}";
        if(isset($params['_order']) && !empty($params['_order'])){
            $sql .= " order by " .$params['_order'] . " " . (empty($params['_sort']) ? 'asc' : 'desc');
        }else{
            $sql .= " order by deal_load_id desc";
        }
        if(isset($params['firstRow']) && $params['listRows'])
        {
            $sql .= " limit " .$params['firstRow'] .",".$params['listRows'];
        }
        $list = $this->findAllBySql($sql, true,array(),true);
        return $list;
    }

    /**
     * 根据查询条件获取返利记录列表数量
     */
    public function getCountByParams($params){
        $whereSql = $this->setWhere($params);
        $sql = "select count(id) from {$this->tableName()} where {$whereSql}";
        $count = $this->countBySql($sql,array(),true);
        return $count;
    }

    /**
     * 设置查询条件
     */
   private function setWhere($params){

        $whereSql = " 1=1 ";

        if(isset($params['consume_user_id'])){
            if(is_array($params['consume_user_id'])){
                $whereSql .= " and consume_user_id in(".implode(',', $params['consume_user_id']).")";
            }else{
            $whereSql .= " and consume_user_id=".intval($params['consume_user_id']);
            }
        }

        if(isset($params['refer_user_id'])){
            if(is_array($params['refer_user_id'])){
                $whereSql .= " and consume_user_id in(".implode(',', $params['refer_user_id']).")";
            }else{
                $whereSql .= " and refer_user_id=".intval($params['refer_user_id']);
            }
        }

        if(isset($params['deal_id'])){
            if(is_array($params['deal_id']) && !empty($params['deal_id'])){
                $whereSql .= " and deal_id in(".implode(',', $params['deal_id']).")";
            }else{
                $whereSql .= " and deal_id=".intval($params['deal_id']);
            }
        }

        if(isset($params['deal_load_id'])){
            $whereSql .= " and deal_load_id=".intval($params['deal_load_id']);
        }

        if(isset($params['short_alias'])){
            $whereSql .= " and short_alias='".trim($params['short_alias'])."'";
        }

        if(isset($params['pay_status'])){
            $whereSql .= " and pay_status=".intval($params['pay_status']);
        }

        if(isset($params['agency_user_id'])){
            $whereSql .= " and agency_user_id = ".intval($params['agency_user_id']);
        }


        if(isset($params['create_time_begin'])){
            $whereSql .= " and create_time >= '".intval($params['create_time_begin'])."'";
        }

        if(isset($params['create_time_end'])){
            $whereSql .= " and create_time <= '".intval($params['create_time_end'])."'";
        }

        if(isset($params['pay_time_begin'])){
            $whereSql .= " and pay_time >= '".intval($params['pay_time_begin'])."'";
        }

        if(isset($params['pay_time_end'])){
            $whereSql .= " and pay_time <= '".intval($params['pay_time_end'])."'";
        }
       if(isset($params['site_id'])){
           $whereSql .= " and site_id=".intval($params['site_id']);
       }
       if(isset($params['client_id'])){
           $whereSql .= " and client_id='".$params['client_id']."'";
       }
       if(isset($params['deal_type'])){
           $whereSql .= " and deal_type='".$params['deal_type']."'";
       }
        return $whereSql;
   }

    /**
     * 根据投资人id获取邀请人id
     * @param int $refer_user_id
     * @return array
     */
    public function getConsumeUserIdsByReferUserId($refer_user_id) {
        $consume_user_ids = array();
        $sql = "SELECT DISTINCT consume_user_id  FROM  " . $this->tableName() . "
        WHERE refer_user_id='%d' AND `deal_status` != 2";
        $sql = sprintf($sql, $this->escape($refer_user_id));
        $result = $this->findAllBySql($sql);
        if (!empty($result)) {
            foreach ($result as $value) {
                $consume_user_ids[$value['consume_user_id']] = $value['consume_user_id'];
            }
        }
        return $consume_user_ids;
    }

    /**
 * 获取姓名查询的用户id
 */
    public function getConsumeIdByRealName($refer_user_id,$realName) {
        $list=array();
        $log_info = array(__CLASS__, __FUNCTION__, APP, $refer_user_id, $realName);
        $cache = \SiteApp::init()->cache;
        $userModel = new UserModel();
        $redis_key = 'couponlog_by_realname_'.$refer_user_id . '_' . $realName;
        $consume_user_ids = $this ->getConsumeUserIdsByReferUserId($refer_user_id);
        if(!empty($consume_user_ids)){
            $condition = sprintf(" real_name = '%s' ", $this->escape($realName));
          //  $invite_count = app_conf("COUPON_BY_REALNAME_MAX");
           /* if(empty($invite_count)){
                $invite_count = 1000;
            }
            if(count($consume_user_ids)<$invite_count){*/
                $consume_user_ids = implode(",",$consume_user_ids);
                $condition.=sprintf(" AND id in (%s) ", $this->escape($consume_user_ids));
                $consumeUserIds =  $userModel -> findAllViaSlave($condition ,true, 'id');
         /*   } else{
               try{
                    $consumeUserIds = $cache->get($redis_key);
                    if (empty($consumeUserIds)) { //如果当前用户id在redis里代表已经处理过。
                        $consumeUserIds =  $userModel -> findAllViaSlave($condition ,true, 'id');
                        if(!empty($consumeUserIds)){
                            $cache->set($redis_key,$consumeUserIds, $this->expire_time);
                        }
                    }
                }catch (\Exception $e){
                    Logger::info(implode(" | ", array_merge($log_info, array('get consume id from redis fail '))));
                }*/
          //  }
        }else{
            return $list;
        }
        if(!empty($consumeUserIds)){
            foreach ($consumeUserIds as $val){
                $list[$val["id"]] = $val["id"];
            }
        }
        unset($consumeUserIds);
        return $list;
    }
    /**
     * 获取流标的记录
     * @param int $pageSize
     * @return array
     */
    public function getAuctionsBidList($pageSize){

        $sql_count = 'select cast(max(id) as signed) - '.intval($pageSize).' from firstp2p_coupon_log';
        $id_begin = $this->countBySql($sql_count, array(), true);
        $condition = "id>{$id_begin} AND deal_status=2";
        $couponCouponIds = $this->findAllViaSlave($condition,true,'id,deal_id');

        return $couponCouponIds;
    }

    //开放平台获取转线下结算总数
    public function getRefererRebateAmountOffline($refer_user_id) {
        $sql = "SELECT SUM(referer_rebate_amount + referer_rebate_ratio_amount) AS referer_rebate_amount_offline FROM %s
                WHERE refer_user_id = %d AND pay_status = 6 AND deal_type != %d AND deal_status != 2";
        $sql = sprintf($sql, $this->tableName(), $this->escape($refer_user_id), CouponLogService::DEAL_TYPE_COMPOUND);
        $data['referer_rebate_amount_offline'] = $this->countBySql($sql, array(), self::$_is_use_slave);
        return $data;
    }

    //开放平台获取返利记录
    public function getRefererRebateList($refer_user_id, $options = array()) {
        $data = array('count' => 0, 'list' => false);

        $sql_count = "SELECT COUNT(*) ";
        $sql_where = "FROM " . $this->tableName() . " WHERE refer_user_id = %d AND deal_status != 2";

        if (false !== $options['payStatus']) {
            $sql_where .= sprintf(" AND pay_status = %d ", intval($this->escape($options['payStatus'])));
        }

        if (!empty($options['payTimeStart'])){
            $sql_where .= sprintf(" AND pay_time >= %d ", intval($this->escape($options['payTimeStart'])));
        }

        if (!empty($options['payTimeEnd'])){
            $sql_where .= sprintf(" AND pay_time <= %d ", intval($this->escape($options['payTimeEnd'])));
        }

        if (!empty($options['createTimeStart'])) {
            $sql_where .= sprintf(" AND create_time >= %d ", intval($this->escape($options['createTimeStart'])));
        }

        if (!empty($options['createTimeEnd'])) {
            $sql_where .= sprintf(" AND create_time <= %d ", intval($this->escape($options['createTimeEnd'])));
        }

        if (!empty($options['consumeUserId'])) {
            $sql_where .= sprintf(" AND consume_user_id = %d ", intval($options['consumeUserId']));
        }

        $sql_count = sprintf($sql_count . $sql_where, $this->escape($refer_user_id));
        $count = $this->countBySql($sql_count, array(), self::$_is_use_slave);

        $data['count'] = $count;
        if ($data['count'] == 0) {
            return $data;
        }

        $sql = "SELECT type,deal_id,deal_load_id,deal_type,deal_load_money,short_alias,pay_status,pay_time,create_time,site_id,
                consume_user_id,refer_user_id,(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount_2part ";
        $sql = sprintf($sql . $sql_where, $this->escape($refer_user_id));
        $sql .= " ORDER BY create_time DESC, pay_time ASC, id DESC LIMIT " . $this->escape($options['firstRow']) . ", " . $this->escape($options['pageSize']);

        $list = $this->findAllBySql($sql, true, array(), self::$_is_use_slave);
        $data['list'] = $list;

        return $data;
    }

    /**
     *获取重复的未结算的邀请码记录
     */
    public function getDuplicateCouponLog($id = 0){
        $sql = "select id,deal_load_id from ".$this->tableName(). " where id > ".$id." and pay_status != 2 group by deal_load_id having count(deal_load_id)>1";
        return $this->findAllBySqlViaSlave($sql,true);
    }

    /**
     *通过条件删除数据
     */
    public function deleteByCondition($condition){
        $sql = "delete from ".$this->tableName()." where ".$condition;
        return $this->execute($sql);
    }

    public function getCountByReferUserId($refer_user_id){
        return $this->countViaSlave("referer_rebate_ratio_amount > 0 and  refer_user_id = " .intval($refer_user_id) .$this->setDealLoadIdCond($this->dataType,self::$module_name));
    }

    /**
     * 根据dealloads获取不在coupon_log里面的投资记录
     */
    public function getNotInCouponLogLoadIds($loadIds = array())
    {
        $sql = 'select deal_load_id from ' . $this->tableName() . ' where deal_load_id in (' . implode(',', $loadIds) . ')';
        $result = $this->findAllBySqlViaSlave($sql, true);
    }
}
