<?php
/**
 * 短期标预约-我的预约页面
 *
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use core\dao\UserReservationModel;
use openapi\controllers\ReserveBaseAction;
use libs\utils\Logger;
use core\service\risk\RiskServiceFactory;
use core\service\ReservationConfService;
use core\service\UserReservationService;
use core\dao\ReservationConfModel;
use libs\utils\Risk;

class ReserveMy extends ReserveBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = $this->sys_param_rules;

        if(!$this->form->validate()){
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

        if(!$userInfo = $this->getUserByAccessToken()){
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $data = $this->form->data;
        $list = array('list'=>array(), 'count'=>0);
        $userAllReserveList = UserReservationModel::instance()->getUserReserveList($userInfo->userId, -1, 1, 1); //获取用户所有的预约列表
        $list['count'] = count($userAllReserveList);
        $siteId = \libs\utils\Site::getId();
        $isBookingButtonUnused = (int) get_config_db('BOOKING_BUTTON_UNUSED', $siteId);
        $list['isBookingButtonUnused'] = $isBookingButtonUnused;

        $this->json_data = $list;
        return true;
    }
}
