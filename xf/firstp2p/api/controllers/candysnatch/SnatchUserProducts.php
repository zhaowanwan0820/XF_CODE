<?php
namespace api\controllers\candysnatch;
use core\service\candy\CandySnatchService;
use api\controllers\AppBaseAction;
use libs\web\Form;

/**
 * 信宝夺宝-我的夺宝
 */
class SnatchUserProducts extends AppBaseAction
{
    const IS_H5 = true;
    const USER_PRODUCT_LIST_LIMIT = 30;
    public function init()
    {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message'=> 'token不能为空'),
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
        $userId = $loginUser['id'];

        $candySnatchService = new CandySnatchService();
        //剩余夺宝机会（次）
        $availableCount = $candySnatchService->getUserCodeAvailable($userId);

        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('currentUserId', $userId);
        $this->tpl->assign('availableCount', $availableCount);
    }
}