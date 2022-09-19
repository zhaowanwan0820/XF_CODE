<?php

namespace core\service\marketing;

use libs\utils\Logger;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;

class BlessService extends BaseService
{

    public function getInfo($id)
    {
        $req = new SimpleRequestBase;
        $req->id = intval($id);

        $res = self::requestMarketing('NCFGroup\Marketing\Services\Bless', 'get', $req);
        if ($res['errCode']) {
            return [];
        }
        return $res['data'];
    }

    public function createInfo($openId, $mobile, $info = '')
    {
        $req = new SimpleRequestBase;
        $req->openId = $openId;
        $req->mobile = $mobile;
        $req->info = $info;

        $res = self::requestMarketing('NCFGroup\Marketing\Services\Bless', 'createInfo', $req);
        if ($res['errCode']) {
            return false;
        }
        return $res['data'];
    }

    public function upvote($openId, $blessId)
    {
        $req = new SimpleRequestBase;
        $req->openId = $openId;
        $req->blessId = $blessId;

        $res = self::requestMarketing('NCFGroup\Marketing\Services\Bless', 'upvote', $req);
        if ($res['errCode']) {
            return false;
        }
        return $res['data'];
    }
}
