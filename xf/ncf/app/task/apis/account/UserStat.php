<?php

namespace task\apis\account;

use task\lib\ApiAction;

class UserStat extends ApiAction
{
    public function invoke()
    {
        $param  = $this->getParam();
        $userId = $param['userId'];
        $isCache = $param['isCache'] ?: false;
        $makeCache = $param['makeCache'] ?: false;
        $siteId = $param['siteId'] ?: 1;

        $result = user_statics($userId, $isCache, $makeCache, $siteId);
        $this->json_data = $result;
    }

}
