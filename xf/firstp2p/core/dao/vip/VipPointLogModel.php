<?php

namespace core\dao\vip;

use core\dao\vip\VipBaseModel;

/**
 * VIP经验值记录
 *
 * @author wangshijie@ucfgroup.com
 */
class VipPointLogModel extends VipBaseModel
{
    /**
     * 领取
     */
    const STATUS_ACQUIRE = 1;

    /**
     * 过期
     */
    const STATUS_EXPIRE  = 2;

    /**
     * [acquirePoint description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function acquirePoint($data)
    {
        if (!isset($data['create_time'])) {
            $data['create_time'] = time();
        }
        $data['source_weight'] = $data['source_weight'] ?: 1;
        $data['status'] = self::STATUS_ACQUIRE;

        $this->setRow($data);
        $this->_isNew = true;
        $result = $this->save();
        if (!$result) {
            return false;
        }
        return $this->id;
    }

    /**
     * [getPointByUserId description]
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function getValidPoint($userId)
    {
        $sql = 'SELECT SUM(point) AS total_point FROM `firstp2p_vip_point_log` WHERE user_id=%s AND status=%s AND expire_time > %s';
        $sql = sprintf($sql, $userId, self::STATUS_ACQUIRE, time());

        return $this->getSlave()->getOne($sql);
    }

    /**
     * [getPointByExpireTime description]
     * @param  [type] $userId [description]
     * @param  string $date   [description]
     * @return [type]         [description]
     */
    public function getPointByExpireTime($userId, $startTime, $endTime)
    {
        $sql = 'SELECT SUM(point) FROM firstp2p_vip_point_log WHERE user_id=%s AND status=%s AND expire_time between %s AND %s';
        $sql = sprintf($sql, $userId, self::STATUS_ACQUIRE, $startTime, $endTime);

        return $this->getSlave()->getOne($sql);
    }

    /**
     * [expirePoint description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function expirePoint($data)
    {
        if (!isset($data['create_time'])) {
            $data['create_time'] = time();
        }
        $data['status'] = self::STATUS_EXPIRE;

        $this->setRow($data);
        $this->_isNew = true;
        $result = $this->save();
        if (!$result) {
            return false;
        }
        return $this->id;
    }

    /**
     * [getByToken description] 默认查主库，防止并发时主键冲突
     * @param  [type] $tokens [description]
     * @return [type]         [description]
     */
    public function getPointByTokens($tokens)
    {
        if (empty($tokens)) {
            return [];
        }
        if (!is_array($tokens)) {
            $tokens = [$tokens];
        }
        $arrToken = [];
        foreach ($tokens as $token) {
            $arrayToken[] = "'".$token."'";
        }

        $sql = 'SELECT * FROM firstp2p_vip_point_log WHERE token IN (%s)';
        $sql = sprintf($sql, implode(',', $arrayToken));

        return $this->db->getAll($sql);
    }

    /**
     * [getByPage description]
     * @param  [type]  $userId [description]
     * @param  [type]  $page   [description]
     * @param  integer $count  [description]
     * @return [type]          [description]
     */
    public function getPointByPage($userId, $page, $count = 10)
    {
        $sql = 'SELECT * FROM firstp2p_vip_point_log WHERE user_id=%s ORDER BY create_time DESC LIMIT %s, %s';
        $sql = sprintf($sql, $userId, ($page - 1) * $count, $count);
        return $this->getSlave()->getAll($sql);
    }

    /**
     * [getPointByCreateTime description]
     * @param  [type] $userId    [description]
     * @param  [type] $startTime [description]
     * @param  [type] $endTime   [description]
     * @return [type]            [description]
     */
    public function getPointByCreateTime($userId, $startTime, $endTime)
    {
        $sql = 'SELECT SUM(point) FROM firstp2p_vip_point_log WHERE user_id=%s AND status=%s AND create_time between %s AND %s';
        $sql = sprintf($sql, $userId, self::STATUS_ACQUIRE, $startTime, $endTime);

        return $this->getSlave()->getOne($sql);
    }

    /**
     * [getExpiredList description]
     * @param  [type] $startTime [description]
     * @param  [type] $endTime   [description]
     * @param  [type] $limit     [description]
     * @param  [type] $count     [description]
     * @return [type]            [description]
     */
    public function getExpiredList($startTime, $endTime, $offset, $count)
    {
        $sql = 'SELECT user_id, SUM(point) as point FROM firstp2p_vip_point_log WHERE status = %s AND expire_time BETWEEN %s AND %s GROUP BY user_id ASC LIMIT %s, %s';
        $sql = sprintf($sql, self::STATUS_ACQUIRE, $startTime, $endTime, $offset, $count);

        return $this->getSlave()->getAll($sql);
    }

    /**
     * [getExpiredTotal description]
     * @param  [type] $startTime [description]
     * @param  [type] $endTime   [description]
     * @return [type]            [description]
     */
    public function getExpiredTotal($startTime, $endTime)
    {
        $sql = 'SELECT COUNT(DISTINCT(user_id)) FROM firstp2p_vip_point_log WHERE status = %s AND expire_time BETWEEN %s AND %s';
        $sql = sprintf($sql, self::STATUS_ACQUIRE, $startTime, $endTime);

        return $this->getSlave()->getOne($sql);
    }
}
