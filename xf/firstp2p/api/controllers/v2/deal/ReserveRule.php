<?php
/**
 * 短期标预约-预约首页的“预约规则说明”
 *
 * @date 2016-11-18
 * @author guofeng@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;
use core\service\ReservationConfService;
use core\dao\ReservationConfModel;
use core\dao\UserReservationModel;

class ReserveRule extends ReserveBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
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
        // 获取后台配置的预约标通知
        $reservationConfService = new ReservationConfService();
        $type = ReservationConfModel::TYPE_NOTICE;
        $reserveNotice = $reservationConfService->getReserveInfoByType($type);
        $data = [
            'reserve_rule' => !empty($reserveNotice['reserve_rule']) ? htmlspecialchars_decode($reserveNotice['reserve_rule']) : '',
        ];
        $this->json_data = $data;
        return true;
    }
}
