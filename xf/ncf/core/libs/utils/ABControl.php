<?php

namespace libs\utils;

class ABControl
{

    const RULE_TYPE_AND = "and";
    const RULE_TYPE_OR = "or";
    const EFFECTIVE = "true";
    const INEFFECTIVE = "false";

    const ABTESTING_DATE_HASH_KEY = "abtesting/hash/data";
    const ABTESSTING_DATAKEY_FORMAT = "%s/%s";

    private static $_ins = null;
    private $_redis = null;

    public static function getInstance() {
        if(self::$_ins == null) {
            self::$_ins = new self();
        }
        return self::$_ins;
    }

    private function __construct() {
        $this->_redis = \SiteApp::init()->dataCache->getRedisInstance();
    }

    public function hit($grayName, $userInfo = array()) {
        $userInfo = empty($userInfo) ? $GLOBALS['user_info'] : $userInfo;
        // userSummaryNew => {"function": "userSummaryNew", "effective" : "true", "type": "and"，"names": ["Percentage", "Tag"], "data":["20", "CONST_TAG_NAME"]}
        $functionInfo = $this->_redis->hGet(self::ABTESTING_DATE_HASH_KEY, $grayName);
        if(empty($functionInfo)) {
            return false;
        }

        $functionInfo = json_decode($functionInfo, true);

        $effective = $functionInfo['effective'];
        if($effective == self::INEFFECTIVE) {
            return false;
        }

        $type = $functionInfo['type'];
        $rules = $functionInfo['names'];
        $data = $functionInfo['data'];
        if(empty($rules)) {
            return false;
        }

        foreach($rules as $i => $rule) {
            $function = "hit" . $rule;
            if($type == self::RULE_TYPE_OR) {
                if($this->{$function}($grayName, $userInfo, $data[$i])) {
                    return true;
                }
            } else {
                if(!$this->{$function}($grayName, $userInfo, $data[$i])) {
                    return false;
                }
            }
        }

        if ($type == self::RULE_TYPE_AND) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 用户组规则
     */
    private function hitUserGroup($grayName, $userInfo, $value)
    {
        $userGroup = isset($userInfo['group_id']) ? intval($userInfo['group_id']) : 0;
        $groupIds = explode(',', $value);

        return in_array($userGroup, $groupIds);
    }

    /**
     * 用户Id白名单规则
     */
    private function hitWhitelist($grayName, $userInfo, $value)
    {
        $userId = isset($userInfo['id']) ? intval($userInfo['id']) : 0;
        if ($userId === 0) {
            return false;
        }

        $whitelist = explode(',', $value);

        return in_array($userId, $whitelist);
    }

    /**
     * 百分比规则 (数值设置为10，则尾号0-9的用户命中规则)
     */
    private function hitPercentage($grayName, $userInfo, $value)
    {
        $userId = isset($userInfo['id']) ? intval($userInfo['id']) : 0;
        $userIdMod = $userId % 100;

        return $userIdMod < $value ? true : false;
    }

    /**
     * 随机规则
     */
    private function hitRandom($grayName, $userInfo, $value)
    {
        return mt_rand(0, 99) < $value ? true : false;
    }

    private function hitMobile($grayName, $userInfo, $value)
    {
        $mobile = isset($userInfo['mobile']) ? intval($userInfo['mobile']) : 0;
        $mobiles = explode(',', $value);

        return in_array($mobile, $mobiles);
    }

}
