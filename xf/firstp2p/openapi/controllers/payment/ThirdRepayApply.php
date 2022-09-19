<?php

/**
 * 享花等第三方项目请求还款划转接口
 * 
 * @author guofeng<guofeng3@ucfgroup.com>
 */

namespace openapi\controllers\payment;

use libs\web\Form;
use openapi\controllers\BaseAction;

/**
 * 享花等第三方项目请求还款划转接口
 * 
 */
class ThirdRepayApply extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'open_id' => array('filter' => 'required', 'message' => 'open_id is required'),
            'out_order_id' => array('filter' => 'required', 'message' => 'out_order_id is required'),
            'deal_id' => array('filter' => 'required', 'message' => 'deal_id is required'),
            'repay_id' => array('filter' => 'required', 'message' => 'repay_id is required'),
            'repay_money' => array('filter' => 'required', 'message' => 'repay_money is required'),
            'bankcard' => array('filter' => 'required', 'message' => 'bankcard is required'),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserByAccessToken();
        if (empty($userInfo)) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $data = $this->form->data;
        if (empty($data['out_order_id']) || !is_numeric($data['out_order_id'])) {
            $this->setErr('ERR_PARAMS_ERROR', '外部订单号不能为空或格式不正确');
            return false;
        }
        if (empty($data['deal_id']) || !is_numeric($data['deal_id'])) {
            $this->setErr('ERR_PARAMS_ERROR', '标的ID不能为空或格式不正确');
            return false;
        }
        if ((int)$data['repay_id'] < 0 || !is_numeric($data['repay_id'])) {
            $this->setErr('ERR_PARAMS_ERROR', '还款记录ID不能为负数或格式不正确');
            return false;
        }
        if ((int)$data['repay_money'] < 0 || !is_numeric($data['repay_money'])) {
            $this->setErr('ERR_PARAMS_ERROR', '还款金额不能为空或格式不正确');
            return false;
        }
        if (empty($data['bankcard']) || !is_numeric($data['bankcard'])) {
            $this->setErr('ERR_PARAMS_ERROR', '电子银行账号不能为空或格式不正确');
            return false;
        }

        // 请求参数
        $params = [
            'out_order_id' => (int)$data['out_order_id'],
            'user_id' => (int)$userInfo->userId,
            'deal_id' => (int)$data['deal_id'],
            'repay_id' => (int)$data['repay_id'],
            'repay_money' => (int)$data['repay_money'],
            'bankcard' => addslashes($data['bankcard']),
        ];
        // 请求划扣受理接口
        $loanThirdService = new \core\service\LoanThirdService();
        $repayRet = $loanThirdService->repayCreateLoanApply($params);
        $this->json_data = $repayRet;
        return true;
    }
}