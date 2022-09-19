<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 2017/2/7
 * Time: 下午5:45
 */

namespace web\controllers\app;

use core\service\PlatformPublishService;

use web\controllers\BaseAction;

class PlatformData extends BaseAction {

    public function invoke() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $data = $redis->get('platform_publish_data');
        if(empty($data)){

            //指定时间,5月20日以后的
            $time = strtotime("2016-5-20 00:00:00");
            //指定类型,普通标,通知贷
            $dealType = '0,1';

            $publishService = new PlatformPublishService();
            $data = json_encode($publishService->create($dealType,$time));
        }

        echo $data;
        return;
    }
}
