<?php
namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\ChargeService;

/**
 * 多卡充值-获取充值方式列表
 *
 * @package api\controllers\payment
 */
class ChargeChannelList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'bankCardId' => array('filter' => 'required', 'message' => 'bankCardId is required'),
            'code' => array('filter' => 'required', 'message' => 'code is required'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['token'])) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $data = $this->form->data;
        // 银行卡唯一标识
        $bankCardId = trim($data['bankCardId']);
        // 银行简码
        $bankCode = trim(strtoupper($data['code']));

        $obj = new ChargeService();
        $result = $obj->queryChannelList($userInfo['id'], $bankCardId, $bankCode);
        $this->json_data = $result;
    }
}