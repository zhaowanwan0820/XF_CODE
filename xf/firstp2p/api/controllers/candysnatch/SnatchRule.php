<?php
namespace api\controllers\candysnatch;

use api\controllers\AppBaseAction;
use core\service\candy\CandySnatchService;
use libs\web\Form;

/**
 * 信宝规则
 */
class SnatchRule extends AppBaseAction
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

        $this->tpl->assign('annualizedAmount', app_conf('CANDY_SNATCH_ANNUALIZED_AMOUNT_CODE_RATE'));
        $this->tpl->assign('presentAmount', app_conf('CANDY_SNATCH_CODE_PRESENT'));
        $this->tpl->assign('startTime', $candySnatchService::SNATCH_START_TIME);
        $this->tpl->assign('endTime', $candySnatchService::SNATCH_END_TIME);
        $this->tpl->assign('token', $data['token']);
    }
}