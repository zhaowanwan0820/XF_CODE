<?php

namespace api\processor;

use libs\utils\Aes;

class DiscountAjaxexpectedearninginfo extends Processor {

    public function beforeInvoke() {
        // 只有p2p需要解密
        if (!is_numeric($this->params['id'])) {
            $this->params['id'] = Aes::decryptForDeal($this->params['id']);
        }
    }

}
