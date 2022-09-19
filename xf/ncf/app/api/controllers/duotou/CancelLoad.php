<?php
/**
 * 多投宝取消投资
 * @author wangchuanlu@ucfgroup.com
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\duotou\DtCancelService;

class CancelLoad extends DuotouBaseAction
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
            'loan_id' => array( //投资记录Id
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
        $loanId = intval($this->form->data['loan_id']);
        $user = $this->user;

        $userId = $user['id'];
        $oDtCancelService = new DtCancelService();
        $res = $oDtCancelService->cancelDealLoan($userId,$loanId);
        $return = array('data'=>$res);
        $this->json_data = $return;
    }

}
