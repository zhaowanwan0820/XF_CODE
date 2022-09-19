<?php
/**
 * AndroidUpgrade Info
 */
namespace api\controllers\common;

use api\controllers\BaseAction;

class AndroidUpgrade extends BaseAction
{
    public function invoke()
    {
        $channel = isset($_SERVER['HTTP_CHANNEL']) ? trim($_SERVER['HTTP_CHANNEL']) : '';
        $version = isset($_SERVER['HTTP_VERSION']) ? trim($_SERVER['HTTP_VERSION']) : 100;

        $rs = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('AndroidUpgradeService\getPackage', [$version, $channel]), 300);
        $this->json_data = $rs;
    }
}
