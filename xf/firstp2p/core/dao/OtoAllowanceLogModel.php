<?php

namespace core\dao;

/**
 * 礼券返利记录
 */
class OtoAllowanceLogModel extends BaseModel
{
    const STATUS_INIT = 0;  // 未返
    const STATUS_DOING = 1; // 返利中
    const STATUS_DONE = 2;  // 已返

    const ACTION_TYPE_TRIGGER = 1;  // 触发返利
    const ACTION_TYPE_ACQUIRE = 2;  // 领取返利
    const ACTION_TYPE_EXCHANGE = 3; // 兑换返利
    const ACTION_TYPE_HAPPY_NEW_YEAR = 4;  // “感恩投资券派送活动”礼包奖励
    const ACTION_TYPE_GAME = 5;     // 游戏活动返利

    public static $_is_use_slave = true;

    public function addRecord($data)
    {
        $this->setRow($data);
        $this->_isNew = true;
        $result = $this->save();
        if (!$result) {
            return false;
        }

        return $this->id;
    }

    /**
     * 开放平台使用，根据邀请人ID，触发返利条件，返利券ID，和触发类型类型，进行
     * 凭证查询
     *
     */
    public function getList($siteId, $toUserId, $actionType, $allowanceType, $allowanceCoupon, $pageNo=1, $pageSize = 10, $sum = 0) {
        $data = array('count' => 0, 'list' => false);
        //查询列表
        $sql = "SELECT id, from_user_id, to_user_id, site_id, acquire_log_id, gift_id, gift_group_id, deal_load_id, action_type, create_time, update_time, allowance_type, allowance_money, allowance_coupon, is_delete, token, status";
        $sql_count = "SELECT count(*) ";

        $sql_where = " FROM   " . $this->tableName() . "
            WHERE  allowance_type = '%d' AND allowance_coupon = '%d' AND action_type = '%d'";
        //查询记录数
        $sql = sprintf($sql . $sql_where,
                $this->escape($allowanceType),
                $this->escape($allowanceCoupon),
                $this->escape($actionType)
                );
        $sql_count = sprintf($sql_count . $sql_where,
                $this->escape($allowanceType),
                $this->escape($allowanceCoupon),
                $this->escape($actionType)
                );
        $count = $this->countBySql($sql_count, array(), self::$_is_use_slave);
        $data['count'] = $count;
        if ($data['count'] == 0) {
            return $data;
        }
        if ($pageSize !== false) {
            $page = (intval($pageNo)-1)*intval($pageSize);
            $sql .= " ORDER BY  id DESC LIMIT " . $this->escape($page) . ", " . $this->escape($pageSize);
        }
        //查询列表记录
        $list = $this->findAllBySql($sql, true, array(), self::$_is_use_slave);
        $data['list'] = $list;
        $data['pageNo'] = $pageNo;
        $data['pageSize'] = $pageSize;

        if($sum == 1){
            //携带今天的和
            date_default_timezone_set('Asia/Shanghai');
            $sql_sum = "SELECT sum(allowance_money) as sum ";
            $now = time();
            $today = strtotime(date('Y-m-d'));
            $sql_where = " FROM   " . $this->tableName() . "
                WHERE  allowance_type = '%d' AND allowance_coupon = '%d' AND action_type = '%d' and create_time > %d and create_time < %d ";

            //查询记录数
            $sql = sprintf($sql_sum . $sql_where,
                    $this->escape($allowanceType),
                    $this->escape($allowanceCoupon),
                    $this->escape($actionType),
                    $this->escape($today),
                    $this->escape($now)
                    );
            $sum = $this->findAllBySql($sql, true, array(), self::$_is_use_slave);
            $data['sum'] = floatval($sum[0]['sum']);
        }

        return $data;
    }


    /**
     * 开放平台使用，根据邀请人ID，触发返利条件，返利券ID，和触发类型类型,创建时间段，进行
     * 凭证查询
     */
    public function getListByTime($siteId, $fromUserId, $actionType, $allowanceType, $allowanceCoupon, $beginTime, $endTime, $pageNo=1, $pageSize=10, $back=0){
        $data = array('count' => 0, 'list' => false);
        date_default_timezone_set('Asia/Shanghai');

        $today = strtotime(date('Y-m-d'));
        if($back && ($beginTime <= $today && $today <= $endTime)){
            $sql_sum = "SELECT count(*) as cn FROM " . $this->tableName() . " WHERE  allowance_type = '%d' AND allowance_coupon = '%d' AND action_type = '%d' and create_time > %d and create_time < %d ";
            $sql_sum = sprintf($sql_sum, $this->escape($allowanceType), $this->escape($allowanceCoupon), $this->escape($actionType), $today, time());
            $sum = $this->findAllBySql($sql_sum, true, array(), self::$_is_use_slave);
            $data['sum'] = floatval($sum[0]['cn']);
        }

        $sql_where = " FROM " . $this->tableName() . " WHERE allowance_type = '%d' AND allowance_coupon = '%d' AND action_type = '%d'";
        $sql_where = sprintf($sql_where, $this->escape($allowanceType), $this->escape($allowanceCoupon), $this->escape($actionType));
        if (!empty($fromUserId)) {
            $sql_where .= sprintf(' AND from_user_id = %d ', $fromUserId);
        }
        if (!empty($beginTime)) {
            $sql_where .= sprintf(' AND create_time >= %d ', $beginTime);
        }
        if (!empty($endTime)) {
            $sql_where .= sprintf(' AND create_time < %d ', $endTime);
        }

        $sql_count = "SELECT count(*) ";
        $sql_count = $sql_count . $sql_where;
        $count = $this->countBySql($sql_count, array(), self::$_is_use_slave);
        $data['count'] = $count;
        if ($data['count'] == 0) {
            return $data;
        }

        //查询列表
        $sql_query = "SELECT id, from_user_id, to_user_id, site_id, acquire_log_id, gift_id, gift_group_id, deal_load_id, action_type, create_time,
                      update_time, allowance_type, allowance_money, allowance_coupon, is_delete, token, status";
        if ($pageSize !== false) {
            $page = (intval($pageNo)-1) * intval($pageSize);
            $sql_where .= " ORDER BY create_time DESC LIMIT " . $this->escape($page) . ", " . $this->escape($pageSize);
        }
        $sql_query = $sql_query . $sql_where;
        $list = $this->findAllBySql($sql_query, true, array(), self::$_is_use_slave);
        $data['list'] = $list;
        $data['pageNo'] = $pageNo;
        $data['pageSize'] = $pageSize;

        return $data;
    }

    /**
     * 根据指定条件获取返利记录
     */
    public function getAllowanceLogByCond($cond, $columns = '*') {
        $sql = "select {$columns} from {$this->tableName()} where $cond";
        //查询列表记录
        $list = $this->findAllBySql($sql, true, array(), self::$_is_use_slave);
        return $list;
    }



}
