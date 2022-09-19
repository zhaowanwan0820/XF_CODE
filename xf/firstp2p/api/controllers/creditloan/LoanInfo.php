<?php

/**
 * LoanInfo.php
 * Descrition: 获取银信通借款信息
 * Author: zhaohui3@ucfgroup.com
 * Date: 2016-09-20
 */

namespace api\controllers\creditloan;

use libs\web\Form;
use api\controllers\AppBaseAction;

class LoanInfo extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array("token" => array("filter" => "required", "message" => "token不能为空"));
        $this->form->validate();

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return false;
        }

        $LoanInfo = $this->rpc->local('CreditLoanService\getCreditingLoanByUserId', array($userInfo['id']));
        $result = array(
                'loan_money' => '0.001',
                'plan_repay_money' => '0.00',
                'plan_time' => '',
                'url' => '/creditloan/applyList',
        );

        if (is_array($LoanInfo)) {
            foreach ($LoanInfo as $info) {
                $result['loan_money'] =  bcadd($result['loan_money'],$info['money'], 2);//借款总金额
                $result['plan_repay_money'] = bcadd(bcadd($info['money'], $info['interest'],2), $info['service_fee'],2);//预计还款金额
                $result['plan_time'] = date('Y-m-d',$info['plan_time']);//预计还款时间
            }
        }
        $this->json_data = $result;
    }

}
