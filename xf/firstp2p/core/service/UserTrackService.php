<?php
/**
 * 用户轨迹服务
 * @date 2018-01-15
 * @author caolong <caolong@ucfgroup.com>
 */

namespace core\service;

/**
 * Class UserTrackService
 * @package core\service
 */
class UserTrackService extends BaseService {

    const REDIS_KEY_LOGIN_SITE = 'TRACK_USER_LOGIN_SITE_%s';

    const REDIS_KEY_LOGIN_SITE_EXPIRE_TIME = 2592000; //过期时间，30天

    public function __construct() {
        $this->redis = \SiteApp::init()->dataCache->getRedisInstance();
    }

    /**
     * 设置登录站点
     * $params int $userId
     * $params int $siteId
     */
    public function setLoginSite($userId, $siteId = 0) {
        $siteId = !empty($siteId) ? $siteId : \libs\utils\Site::getId();
        $key = sprintf(self::REDIS_KEY_LOGIN_SITE, $userId);
        return $this->redis->setex($key, self::REDIS_KEY_LOGIN_SITE_EXPIRE_TIME, $siteId);
    }

    /**
     * 获取登录站点
     * $params int $userId
     */
    public function getLoginSite($userId) {
        $key = sprintf(self::REDIS_KEY_LOGIN_SITE, $userId);
        return $this->redis->get($key);
    }

    /**
     * 清理登录站点
     * $params int $userId
     */
    public function clearLoginSite($userId) {
        $key = sprintf(self::REDIS_KEY_LOGIN_SITE, $userId);
        return $this->redis->del($key);
    }

    /**
     * 主站登录
     */
    public function isWxlcLogin($userId) {
        $siteId = $this->getLoginSite($userId);
        if (null !== $siteId && $siteId == $GLOBALS['sys_config']['TEMPLATE_LIST']['firstp2p']) {
            return true;
        }
        return false;
    }
}
