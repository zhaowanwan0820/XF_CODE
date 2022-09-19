<?php

namespace api\processor;

class EventOur extends Processor {

    public function beforeInvoke() {
        $this->params['timestamp'] = time();
        $this->params['signature'] = Signature::generate($this->params, '&key=' . $this->config->application->eventSalt);
    }

}
