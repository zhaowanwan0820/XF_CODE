<?php

namespace api\controllers\candysnatch;

use api\controllers\AppBaseAction;
use core\service\candy\CandySnatchService;
use libs\web\Form;

/**
 * 信宝夺宝-往期记录
 */
class SnatchPastPeriod extends AppBaseAction
{
    const PAST_PERIOD_LIST_LIMIT = 30;

    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'token不能为空'),
            'offset' => array('filter' => 'required', 'message' => '页码不能为空'),
        );
        if (!$this->form->validate()) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL',$this->form->getErrorMsg());
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        $offset = $data['offset'];
        if (empty($loginUser)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }
        $candySnatchService = new CandySnatchService();
        //往期记录
        $pastAuction = $candySnatchService->getPastPeriodList($offset * self::PAST_PERIOD_LIST_LIMIT, self::PAST_PERIOD_LIST_LIMIT);
        $this->json_data = [
            $pastAuction
        ];
    }
}