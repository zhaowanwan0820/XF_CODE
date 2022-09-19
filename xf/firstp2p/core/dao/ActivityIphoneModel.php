<?php

/**
 * Description of class
 *
 * @author yutao <yutao@ucfgroup.com>
 */

namespace core\dao;

class ActivityIphoneModel extends BaseModel {

    /**
     * 根据当天时间戳获得符合抽奖条件的用户
     * @param  $time
     * @return type
     */
    public function findUsersByTime($time) {
        $condition = sprintf("`stat_time` = '%d'", $this->escape($time));
        return $this->findAll($condition);
    }

    public function findUsersByStatus($is_win = 1) {
        $condition = sprintf("`is_win` = '%d'", $this->escape($is_win));
        return $this->findAll($condition);
    }

    public function getCount($time) {
        $condition = sprintf("`stat_time` = '%d'", $this->escape($time));
        return $this->count($condition);
    }

    public function delUserByTime($time) {
        $sql = sprintf("DELETE FROM " . $this->tableName() . " WHERE `stat_time` = '%d'", $this->escape($time));
        return $this->execute($sql);
    }

    public function getLastUserTime() {
        $condition = "1=1 order by stat_time DESC limit 1";
        return $this->findBy($condition, "stat_time");
    }

    public function updateUserWin($time, $num, $status = 1) {
        $sql = sprintf("UPDATE " . $this->tableName() . " SET `is_win` = '%d' WHERE `stat_time` = '%d' and `user_lottery_num` = '%s' ", $this->escape($status), $this->escape($time), $this->escape($num));
       
        var_dump($sql);
        return $this->updateRows($sql);
    }

    public function insertUserList($userList) {

        $sql = "INSERT INTO " . $this->tableName() . "(`user_lottery_num`,`user_id`,`user_name`,`deal_time`,`create_time`,`stat_time`) VALUES ";
        foreach ($userList as $value) {
            extract($value);
            $time = time();
            $statTime = strtotime(date("Y-m-d"));
            $sql .= "('{$user_lottery_num}','{$user_id}','{$user_name}','{$deal_time}','{$time}','{$statTime}'),";
        }
        $sql = rtrim($sql, ',');
        return $this->execute($sql);
    }

    public function getLottery($limit) {
        $sql = "SELECT * FROM " . DB_PREFIX . "lottery ORDER BY stat_date DESC";
        if ($limit > 0) {
            $sql .= " limit {$limit} ";
        }
        return $this->execute($sql);
    }

}
