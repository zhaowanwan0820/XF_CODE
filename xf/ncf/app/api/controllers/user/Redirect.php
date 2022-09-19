<?php

namespace api\controllers\user;

use api\controllers\BaseAction;
use core\service\open\OpenService;
use libs\utils\Logger;

class Redirect extends BaseAction {
    // 是否启用session
    protected $useSession = true;
    // 是否需要授权
    protected $needAuth = false;

    public function init() {
        parent::init();
    }

    public function invoke() {
        $sFromUrl = urldecode($_GET['url']);
        if (empty($sFromUrl)) {
            return false;
        }

        $host = parse_url($sFromUrl, PHP_URL_HOST);
        // 只能从m.ncfwx.com跳转过来
        if ($host != 'm.ncfwx.com') {
            //判断是否是分站
            $aFenzhan = OpenService::openSiteConf($host);
            if(empty($aFenzhan['id'])){
                return false;
            }
        }
        header("Location:$sFromUrl");
        exit;
        return false;
    }
}