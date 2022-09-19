<?php
namespace api\controllers\payment;

use NCFGroup\Common\Library\Idworker;
use libs\web\Form;
use libs\utils\Logger;
use api\controllers\AppBaseAction;
use core\service\PaymentService;
use core\dao\SupervisionTransferModel;

class OfflineChargeOrderNum extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken(false);
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = (int)$userInfo['id'];
        $service = new PaymentService();
        $cnt = $service->getMyOfflineOrderNum($userId);
        $result['count'] = $cnt;
        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $data['token'])));
        $this->json_data = $result;
    }
}
