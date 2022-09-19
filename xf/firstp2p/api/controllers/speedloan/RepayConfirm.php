<?php
namespace api\controllers\speedloan;

use libs\web\Form;
use api\controllers\SpeedLoanBaseAction;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Protos\Creditloan\Enum\CreditLoanEnum;
use NCFGroup\Protos\Creditloan\Enum\RepayApplyEnum;
use core\service\speedLoan\LoanService;
use core\service\speedLoan\RepayService;
use core\service\UserService;

/**
 * RepayConfirm
 * 确认还款申请
 *
 * @uses BaseAction
 * @package default
 */
class RepayConfirm extends SpeedLoanBaseAction
{

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),
            'repay_amount' => array('filter' => 'required', 'message' => 'ERR_PARAMS_ERROR'),
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
        $loanId = (int) $data['id']; //借款id
        $repayAmount = (int) $data['repay_amount']; //还款金额

        $creditSerivce = new LoanService();
        //用户借款信息
        try {
            $creditLoanInfo = $creditSerivce->getCreditLoanById($loanId, $userId);
        } catch (\Exception $e) {
        }
        if (empty($creditLoanInfo)) {
            $this->setErr('ERR_SPEEDLOAN_REPAY_APPLY_FAIL');
            return false;
        }

        if (!$this->isServiceTime()) {
            $tomorrow = '';
            if (intval(date('His')) > str_replace(';','', app_conf('SPEED_LOAN_SERVICE_HOUR_END').'00')) {
                $tomorrow = '次日';
            } else if (intval(date('His')) < str_replace(';','', app_conf('SPEED_LOAN_SERVICE_HOUR_START').'00')) {
                $tomorrow = '今日';
            }
            $this->setErr('ERR_MANUAL_REASON', '温馨提示：请于'.$tomorrow.str_replace(';',':', app_conf('SPEED_LOAN_SERVICE_HOUR_START')).'后操作在线还款');
            return false;
        }
        //检查还款金额是否发生变化
        if ($creditLoanInfo['repayAmount'] != $repayAmount) {
            $this->setErr('ERR_SPEEDLOAN_REPAY_APPLY_FAIL');
            return false;
        }

        //检查借款状态，处理中
        if ($creditLoanInfo['loanStatus'] != CreditLoanEnum::LOAN_STATUS_SUCESS) {
            $this->setErr('ERR_SPEEDLOAN_REPAY_APPLY_FAIL');
            return false;
        }

        //检查余额
        $userService = new UserService();
        $moneyInfo = $userService->getMoneyInfo($userInfo, 0);
        $balance = $moneyInfo['lc'];
        $repayAmount = bcdiv($creditLoanInfo['repayAmount'], 100, 2);
        if (bccomp($balance, $repayAmount, 2) == -1) {
            $this->setErr('ERR_SPEEDLOAN_REPAY_BALANCE_NOT_ENOUGTH');
            return false;
        }
        $orderId = Idworker::instance()->getId();//创建还款单号

        //申请还款
        $creditRepaySerivce = new RepayService();
        $params = [
            'loanId' => $loanId,
            'userId' => $userId,
            'orderId' => $orderId,
            'principal' => $creditLoanInfo['principalWaiting'], //本金
            'interest' => $creditLoanInfo['interestWaiting'], //利息
            'serviceFee' => $creditLoanInfo['serviceFeeWaiting'], //服务费
        ];
        $result = $creditRepaySerivce->doApplyRepay($params);
        if (!$result) {
            $this->setErr('ERR_SPEEDLOAN_REPAY_APPLY_FAIL');
            return false;
        }
        $this->json_data  = [
            'orderId' => $orderId,
            'token' => $data['token'],
        ];
    }
}
