<?php

namespace api\processor;

class AccountQuerychargelist extends Processor {

    public function afterInvoke() {
        $result = $this->addId($this->fetchResult);
        $this->setApiRespData($result);
    }

}
