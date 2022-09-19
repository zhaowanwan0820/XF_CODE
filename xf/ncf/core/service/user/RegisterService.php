<?php
/**
 * RegisterService.php
 *
 * @date 2014-11-25
 * @author wangfei <wangfei5@ucfgroup.com>
 */

namespace core\service\user;
use core\service\BaseService;

/**
 * 用于对注册流程的控制，方便后续如果推出强制身份认证流程。
 */

class RegisterService extends BaseService {
    /**
    *   根据注册的第几步判断下一个页面跳转至什么地方
    *   setp含义：1:login|2:addbank|3:success
    *   @param int
    *   @return string
    */
    private function getJumpUri($step){
        if (intval($step) == 2) {
            return "/account/addbank";
        }else{
            return "/";
        }
    }

    /**
    *
    *   生成注册流程的key
    */
    private function generateRegisterCacheKey(){
        $userId = isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] : 0;
        return sprintf("register_step_%s", $userId);
    }

    /**
    *
    *   注册最前置的判断,看看是否需要直接302到某个地方
    *   @return jump url
    */
    public function beforRegister() {
        // 如果用户已经登录，就不用注册，如果用户返回键后刷新那么根据当前的step自动跳转到对应页面。
        if (!empty($GLOBALS ['user_info']) && empty($_GET['client_id']) ) {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if (!empty($redis)) {
                $key = $this->generateRegisterCacheKey();
                $step = $redis->get($key);
                // 如果没有步数，直接
                if (empty($step)) {
                    return "/";
                }else{
                    // 根据step来进行不同页面的跳转
                    return $this->getJumpUri($step);
                }
            }
        }
        return "";
    }

    /**
    * 注册逻辑完成后调用，用于设置cache中step
    */
    public function afterRegister() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!empty($redis)) {
            $key = $this->generateRegisterCacheKey();
            $step = $redis->set($key,2);
        }
        return true;
    }

    /**
    *
    *   实名认证完成后调用，删除cache中的key
    */
    public function afterAddBank() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if (!empty($redis)) {
            $key = $this->generateRegisterCacheKey();
            $step = $redis->del($key);
        }
        return true;
    }
}