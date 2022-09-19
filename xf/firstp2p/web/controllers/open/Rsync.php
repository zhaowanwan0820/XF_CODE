<?php

namespace web\controllers\open;

use libs\web\Form;
use libs\web\Open;
use web\controllers\BaseAction;

class Rsync extends BaseAction {

    const TYPE_APP_INFO_DETAIL = 1; //缓存app信息
    const TYPE_APP_CONF_DETAIL = 2; //缓存conf信息
    const TYPE_APP_ADVS_DETAIL = 3; //缓存广告信息

    public function init() {
        $remoteIP = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
        if (strtolower(trim(get_cfg_var("phalcon.env"))) == 'product' && !in_array($remoteIP, array('172.21.11.74', '172.21.11.73'))) {
//            return ajax_return(array('errno' => 1, 'errmsg' => '访问拒绝'));
        }

        $this->form = new Form();
        $this->form->rules = array(
            'siteId' => array("filter" => "reg", "option" => array("regexp" => '/^\d+$/'), "message" => "siteId错误"),
            'type'   => array('filter' => 'reg', "option" => array("regexp" => '/^\d+$/'), "message" => "type错误"),
            'time'   => array('filter' => 'reg', "option" => array("regexp" => '/^\d+$/'), "message" => "time错误"),
            'secret' => array('filter' => 'reg', "option" => array("regexp" => '/^\w+$/'), "message" => "secret错误"),
            'data'   => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            setLog(array('open_rsync_vali' => 'param error'));
            return ajax_return(array('errno' => 1, 'errmsg' => '参数错误'));
        }
    }

    public function invoke() {
        $param = $this->form->data;
        if (abs(time() - $param['time']) > 600 || md5('open' . $param['data'] . 'open') != $param['secret']) {
            setLog(array('open-rsync-param' => $param));
            return ajax_return(array('errno' => 1, 'errmsg' => '无效请求'));
        }

        $appId = intval($param['siteId']);
        $type  = intval($param['type']);
        $data  = json_decode(urldecode($param['data']), true);

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
            setLog(array('open-rsync-save' => false));
            return ajax_return(array('errno' => 1, 'errmsg' => '保存失败'));
        }

        $result = $instance->exec() ? array('errno' => 0) : array('errno' => 1, 'errmsg' => '保存失败');

        setLog(array('open-rsync-save' => $result));
        return ajax_return($result);
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
