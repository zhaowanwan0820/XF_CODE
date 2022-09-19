<?php
/**
 * 短期标预约
 * 获取起投金额
 *
 * @date 2017-11-15
 * @author weiwei12@ucfgroup.com
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;
use core\service\ReservationDealService;
use libs\utils\Logger;

class ReserveMinLoanMoney extends ReserveBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'invest' => array('filter' => 'required', 'message' => 'invest is required'),
            'deal_type' => array('filter' => 'required', 'message' => 'deal_type is required'),
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

        $data = $this->form->data;
        if (false === strpos($data['invest'], '_')) {
            $this->setErr('ERR_MANUAL_REASON', '投资期限参数不合法');
            return false;
        }
        list($investDeadline, $investDeadlineUnit) = explode('_', $data['invest']);
        $dealType = $data['deal_type'];

        // 获取起投金额
        $reservationDealService = new ReservationDealService();
        $money = $reservationDealService->getReserveMinLoanMoney($dealType, $investDeadline, $investDeadlineUnit);
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $money)));
        $this->json_data = ['money' => $money];
        return true;
    }
}
