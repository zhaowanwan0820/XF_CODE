<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use NCFGroup\Protos\Creditloan\Enum\CreditUserEnum;

/**
 * Loan
 * 申请借款页面
 *
 * @uses BaseAction
 * @package default
 */
class Loan extends SpeedLoanBaseAction
{
    const IS_H5 = true;

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
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
            $this->setErr('ERR_AUTH_FAIL');
            return false;
        }
        $userId = $userInfo['id'];

        // 限制用户借款, 前端禁止用户提交借款申请 不在服务时间内， 用户状态被禁用
        if (!$this->isServiceOpen() ||  $creditUserInfo['account_status'] == CreditUserEnum::ACCOUNT_STATUS_DISABLE) {
            $this->tpl->assign('serviceNotOpen', 1);
        }
        // 速贷信息
        $creditSerivce = new \core\service\speedLoan\LoanService();
        $creditUserInfo = $creditSerivce->getUserCreditInfo($userId);
        // 银行卡信息
        $bankcardInfo = (new \core\service\UserBankcardService())->getCreditBankInfo($userId);
        $creditUserInfo['bankAndCardFormat'] = $bankcardInfo['bankAndCardFormat'];
        // 借款期限信息
        $loanDays = '';
        if (empty($creditUserInfo['repay_date']) || $creditUserInfo['repay_date'] == '0000-00-00') {
            $loanDays = '-';
        } else {
            $loanDays = (new \core\service\speedLoan\LoanService())->getLoanDays($creditUserInfo['repay_date']);
            $loanDays .= '天';
        }
        $this->tpl->assign('loanDays', $loanDays);
        // 校验方式
        $validateMethod = $this->getLoanValidateMethod($userId);
        $this->tpl->assign('validateMethod', $validateMethod);
        $this->tpl->assign('limitMinFormat', number_format(app_conf('SPEED_LOAN_MIN_AMOUNT'),2));
        $this->tpl->assign('limitMaxFormat', $creditUserInfo['usableAmountFormat']);
        $this->tpl->assign('limitMin', app_conf('SPEED_LOAN_MIN_AMOUNT'));
        $this->tpl->assign('onceMax', app_conf('SPEED_LOAN_MAX_AMOUNT'));
        $this->tpl->assign('usableAmount', bcdiv($creditUserInfo['usable_amount'], 100, 2));
        $this->tpl->assign('limitMax', bcdiv($creditUserInfo['usable_amount'], 100, 2));
        $this->tpl->assign('phone', $userInfo['mobile']);
        $this->tpl->assign('dailyRate', app_conf('SPEED_LOAN_DAILY_RATE'));
        $this->tpl->assign('serviceFeeRate', app_conf('SPEED_LOAN_SERVICE_RATE'));
        $this->tpl->assign('creditUserInfo', $creditUserInfo);
        $this->tpl->assign('token', $data['token']);
    }
}
