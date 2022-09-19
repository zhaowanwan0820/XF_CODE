<?php
namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use core\service\LoanIntentionService;
use libs\utils\ABControl;

/**
 * LoanIndex
 * 首页
 *
 * @uses BaseAction
 * @package default
 */
class LoanIndex extends AppBaseAction
{

    const IS_H5 = true;

    public function init()
    {

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user_info = $this->getUserByToken();
        // 判断是否是网信房贷的用户
        $isHouseUser = $this->rpc->local('HouseService\getUserStatus', array($user_info['id']), 'house');

        // 银信通
        $is_credit_loan_user = false;
        if(app_conf('CREDIT_LOAN_SWITCH') == 1) { // 信用贷功能开关
            $is_credit_loan_user = $this->rpc->local('CreditLoanService\isShowCreditEntrance', array($user_info['id']));
        }
        $showSpeedLoan= true;
        if (app_conf('SPEED_LOAN_SWITCH') != 1) {
            $showSpeedLoan = false;
        }
        $is_bxt_user = $this->rpc->local('CreditLoanService\isBXTUser', array($user_info['id'])); // 变现通入口条件
        $is_job_loan_user = $this->rpc->local('CreditLoanService\isJobLoanUser', array($user_info['id']));  // 职易贷入口条件
        $isHouseOpen = $this->rpc->local('HouseService\isHouseOpen', array(), 'house');

        $this->tpl->assign('idcardpassed', $user_info['idcardpassed']);     // 判断是否是网信实名认证账户
        $this->tpl->assign('is_house_user', $isHouseUser);                  // 判断是否是网信房贷用户
        $this->tpl->assign('isHouseOpen', $isHouseOpen);
        $this->tpl->assign('is_credit_loan_user', $is_credit_loan_user);
        $this->tpl->assign('showSpeedLoan', $showSpeedLoan);
        $this->tpl->assign('is_bxt_user', $is_bxt_user);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('code_bxt', LoanIntentionService::SPECIAL_SUPER_BXT_CODE);
        $this->tpl->assign('code_job_loan', LoanIntentionService::SPECIAL_XFD_CODE);
        $this->tpl->assign('timeSeed', microtime());


        // 如果是速贷
        if (ABControl::getInstance()->hit('speedLoan')) {
            $this->template = $this->getTemplate('new_loan_index');
            return ;
        } else {
            $this->template = $this->getTemplate('loan_index');
        }
    }
}
