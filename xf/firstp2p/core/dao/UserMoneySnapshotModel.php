<?php
/**
 * 用户余额快照表
 * @date 2017-09-07
 * @author guofeng3 <guofeng3@ucfgroup.com>
 */

namespace core\dao;

class UserMoneySnapshotModel extends BaseModel {
    /**
     * 连firstp2p_payment库
     */
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('firstp2p_payment');
        parent::__construct();
    }

    /**
     * 批量获取用户指定日期范围的余额快照
     * @param int $userId
     * @param int $moneyDateStart
     * @param int $moneyDateEnd
     * @return array
     */
    public function getMoneySnapshotList($userId, $moneyDateStart, $moneyDateEnd)
    {
        if (empty($userId) || empty($moneyDateStart)) {
            return false;
        }
        $condition = sprintf("`user_id`='%d' AND `money_date` BETWEEN '%d' AND '%d' ORDER BY `id` DESC", (int)$userId, (int)$moneyDateStart, (int)$moneyDateEnd);
        return $this->findAll($condition, true);
    }

    /**
     * 添加用户余额快照
     * @param int $userId 用户ID
     * @param int $money 网信余额
     * @param int $supervisionMoney 网贷余额
     * @param int $bonusMoney 红包余额
     * @param int $moneyDate 哪天的余额
     * @return boolean
     */
    public function addSnapshot($userId, $money, $supervisionMoney, $bonusMoney, $moneyDate)
    {
        if (empty($userId)) {
            return false;
        }

        $data = array(
            'user_id'           => (int)$userId,
            'money'             => (int)$money,
            'supervision_money' => (int)$supervisionMoney,
            'bonus_money'       => (int)$bonusMoney,
            'money_date'        => (int)$moneyDate,
            'create_time'       => time(),
        );
        $this->setRow($data);

        if (!$this->insert()) {
            return false;
        }
        return $this->db->insert_id();
    }

    /**
     * 查询用户闲置资金
     * @param int $userId 用户ID
     * @param int $remindDay 用户设置的提醒天数，不能超过30天
     */
    public function getUserIdleMoney($userId, $remindDay)
    {
        // 查询开始日期
        $startDate = date('Ymd', strtotime(sprintf('-%d day', $remindDay)));
        // 查询截止日期
        $endDate = date('Ymd');
        // 批量获取用户指定日期范围的余额快照
        $userMoneySnapshotList = $this->getMoneySnapshotList($userId, $startDate, $endDate);
        // 没有闲置资金
        if (empty($userMoneySnapshotList)) {
            return [];
        }

        // 按日期整理数据
        return $this->_reorganizeList($userMoneySnapshotList);
    }

    /**
     * 按日期整理数据
     * @param array $list
     */
    private function _reorganizeList($list)
    {
        if (empty($list)) {
            return [];
        }

        $result = [];
        foreach ($list as $item) {
            if (!empty($result[$item['money_date']])) {
                continue;
            }
            $result[$item['money_date']] = $item;
        }
        unset($list);
        return $result;
    }
}