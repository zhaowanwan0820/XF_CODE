<?php
/**
 * PtpOpenService
 * 开放平台同步操作
 * @uses ServiceBase
 * @package default
 */

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use NCFGroup\Common\Extensions\Base\ResponseBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use libs\utils\Logger;
use libs\web\Open;


require_once(APP_ROOT_PATH . 'system/libs/msgcenter.php');

class PtpRsyncService extends ServiceBase {

    const TYPE_APP_INFO_DETAIL = 1; //缓存app信息
    const TYPE_APP_CONF_DETAIL = 2; //缓存conf信息
    const TYPE_APP_ADVS_DETAIL = 3; //缓存广告信息

    public function rsync(SimpleRequestBase $conf){
        $param = $conf->getParamArray();

        $appId = intval($param['siteId']);
        $type  = intval($param['type']);
        $data  = json_decode(urldecode(gzdecode($param['data'])), true);

        $instance = \SiteApp::init()->dataCache->getRedisInstance();
        $odomain = $instance->hget(Open::KEY_DOMAIN_APPID_MAP, $appId);

        $instance->multi();
        if (self::TYPE_APP_INFO_DETAIL == $type) {
            $result = $this->saveAppInfo($appId, $data, $odomain, $instance);
        } elseif (self::TYPE_APP_CONF_DETAIL == $type) {
            $result = $this->saveConfInfo($appId, $data, $instance);
        } elseif (self::TYPE_APP_ADVS_DETAIL == $type) {
            $result = $this->saveAdvsInfo($appId, $data, $instance);
        }

        if (false === $result) {
            Logger::error('保存失败open-rsync-param:false'.json_encode($param));
            return array('errno' => 1, 'errmsg' => '保存失败');
        }

        $result = $instance->exec() ? array('errno' => 0) : array('errno' => 1, 'errmsg' => '保存失败');

        Logger::info('open-rsync-save'.json_encode($result));
        return $result;
    }

    private function saveAppInfo($appId, $data, $odomain, $instance) {
        $odomain = json_decode($odomain, true);
        if (!empty($odomain)) {
            if (!$instance->hdel(Open::KEY_DOMAIN_APPID_MAP, $odomain)) {
                $instance->discard();
                return false;
            }
            if (!$instance->hdel(Open::KEY_DOMAIN_APPID_MAP, $appId)) {
                $instance->discard();
                return false;
            }
        }

        if (!(intval($data['onlineStatus']) & 6)) { // 6 表示 wap / web 都在线
            if (!$instance->srem(Open::KEY_APPID_LIST, $appId)) {
                $instance->discard();
                return false;
            }
            return true;
        }

        if (!$instance->sadd(Open::KEY_APPID_LIST, $appId)) {
            $instance->discard();
            return false;
        }

        $domains = array();
        foreach (array('usedWebDomain', 'usedWapDomain') as $key) {
            $domain = strtolower(trim($data[$key]));
            if (empty($domain)) {
                continue;
            }
            if (!$instance->hset(Open::KEY_DOMAIN_APPID_MAP, $domain , $appId)) {
                $instance->discard();
                return false;
            }
            $domains[] = $domain;
        }

        if (!$instance->hset(Open::KEY_DOMAIN_APPID_MAP, $appId, json_encode($domains))) {
            $instance->discard();
            return false;
        }

        $feildName = 'info_' . $appId;
        if (!$instance->hset(Open::KEY_APP_DETAIL_LIST, $feildName, json_encode($data))) { //hash
            $instance->discard();
            return false;
        }

        return true;
    }

    private function saveConfInfo($appId, $data, $instance) {
        $feildName = 'conf_' . $appId;
        if (!$instance->hset(Open::KEY_APP_DETAIL_LIST, $feildName, json_encode($data))) { //hash
            $instance->discard();
            return false;
        }
        return true;
    }

    private function saveAdvsInfo($appId, $data, $instance) {
        $feildName = 'advs_' . $appId;
        if (!$instance->hset(Open::KEY_APP_DETAIL_LIST, $feildName, json_encode($data))) { //hash
            $instance->discard();
            return false;
        }
        return true;
    }



}
