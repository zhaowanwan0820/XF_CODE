<?php

namespace api\processor;

class NewsFinance extends Processor {

    public function checkApiReturn($result) {
        if (empty($result)) {
            $this->setApiRespErr('ERR_SYSTEM', sprintf('调用接口 %s 无返回', $this->apiName));
            return false;
        }

        $result = json_decode(gzdecode($result), true);
        if (!isset($result['data'])) {
            $this->setApiRespErr('ERR_SYSTEM', sprintf('调用接口 %s 返回数据格式错误', $this->apiName));
            return false;
        }

        $this->setApiRespData($result);
    }

}
