<?php

namespace api\controllers\account;

use libs\web\Form;
use libs\utils\PaymentApi;
use api\conf\ConstDefine;
use api\controllers\PayBaseAction;
use libs\utils\Aes;
use libs\utils\Logger;
use core\dao\PaymentNoticeModel;

class CredibleNotify extends PayBaseAction
{

    public function init()
    {
        parent::init();
        PaymentApi::log('CredibleNotifyRequest. params:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));

        $this->form = new Form('post');
        $this->form->rules = array(
            'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
            'merchantId' => array('filter' => "string", 'option' => array('optional' => true)),
            'userId' => array('filter' => "int"),
            'accountNo' => array('filter' => "string", 'option' => array('optional' => true)),
            'merchantNo' => array('filter' => "string", 'option' => array('optional' => true)),
            'amount' => array('filter' => "int", 'option' => array('optional' => true)),
            'bankName' => array('filter' => "string", 'option' => array('optional' => true)),
            'bankNo' => array('filter' => "string", 'option' => array('optional' => true)),
            'province' => array('filter' => "string", 'option' => array('optional' => true)),
            'city' => array('filter' => "string", 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            PaymentApi::log('CredibleNotifyResponseError. msg:'.$this->form->getErrorMsg());
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $ret = $this->rpc->local('PaymentService\bankcardBindCallback', array($data));
        if ($ret['status'] === 0) {
            $result = array("success" => ConstDefine::RESULT_SUCCESS);
        } else {
            $result = array("success" => ConstDefine::RESULT_FAILURE);
        }
        $queryString = \libs\utils\Aes::buildString($result);
        $signature = md5($queryString."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);
        $aesData = \libs\utils\Aes::encode($queryString."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));

        if ($ret['status'] != 0) {
            PaymentApi::log('CredibleNotifyResponseError. ret:'.json_encode($ret, JSON_UNESCAPED_UNICODE));
            $this->setErr('ERR_MANUAL_REASON', $ret['msg']);
            return false;
        }

        PaymentApi::log('CredibleNotifyResponse. ret:'.json_encode($ret, JSON_UNESCAPED_UNICODE));
        $this->json_data = $aesData;
        return true;
    }

}
