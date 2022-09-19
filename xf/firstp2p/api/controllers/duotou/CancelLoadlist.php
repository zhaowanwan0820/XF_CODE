<?php
/**
 * 多投宝取消投资列表
 * @author wangchuanlu@ucfgroup.com
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;

class CancelLoadlist extends DuotouBaseAction
{
    const IS_H5 = true;
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = $user['id'];
        $res = $this->rpc->local('DtCancelService\getCanCancelDealLoans', array($userId),'duotou');
        $return = array('data'=>$res);
        $this->tpl->assign("list", $return);
        $this->tpl->assign('token',$data['token']);

    }

    public function _after_invoke() {
        $this->afterInvoke();
        if($this->errno != 0){
            parent::_after_invoke();
        }
        $this->tpl->display($this->template);
    }

}
