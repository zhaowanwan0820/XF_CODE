<?php

/**
 * 嘉年华活动
 *
 * @author yutao <yutao@ucfgroup.com>
 */

namespace core\service;

use core\dao\ActivityCarnivalModel;

class ActivityCarnivalService extends BaseService {

    const EXPIRE = "2014-11-30 23:59:59";

    public function insertCarnivalUser($user_id, $user_name, $gift_practical, $gift_virtual) {
        $userInfo = array();
        $userInfo['user_id'] = $user_id;
        $userInfo['user_name'] = $user_name;
        $userInfo['gift_practical'] = $gift_practical;
        $userInfo['gift_virtual'] = $gift_virtual;
        $userInfo['create_time'] = get_gmtime();
        $userInfo['last_changed_time'] = get_gmtime();
        $userInfo['expire_time'] = to_timespan(self::EXPIRE);
        if ($userInfo['user_id'] > 0 && !empty($userInfo['user_name'])) {
            return ActivityCarnivalModel::instance()->insertWinUsers($userInfo);
        }
        return FALSE;
    }

    public function getUserInfo($user_id) {
        return ActivityCarnivalModel::instance()->findUsersById($user_id);
    }

    public function updateUserInfo($user_id, $gift_choose, $recipient_name = '', $mobile = '', $province = '', $city = '', $country = '', $address = '') {
        $last_changed_time = get_gmtime();
        return ActivityCarnivalModel::instance()->updateUserWin($user_id, 1, $gift_choose, $last_changed_time, $recipient_name, $mobile, $province, $city, $country, $address);
    }

}
