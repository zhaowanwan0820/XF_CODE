<?php

namespace api\processor;

class UserSendverifycode extends Processor {
    public function afterInvoke() {
        $this->setApiRespData(array('status' => $this->fetchResult[0]));
    }

}
