<?php

namespace openapi\controllers\account;

use libs\rpc\Rpc;
use libs\web\Form;
use openapi\controllers\BaseAction;
use openapi\conf\Error;
/**
 * 合同列表
 */
class Contract extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "pn"=> array('filter' => 'int', 'option' => array('optional' => true)),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {

        $return = array();
        $data = $this->form->data;
        $pageNum = $data['pn'] <= 0 ? 1 : $data['pn'];
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $userId = $userInfo->userId;
        $userName = $userInfo->userName;
        $userInfo = array('id' => $userId,'user_name' => $userName);
        $list = $this->rpc->local('ContractService\getContDealList', array($userInfo, $pageNum));
        $ret = array('count'=>$list['count']);
        $ret['list'] = array();
        foreach($list['list'] as $one){
            $tmp = array();
            $tmp['dealId'] = $one['id'];
            $tmp['dealOldName'] = $one['old_name'];
            $tmp['dealName'] = $one['name'];
            $tmp['isHaveSign'] = $one['is_have_sign'];
            $tmp['userRealName'] = $one['user_real_name'];
            $tmp['incomeFeeFormat'] = $one['income_fee_rate_format'];
            $tmp['loanTypeName'] = $one['loantype_name'];
            $tmp['borrowAmount'] = $one['borrow_amount'];
            $tmp['isBxt'] = $one['isBxt'];
            $tmp['maxRate'] = floatval($one['max_rate']);
            $tmp['period'] = ($one['loantype'] == 5) ? $one['repay_time'] . '天' : $one['repay_time'] . '个月';
            $tmp['isDealZX'] = $one['isDealZX'];
            $tmp['income_base_rate'] = $one['income_base_rate'];
            $ret['list'][] = $tmp;
        }
        $this->json_data = $ret;
    }
}
