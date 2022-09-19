<?php
/**
 * 网信生活-网信出行-创建订单
*/
namespace api\controllers\life;
use api\controllers\LifeBaseAction;
use libs\web\Form;
use core\service\life\UserTripService;

class TripCreateOrder extends LifeBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'token is required'),
            'merchantId' => array('filter' => 'required', 'message' => 'merchantId is required'),
            'cityCode' => array('filter' => 'required', 'message' => 'cityCode is required'),
            'serviceType' => array('filter' => 'required', 'message' => 'serviceType is required'),
            'carTypeId' => array('filter' => 'required', 'message' => 'carTypeId is required'),
            'passengerPhone' => array('filter' => 'required', 'message' => 'passengerPhone is required'),
            'passengerName' => array('filter' => 'required', 'message' => 'passengerName is required'),
            'estimateAmount' => array('filter' => 'required', 'message' => 'estimateAmount is required'),
            'estimateMileage' => array('filter' => 'required', 'message' => 'estimateMileage is required'),
            'estimateTime' => array('filter' => 'required', 'message' => 'estimateTime is required'),
            'fromAddress' => array('filter' => 'string'), // 出发地地址
            'fromLongitude' => array('filter' => 'string'), // 出发地经度坐标
            'fromLatitude' => array('filter' => 'string'), // 出发地纬度坐标
            'toAddress' => array('filter' => 'string'), // 目的地地址
            'toLongitude' => array('filter' => 'string'), // 目的地经度坐标
            'toLatitude' => array('filter' => 'string'), // 目的地纬度坐标
            'bookTime' => array('filter' => 'string'), // 乘车时间
            'flightNo' => array('filter' => 'string'), // 航班号
            'msgBoard' => array('filter' => 'string'), // 客户留言
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
            $response = $obj->createOrder($data);
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