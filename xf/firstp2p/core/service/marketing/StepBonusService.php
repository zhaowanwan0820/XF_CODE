<?php

namespace core\service\marketing;

use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class StepBonusService extends BaseService
{
    public static function isLock($userId)
    {
        $req = new SimpleRequestBase();
        $req->userId = $userId;
        $res = self::requestMarketing('NCFGroup\Marketing\Services\StepBonus', 'isLock', $req);

        if ($res['errCode']) {
            return [];
        }
        return $res['data'];
    }
}