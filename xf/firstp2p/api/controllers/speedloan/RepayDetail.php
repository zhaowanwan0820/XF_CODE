<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;

/**
 * RepayDetail
 * 还款明细
 *
 * @uses BaseAction
 * @package default
 */
class RepayDetail extends SpeedLoanBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'loanId' => array('filter'=> 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        $loanService = new \core\service\speedLoan\LoanService();
        $creditInfo = $loanService->getCreditLoanInfoById($data['loanId']);
        // 还款流水记录查询
        $repayList = $loanService->getCreditRepayList($data['loanId']);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('creditInfo', $creditInfo);
        $this->tpl->assign('repayItems', json_encode($repayList));
        $this->tpl->assign('loanId', $data['loanId']);
    }
}
