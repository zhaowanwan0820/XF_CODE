<?php

namespace api\processor;

use libs\utils\Aes;

class DealsDetail extends Processor {

    public function beforeInvoke() {
        if (!is_numeric($this->params['id'])) {
            $this->params['id'] = Aes::decryptForDeal($this->params['id']);
        }
    }

}
