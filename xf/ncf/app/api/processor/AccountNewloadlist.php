<?php

namespace api\processor;

class AccountNewloadlist extends Processor {

    public function beforeInvoke() {
        $this->params['pageNo'] = isset($this->params['page']) ? $this->params['page'] : 1;
    }

}
