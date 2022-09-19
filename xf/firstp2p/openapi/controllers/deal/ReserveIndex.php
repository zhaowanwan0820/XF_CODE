<?php
/**
 * 短期标预约-预约首页
 *
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;
use libs\utils\Logger;
use core\service\risk\RiskServiceFactory;
use core\service\ReservationConfService;
use core\service\UserReservationService;
use core\dao\ReservationConfModel;
use libs\utils\Risk;

class ReserveIndex extends ReserveBaseAction
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
        if(!$userInfo = $this->getUserByAccessToken()){
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        if(!$this->isOpenReserve()){
            $this->setErr('ERR_RESERVE_CLOSE');
            return false;
        }

        $userId = $userInfo->userId;
        $this->chkUserOfter($userInfo->userId);

        $data = $this->form->data;

        //获取后台配置的预约标通知
        $reservationConfService = new ReservationConfService();
        $reserveNotice = $reservationConfService->getReserveInfoByType(ReservationConfModel::TYPE_NOTICE);

        $isReserve = 0;

        $resData['banner_uri'] = $reserveNotice['banner_uri'];
        $userReservationService = new UserReservationService();
        $resData['isReserve'] = $userReservationService->checkUserIsReserve($userId);
        $resData['userInfo'] = $userInfo; 
        $resData['isBookingButtonUnused'] = $this->_isBookingButtonUnused;
        $detail_herf = sprintf('details?%s&line_unit=', 'ref=1');
        $description = str_replace('$detailherf', $detail_herf, $reserveNotice['description']);
        $resData['description'] = $description;
        $this->json_data = $resData;
    }
}
