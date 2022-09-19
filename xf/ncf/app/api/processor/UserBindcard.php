<?php

namespace api\processor;

class UserBindcard extends Processor {

    public function afterInvoke() {
        $result = $this->fetchResult;
        if (!empty($result['h5AuthCardUrl'])) {
            $result['h5AuthCardUrl'] = $this->urlReplace($result['h5AuthCardUrl'], $this->getHttpHost());
        }
        $this->setApiRespData($result);
    }

}
