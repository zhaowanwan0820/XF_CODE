<?php

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\ContractService;
use core\service\ncfph\AccountService;
use api\conf\Error;

/**
 * 合同列表
 */
class Contract extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "page"=> array("filter" => "int", "message" => "page is error", 'option' => array('optional' => true)),
            "type"=> array("filter" => "int", 'option' => array('optional' => true)),  // 不传或者为0，是网信；为1，是普惠
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $page = isset($data['page']) ? $data['page'] : 1;
        $userId = $user['id'];
        $userName = $user['user_name'];
        $userInfo = array('id' => $userId,'user_name' => $userName);
        $list = array();
        if(isset($data['type']) && $data['type'] == 1){
            // 访问普惠的接口
            $list = (new AccountService())->getContractDeals($userId, $page,app_conf("PAGE_SIZE"), null, true);
            foreach($list['list'] as $k => $v){
                $list['list'][$k]['isDealZX'] = false;
            }
        }else{
            $list = $this->rpc->local('ContractService\getContDealList', array($userInfo, $page));
        }

        $result = array('count'=>$list['count']);
        $result['list'] = array();
        foreach($list['list'] as $item){
            $tmp = array();
            $tmp['dealId'] = $item['id'];
            $tmp['dealOldName'] = $item['old_name'];
            $tmp['dealName'] = $item['name'];
            $tmp['isHaveSign'] = ('0' == $item['is_have_sign']) ? 0 : 1;
            $tmp['userRealName'] = $item['user_real_name'];
            $tmp['incomeFeeFormat'] = $item['income_fee_rate_format'];
            $tmp['loanTypeName'] = $item['loantype_name'];
            $tmp['borrowAmount'] = $item['borrow_amount'];
            $tmp['isBxt'] = $item['isBxt'];
            $tmp['maxRate'] = floatval($item['max_rate']);
            $tmp['period'] = ($item['loantype'] == 5) ? $item['repay_time'] . '天' : $item['repay_time'] . '个月';
            $tmp['isDealZX'] = $item['isDealZX'];
            $tmp['income_base_rate'] = $item['income_base_rate'];
            $result['list'][] = $tmp;
        }
        $this->json_data = $result;
    }
}

