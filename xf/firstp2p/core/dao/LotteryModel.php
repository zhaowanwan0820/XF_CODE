<?php

/**
 * Description of class
 *
 * @author yutao <yutao@ucfgroup.com>
 */

namespace core\dao;

class LotteryModel extends BaseModel {

    public function getLottery($limit) {
        $condition = sprintf(" 1=1 ORDER BY stat_date DESC");
        if ($limit > 0) {
            $condition .= " limit {$limit}";
        }
        return $this->findAll($condition);
    }

    public function insertLottery($lotteryNum, $stat_time) {
        $time = time();
        $sql = sprintf("INSERT INTO " . DB_PREFIX . "lottery (`lottery_num`,`create_time`,`stat_date`) VALUES ('%s','%d','%d')", $this->escape($lotteryNum), $this->escape($time), $this->escape($stat_time));
        return $this->execute($sql);
    }

}
