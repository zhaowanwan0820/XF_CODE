<?php

/**
 * 红包账户
 */
namespace core\service\bonus;

use core\dao\BonusModel;
use core\dao\BonusUserModel;
use core\service\BonusService;

/**
 * 红包账户
 */
class BonusUser {

    /**
     * 缓存红包信息的key
     */
    const BONUS_ACCOUNT = 'bonus_user';
    const BONUS_CURSOR_ACCOUNT = 'bonus_cursor_user';

    /**
     * 获取用户的红包账户信息
     */
    public function getUserByUid($user_id) {
        $user_id = intval($user_id);
        if ($user_id  <= 0) {
            return false;
        }
        // $key = self::BONUS_ACCOUNT . '_'. $user_id;
        // $result = \SiteApp::init()->cache->get($key);
        // if (empty($result)) {
        //     $result = BonusUserModel::instance()->getUser($user_id);
        //     \SiteApp::init()->cache->set($key, $result, 86400);
        // }

        // if (empty($result)) {
        //     $get_bonus_id = $send_bonus_id = 0;
        // } else {
        //     $get_bonus_id = intval($result['till_get_bonus_id']);
        //     $send_bonus_id = intval($result['till_send_bonus_id']);
        // }

        // $time = time();
        // if ($get_bonus_id) {
        //     $get_result = $this->getTotalBonus($user_id, 0, $time);
        //     $cursor_key = self::BONUS_CURSOR_ACCOUNT . '_'. $user_id;
        //     $cursor_result = \SiteApp::init()->cache->get($cursor_key);
        //     if ($cursor_result === false) {
        //         $cursor_result = $this->getTotalBonus($user_id, 0, $time, true);
        //         \SiteApp::init()->cache->set($cursor_key, $cursor_result, 86400);
        //     }
        //     $get_result['get_used_count'] = $get_result['get_used_count'] + $cursor_result['get_used_count'];
        //     $get_result['get_used_money'] = $get_result['get_used_money'] + $cursor_result['get_used_money'];
        //     $get_result['get_expired_count'] = $get_result['get_expired_count'] + $cursor_result['get_expired_count'];
        //     $get_result['get_expired_money'] = $get_result['get_expired_money'] + $cursor_result['get_expired_money'];
        //     $result['get_used_count'] = $result['get_used_money'] = $result['get_expired_money'] = $result['get_expired_count'] = 0;
        // } else {
        //     $get_result = $this->getTotalBonus($user_id, $get_bonus_id, $time);
        // }

        // $send_result = $this->getTotalBonusSend($user_id, $send_bonus_id, $time);

        // $return = $this->getBonusData($get_result, $send_result, $result);
        // $bonus_service = new BonusService();
        // $result = $bonus_service->get_useable_money($user_id);
        // $return['get_unused_money'] = $result['money'];

        $bonus_service = new BonusService();
        $userInfo = $bonus_service->getUserBonusInfo($user_id);

        $send_result = $this->getTotalBonusSend($user_id, 0);
        $return = array(
            'get_used_money' => $userInfo['usedMoney'] ?: 0,
            'get_used_count' => 0,
            'get_expired_money' => $userInfo['expiredMoney'] ?: 0,
            'get_expired_soon' => $userInfo['expireSoon']['money'] ?: 0,
            'get_expired_date' => $userInfo['expireSoon']['expireDate'] ?: '',
            'get_expired_count' => 0,
            'by_get_money' => 0,
            'by_get_count' => $send_result['by_get_count'] ?: 0,
            'by_used_money' => 0,
            'by_used_count' => $send_result['by_used_count'] ?: 0,
            'get_unused_money' => $userInfo['usableMoney'] ?: 0,
        );

        return $return;
    }

    /**
     * 更新账户信息
     */
    public function updateUserByUid($user_id, $data) {
        $result = BonusUserModel::instance()->updateUser($user_id, $data);
        if ($result) {
            $key = self::BONUS_ACCOUNT . '_'. $user_id;
            $result = \SiteApp::init()->cache->delete($key);
        }
        return $result;
    }

    /**
     * 生成红包账户
     */
    public function saveUserByUid($user_id, $data) {
        $data['user_id'] = $user_id;
        return BonusUserModel::instance()->saveUser($data);
    }

    /**
     * 根据时间与红包ID获取账户信息
     */
    public function getTotalBonus($user_id, $till_time, $created_at = 0, $is_hash = false) {

        $table = DB_PREFIX.'bonus';
        if ($is_hash) {
            $table .= "_".self::getTableId($user_id);
        }
        //使用的红包数据汇总
        $sql = "SELECT SUM(money) AS total_money, COUNT(`owner_uid`) AS total_count FROM %s WHERE";
        $sql .= " owner_uid=%s && created_at >= %s && created_at < %s && status = 2 GROUP BY owner_uid";
        $used = BonusModel::instance()->findBySql(sprintf($sql, $table, intval($user_id), intval($till_time), intval($created_at)), array(), true);

        //过期的红包数据汇总
        $sql = "SELECT SUM(money) AS total_money, COUNT(`owner_uid`) AS total_count FROM %s WHERE";
        $sql .= " owner_uid=%s && created_at >= %s && created_at < %s && status = 1 && expired_at < %s GROUP BY owner_uid";
        $expired = BonusModel::instance()->findBySql(sprintf($sql, $table, intval($user_id), intval($till_time), intval($created_at), time()), array(), true);

        if (empty($used) && empty($expired)) {
            return array();
        }
        return array('get_used_money' => $used['total_money'], 'get_used_count' => $used['total_count'],
                'get_expired_money' => $expired['total_money'], 'get_expired_count' => $expired['total_count']);
    }

    /**
     * 根据时间与红包ID获取账户信息
     */
    public function getTotalBonusSend($user_id, $till_time, $created_at = 0) {

        // //领取的红包数据汇总
        // $sql = "SELECT SUM(money) AS total_money, COUNT(`sender_uid`) AS total_count FROM %s WHERE";
        // $sql .= " sender_uid=%s && created_at >= %s && created_at < %s && status > 0 GROUP BY sender_uid";
        // $get = BonusModel::instance()->findBySql(sprintf($sql, 'firstp2p_bonus', intval($user_id), intval($till_time), intval($created_at)), array(), true);

        // //使用的红包数据汇总
        // $sql = "SELECT SUM(money) AS total_money, COUNT(`sender_uid`) AS total_count FROM %s WHERE";
        // $sql .= " sender_uid=%s && created_at >= %s && created_at < %s && status = 2 GROUP BY sender_uid";
        // $used = BonusModel::instance()->findBySql(sprintf($sql, 'firstp2p_bonus', intval($user_id), intval($till_time), intval($created_at)), array(), true);

        // if (empty($used) && empty($get)) {
        //     return array();
        // }
        // return array('by_get_money' => $get['total_money'], 'by_get_count' => $get['total_count'],
        //         'by_used_money' => $used['total_money'], 'by_used_count' => $used['total_count']);

        $user_id = intval($user_id);
        $sql = "SELECT SUM(get_count) AS total_get_count, SUM(used_count) AS total_used_count FROM firstp2p_bonus_group WHERE user_id = {$user_id}";

        $res = BonusModel::instance()->findBySql($sql, [], true);
        return ['by_get_count' => $res['total_get_count'], 'by_used_count' => $res['total_used_count']];


    }

    /**
     * 整理数据
     */
    public function getBonusData($get_result, $send_result, $result) {
        $data = array(
            'get_used_money' => $result['get_used_money'] + $get_result['get_used_money'],
            'get_used_count' => $result['get_used_count'] + $get_result['get_used_count'],
            'get_expired_money' => $result['get_expired_money'] + $get_result['get_expired_money'],
            'get_expired_count' => $result['get_expired_count'] + $get_result['get_expired_count'],
            'by_get_money' => $result['by_get_money'] + $send_result['by_get_money'],
            'by_get_count' => $result['by_get_count'] + $send_result['by_get_count'],
            'by_used_money' => $result['by_used_money'] + $send_result['by_used_money'],
            'by_used_count' => $result['by_used_count'] + $send_result['by_used_count']
        );
        return $data;
    }

    public static function getTableId($user_id) {
        return $user_id % 32;
    }

    /**
     * checkRules
     *
     * @param mixed $uid
     * @param mixed $rules
     * @access public
     * @return boolean
     */
    public static function checkRules($uid, $rules) {
        if (empty($rules)) {
            return false;
        }
        $rules = explode(',',$rules);
        foreach ($rules as $rule) {
            if (is_numeric($rule)) {
                if (substr($uid, -strlen($rule)) === $rule) {
                    return true;
                }
                continue;
            }

            $section = explode('-', $rule);
            $len = strlen($section[0]);
            $left = intval($section[0]);
            $right = intval($section[1]);
            for ($i = $left; $i <= $right; $i++) {
                if (substr($uid, -$len) === str_pad($i, $len, "0", STR_PAD_LEFT)) {
                    return true;
                }
            }
        }
        return false;
    }
}
