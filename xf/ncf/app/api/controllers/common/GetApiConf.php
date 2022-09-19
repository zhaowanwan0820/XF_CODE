<?php
/**
 * 获取apiconf配置信息
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */
namespace api\controllers\common;

use core\service\conf\ApiConfService;
use api\controllers\AppBaseAction;
use libs\utils\Site;

class GetApiConf extends AppBaseAction {
    protected $needAuth = false;

    private $siteId = 100;

    public function init() {
        parent::init();
        $this->siteId = Site::getId();
    }

    public function invoke() {
        //判断后台是否更新，如果更新了，则重新查询并缓存起来，反之走正常的缓存逻辑
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $new_modify_time = $redis->get('api_conf_last_modify_time');
        $old_modify_time = $redis->get('old_api_conf_last_modify_time_'.$this->siteId);
        $apiConfService = new ApiConfService();
        if ($new_modify_time != $old_modify_time) {
            $old_modify_time = $redis->set('old_api_conf_last_modify_time_'.$this->siteId,$new_modify_time);
            $rs = \SiteApp::init()->dataCache->call($apiConfService, 'getApiConfBySiteId', array($this->siteId), 300, true);
        } else {
            $rs = \SiteApp::init()->dataCache->call($apiConfService, 'getApiConfBySiteId', array($this->siteId), 300);
        }
        foreach ($rs as $name_value) {
            if ($name_value['conf_type'] == '1') {
                $ret['common'][] = array(
                    'name' => $name_value['name'],
                    'value' => $name_value['value'],
                );
            } elseif ($name_value['site_id'] == $this->siteId) {
                $ret['site'][] = array(
                    'name' => $name_value['name'],
                    'value' => $name_value['value'],
                );
            }
        }
        $this->json_data = $ret;

    }
}
