<?php
/**
 * 短期标预约-预约首页的“预约规则说明”
 *
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;
use core\service\ReservationConfService;
use core\dao\ReservationConfModel;

class ReserveRule extends ReserveBaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = $this->sys_param_rules;

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if(!$this->isOpenReserve()){
            $this->setErr('ERR_RESERVE_CLOSE');
            return false;
        }

        $reserveNotice = (new ReservationConfService())->getReserveInfoByType(ReservationConfModel::TYPE_NOTICE); //获取后台配置的预约标通知
        $notice = !empty($reserveNotice['reserve_rule']) ? htmlspecialchars_decode($reserveNotice['reserve_rule']) : '';

        $this->json_data = $notice;
        return true;
    }
}
