<?php
namespace api\controllers\common;

use api\conf\Error;
use core\dao\FundMoneyLogModel;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use api\controllers\FundBaseAction;
use libs\web\Form;

/**
 * Sms
 * 发送短信接口
 * @uses FundBaseAction
 * @package default
 */
class Sms extends FundBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'signature' => array('filter'=>"required", 'message'=> '签名不能为空！'),
            'userId' => array('filter' => 'int'),
            'mobile' => array('filter' => 'string'),
            'smsKey' => array('filter' => 'string'),
            'params' => array('filter' => 'string', 'option' => array('optional' => true)),
            'mark' => array('filter' => 'string', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $userId = $data['userId'];
        $mobile = $data['mobile'];
        $smsKey = $data['smsKey'];
        $params = isset($data['params']) ? json_decode(urldecode($data['params'])) : array();

        \libs\sms\SmsServer::instance()->send($mobile, $smsKey, $params, $userId);

        // 记录日志
        $apiLog = $data;
        $apiLog['time'] = date('Y-m-d H:i:s');
        $apiLog['ip'] = get_real_ip();
        PaymentApi::log("API_SMS_LOG:".json_encode($apiLog), Logger::INFO);
        $this->json_data = array('success' => 0);

        return true;

    }
}
