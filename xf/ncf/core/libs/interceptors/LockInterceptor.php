<?php
namespace libs\interceptors;
require_once(ROOT_PATH.'core/libs/utils/es_session.php');
require_once(ROOT_PATH.'core/libs/lock/LockFactory.php');

use libs\lock\LockFactory;

/**
 * LockInterceptor controller锁实现
 * 
 * @author jingxu<jingxu@ucfgroup.com>
 * @package libs\interceptors
 */
class LockInterceptor {
    private static $lock_key = false;
    const USER_LOCK_ANNOTATION = '@userLock';
    const ACTION_LOCK_ANNOTATION = '@actionLock';

    /**
     * before 调用action之前所进行的锁操作
     * 
     * @param mixed $class_str 类名
     * @param mixed $method_str  方法名
     * @static
     * @access public
     * @return bool
     */
    public static function before($class_str, $method_str = null) {
        self::$lock_key = self::getLockKey($class_str, $method_str);
        if(!self::$lock_key) {
            return true;
        }

        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if(!$lock->getLock(self::$lock_key, 50)) {
            return false;
        }

        return true;
    }

    private static function getId($is_admin) {
        if($is_admin) {
            $adm_session = \es_session::get(md5(conf("AUTH_KEY")));
            if(isset($adm_session['adm_id'])) {
                return $adm_session['adm_id'];
            }
        } else {
            $user_info = \es_session::get('user_info');
            if(isset($user_info['id'])) {
                return $user_info['id'];
            }
        }

        return false;
    }

    private static function getLockKey($class_str, $method_str) {
        $doc = self::getDoc($class_str, $method_str);
        if(stripos($doc, self::USER_LOCK_ANNOTATION) !== false) {
            $is_admin = $method_str;
            $id = self::getId($is_admin);
            if($id) {
                return $class_str.$method_str.$id;
            }
        } else if(stripos($doc, self::ACTION_LOCK_ANNOTATION) !== false) {
            return $class_str.$method_str;
        }

        return false;
    }

    private static function getDoc($class_str, $method_str) {
        if($method_str && !method_exists($class_str, $method_str)) {
            return '';
        }

        if($method_str) {
            $method = new \ReflectionMethod($class_str, $method_str);
            return $method->getDocComment();
        }

        $class = new \ReflectionClass($class_str);
        return $class->getDocComment();
    }

    /**
     * after 调用action之后所进行的操作
     * 
     * @static
     * @access public
     * @return bool
     */
    public static function after() {
        if(self::$lock_key) {
            $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
            $lock->releaseLock(self::$lock_key);
        }

        return true;
    }
}
