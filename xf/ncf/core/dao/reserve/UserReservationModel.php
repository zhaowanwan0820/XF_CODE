<?php
/**
 * 用户预约记录表
 * @date 2016-11-14
 * @author guofeng3@ucfgroup.com>
 */

namespace core\dao\reserve;

use core\dao\BaseModel;
use core\dao\deal\DealModel;
use core\enum\ReserveEnum;

class UserReservationModel extends BaseModel
{
    /**
     * 根据主键ID获取预约记录
     * @param int $id 预约ID
     * @return \libs\db\model
     */
    public function getReservationById($id)
    {
        return $this->findBy('`id`=:id', '*', array(':id'=>intval($id)), true);
    }

   /**
     * 根据主键ID，获取用户的预约记录
     * @param int $id 预约ID
     * @param int $userId 用户ID
     * @return \libs\db\model
     */
    public function getUserReserveById($id, $userId)
    {
        return $this->findBy('`id`=:id AND `user_id`=:user_id', '*', array(':id'=>intval($id), ':user_id' => $userId), true);
    }

    /**
     * 根据用户ID，获取用户的预约记录列表
     * @param int $userId 用户ID
     * @param int $reserveStatus 预约状态
     * @return \libs\db\model
     */
    public function getUserReserveList($userId, $reserveStatus = -1, $page = 0, $pageSize = 0, $dealTypeList = [])
    {
        $orderBy = ' ORDER BY `reserve_status` ASC,`start_time` DESC ';
        $limit = ($page > 0 && $pageSize > 0) ? sprintf(' LIMIT %d,%d ', (($page - 1) * $pageSize), $pageSize) : '';
        $whereParams = $reserveStatus >= 0 ? '`user_id`=:user_id AND `reserve_status`=:reserve_status' : '`user_id`=:user_id';
        $whereValues = $reserveStatus >= 0 ? array(':user_id'=>intval($userId), ':reserve_status'=>intval($reserveStatus)) : array(':user_id'=>intval($userId));
        if (!empty($dealTypeList)) {
            $whereParams .= sprintf(' AND `deal_type` in (%s)', implode(',', $dealTypeList));
        }
        return $this->findAllViaSlave($whereParams . $orderBy . $limit, true, '*', $whereValues);
    }

    /**
     * 根据预约状态，获取符合条件的预约记录列表
     * @param int $reserveStatus 预约状态
     * @return \libs\db\model
     */
    public function getReserveListByReserveStatus($reserveStatus = ReserveEnum::RESERVE_STATUS_ING)
    {
        intval($reserveStatus) <= 0 && $reserveStatus = ReserveEnum::RESERVE_STATUS_ING;
        return $this->findAllViaSlave('`reserve_status`=:reserve_status', true, '*', array(':reserve_status'=>intval($reserveStatus)));
    }

    /**
     * 根据期限获取预约记录列表
     * @param int $userId 用户ID
     * @param int $reserveStatus 预约状态
     * @param int $deadline 期限
     * @param int $deadlineUnit 期限单位
     * @return \libs\db\model
     */
    public function getUserReserveListByDeadline($userId, $reserveStatus, $deadline, $deadlineUnit)
    {
        $orderBy = ' ORDER BY id ASC ';
        $limit = '';
        $whereParams = '`user_id`=:user_id AND `reserve_status`=:reserve_status AND `invest_deadline`=:invest_deadline AND `invest_deadline_unit`=:invest_deadline_unit';
        $whereValues = [
            ':user_id'                  => intval($userId),
            ':reserve_status'           => intval($reserveStatus),
            ':invest_deadline'          => intval($deadline),
            ':invest_deadline_unit'     => intval($deadlineUnit),
        ];
        if ($reserveStatus == ReserveEnum::RESERVE_STATUS_ING) {
            $whereParams .= ' AND `end_time` > :end_time';
            $whereValues[':end_time'] = time();
        }
        return $this->findAllViaSlave($whereParams . $orderBy . $limit, true, '*', $whereValues);
    }

    /**
     * 创建用户预约投标记录
     * @param int $userId 用户ID
     * @param int $reserveAmountCent 预约金额，单位为分
     * @param int $investDeadline 投资期限
     * @param int $expire 预约有效期
     * @param float $investRate 投资预期年化
     * @param string $inviteCode 邀请码
     * @param int $investDeadlineUnit 投资期限单位(1:天2:月)
     * @param int $expireUnit 预约有效期单位(1:小时2:天)
     * @param int $reserveReferer 预约来源
     * @param int $siteId 分站id
     * @param array $extraInfo 额外信息
     * @param int $discountId 投资券id
     * @param int $dealType 贷款类型
     * @param int $rateFactor 年化收益折算系数
     * @return boolean
     */
    public function createUserReserve($userId, $reserveAmountCent, $investDeadline, $expire, $investRate = 0, $inviteCode = '', $investDeadlineUnit = 1, $expireUnit = 1, $reserveReferer = 1, $siteId = 1, $extraInfo = [], $discountId = 0, $dealType = 0, $rateFactor = 1, $loantype = 0)
    {
        try {
            if (!isset(ReserveEnum::$investDeadLineUnitConfig[$investDeadlineUnit]) OR !isset(ReserveEnum::$expireUnitConfig[$expireUnit]))
            {
                throw new \Exception('投资期限单位或预约有效期单位无效');
            }
            // 预约提交时间
            $startTime = time();
            // 预约有效期单位(1:小时2:天)
            switch ($expireUnit)
            {
                case ReserveEnum::EXPIRE_UNIT_DAY:
                    $endTime = $startTime + (86400 * intval($expire));
                    break;
                case ReserveEnum::EXPIRE_UNIT_HOUR:
                default:
                    $endTime = $startTime + (3600 * intval($expire));
                    break;
            }
            $this->user_id = intval($userId);
            $this->reserve_status = ReserveEnum::RESERVE_STATUS_ING;
            $this->reserve_amount = intval($reserveAmountCent); // 预约金额，单位为分
            $this->start_time = $startTime; // 预约提交时间
            $this->end_time = $endTime; // 预约截止时间
            $this->invest_deadline = $investDeadline; // 投资期限
            $this->invest_deadline_unit = $investDeadlineUnit; // 投资期限单位(1:天2:月)
            $this->invest_rate = $investRate; // 投资预期年化
            $this->expire = intval($expire); // 预约有效期
            $this->expire_unit = $expireUnit; // 预约有效期单位(1:小时2:天)
            $this->invite_code = $inviteCode; // 邀请码
            $this->reserve_referer = (int)$reserveReferer; //预约来源(1:APP|2:M|3:Admin)
            $this->site_id = (int)$siteId; //分站id
            //额外数据
            if (!empty($extraInfo)) {
                $this->extra_info = json_encode($extraInfo);
            }
            $this->rate_factor = $rateFactor; //年化收益折算系数
            $this->deal_type = (int)$dealType; //贷款类型
            $this->discount_id = (int)$discountId; //投资券id
            $this->discount_status = !empty($discountId) ? ReserveEnum::DISCOUNT_STATUS_PROCESSING : ReserveEnum::DISCOUNT_STATUS_DEFAULT; //投资券使用状态
            $this->loantype = (int)$loantype; //还款方式
            $this->db->startTrans();
            $result = $this->save();
            if(!$result) {
                throw new \Exception('insert user_reservation failed');
            }
            $commitResult = $this->db->commit();
            if(!$commitResult) {
                throw new \Exception('commit user_reservation failed');
            }
            return array('ret'=>true, 'id'=>$this->id);
        } catch (\Exception $e) {
            $this->db->rollback();
            return array('ret'=>false, 'errorMsg' => '网络错误，请重试');
        }
    }

    /**
     * 通过预约ID、用户ID，取消预约记录(预约成功+预约中的可取消预约)
     * @param int $id
     * @param int $userId
     * @return boolean
     */
    public function cancelUserReserveById($id, $userId)
    {
        if ($id <= 0 || $userId <= 0) {
            return false;
        }
        $this->updateBy(
            array(
                'reserve_status' => ReserveEnum::RESERVE_STATUS_END,
                'update_time' => time(),
            ),
            sprintf('`id`=%d AND `user_id`=%d AND `reserve_status`=%d AND `proc_status`=%d', intval($id), intval($userId), ReserveEnum::RESERVE_STATUS_ING, ReserveEnum::PROC_STATUS_NORMAL)
        );
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 通过用户ID，批量取消某用户的预约记录
     * @param int $userId
     * @return boolean
     */
    public function cancelUserReserveByUserId($userId)
    {
        if ($userId <= 0) {
            return false;
        }
        $this->updateBy(
            array(
                'reserve_status' => ReserveEnum::RESERVE_STATUS_END,
                'update_time' => time(),
            ),
            sprintf('`user_id`=%d AND `reserve_status`=%d AND `proc_status`=%d', intval($userId), ReserveEnum::RESERVE_STATUS_ING, ReserveEnum::PROC_STATUS_NORMAL)
        );
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 通过预约ID列表，批量取消某用户的预约记录
     * @param int $userId
     * @param array $reserveIds
     * @return boolean
     */
    public function cancelUserReserveByIds($userId, $reserveIds)
    {
        if ($userId <= 0 || empty($reserveIds)) {
            return false;
        }
        return $this->updateBy(
            array(
                'reserve_status' => ReserveEnum::RESERVE_STATUS_END,
                'update_time' => time(),
            ),
            sprintf('`id` IN (%s) AND `user_id`=%d AND `reserve_status`=%d AND `proc_status`=%d AND `end_time` < UNIX_TIMESTAMP()', join(',', $reserveIds), intval($userId), ReserveEnum::RESERVE_STATUS_ING, ReserveEnum::PROC_STATUS_NORMAL)
        );
    }

    /*
     * 按批获取用户预约记录列表
     * @param int $endTime
     * @param int $offset
     * @param int $pageSize
     * @return array
     */
    public function getUserReserveListByBatch($endTime, $offset = 0, $pageSize = 500)
    {
        return $this->findAll('`end_time`>=:end_time ORDER BY id ASC LIMIT :offset, :page_size', true, '*', array(':end_time'=>intval($endTime), ':offset'=>intval($offset), ':page_size'=>intval($pageSize)));
    }

    /*
     * 获取用户预约记录列表 新
     * @param int $endTime
     * @param int $pageSize
     * @param int $reserveId 预约id
     * @param int $deadline 投资期限
     * @param int $deadlineUnit 投资期限的单位
     * @param int $dealType 借款类型
     * @param int $minLoanMoney 最低投资额 单位元
     * @param int $maxLoanMoney 最高投资额 单位元
     * @return array
     */
    public function getUserReserveListByLimit($endTime, $pageSize = 500, $reserveId = 0, $deadline = 0, $deadlineUnit = 0, $dealType = null, $minLoanMoney = 0, $maxLoanMoney = 0, $investRate = 0, $loantype = 0)
    {
        $whereParams = ' `end_time` >= :end_time  AND `reserve_status` = :reserve_status AND `proc_status` = :proc_status';
        $orderBy = ' ORDER BY id ASC ';
        $limit = ' LIMIT :page_size ';
        $whereValues = [':end_time'=>intval($endTime), ':page_size'=>intval($pageSize), ':reserve_status' => ReserveEnum::RESERVE_STATUS_ING, ':proc_status' => ReserveEnum::PROC_STATUS_NORMAL];
        if ($reserveId > 0) {
            $whereParams .= ' AND `id` > :id ';
            $whereValues[':id'] = $reserveId;
        }
        if ($deadline > 0 && $deadlineUnit > 0) {
            $whereParams .= ' AND `invest_deadline` = :deadline AND `invest_deadline_unit` = :deadline_unit ';
            $whereValues[':deadline'] = $deadline;
            $whereValues[':deadline_unit'] = $deadlineUnit;
        }
        if ($dealType !== null) {
            $whereParams .= ' AND `deal_type` = :deal_type ';
            $whereValues[':deal_type'] = $dealType;
        }
        if (bccomp($minLoanMoney, 0 ,2) === 1) {
            $whereParams .= ' AND `reserve_amount` - `invest_amount` >= :min_amount ';
            $whereValues[':min_amount'] = bcmul($minLoanMoney, 100);
        }
        if (bccomp($maxLoanMoney, 0 ,2) === 1) {
            $whereParams .= ' AND `reserve_amount` - `invest_amount` <= :max_amount ';
            $whereValues[':max_amount'] = bcmul($maxLoanMoney, 100);
        }
        if ($investRate > 0) {
            $whereParams .= ' AND invest_rate = :invest_rate';
            $whereValues[':invest_rate'] = sprintf("%.2f", $investRate);
        }
        if ($loantype > 0) {
            $whereParams .= sprintf(' AND loantype in (0, %s)', $loantype);
        }

        return $this->findAll($whereParams . $orderBy . $limit, true, '*', $whereValues);
    }

    /**
     * 更新预约记录
     * @param int $id
     * @param int $userId
     * @param int $reserveAmount 预约金额 分
     * @param int $investAmount 投资金额 分
     * @param int $newInvestAmount 新投资 分
     * @return boolean
     */
    public function updateUserReservation($id, $userId, $reserveAmount, $investAmount, $newInvestAmount)
    {
        $sql = sprintf('UPDATE ' . $this->tableName() . ' SET `invest_count` = `invest_count` + 1, `invest_amount` = `invest_amount` + %d, `update_time` = %d', intval($newInvestAmount), time());
        if ($reserveAmount == $investAmount + $newInvestAmount) {
            $sql .= sprintf(', `reserve_status` = %d', ReserveEnum::RESERVE_STATUS_END);
        }
        $sql .= sprintf(' WHERE `id` = %d AND `user_id` = %d AND `reserve_status` = %d AND `invest_amount` < `reserve_amount` AND `invest_amount` = %d', intval($id), intval($userId), ReserveEnum::RESERVE_STATUS_ING, intval($investAmount));
        return $this->updateRows($sql); //返回影响的行数
    }

    /**
     * 更新投资金额，新
     * @param int $id
     * @param int $userId
     * @param int $investAmount 投资金额 分
     * @param int $procId 处理订单号
     * @return boolean
     */
    public function updateInvestAmount($id, $userId, $investAmount, $procId) {
        //更新预约金额，处理状态置为0
        $sql = sprintf('UPDATE ' . $this->tableName() . ' SET `invest_count` = `invest_count` + 1, `invest_amount` = `invest_amount` + %d, `proc_status` = %d, `update_time` = %d, `proc_id` = 0', intval($investAmount), ReserveEnum::PROC_STATUS_NORMAL, time());
        $sql .= sprintf(' WHERE `id` = %d AND `user_id` = %d AND `reserve_status` = %d AND `invest_amount` + %d <= `reserve_amount` AND `proc_status` = %d AND `proc_id` = %d', intval($id), intval($userId), ReserveEnum::RESERVE_STATUS_ING, intval($investAmount), ReserveEnum::PROC_STATUS_ING, $procId);
        $r = $this->db->query($sql);
        if ($r === false || !$this->db->affected_rows()) {
            return false;
        }

        //更新预约状态
        $UserReservation = $this->find($id);
        if ($UserReservation['invest_amount'] == $UserReservation['reserve_amount']) {
            $UserReservation->reserve_status = ReserveEnum::RESERVE_STATUS_END;
            if ($UserReservation->save() === false) {
                return false;
            }
        }
        return true;
    }

    /*
     * 按批获取过期的用户预约记录列表
     * @param int $endTime
     * @param int $offset
     * @param int $pageSize
     * @return array
     */
    public function getExpiredUserReserveListByBatch($endTime, $offset = 0, $pageSize = 500)
    {
        return $this->findAll('`end_time`<=:end_time ORDER BY id ASC LIMIT :offset, :page_size', true, '*', array(':end_time'=>intval($endTime), ':offset'=>intval($offset), ':page_size'=>intval($pageSize)));
    }

    /*
     * 按批获取过期的用户预约记录列表 新
     * @param int $endTime
     * @param int $pageSize
     * @return array
     */
    public function getExpiredUserReserveListByLimit($endTime, $pageSize = 500, $reserveId = 0)
    {
        $whereParams = ' `end_time` >= :begin_time AND `end_time` <= :end_time  AND `reserve_status` = :reserve_status AND `proc_status` = :proc_status ';
        $orderBy = ' ORDER BY id ASC ';
        $limit = ' LIMIT :page_size ';
        $whereValues = [
            ':begin_time' => intval($endTime) - 30 * 24 * 60 * 60, //最近30天的记录
            ':end_time'=>intval($endTime),
            ':page_size'=>intval($pageSize),
            ':reserve_status' => ReserveEnum::RESERVE_STATUS_ING,
            ':proc_status' => ReserveEnum::PROC_STATUS_NORMAL
        ];
        if ($reserveId > 0) {
            $whereParams .= ' AND `id` > :id ';
            $whereValues[':id'] = $reserveId;
        }
        return $this->findAll($whereParams . $orderBy . $limit, true, '*', $whereValues);
    }

    /*
     * 预约统计
     * @return array
     */
    public function getReservationSummary() {
        //预约人数
        $sql = 'SELECT COUNT(DISTINCT(user_id)) FROM `firstp2p_user_reservation` WHERE reserve_status = 0 AND end_time >= unix_timestamp()';
        $reserveUsers = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne($sql);

        //有效预约人数、有效预约金额
        $sql = 'SELECT SUM(count) AS total,SUM(userMoney) AS sumUserMoney FROM (SELECT COUNT(DISTINCT(ur.user_id)) AS count, SUM(LEAST((ur.reserve_amount-ur.invest_amount)/100, u.money)) AS userMoney FROM `firstp2p_user_reservation` AS ur LEFT JOIN `firstp2p_user` AS u ON u.id = ur.user_id WHERE ur.reserve_status = 0 AND ur.end_time >= unix_timestamp() AND u.money >= 100 AND (ur.reserve_amount - ur.invest_amount) >= 10000 GROUP BY ur.user_id) AS t';
        $effectReserveData = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow($sql);

        return array(
            'reserveUsers'          => $reserveUsers,
            'effectReserveUsers'    => !empty($effectReserveData['total']) ? $effectReserveData['total'] : 0,
            'effectReserveAmounts'  => !empty($effectReserveData['sumUserMoney']) ? format_price($effectReserveData['sumUserMoney'], true) : '0.00元',
        );
    }

    /**
     * 获取当日预约总人数、总的预约投资金额
     */
    public function getReservationStatisticsForCard($deadline = 21, $unit = 1) {
        // 当日预约总人数
        $beginTime = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endTime = mktime(0, 0, 0, date('m'), date('d')+1, date('Y'));

        $countSql = sprintf('SELECT COUNT(*) FROM `%s` ', $this->tableName());

        $sumSql = sprintf('SELECT SUM(ROUND(invest_amount/100, 2)) AS sumInvestMoney FROM `%s`', $this->tableName());

        if ($deadline && $unit) {
            $deadlineSql = sprintf(" invest_deadline = %d AND invest_deadline_unit = %d ", $deadline, $unit);
            $countSql = $countSql.' WHERE '.$deadlineSql;
            $sumSql = $sumSql.' WHERE '.$deadlineSql;
        }
        $reserveUserCountToday = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne($countSql);

        // 总的预约投资金额
        $reserveSumInvestMoney = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne($sumSql);

        return array(
            'reserveUserCountToday' => $reserveUserCountToday ?: '',
            'reserveSumInvestMoney' => $reserveSumInvestMoney ?: '',
        );
    }

    /**
     * 获取当日预约总人数、总的预约投资金额
     */
    public function getReserveStats($investLine, $investUnit, $dealType, $investRate, $loantype) {
        $stats = [
            'reserveUserCountToday' => 0,
            'reserveSumInvestMoney' => 0,
        ];
        if (empty($investLine) || empty($investUnit)) {
            return $stats;
        }
        $sumSql = sprintf('SELECT COUNT(*) AS investCount, SUM(ROUND(invest_amount/100, 2)) AS sumInvestMoney FROM `%s` WHERE invest_deadline = %d AND invest_deadline_unit = %d'
            , $this->tableName(), $investLine, $investUnit);

        if (isset($dealType)) {
            $sumSql .= sprintf(' AND deal_type = %d', $dealType);
        }
        if (!empty($loantype)) {
            $sumSql .= sprintf(' AND loantype in (%d, 0)', $loantype);
        }
        if ($investRate > 0) {
            $sumSql .= sprintf(' AND invest_rate = %s', $investRate);
        }
        $sum = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow($sumSql);
        if (!empty($sum)) {
            $stats['reserveUserCountToday'] = $sum['investCount'];
            $stats['reserveSumInvestMoney'] = $sum['sumInvestMoney'];
        }
        return $stats;
    }

    /*
     * 根据预约ID、咨询机构ID，查询符合条件的累计投资总金额
     * @return int
     */
    public function getReservationSumMoneyById($userReserveInfo, $advisoryId = 0) {
        if (!empty($advisoryId)) {
            $condition[] = sprintf('d.advisory_id=%d', $advisoryId);
        }
        if ($userReserveInfo['invest_deadline_unit'] == ReserveEnum::INVEST_DEADLINE_UNIT_DAY) {
            $condition[] = sprintf('d.loantype=5 AND d.`repay_time`=%d', $userReserveInfo['invest_deadline']);
        } else {
            $condition[] = sprintf('d.loantype!=5 AND d.`repay_time`=%d', $userReserveInfo['invest_deadline']);
        }
        // 该笔预约的累计投资总金额
        $sql = sprintf('SELECT d.id AS dealId,SUM(ROUND(dl.money, 2)) AS investSumMoney FROM `firstp2p_deal_load` AS dl 
                LEFT JOIN `firstp2p_reservation_deal_load` AS rdl ON rdl.load_id=dl.id 
                LEFT JOIN `firstp2p_deal` AS d ON d.id=dl.deal_id 
                WHERE rdl.reserve_id=%d AND %s', $userReserveInfo['id'], join(' AND ', $condition));
        return \libs\db\Db::getInstance('firstp2p', 'slave')->getRow($sql);
    }
    
    /**
     * 查询预约金额
     * @param int $investDeadline  时间期限
     * @param int $investDeadlineUnit  时间期限单位
     * @return array 
     */
    public function getReservationInfo($investDeadline,$investDeadlineUnit) {
        $sql = sprintf('SELECT SUM(least((ur.reserve_amount-ur.invest_amount)/100, u.money)) AS amount FROM 
            `firstp2p_user_reservation` AS ur LEFT JOIN `firstp2p_user` AS u ON u.id=ur.user_id 
            WHERE reserve_status=0 AND end_time > unix_timestamp() AND u.money >= 100 AND (ur.reserve_amount-ur.invest_amount)>=10000 
            AND ur.invest_deadline = %d AND ur.invest_deadline_unit = %d',$investDeadline,$investDeadlineUnit);
        $ret = \libs\db\Db::getInstance('firstp2p', 'slave')->getRow($sql);
        return $ret;
    }

    /**
     * 更新为处理中
     * 锁定资源
     */
    public function updateProcessing($id, $userId, $investAmount, $procId) {
        if ($id <= 0 || $userId < 0) {
            return false;
        }
        $this->updateBy(
            array(
                'proc_status' => ReserveEnum::PROC_STATUS_ING,
                'proc_id' => $procId,
                'update_time' => time(),
            ),
            sprintf('`id`=%d AND `user_id`=%d AND `invest_amount` + %d <= `reserve_amount` AND `reserve_status`=%d AND `proc_status`=%d AND `proc_id` = 0', intval($id), intval($userId), $investAmount, ReserveEnum::RESERVE_STATUS_ING, ReserveEnum::PROC_STATUS_NORMAL)
        );
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /**
     * 恢复处理状态
     * 释放资源
     */
    public function restoreProcStatus($id, $userId, $procId) {
        if ($id <= 0 || $userId < 0) {
            return false;
        }
        $this->updateBy(
            array(
                'proc_status' => ReserveEnum::PROC_STATUS_NORMAL,
                'proc_id' => 0,
                'update_time' => time(),
            ),
            sprintf('`id`=%d AND `user_id`=%d AND `proc_status`=%d AND `proc_id`=%d', intval($id), intval($userId), ReserveEnum::PROC_STATUS_ING, $procId)
        );
        return $this->db->affected_rows() > 0 ? true : false;
    }

    /*
     * 是否包含优惠券
     * @return bool
     */
    public function hasDiscount($discountId) {
        $sql = sprintf('SELECT COUNT(*) FROM `firstp2p_user_reservation` WHERE reserve_status = 0 AND discount_id = %d', $discountId);
        $ret = \libs\db\Db::getInstance('firstp2p', 'slave')->getOne($sql);
        return $ret > 0 ? true : false;
    }

    /**
     * 更新投资券使用状态
     * @return bool
     */
    public function updateDiscountStatus($id, $discountStatus) {
        if (empty($id) || empty($discountStatus)) {
            return false;
        }
        return $this->updateBy(
            array(
                'discount_status' => $discountStatus,
                'update_time' => time(),
            ),
            sprintf('`id` = %d AND discount_status = %d', intval($id), ReserveEnum::DISCOUNT_STATUS_PROCESSING)
        );
    }

    /**
     * 获取最新预约动态
     */
    public function getNewReserve($money, $limit, $limitTime = 86400)
    {
        $money = intval($money) * 100;
        $limit = intval($limit);
        $createTimeBegin = time() - $limitTime;
        $condition = " reserve_amount > {$money} AND start_time >= {$createTimeBegin} ORDER BY id DESC LIMIT {$limit}";
        return $this->findAllViaSlave($condition, true, '*');
    }

}
