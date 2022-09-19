<?php

namespace api\controllers\account;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\PayBaseAction;
use libs\utils\Aes;
use libs\utils\Logger;

class PayNotify extends PayBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        // 请求日志前置，防止表单失败不记录
        \libs\utils\PaymentApi::log('MobilePayNotify request. data:'.json_encode($_POST, JSON_UNESCAPED_UNICODE));
        //$this->form = new Form();
        $this->form->rules = array(
            'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
            'merchantId' => array('filter' => "string", 'option' => array('optional' => true)),
            'currType' => array('filter' => "string", 'option' => array('optional' => true)),
            'orderNo' => array('filter' => "string"),
            'orderAmt' => array('filter' => "string"),
            'tranSerialNo' => array('filter' => "string"),
            'tranTime' => array('filter' => "string", 'option' => array('optional' => true)),
            'tranStat' => array('filter' => "int"), // 1 成功，0失败，2处理中
            'remark' => array('filter' => "string", 'option' => array('optional' => true)),
            // 接口更新后新增字段
            'accountNo' => array('filter' => "string", 'option' => array('optional' => true)),
            'bankName' => array('filter' => "string", 'option' => array('optional' => true)),
            'bankNo' => array('filter' => "string", 'option' => array('optional' => true)),
            'province' => array('filter' => "string", 'option' => array('optional' => true)),
            'city' => array('filter' => "string", 'option' => array('optional' => true)),
            'amountLimit' => array('filter' => "int", 'option' => array('optional' => true)), // 1小额，2大额
            'bankCardType' => array('filter' => "int", 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        // TODO 处理逻辑
        $ret = $this->rpc->local('PaymentService\response', array($data));

        \libs\utils\PaymentApi::log("MobilePayNotify response. orderId:{$data['orderNo']}, ret:".json_encode($ret, JSON_UNESCAPED_UNICODE));

        if ($ret['status'] === 0) {
            $result = array("success" => ConstDefine::RESULT_SUCCESS);
        } else {
            $result = array("success" => ConstDefine::RESULT_FAILURE);
        }
        $query_string = \libs\utils\Aes::buildString($result);
        $signature = md5($query_string."&key=".$GLOBALS['sys_config']['XFZF_SEC_KEY']);

        $aesData = \libs\utils\Aes::encode($query_string."&sign=".$signature, base64_decode($GLOBALS['sys_config']['XFZF_AES_KEY']));
        $log = array(
                'request' => $data,
                'response' => $query_string
                );
        logger::wLog(json_encode($log));
        if ($ret['status'] === 0) {
            $this->json_data = $aesData;
            return true;
        } else {
            $this->setErr('ERR_MANUAL_REASON', $ret['msg']);
            return false;
        }
    }
}
