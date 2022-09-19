<?php
/**
 * 随心约信息披露 标的列表
 *
 * @date 2018-01-12
 * @author weiwei12@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\dao\reserve\UserReservationModel;
use core\enum\ReserveEnum;

class ReserveDisclosureList extends ReserveBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'invest' => array('filter' => 'required', 'message' => 'invest is required'),
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
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;
        if (false === strpos($data['invest'], '_')) {
            $this->setErr('ERR_MANUAL_REASON', '投资期限参数不合法');
            return false;
        }
        list($investDeadline, $investDeadlineUnit) = explode('_', $data['invest']);

        //是否存在预约
        $reserveList = UserReservationModel::instance()->getUserReserveListByDeadline($userInfo['id'], ReserveEnum::RESERVE_STATUS_ING, $investDeadline, $investDeadlineUnit);
        $hasReserve = !empty($reserveList) ? true : false;

        $this->json_data = array(
            'invest' => $data['invest'],
            'hasReserve' => $hasReserve,
            'userClientKey' => $data['userClientKey']
        );
    }
}
