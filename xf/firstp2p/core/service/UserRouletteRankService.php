<?php
/**
 * UserRouletteRankService class file.
 *
 * @author 王传路<wangchuanlu@ucfgroup.com>
 **/

namespace core\service;

use core\dao\UserRouletteRankModel;
/**
 * undocumented class
 *
 * @packaged default
 * @author 王传路<wangchuanlu@ucfgroup.com>
 **/
class UserRouletteRankService extends BaseService {

    const ROULETTE_RANK_KEY = 'firstp2p_roulette_';//摇奖Key
    const LIST_RANK_NUM = 10;
    const LIST_RANK_HASH_KEY = 'rank_list';

    /**
     * 更新用户投资额
     * @param int $userId 用户id
     * @param unknown $userName 用户名
     * @param unknown $userDealName 用户投标名称
     * @param string $money 年化投资额
     * @return boolean
     */
    public function updateUserMoney($userId,$userName,$userDealName, $money) {
        if($money <= 0) {//金额不正确
            return false;
        }

        $now = time();
        $todayBeginTime = strtotime(date('Y-m-d'));

        $sql = "INSERT INTO
            %s (
            `user_id`,
            `date`,
            `money`,
            `user_name`,
            `user_deal_name`,
            `create_time`,
            `update_time`)
        VALUES
            (%d,%d,%s,'%s','%s',%d,%d)
        ON DUPLICATE KEY UPDATE
            `money` = `money` + VALUES(money),
            `update_time` = VALUES(update_time)";

        $sql = sprintf($sql,
                UserRouletteRankModel::instance()->tableName(),
                $userId,
                $todayBeginTime,
                $money,
                $userName,
                $userDealName,
                $now,
                $now);

        $GLOBALS['db']->query($sql);

        return $GLOBALS['db']->affected_rows() > 1 ? true : false;
    }

    /**
     * 获取前30位用户排名以及 自己的排名
     * @param int $userId 用户Id
     * @param string $rank_date 排名日期
     * @return multitype:multitype: number |multitype:multitype: number mixed
     */
    public function getRanks($userId = -1,$rank_date = '') {
       $ret = array(
            'rankList' => array(),
            'userRank' => 0,
        );
       $redis_key = $this->_getRedisKey($rank_date);

       $redis = \SiteApp::init()->dataCache->getRedisInstance();
       if ($redis  === NULL) {
           return $ret;
       }

       $list_info = $redis->hget($redis_key,self::LIST_RANK_HASH_KEY);
       if(null == $list_info && '' != $rank_date) {
           $this->syncRedisRanks(strtotime($rank_date));
       }
       if($list_info) {
           $ret['rankList'] = json_decode($list_info,true);
       }

       if($userId > -1) {
           $user_info = $redis->hget($redis_key,$userId);
           if($user_info) {
               $ret['userRank'] = json_decode($user_info,true);
           }
       }
        return $ret;
    }

    /**
     * 同步抽奖排名到redis上
     * @return boolean
     */
    public function syncRedisRanks($todayBeginTime = 0) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis  === NULL) {
            return false;
        }
        $redis_key = $this->_getRedisKey();

        if ($todayBeginTime == 0) {
            $todayBeginTime = strtotime(date('Y-m-d'));
            $redis_key = $this->_getRedisKey();
        } else {
            $redis_key = $this->_getRedisKey(date('Y-m-d',$todayBeginTime));
        }

        $sql = 'SELECT `user_id`,`money`,`user_name`,`user_deal_name` FROM %s WHERE `date` = %d ORDER BY `money` DESC, `update_time` ASC';
        $sql = sprintf($sql, UserRouletteRankModel::instance()->tableName(),$todayBeginTime);

        $list = array();
        $userRanks = $GLOBALS['db']->getAll($sql);

        $i = 1;
        foreach ($userRanks as $userRank) {
           $rankInfo = array();

           $rankInfo['rank'] = $i;
           $rankInfo['money'] = $userRank['money'];
           if($i <= self::LIST_RANK_NUM) {
               $rankInfoFull = $rankInfo;
               $rankInfoFull['name'] = user_name_format($userRank['user_name']) . '('.($userRank['user_deal_name']).')';
               $list[] = $rankInfoFull;
           }
           $redis->hset($redis_key,$userRank['user_id'],json_encode($rankInfo));
           $i++ ;
        }
        $redis->hset($redis_key,self::LIST_RANK_HASH_KEY,json_encode($list));
        $redis->expire($redis_key,30*86400);

        return true;
    }

    /**
     * 获取 redisKey
     * @return string
     */
    private function _getRedisKey($date = '') {
        if('' == $date) {
            $date = date('Ymd');
        }
        return self::ROULETTE_RANK_KEY.$date;
    }

} // END class
