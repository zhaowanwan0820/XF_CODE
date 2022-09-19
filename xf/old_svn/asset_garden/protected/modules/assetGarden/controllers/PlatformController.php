<?php

class PlatformController extends CommonController
{

    /**
     * 平台列表
     */
    public function actionGetList(){
        $platformList = AgPlatformService::getInstance()->getPlatformListFromCache(isset($_POST['platform_name'])?$_POST:[]);
        $this->echoJson($platformList, 0);
    }

}
