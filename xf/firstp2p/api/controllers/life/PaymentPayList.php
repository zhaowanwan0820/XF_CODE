<?php
/**
 * 网信生活-收银台-付款方式列表
*/
namespace api\controllers\life;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use core\service\life\PaymentUserService;

class PaymentPayList extends LifeBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'merchantId' => array('filter' => 'required', 'message' => 'merchantId is required'),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
        return true;
    }

    public function invoke() {
        try {
            $userInfo = $this->getUserByToken();
            if (empty($userInfo)) {
                throw new \Exception('ERR_GET_USER_FAIL');
            }

            $data = $this->form->data;
            // 用户ID
            $data['userId'] = $userInfo['id'];
            $obj = new PaymentUserService();
            $response = $obj->getMyPayCardList($data['userId'], $data['merchantId']);
            $this->json_data = $response;
        } catch (\Exception $e) {
            if ($e->getCode() == 0) {
                $this->setErr($e->getMessage());
            } else {
                $this->setErr('ERR_MANUAL_REASON', $e->getMessage());
            }
            return false;
        }
    }
}