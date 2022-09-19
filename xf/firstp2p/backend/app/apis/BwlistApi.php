<?php

namespace NCFGroup\Ptp\Apis;

use core\service\BwlistService;
use NCFGroup\Common\Library\ApiBackend;
/**
 * 查询用户黑白名单接口
 */
class BwlistApi extends ApiBackend
{
    public function inList(){
        $type_key = $this ->getParam('type_key');
        $value = $this ->getParam('value');
        $value2 = $this ->getParam('value2');
        $value3= $this ->getParam('value3');
        $bwlistService = new BwlistService();
        $result = $bwlistService->inList($type_key, $value, $value2, $value3);
        return $this->formatResult($result);
    }
}
