<?php

namespace api\processor;

use Backend\Api\Plugins\Signature;

class Event extends Processor {

    public function beforeInvoke() {
        $this->params['timestamp'] = time();
        $this->params['type'] = 'getactivity' == $this->context['action'] ? 'activity' : 'news';
        $this->params['signature'] = Signature::generate($this->params, '&key=' . $this->config->application->eventSalt);
    }

}
