<?php
/**
 * @wap站预约详情页
 * @author:liuzhenpeng
 * @date:2017-05-15
 */

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\controllers\ReserveBaseAction;
use core\service\ReservationConfService;
use core\service\UserReservationService;
use core\service\ReservationCardService;
use core\dao\ReservationConfModel;
use libs\utils\Logger;

class ReserveDetail extends ReserveBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'line_unit' => array('filter' => 'required', 'message' => 'line_unit is required'),
            'access_check' => array('filter' => 'int', 'option' => array('optional' => true)),
            'deal_type' => array('filter' => 'int'),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

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

        $data = $this->form->data;
        $dealType = !empty($data['deal_type']) ? (int) $data['deal_type'] : 0;
        $access_check = (int) $data['access_check'];
        $access_check = ($access_check !== 0 &&  $access_check !== 1) ? 1 : $access_check;
        if(($access_check == 1) && !$userInfo = $this->getUserByAccessToken()){
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $reservationConfService = new ReservationConfService();
        //获取后台配置的预约标配置信息
        $reserveConf = $reservationConfService->getReserveInfoByType(ReservationConfModel::TYPE_CONF);
        if(empty($reserveConf) || empty($reserveConf['min_amount'])){
            $this->setErr('ERR_MANUAL_REASON', '尚未配置预约信息');
            return false;
        }

        if(empty($reserveConf['invest_conf'])){
            $this->setErr('ERR_MANUAL_REASON', '尚未配置投资期限');
            return false;
        }

        if(empty($reserveConf['reserve_conf'])){
            $this->setErr('ERR_MANUAL_REASON', '尚未配置预约有效期');
            return false;
        }

        $reservationCardService = new ReservationCardService();
        $reserveDetail = $reservationCardService->getReserveCardDetail($data["line_unit"]);

        // 最低预约金额,单位元
        $reserveLimitAmount = $reservationConfService->getReserveLimitAmountByDealType($dealType);
        $minAmount = $reserveLimitAmount['min_amount'];
        $maxAmount = $reserveLimitAmount['max_amount'];
        //用于页面显示的授权金额
        $authorizeAmountString = $reservationConfService->getAuthorizeAmountString($reserveLimitAmount['min_amount'], $reserveLimitAmount['max_amount']);

        Logger::info(implode(' | ', array(__CLASS__, 'ReserveDetail', WAP, $data["line_unit"], $userId)));

        $isReserve = 0;

        //用户ID
        $userId = $userInfo->userId;

        $resData['min_amount'] = $minAmount;
        $resData['max_amount'] = $maxAmount;
        $resData['is_identify'] = ($access_check == 0) ? 1 : ($userInfo->idcardPassed == 1) ? 1 : 0; //是否已实名认证
        $resData['is_reserve']  = (new UserReservationService())->checkUserIsReserve($userId);
        $resData['invest_line'] = $reserveDetail['investLine']; //投资期限
        $resData['invest_unit'] = $reserveDetail['investUnit']; //时间单位 个月or天
        $resData['rate']        = $reserveDetail['rate'];   //预期年化
        $resData['tagBefore']   = $reserveDetail['tagBefore'];  //头部标签1
        $resData['tagAfter']    = $reserveDetail['tagAfter']; //头部标签2
        $resData['amount']      = $reserveDetail['amount']; //已预约投资金额
        $resData['countDisplay']= $reserveDetail['countDisplay'];   //是否启用 显示预约人次
        $resData['count']       = $reserveDetail['count'];  //已投统计
        $resData['deal_type']   = $reserveDetail['dealType'];  //deal_type
        $resData['description'] = !empty($reserveDetail['description']) ? htmlspecialchars_decode($reserveDetail['description']) : '';
        $siteId = \libs\utils\Site::getId();
        $isBookingButtonUnused = (int) get_config_db('BOOKING_BUTTON_UNUSED', $siteId);
        $resData['isBookingButtonUnused'] = $isBookingButtonUnused;

        $this->json_data = $resData;
    }
}
