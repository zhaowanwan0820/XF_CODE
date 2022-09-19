<?php

namespace api\processor;

class OpenSiteconf extends Processor {

    public function beforeInvoke() {
        $appInfo = $this->redis->get($this->_getCacheKey());
        if (!empty($appInfo)) {
            $appInfo = json_decode(gzdecode($appInfo), true);
            $this->setApiRespData($appInfo);
            return false;
        }
    }

    public function afterInvoke() {
        $result = $this->fetchResult;
        $result = $this->unsetInfo($result);
        $this->redis->setex($this->_getCacheKey(), 5 * 60, gzencode(json_encode($result), 6)); //缓存5min
    }

    private function _getCacheKey() {
        return md5('fenzhan:domain:' . $this->getHttpHost());
    }

}
