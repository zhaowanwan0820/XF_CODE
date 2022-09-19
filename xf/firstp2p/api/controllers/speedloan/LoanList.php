<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;

/**
 * LoanList
 * 借款记录
 *
 * @uses BaseAction
 * @package default
 */
class LoanList extends SpeedLoanBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'page' => array('filter' => 'string'),
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
        $this->tpl->assign('token', $data['token']);
        $pageNo = !empty($data['page']) ? intval($data['page']) : 1;
        $loanService = new \core\service\speedLoan\LoanService();
        $loanList = $loanService->getLoanList($userInfo['id'], $pageNo, 10);
        $this->tpl->assign('loanList', json_encode($loanList));
    }
}
