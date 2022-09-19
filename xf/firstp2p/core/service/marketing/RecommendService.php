<?php

namespace core\service\marketing;

use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class RecommendService extends BaseService
{

    public function getDealRecommend($userId)
    {
        $req = new SimpleRequestBase;
        $req->uid = $userId;

        return self::requestMarketing('NCFGroup\Marketing\Services\Recommend', 'getDealRecommend', $req);
    }

}
