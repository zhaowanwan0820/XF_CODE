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
use core\dao\ReservationConfModel;
use core\dao\UserReservationModel;

class ReserveDisclosureList extends ReserveBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'invest' => array('filter' => 'required', 'message' => 'invest is required'),
            'product_type' => array('filter' => 'int'),
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
        $reserveList = UserReservationModel::instance()->getUserReserveListByDeadline($userInfo['id'], UserReservationModel::RESERVE_STATUS_ING, $investDeadline, $investDeadlineUnit);
        $hasReserve = !empty($reserveList) ? true : false;

        //随心约产品类型
        $productType = !empty($data['product_type']) ? (int) $data['product_type'] : UserReservationModel::PRODUCT_TYPE_P2P;
        $this->tpl->assign('product_type', $productType);

        $this->tpl->assign("invest", $data['invest']);
        $this->tpl->assign("hasReserve", $hasReserve);
        $this->tpl->assign('userClientKey', $data['userClientKey']);
    }
}
