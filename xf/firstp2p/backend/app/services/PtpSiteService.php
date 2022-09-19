<?php

namespace NCFGroup\Ptp\services;

use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

/**
 * PtpBonusService.
 *
 * @uses ServiceBase
 */
class PtpSiteService extends ServiceBase
{
    public function getSiteList(SimpleRequestBase $request)
    {
        return $GLOBALS['sys_config']['TPL_SITE_LIST'];
    }
}
