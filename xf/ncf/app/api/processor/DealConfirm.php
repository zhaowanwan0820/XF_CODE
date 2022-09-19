<?php

namespace api\processor;

use libs\utils\Aes;

class DealConfirm extends Processor {

    public function beforeInvoke() {
        if (!is_numeric($this->params['id'])) {
            $this->params['id'] = Aes::decryptForDeal($this->params['id']);
        }
    }

    public function afterInvoke() {
        $result = $this->fetchResult;

        //判断是否满标
        $result['isFull'] = isset($result['deal']) ? 0 : 1;

        //优惠码
        $result['code'] = isset($this->params['code']) ? $this->params['code'] : '';

        $result['deal']['productID'] = Aes::encryptForDeal($result['deal']['productID']);

        $this->setApiRespData($result);
    }

}
