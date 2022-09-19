<?php
/**
 * 短期标预约-取消预约的按钮
 *
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;
use core\service\UserReservationService;
use libs\utils\Logger;

class ReserveCancel extends ReserveBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'id'   => array('filter' => 'required', 'message' => 'id is required'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if(!$this->form->validate()){
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        if(!$this->isOpenReserve()){
            $this->setErr('ERR_RESERVE_CLOSE', '随心约关闭');
            return false;
        }

        if(!$userInfo = $this->getUserByAccessToken()){
            $this->setErr('ERR_TOKEN_ERROR', 'Token不正确');
            return false;
        }

        $data = $this->form->data;
        if (empty($data['id']) || !is_numeric($data['id'])) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }

        //取消预约
        $userReservationService = new UserReservationService();
        $ret = $userReservationService->cancelUserReserve(intval($data['id']), $userInfo->userId);
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, WAP, 'ReserveCancelEnd', json_encode(array('ret'=>$ret)))));
        $this->json_data = array('code'=>($ret ? 0 : 1));
        return true;
    }
}
