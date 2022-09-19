<?php

namespace api\controllers\candysnatch;


use api\controllers\AppBaseAction;
use core\service\candy\CandySnatchService;
use libs\web\Form;

/**
 * 信宝夺宝-正在热拍
 */
class SnatchAuction extends AppBaseAction
{
    const IS_H5 = true;
    const PRIZE_INFO_LIMIT = 30;

    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'token不能为空'),
            'clearCookie' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $this->tpl->assign('token', $data['token']);
        $candySnatchService = new CandySnatchService();
        //正在热拍
        $processAuction = $candySnatchService->getAuctionPeriodList();
        //悬浮条信息
        $recentPrizeInfo = $candySnatchService->getRecentPrizeInfo(self::PRIZE_INFO_LIMIT);

        $this->tpl->assign('processAuction', $processAuction);
        $this->tpl->assign('recentPrizeInfo', $recentPrizeInfo);
        $this->tpl->assign('clearCookie', $data['clearCookie']);
    }
}