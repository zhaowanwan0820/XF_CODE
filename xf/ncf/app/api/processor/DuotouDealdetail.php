<?php

namespace api\processor;

class DuotouDealdetail extends Processor {

    public function afterInvoke() {
        unset($this->fetchResult['backurl']);
        $this->setApiRespData($this->fetchResult);
    }

}
