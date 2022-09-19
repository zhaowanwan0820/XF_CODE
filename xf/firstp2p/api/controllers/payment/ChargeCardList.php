<?php
namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\UserBankcardService;

/**
 * 多卡充值-获取充值卡列表
 *
 * @package api\controllers\payment
 */
class ChargeCardList extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
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

        try {
            $obj = new UserBankcardService();
            $result = $obj->queryCardListLimits($userInfo['id']);
        } catch (\Exception $e) {
            $this->setErr('ERR_MANUAL_REASON', '获取银行卡失败');
            return false;
        }
        $this->json_data = !empty($result) ? $result : [];
    }
}