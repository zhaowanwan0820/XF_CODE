<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;

use NCFGroup\Protos\Creditloan\Enum\CreditLoanEnum;
/**
 * LoanDetail
 * 借款详情
 *
 * @uses BaseAction
 * @package default
 */
class LoanDetail extends SpeedLoanBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=>'ERR_AUTH_FAIL'),
            'orderId' => array('filter'=> 'required'),
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
        $loanInfo = $loanService->getCreditLoanInfoById($data['orderId']);
        $this->tpl->assign('loanInfo', $loanInfo);
        $this->tpl->assign('token', $data['token']);
    }
}
