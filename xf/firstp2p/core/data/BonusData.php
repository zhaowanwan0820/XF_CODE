<?php

/**
 * BonusData class file
 * @author 张磊 <zhanglei5@ucfgroup.com>
 */

namespace core\data;

class BonusData extends BaseData {
    private $_arr_activity = array("key"=>"act_", "time"=>3600);

    /**
     * getActivityByGroupId 
     * 从redis中获得活动信息
     * 
     * @param mixed $group_id 
     * @access public
     * @return void
     */
    public function getActivityByGroupId($group_id) {
        $result = \SiteApp::init()->cache->get($this->_arr_activity["key"] . $group_id);
        if ($result) {
            return json_decode($result, true);
        } else {
            return false;
        }
    }

    /**
     * setActivity 
     * 设置活动信息到redis中
     * 
     * @param mixed $group_id 
     * @param array $value 
     * @access public
     * @return void
     */
    public function setActivity($group_id, $value=array()) {
        $str = json_encode($data);
        return \SiteApp::init()->cache->set($this->_arr_activity["key"] . $group_id, $str, $this->_arr_activity['time']);
    }
}
