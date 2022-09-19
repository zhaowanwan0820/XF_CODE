<?php

namespace api\processor;

class DuotouDuotoubidreturn extends DealBidreturn {

    public function afterInvoke() {
        $result = $this->_getPrizeUrl($this->fetchResult);
        $this->setApiRespData($result);
    }

}
