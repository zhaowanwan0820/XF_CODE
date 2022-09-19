<?php
namespace api\controllers\candysnatch;

use api\controllers\AppBaseAction;
use core\service\candy\CandySnatchService;
use libs\web\Form;

/**
 * 信宝夺宝-夺宝
 */
class SnatchAction extends AppBaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'token不能为空'),
            'periodId' => array('filter'=>'required', 'message'=> '期号不能为空'),
            'amount' => array('filter'=>'required', 'message'=> '夺宝数不能为空'),
        );
        if (!$this->form->validate()) {
            return $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
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

        $codes = $candySnatchService->snatchAction($loginUser['id'], $data['periodId'], $data['amount']);

        $this->json_data = [
            'codes' => $codes,
        ];
    }
}