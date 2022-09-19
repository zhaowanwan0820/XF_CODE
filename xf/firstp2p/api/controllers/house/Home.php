<?php
/**
 * 网信房贷 首页
 * @author sunxuefeng sunxuefeng@ucfgroup
 * @data 2017.9.29
 */

namespace api\controllers\house;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\LoanIntentionService;

class Home extends AppBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_TOKEN_ERROR')
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        // 判断是否是网信房贷的用户
        $isHouseUser = $this->rpc->local('HouseService\getUserStatus', array($loginUser['id']), 'house');

        $is_credit_loan_user = false;
        // 信用贷功能开关
        if (app_conf('CREDIT_LOAN_SWITCH') == 1) {
            $is_credit_loan_user = $this->rpc->local('CreditLoanService\isCreditLoanUser', array($loginUser['id']));
        }

        // 变现通入口条件
        $is_bxt_user = $this->rpc->local('CreditLoanService\isBXTUser', array($loginUser['id']));

        // 职易贷入口条件
        $is_job_loan_user = $this->rpc->local('CreditLoanService\isJobLoanUser', array($loginUser['id']));

        $this->tpl->assign('idcardpassed', $loginUser['idcardpassed']);     // 判断是否是网信实名认证账户
        $this->tpl->assign('is_house_user', $isHouseUser);
        $this->tpl->assign('is_credit_loan_user', $is_credit_loan_user);
        $this->tpl->assign('is_bxt_user', $is_bxt_user);
        $this->tpl->assign('is_job_loan_user', $is_job_loan_user);
        $this->tpl->assign('token', $data['token']);
        $this->tpl->assign('code_bxt', LoanIntentionService::SPECIAL_SUPER_BXT_CODE);
        $this->tpl->assign('code_job_loan', LoanIntentionService::SPECIAL_XFD_CODE);
        $this->template = $this->getTemplate('net_mortgage_profile');
    }
}
