<?php
/**
 * 多投宝取消投资列表
 * @author wangchuanlu@ucfgroup.com
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\duotou\DtCancelService;

class CancelLoadlist extends DuotouBaseAction
{
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
        $user = $this->user;

        $userId = $user['id'];
        $dtCancelService = new DtCancelService();
        $res = $dtCancelService->getCanCancelDealLoans($userId);
        $return = array('data'=>$res);
        $this->json_data = array('list' => $return);
    }

}
