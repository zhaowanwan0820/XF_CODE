<?php
/**
 * AndroidUpgrade Info
 */
namespace api\controllers\common;

use api\controllers\AppBaseAction;
use core\service\attachment\AndroidUpgradeService;

class AndroidUpgrade extends AppBaseAction
{
    protected $needAuth = false;
    public function invoke()
    {
        $channel = isset($_SERVER['HTTP_CHANNEL']) ? trim($_SERVER['HTTP_CHANNEL']) : '';

        $rs = \SiteApp::init()->dataCache->call(new AndroidUpgradeService(), 'getPackage', [$this->app_version, $channel], 300);

        $this->json_data = $rs;
    }
}
