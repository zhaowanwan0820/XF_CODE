<?php
/**
 * 网信生活-网信出行-取消订单
*/
namespace api\controllers\life;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use core\service\life\UserTripService;
use NCFGroup\Protos\Life\Enum\TripEnum;

class TripCancelOrder extends LifeBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token'        => array('filter' => 'required', 'message' => 'token is required'),
            'merchantId'   => array('filter' => 'required', 'message' => 'merchantId is required'),
            'outOrderId'   => array('filter' => 'required', 'message' => 'outOrderId is required'),
            'tryCancel'    => array('filter' => 'required', 'message' => 'tryCancel is required'),
            'cancelReason' => array('filter' => 'string'),
            'cancelDesc'   => array('filter' => 'string'),
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
            $data['userId'] = $userInfo['id']; // 用户ID
            $obj = new UserTripService();
            $response = $obj->cancelOrder($data);
            if (!isset($response['errorCode']) || $response['errorCode'] != 0) {
                throw new \Exception($response['errorMsg'], $response['errorCode']);
            }

            $this->json_data = $response['data'];
        } catch (\Exception $e) {
            if ($e->getCode() == 0) {
                $this->setErr($e->getMessage());
            } else {
                $this->setErr('ERR_MANUAL_REASON', $e->getMessage());
                $this->errno = $e->getCode();
            }
            return false;
        }
    }
}