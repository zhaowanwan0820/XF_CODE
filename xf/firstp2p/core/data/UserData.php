<?php

namespace core\data;

/**
 * UserData
 * 用户相关数据
 * @uses BaseData
 * @package default
 * @author yangqing <yangqing@ucfgroup.com> 
 */
class UserData extends BaseData {
    private $_user_cache = array("key"=>"USER_",'list_max'=>10000, 'time'=>172800);
    private $_user_summary = array("key"=>"user_summary_");

    public function getUserSummary($user_id) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis !== null) {
            $result = $redis->get($this->_user_summary['key'] . $user_id);
            if ($result) {
                return json_decode($result, true);   
            }
        }
        return false;
    }

    public function setUserSummary($user_id, $data) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis !== null) {
            return $redis->set($this->_user_summary['key'] . $user_id, json_encode($data));
        }
        return false;
    }

    public function clearUserSummary($user_id) {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if ($redis !== null) {
            return $redis->del($this->_user_summary['key'] . $user_id);
        }
        return false;
    }

    public function setCreditRegCount($value){
        $cache = \SiteApp::init()->cache;
        $key = $this->_user_cache['key'].'CREDIT_CHINA_REG_COUNT';
        $res = $cache->set($key, $value, $this->_user_cache['time']);
        if($res){
            return $res;
        }else{
            return false;
        }
    }

    public function getCreditRegCount($key) {
        $cache = \SiteApp::init()->cache;
        $key = $this->_user_cache['key'].'CREDIT_CHINA_REG_COUNT';
        $result = $cache->get($key);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function incrOpenBillCnt($key) {
        $real_key = $this->_arr_email["key"] . $key;
        $cache = \SiteApp::init()->cache;
        return $cache->incr(md5($cache->keyPrefix.$real_key));
    }

    public function setOpenBillCnt($key,$value) {
        $real_key = $this->_arr_email["key"] . $key;
        $cache = \SiteApp::init()->cache;
        $result = $cache->set($real_key,$value);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function pushCreditReg($value){
        $cache = \SiteApp::init()->cache;
        $key = $this->_user_cache['key'].'CREDIT_CHINA_REG_LOG';
        $value = json_encode($value);
        if($cache->llen($key) > $this->_user_cache['list_max']){
            $cache->rpop($key);
        }
        $result = $cache->lpush($key,$value);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    public function popCreditReg($len){
        $cache = \SiteApp::init()->cache;
        $key = $this->_user_cache['key'].'CREDIT_CHINA_REG_LOG';
        $value = json_encode($value);
        $list = array();
        //pop前10个
        for($num=1; $num <= $len; $num++){
            $value = $cache->rpop($key);
            if($value){
                $list[]= $value;
            }else{
                break;
            }
        }
        return $list;
    }
}
