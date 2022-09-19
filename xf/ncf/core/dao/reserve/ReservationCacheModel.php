<?php
/**
 * 预约相关缓存
 * @date 2017-07-05
 * @author weiwei12@ucfgroup.com>
 */

namespace core\dao\reserve;

use core\dao\BaseModel;
use core\enum\DealLoanRepayEnum;
use core\enum\DealLoadEnum;
use core\service\reserve\ReservationMatchService;

class ReservationCacheModel extends BaseModel
{
    //记录用户id
    const KEY_DEAL_REPAY_USER_IDS = 'PH_RESERVE_DEAL_REPAY_USER_IDS_%s'; //按天记录回款
    const KEY_DEAL_LOANS_USER_IDS = 'PH_RESERVE_DEAL_LOANS_USER_IDS_%s'; //按天记录放款
    const KEY_DEAL_CONTRACT_USER_IDS = 'PH_RESERVE_DEAL_CONTRACT_USER_IDS_%s'; //按天记录合同签署

    //记录放款标的ids，防止重复处理
    const KEY_DEAL_LOANS_DEAL_IDS = 'PH_RESERVE_DEAL_LOANS_IDS_%s';
    //记录回款ids，防止重复处理
    const KEY_DEAL_REPAY_IDS = 'PH_RESERVE_DEAL_REPAY_IDS_%s';

    //记录用户数据
    const KEY_USER_DATA = 'PH_RESERVE_USER_DATA_%s_%s'; //按天记录

    //过期时间 7天
    const EXPIRE_TIME = 604800;

    /**
     * 获取回款用户集合
     */
    public function getReserveDealRepayUserIds($timestamp) {
        $date = date('Ymd', $timestamp);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $userIdsKey = sprintf(self::KEY_DEAL_REPAY_USER_IDS, $date);
        return $redis->smembers($userIdsKey);
    }

    /**
     * 获取放款用户集合
     */
    public function getReserveDealLoansUserIds($timestamp) {
        $date = date('Ymd', $timestamp);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $userIdsKey = sprintf(self::KEY_DEAL_LOANS_USER_IDS, $date);
        return $redis->smembers($userIdsKey);
    }

    /**
     * 获取合同签署用户集合
     */
    public function getReserveDealContractUserIds($timestamp) {
        $date = date('Ymd', $timestamp);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $userIdsKey = sprintf(self::KEY_DEAL_CONTRACT_USER_IDS, $date);
        return $redis->smembers($userIdsKey);
    }

    /**
     * 获取用户缓存数据
     */
    public function getUserDataCache($userId, $timestamp) {
        $date = date('Ymd', $timestamp);
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $userDataKey = sprintf(self::KEY_USER_DATA, $date, $userId);
        return $redis->hgetall($userDataKey);
    }

    /**
     * 获取用户回款缓存
     */
    public function getUserReserveDealRepayCache($userId, $timestamp) {
        $data = $this->getUserDataCache($userId, $timestamp);
        return [
            'cnt' => isset($data['repayCount']) ? $data['repayCount'] : 0,
            'principal' => isset($data['principal']) ? bcdiv($data['principal'], 100, 2) : 0,
            'intrest' => isset($data['intrest']) ? bcdiv($data['intrest'], 100, 2) : 0,
            'prepay' => isset($data['prepay']) ? bcdiv($data['prepay'], 100, 2) : 0,
            'compensation' => isset($data['compensation']) ? bcdiv($data['compensation'], 100, 2) : 0,
            'impose' => isset($data['impose']) ? bcdiv($data['impose'], 100, 2) : 0,
            'prepayIntrest' => isset($data['prepayIntrest']) ? bcdiv($data['prepayIntrest'], 100, 2) : 0,
        ];
    }

    /**
     * 获取用户放款缓存
     */
    public function getUserReserveDealLoansCache($userId, $timestamp) {
        $data = $this->getUserDataCache($userId, $timestamp);
        return [
            'c' => isset($data['loansCount']) ? $data['loansCount'] : 0,
            'm' => isset($data['loansMoney']) ? bcdiv($data['loansMoney'], 100, 2) : 0,
        ];
    }

    /**
     * 获取用户合同缓存
     */
    public function getUserReserveDealContractCache($userId, $timestamp) {
        $data = $this->getUserDataCache($userId, $timestamp);
        return [
            'signContract' => isset($data['signContract']) ? $data['signContract'] : 0,
        ];
    }

    /**
     * 设置预约回款缓存
     */
    public function setReserveDealRepayCache($dealId, $dealRepayId) {
        $dealId = intval($dealId);
        $dealRepayId = intval($dealRepayId);
        $sql = "SELECT SUM(dlr.`money`) AS `m`, dlr.`loan_user_id`, dlr.`type`, dlr.`deal_loan_id`, COUNT(DISTINCT(dlr.`deal_loan_id`)) AS `c`, dlr.real_time FROM firstp2p_deal_loan_repay AS dlr
                LEFT JOIN `firstp2p_deal_load` AS dl ON dlr.deal_loan_id = dl.id
                WHERE dlr.`deal_id` = '{$dealId}' AND dlr.`deal_repay_id` = '{$dealRepayId}' AND dlr.`status`='" . DealLoanRepayEnum::STATUS_ISPAYED . "' AND dl.`deal_id` = '{$dealId}' AND dl.`source_type` = '" . DealLoadEnum::$SOURCE_TYPE['reservation'] . "'
                GROUP BY dlr.`loan_user_id`, dlr.`type`, dlr.real_time";
        $result = $this->findAllBySql($sql, true, array());
        $ret = [];
        foreach ($result as $val) {
            if (empty($val['real_time'])) {
                continue;
            }
            $realDate = date('Ymd', $val['real_time'] + 28800);
            $userId = $val['loan_user_id'];
            $ret[$realDate][$userId]['repayCount'] = $val['c'];
            switch($val['type']) {
                case DealLoanRepayEnum::MONEY_PRINCIPAL:
                    $ret[$realDate][$userId]['principal'] = bcmul($val['m'], 100);
                    break;
                case DealLoanRepayEnum::MONEY_INTREST:
                    $ret[$realDate][$userId]['intrest'] = bcmul($val['m'], 100);
                    break;
                case DealLoanRepayEnum::MONEY_PREPAY:
                    $ret[$realDate][$userId]['prepay'] = bcmul($val['m'], 100);
                    break;
                case DealLoanRepayEnum::MONEY_COMPENSATION:
                    $ret[$realDate][$userId]['compensation'] = bcmul($val['m'], 100);
                    break;
                case DealLoanRepayEnum::MONEY_IMPOSE:
                    $ret[$realDate][$userId]['impose'] = bcmul($val['m'], 100);
                    break;
                case DealLoanRepayEnum::MONEY_PREPAY_INTREST:
                    $ret[$realDate][$userId]['prepayIntrest'] = bcmul($val['m'], 100);
                    break;
            }
        }

        //set redis
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        foreach ($ret as $date => $val) {
            //检查是否设置过缓存
            $repayIdsKey = sprintf(self::KEY_DEAL_REPAY_IDS, $date);
            $addRet = $redis->sadd($repayIdsKey, $dealId . '-' . $dealRepayId);
            if (!$addRet) {
                continue;
            }
            $redis->expire($repayIdsKey, self::EXPIRE_TIME);

            //添加回款数据缓存
            $userIdsKey = sprintf(self::KEY_DEAL_REPAY_USER_IDS, $date);
            foreach ($val as $userId => $_val) {
                $userDataKey = sprintf(self::KEY_USER_DATA, $date, $userId);
                foreach ($_val as $key => $item) {
                    $redis->hincrby($userDataKey, $key, $item);
                }
                $redis->expire($userDataKey, self::EXPIRE_TIME);
                $redis->sadd($userIdsKey, $userId);
            }
            $redis->expire($userIdsKey, self::EXPIRE_TIME);
        }
        return true;
    }

    /**
     * 设置预约放款缓存
     */
    public function setReserveDealLoansCache($dealId) {
        $dealId = intval($dealId);

        //放款日期
        $sql = "SELECT repay_start_time FROM firstp2p_deal WHERE `id` = '{$dealId}'";
        $result = $this->findBySql($sql, [], true);
        if (empty($result['repay_start_time'])) {
            return true;
        }
        $date = date('Ymd', $result['repay_start_time'] + 28800);

        //放款数据
        $sql = "SELECT SUM(`money`) as `m`, `user_id`, `site_id`, `create_time`, COUNT(`id`) AS `c` FROM firstp2p_deal_load WHERE `deal_id`='{$dealId}' AND `source_type` = '" . DealLoadEnum::$SOURCE_TYPE['reservation'] . "' GROUP BY `user_id`";
        $result = $this->findAllBySql($sql, true, array(), true);
        if (empty($result)) {
            return true;
        }

        //检查是否设置过缓存
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $dealIdsKey = sprintf(self::KEY_DEAL_LOANS_DEAL_IDS, $date);
        $addRet = $redis->sadd($dealIdsKey, $dealId);
        if (!$addRet) {
            return true;
        }
        $redis->expire($dealIdsKey, self::EXPIRE_TIME);

        //set redis
        $userIdsKey = sprintf(self::KEY_DEAL_LOANS_USER_IDS, $date);
        foreach ($result as $val) {
            $userId = $val['user_id'];
            $userDataKey = sprintf(self::KEY_USER_DATA, $date, $userId);
            $redis->sadd($userIdsKey, $userId);
            $redis->hincrby($userDataKey, 'loansCount', $val['c']);
            $redis->hincrby($userDataKey, 'loansMoney', bcmul($val['m'], 100));
            $redis->expire($userDataKey, self::EXPIRE_TIME);
        }
        $redis->expire($userIdsKey, self::EXPIRE_TIME);
        return true;
    }

    /**
     * 设置签约合同缓存
     */
    public function setReserveDealContractCache($dealId) {
        $dealId = intval($dealId);

        //签约合同日期
        $sql = "SELECT sign_time FROM firstp2p_deal_contract WHERE `deal_id` = '{$dealId}' and status = 1";
        $result = $this->findBySql($sql, [], true);
        if (empty($result['sign_time'])) {
            return;
        }
        $date = date('Ymd', $result['sign_time']);

        //签约合同用户
        $sql = "SELECT `user_id` FROM firstp2p_deal_load WHERE `deal_id`='{$dealId}' AND `source_type` = '" . DealLoadEnum::$SOURCE_TYPE['reservation'] . "' GROUP BY `user_id`";
        $result = $this->findAllBySql($sql, true, array(), true);

        //set redis
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $userIdsKey = sprintf(self::KEY_DEAL_CONTRACT_USER_IDS, $date);
        foreach ($result as $val) {
            $userId = $val['user_id'];
            $userDataKey = sprintf(self::KEY_USER_DATA, $date, $userId);
            $redis->sadd($userIdsKey, $userId);
            $redis->hset($userDataKey, 'signContract', 1);
            $redis->expire($userDataKey, self::EXPIRE_TIME);
        }
        $redis->expire($userIdsKey, self::EXPIRE_TIME);
        return true;
    }
}
