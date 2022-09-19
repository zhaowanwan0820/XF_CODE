<?php

/**
 * iphone6抽奖活动service
 *
 * @author yutao <yutao@ucfgroup.com>
 */

namespace core\service;

use core\dao\ActivityIphoneModel;
use core\dao\LotteryModel;

class ActivityIphoneService extends BaseService {

    private $_displayItemCount = 7;

    /**
     * 
     * @var 完成抽奖的用户数据
     */
    private $_userWinOld = array(
        array(
            'date' => '10-8',
            'lottert_value' => '370478',
            'deal_count' => '1143',
            'user_lottery_num' => '00146',
            'user_name' => 'fengw**',
            'deal_time' => '22:38:57',
        ),
        array(
            'date' => '10-9',
            'lottert_value' => '361370',
            'deal_count' => '1030',
            'user_lottery_num' => '00870',
            'user_name' => 'sunyu88882**',
            'deal_time' => '15:54:29',
        ),
        array(
            'date' => '10-10',
            'lottert_value' => '897361',
            'deal_count' => '1379',
            'user_lottery_num' => '01011',
            'user_name' => 'ppowoaiwozi**',
            'deal_time' => '13:54:54',
        ),
        array(
            'date' => '10-11',
            'lottert_value' => '460897',
            'deal_count' => '1745',
            'user_lottery_num' => '00217',
            'user_name' => 'seeyousee**',
            'deal_time' => '20:30:06',
        ),
        array(
            'date' => '10-12',
            'lottert_value' => '669460',
            'deal_count' => '1408',
            'user_lottery_num' => '00660',
            'user_name' => 'ZHY01**',
            'deal_time' => '10:16:54',
        ),
        array(
            'date' => '10-13',
            'lottert_value' => '209669',
            'deal_count' => '983',
            'user_lottery_num' => '00290',
            'user_name' => 'lichunh**',
            'deal_time' => '7:25:48',
        ),
        array(
            'date' => '10-14',
            'lottert_value' => '066209',
            'deal_count' => '1543',
            'user_lottery_num' => '01403',
            'user_name' => '138106452**',
            'deal_time' => '16:27:42',
        ),
        array(
            'date' => '10-15',
            'lottert_value' => '524066',
            'deal_count' => '1594',
            'user_lottery_num' => '01234',
            'user_name' => 'Duliqun8**',
            'deal_time' => '14:03:54',
        ),
        array(
            'date' => '10-16',
            'lottert_value' => '339524',
            'deal_count' => '1357',
            'user_lottery_num' => '00274',
            'user_name' => 'yui**',
            'deal_time' => '20:40:36',
        ),
    );

    /**
     * 获得当天的抽奖用户列表
     * @param type $time 当天的时间戳
     * @return array
     */
    public function getIphoneUserList($time) {
        $dayTime = strtotime(date("Y-m-d", $time));
        $userList = ActivityIphoneModel::instance()->findUsersByTime($dayTime);
        if (count($userList) > 0) {
            foreach ($userList as $key => $value) {
                $userList[$key]->user_name = substr($value->user_name, 0, strlen($value->user_name) - 2) . '**';
                //$userList[$key]->user_lottery_num = substr("0000" . $value->user_lottery_num, -5);
                $userList[$key]->deal_time = date('Y/m/d H:i:s', strtotime('+8 hours', $value->deal_time));
            }
        }
        return $userList;
    }

    /**
     * 获得获奖用户列表
     * @return array
     */
    public function getIphoneUserWin() {
        $dayTime = ActivityIphoneModel::instance()->getLastUserTime()->stat_time;
        $userList = ActivityIphoneModel::instance()->findUsersByStatus(1);

//        if (count($userList) > 0) {
        $lotteryList = $this->getLottery();

        foreach ($userList as $key => $value) {
            $userList[$key]->date = date('m-d', $value->stat_time);
            $userList[$key]->user_name = substr($value->user_name, 0, strlen($value->user_name) - 2) . '**';
            //$userList[$key]->user_lottery_num = substr("0000" . $value->user_lottery_num, -5);
            $userList[$key]->deal_time = date('H:i:s', strtotime('+8 hours', $value->deal_time));
            $userList[$key]->deal_count = ActivityIphoneModel::instance()->getCount($value->stat_time);
            $lotteryBefore = $value->stat_time - 24 * 60 * 60;
            $userList[$key]->lottert_value = $lotteryList[$value->stat_time]['lottery_num'] . $lotteryList[$lotteryBefore]['lottery_num'];
        }

        $lastKey = count($userList) - 1;
        $lastCount = ActivityIphoneModel::instance()->getCount($dayTime);
        if ($lastCount > 0) {
            $lastKey = count($userList) - 1;
            if ($dayTime != $userList[$lastKey]['stat_time']) {
                $lastUser = new ActivityIphoneModel();
                $lastUser->date = date('m-d', $dayTime);
                $lastUser->deal_count = $lastCount;
                $lastUser->user_lottery_num = '***';
                $lastUser->user_name = '***';
                $lastUser->lottert_value = '***' . $lotteryList[$dayTime - 24 * 60 * 60]['lottery_num'];
                $lastUser->deal_time = '***';
                $lastUser->stat_time = $dayTime;
                $userList[] = $lastUser;
            }
        }

        $users = array();
        if (count($userList) > 0) {
            foreach ($userList as $key => $value) {
                $users[] = $value->getRow();
            }
        }
        $userList = array_merge($this->_userWinOld, $users);
        $userList = array_slice($userList, count($userList) - $this->_displayItemCount);
        return $userList;
    }

    /**
     * 批量插入抽奖用户
     * @param type $userList 
     * @return boolean
     */
    public function insertUserList($userList) {
        if (count($userList) > 0) {
            return ActivityIphoneModel::instance()->insertUserList($userList);
        }
        return FALSE;
    }

    /**
     * 获取福彩3D号码list
     * @param type $limit limit
     * @return type
     */
    function getLottery($limit) {
        $lotteryList = LotteryModel::instance()->getLottery($limit);
        if (count($lotteryList) > 0) {
            foreach ($lotteryList as $key => $value) {
                // $lotteryList[$key]->create_time = date('H:i:s', $value->create_time);
                $lotteryListInfo[$value->stat_date]['lottery_num'] = str_replace(',', '', $value->lottery_num);
                $lotteryListInfo[$value->stat_date]['date'] = date('m月d日', $value->stat_date);
                $lotteryListInfo[$value->stat_date]['lottery_str'] = str_replace(',', '、', $value->lottery_num);
            }
        }
        return $lotteryListInfo;
    }

    /**
     * 插入福彩数据
     * @param type $lotteryStr
     * @param type $time
     * @return type
     */
    public function insertLottery($lotteryStr, $time) {
        $dayTime = strtotime(date("Y-m-d", $time));
        return LotteryModel::instance()->insertLottery($lotteryStr, $dayTime);
    }

    /**
     * 获得抽奖用户数量
     * @param type $time 当天时间戳
     * @return type
     */
    public function getUserCount($time) {
        $dayTime = strtotime(date("Y-m-d", $time));
        return ActivityIphoneModel::instance()->getCount($dayTime);
    }

    /**
     * 更新得奖用户状态
     * @param type $time 当天时间戳
     * @param type $num  用户抽奖值
     * @return int
     */
    public function updateUserWin($time, $num) {
        $dayTime = strtotime(date("Y-m-d", $time));
        return ActivityIphoneModel::instance()->updateUserWin($dayTime, $num);
    }

}
