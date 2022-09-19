<?php

namespace api\processor;

class AccountQuerysvchargelist extends Processor {

    public function afterInvoke() {
        $result = $this->addId($this->fetchResult);
        $this->setApiRespData($result);
    }

}
