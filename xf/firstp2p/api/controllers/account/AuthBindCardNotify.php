<?php

namespace api\controllers\account;

use libs\web\Form;
use libs\utils\PaymentApi;
use api\conf\ConstDefine;
use api\controllers\PayBaseAction;
use libs\utils\Aes;
use libs\utils\Logger;
use core\dao\PaymentNoticeModel;

class AuthBindCardNotify extends PayBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
            'merchantId' => array('filter' => "string", 'option' => array('optional' => true)),
            'userId' => array('filter' => "int"),
            'cardNo' => array('filter' => "string", 'option' => array('optional' => true)),
            'bankName' => array('filter' => "string", 'option' => array('optional' => true)),
            'bankCode' => array('filter' => "string", 'option' => array('optional' => true)),
            'certStatus' => array('filter' => "string", 'option' => array('optional' => true)),
            'bankCardType' => array('filter' => "int", 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        PaymentApi::log("AuthBindCardNotifyRequest. data:".json_encode($data, JSON_UNESCAPED_UNICODE));

        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        if ($os == 'Android') {
            $platform = PaymentNoticeModel::PLATFORM_ANDROID;
        } else {
            $platform = PaymentNoticeModel::PLATFORM_IOS;
        }

        $ret = $this->rpc->local('PaymentService\bindcardNotifyCallback', array($data, $platform));
        if ($ret['respCode'] == '00') {
            $result = array("success" => ConstDefine::RESULT_SUCCESS);
        } else {
            $result = array("success" => ConstDefine::RESULT_FAILURE);
        }
        $queryString = \libs\utils\Aes::buildString($result);
        $signature = md5($queryString."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);

        $aesData = \libs\utils\Aes::encode($queryString."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        PaymentApi::log("AuthBindCardNotifyResponse. ret:" . json_encode($ret, JSON_UNESCAPED_UNICODE) . ' userId:' .$data['userId']);
        if ($ret['respCode'] == '00') {
            $this->json_data = $aesData;
            return true;
        } else {
            $this->setErr('ERR_MANUAL_REASON', $ret['msg']);
            return false;
        }
    }
}

