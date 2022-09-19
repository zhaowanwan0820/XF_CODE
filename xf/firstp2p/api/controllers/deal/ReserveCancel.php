<?php
/**
 * 短期标预约-取消预约的按钮
 *
 * @date 2016-11-17
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\UserReservationService;
use libs\utils\Logger;

class ReserveCancel extends ReserveBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'id'   => array('filter' => 'required', 'message' => 'id is required'),
            'asgn' => array('filter' => 'string'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }

        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo))
        {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;
        if (empty($data['id']) || !is_numeric($data['id'])) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }

        // 取消预约
        $userReservationService = new UserReservationService();
        $ret = $userReservationService->cancelUserReserve(intval($data['id']), $userInfo['id']);
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, 'ReserveCancelEnd', json_encode(array('ret'=>$ret)))));
        $this->json_data = array('code'=>($ret ? 0 : 1));
        return true;
    }
}