<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use core\service\speedLoan\LoanService;
use core\service\UserService;
use NCFGroup\Protos\Creditloan\Enum\CreditLoanEnum;

/**
 * Repay
 * 还款申请页面
 *
 * @uses BaseAction
 * @package default
 */
class Repay extends SpeedLoanBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),
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
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userId = $userInfo['id'];
        $loanId = $data['id']; //借款id

        try {
            $creditSerivce = new LoanService();
            $creditLoanInfo = $creditSerivce->getCreditLoanById($loanId, $userId);
        } catch (\Exception $e) {
        }
        if (empty($creditLoanInfo)) {
            $this->setErr('ERR_SPEEDLOAN_REPAY_APPLY_FAIL');
            return false;
        }

        //检查状态，申请中
        if ($creditLoanInfo['loanStatus'] != CreditLoanEnum::LOAN_STATUS_SUCESS) {
            $this->setErr('ERR_SPEEDLOAN_REPAY_HAS_APPLIED');
            return false;
        }

        $userService = new UserService();
        $moneyInfo = $userService->getMoneyInfo($userInfo, 0);
        $balance = $moneyInfo['lc'];
        $balance = $balance < 0 ? 0 : $balance;

        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('balanceFormat', number_format($balance, 2));
        $this->tpl->assign('balance', $balance);
        $this->tpl->assign('id', $loanId);
        $this->tpl->assign('creditLoanInfo', $creditLoanInfo);
    }
}
