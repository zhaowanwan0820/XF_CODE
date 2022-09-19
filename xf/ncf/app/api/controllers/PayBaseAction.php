<?php

namespace api\controllers;

use api\conf\ConstDefine;
use api\controllers\BaseAction;
use libs\utils\Aes;

/**
 * PayBaseAction
 * 支付api，安全验证部分
 */
class PayBaseAction extends BaseAction {
    public function _before_invoke() {
        $datas = $this->form->data;
        $signStr = \libs\utils\Aes::signature($datas, $GLOBALS['sys_config']['XFZF_SEC_KEY']);
        if (strcasecmp($signStr, $datas[ConstDefine::SIGNATURE_KEY]) !== 0) {
            // 签名证书不正确
            $this->setErr('ERR_SIGNATURE_FAIL');
        }

        return true;
    }
}
