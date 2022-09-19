<?php

/**
 * 红包跳转
 * @author By yutao
 */

namespace core\service;

class H5UnionService extends BaseService {

    private static $_instance;
    private $_src;
    private $_redisKeyPrefix = "firstp2p_union";
    private $_beginTime;
    private $_endTime;
    private $_count;
    private $_type;
    private $_activityInDocument;
    private $_activityEndDocument;
    private $_invite;

    private function __construct($h5UnionConf) {
        extract($h5UnionConf);
        $this->_src = $src;
        $this->_beginTime = $beginTime;
        $this->_endTime = $endTime;
        $this->_count = $count;
        $this->_type = $type;
        $this->_invite = $invite;
        $this->_activityInDocument = $activityInDocument;
        $this->_activityEndDocument = $activityEndDocument;
    }

    private function __clone() {
        
    }

    public static function getInstance($confArray) {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($confArray);
        }
        return self::$_instance;
    }

    /**
     * 
     * 判断活动是否在有效期内
     * @return boolean      
     */
    public function activityIsInTime() {
        $date = date("Y-m-d H:i:s");
        if ($date > $this->_endTime) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 
     * 判断活动是否可领取红包
     * @return boolean      
     */
    public function activityIsActive() {
        if ($this->activityIsInTime()) {
            if ($this->getRedisCount() < $this->_count) {
                return TRUE;
            }
            return FALSE;
        }
        return FALSE;
    }

    /**
     * 
     * 获得页面头部显示文案
     * @return String
     */
    public function getHeaderDoc() {
        if ($this->activityIsActive()) {
            return $this->_activityInDocument['headerDoc'];
        }
        return $this->_activityEndDocument['headerDoc'];
    }

    /**
     * 
     * 获得按钮显示文案
     * @return String
     */
    public function getButtonDoc() {
        if ($this->activityIsActive()) {
            return $this->_activityInDocument['buttonDoc'];
        }
        return $this->_activityEndDocument['buttonDoc'];
    }

    /**
     * 
     * 获得本活动的邀请码
     * @return String
     */
    public function getInvite() {
        if ($this->activityIsActive()) {
            return $this->_invite;
        }
        return "";
    }

    /**
     * 获得本活动成功的计数器数字
     * @return String 
     */
    public function getRedisCount() {
        return \SiteApp::init()->cache->getValue($this->getRedisKey());
    }

    /**
     * 
     * 增加本活动计数
     * @return String
     */
    public function addRedisCount() {
        $result = \SiteApp::init()->cache->incr($this->getRedisKey());
        return $result;
    }

    /**
     * 
     * 获得rediaCache的key
     * @return type
     */
    public function getRedisKey() {
        return $this->_redisKeyPrefix . "_" . $this->_src;
    }

}
