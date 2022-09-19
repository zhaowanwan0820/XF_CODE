<?php

namespace api\processor;

use libs\utils\Aes;

class DiscountPicklist extends Processor {

    public function beforeInvoke() {
        if (!is_numeric($this->params['deal_id'])) {
            $this->params['deal_id'] = Aes::decryptForDeal($this->params['deal_id']);
        }
    }

}
