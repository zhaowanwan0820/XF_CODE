<?php
/**
 * 用户预约与投标关系表
 * @date 2016-11-15
 * @author weiwei12@ucfgroup.com>
 */

namespace core\dao\reserve;

use core\dao\BaseModel;

class ReservationDealLoadModel extends BaseModel
{
    /**
     * 查询预约ID跟用户投标的关系列表
     * @param int $reserveId 用户预约ID
     * @return \libs\db\model
     */
    public function getInfoByReserveId($reserveId)
    {
        return $this->findAllViaSlave('`reserve_id`=:reserve_id', true, '*', array(':reserve_id'=>intval($reserveId)));
    }

    /**
     * 根据预约ID，获取任意一条预约投资数据
     * @param int $reserveId
     */
    public function getOneLoadByReserveId($reserveId)
    {
        return $this->findByViaSlave('`reserve_id`=:reserve_id', '*', array(':reserve_id'=>intval($reserveId)));
    }

    /**
     * 存在关系
     * @param int $reserveId
     * @param int $loadId
     */
    public function isExistRelation($reserveId, $loadId)
    {
        $result = $this->findBy('`reserve_id`=:reserve_id AND `load_id`=:load_id', '*', array(':reserve_id'=>intval($reserveId), ':load_id'=>intval($loadId)));
        return $result ? true : false;
    }

    /**
     * 添加用户预约投标关系
     * @param int $reserve_id 用户预约id
     * @param int $load_id 用户投标id
     * @return boolean
     */
    public function addRelation($reserveId, $loadId, $userId, $dealId, $investAmount)
    {
        $this->reserve_id = (int)$reserveId;
        $this->load_id = (int)$loadId;
        $this->user_id = (int)$userId;
        $this->deal_id = (int)$dealId;
        $this->invest_amount = (int)$investAmount;
        $this->create_time = time();
        try {
            $result = $this->insert();
            if(!$result) {
                throw new \Exception("insert reservation_deal_load failed");
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取交易列表
     * @param int $date 交易日期 yyyy-mm-dd
     */
    public function getList($date, $id = 0, $pageSize = 500)
    {
        $startTime = strtotime($date);
        $endTime = strtotime($date) + 86400;
        $whereParams = ' `create_time` >= :start_time AND `create_time` < :end_time';
        $orderBy = ' ORDER BY id ASC ';
        $limit = ' LIMIT :page_size ';
        $whereValues = [':start_time'=>intval($startTime), ':end_time'=>intval($endTime), ':page_size'=>intval($pageSize)];
        if ($id > 0) {
            $whereParams .= ' AND `id` > :id ';
            $whereValues[':id'] = $id;
        }
        return $this->findAllViaSlave($whereParams . $orderBy . $limit, true, '*', $whereValues);
    }
}
