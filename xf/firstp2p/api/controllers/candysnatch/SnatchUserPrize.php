<?php
namespace api\controllers\candysnatch;

use api\controllers\AppBaseAction;
use core\service\candy\CandySnatchService;
use libs\web\Form;

/**
 * 信宝夺宝-我的奖品
 */
class SnatchUserPrize extends AppBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'token不能为空'),
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

        $candySnatchService = new CandySnatchService();

        $period = $candySnatchService->getUserPrize($loginUser['id']);

        if (!empty($period)) {
            $period = $candySnatchService->attachProductInfo($period);
        }

        $this->tpl->assign('period', $period);
        $this->tpl->assign('token', $data['token']);
    }
}