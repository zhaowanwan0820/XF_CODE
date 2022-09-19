<?php
namespace api\controllers\candysnatch;

use api\controllers\AppBaseAction;
use core\service\candy\CandySnatchService;
use libs\web\Form;

/**
 * 信宝夺宝-参与记录
 */
class SnatchOrders extends AppBaseAction
{
    const IS_H5 = true;
    const LIMITRECORD = 30;//分页显示参与记录
    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'token不能为空'),
            'periodId' => array('filter'=>'required', 'message'=> '期号不能为空'),
            'offset' => array('filter'=>'required', 'message'=> '页码不能为空'),
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
        $offset = empty($data['offset'])? 0 : $data['offset'];
        $candySnatchService = new CandySnatchService();
        $periodOrders = $candySnatchService->getPeriodOrders($data['periodId'], $offset * self::LIMITRECORD, self::LIMITRECORD);

        $this->tpl->assign('periodOrders', $periodOrders);
        $this->tpl->assign('token', $data['token']);
    }
}
