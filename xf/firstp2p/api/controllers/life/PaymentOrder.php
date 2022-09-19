<?php
/**
 * 网信生活-收银台-支付订单
*/
namespace api\controllers\life;
use NCFGroup\Protos\Life\Enum\ErrorCode;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use core\service\life\PaymentUserService;

class PaymentOrder extends LifeBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token'      => array('filter' => 'required', 'message' => 'token is required'),
            'merchantId' => array('filter' => 'required', 'message' => 'merchantId is required'),
            'payOrderId' => array('filter' => 'required', 'message' => 'payOrderId is required'),
            'outOrderId' => array('filter' => 'required', 'message' => 'outOrderId is required'),
            'cardId'     => array('filter' => 'required', 'message' => 'cardId is required'),
            'amount'     => array('filter' => 'int'),
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
            $response = $obj->paymentOrder($data);
            if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
                throw new \Exception($response['errorMsg'], $response['errorCode']);
            }

            $this->json_data = $response['data'];
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            if ($errorCode == 0) {
                $this->setErr($e->getMessage());
            } else if ($errorCode == ErrorCode::JOBS_NETWORK_FAILED) {
                $this->setErr('ERR_LIFE_NETWORK_FAILED');
            } else {
                $this->setErr('ERR_MANUAL_REASON', $e->getMessage());
                $this->errno = $e->getCode();
            }
            return false;
        }
    }
}