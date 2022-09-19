<?php

namespace api\processor;

class DuotouLoadlist extends Processor {

    public function afterInvoke() {
        $this->fetchResult = $this->unsetInfo($this->fetchResult);
        $this->setApiRespData($this->fetchResult);
    }

}
