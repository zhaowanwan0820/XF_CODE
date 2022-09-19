<?php
/**
 * 网信生活-网信出行-开发票历史列表接口
*/
namespace api\controllers\life;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use core\service\life\UserTripService;

class TripInvoiceHistoryList extends LifeBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'merchantId' => array('filter' => 'required', 'message' => 'merchantId is required'),
            'page' => array('filter' => 'int', 'option' => array('optional' => true)),
            'count' => array('filter' => 'int', 'option' => array('optional' => true)),
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
            $page = !empty($data['page']) ? (int)$data['page'] : 1;
            $count = !empty($data['count']) ? (int)$data['count'] : 10;
            $obj = new UserTripService();
            $response = $obj->getInvoiceHistoryTripList($userInfo['id'], $data['merchantId'], $page, $count);
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