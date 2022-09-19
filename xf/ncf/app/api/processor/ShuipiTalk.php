<?php

namespace api\processor;

class ShuipiTalk extends Processor {

    public function checkApiReturn($result) {
        $result = (array) json_decode($result, true);
        if (empty($result) || !isset($result['data'])) {
            $this->setApiRespErr('ERR_SYSTEM', sprintf('调用接口 %s 返回数据格式错误', $this->apiName));
            return false;
        }

        $this->setApiRespData($result);
    }

}
