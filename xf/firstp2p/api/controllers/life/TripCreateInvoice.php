<?php
/**
 * 网信生活-网信出行-创建发票
*/
namespace api\controllers\life;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use core\service\life\UserTripService;

class TripCreateInvoice extends LifeBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'merchantId' => array('filter' => 'required', 'message' => 'merchantId is required'),
            'outOrderId' => array('filter' => 'required', 'message' => 'outOrderId is required'),
            'amount' => array('filter' => 'required', 'message' => 'amount is required'),
            'title' => array('filter' => 'required', 'message' => 'title is required'),
            'userName' => array('filter' => 'required', 'message' => 'userName is required'),
            'phone' => array('filter' => 'required', 'message' => 'phone is required'),
            'address' => array('filter' => 'required', 'message' => 'address is required'),
            'invoiceType' => array('filter' => 'required', 'message' => 'invoiceType is required'),
            'typeId' => array('filter' => 'int'),
            'province' => array('filter' => 'string'),
            'city' => array('filter' => 'string'),
            'area' => array('filter' => 'string'),
            'companyCode' => array('filter' => 'string'),
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
            $response = $obj->createInvoice($data);
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