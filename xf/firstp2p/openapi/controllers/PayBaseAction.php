<?php
namespace api\controllers;

use libs\rpc\Rpc;
use api\conf\Error;
use api\conf\ConstDefine;
use api\controllers\BaseAction;
use libs\utils\Aes;

/**
 * PayBaseAction 
 * 支付api，安全验证部分
 * 
 * @uses BaseAction
 * @package 
 * @version $id$
 * @author Pine wangjiansong@ucfgroup.com 
 */
class PayBaseAction extends BaseAction
{
    public function _before_invoke() {
        $datas = $this->form->data;
        $signStr = \libs\utils\Aes::signature($datas);
        if (strcasecmp($signStr, $datas[ConstDefine::SIGNATURE_KEY]) !== 0) {
            $this->setErr('ERR_SIGNATURE_FAIL'); // 签名证书不正确
            return false;
        }
        return true;
    }
}
