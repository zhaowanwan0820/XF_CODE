<?php
/**
* FestivalActivitiesService.php
*
* @date 2016-12-02
* @author zhaohui <zhaohui3@ucfgroup.com>
*/

namespace core\service;

use core\dao\UserFestivalActivitiesModel;
/**
 * Class FestivalActivitiesService
 * @package core\service
 */
class UserFestivalActivitiesService extends BaseService {

    /**
     * 根据uid，查询用户参与活动信息
     * @param
     * @return boolean
     */
    public function getUserActivityInfo($uid) {
        $UserFestivalActivities = UserFestivalActivitiesModel::instance();
        $fields = 'id,user_id,activity_id,current_count,current_count_day,game_count,activity_count,award,activity_flag,game_start_time,ticket';
        $condition = "`user_id` = '{$uid}'";
        return $UserFestivalActivities->getUserActivityInfoByCondition($condition,$is_array=true,$fields, $params = array());
    }

    /**
     * 将用户参与信息写入数据库
     * @param
     * $data(array):user_id,activity_id,activity_name,type,count_day,count_limit_day,count_limit,activity_count,game_count,game_start_time,create_time,update_time
     * @return boolean
     */
    public function insertActivityInfoById($data) {
        if (!$data['create_time']) {
            $data['create_time'] = time();
        }
        if (!$data['update_time']) {
            $data['update_time'] = time();
        }
        if (!empty($data)) {
            return UserFestivalActivitiesModel::instance()->insertUserActivityInfo($data);
        }
        return false;
    }
    /**
     * 更新用户参与信息
     * @param
     * $data(array):user_id,activity_id,activity_name,type,count_day,count_limit_day,count_limit,activity_count,game_count,game_start_time,create_time,update_time
     * @return boolean
     */
    public function updateActivityInfoById($data) {
        $data['update_time'] = isset($data['update_time']) ? $data['update_time'] : '';
        if (!$data['update_time']) {
            $data['update_time'] = time();
        }
        if (!empty($data)) {
            return UserFestivalActivitiesModel::instance()->updateUserActivityInfo($data);
        }
        return false;
    }
}
