<?php

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\ContractNewService;
use api\conf\Error;
/**
 * 项目合同列表
 */
class ProjectContract extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "page"=> array("filter" => "int", "message" => "page is error", 'option' => array('optional' => true)),
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
        $role = isset($data['role']) ? $data['role']:'';
        $userId = $user['id'];
        $userName = $user['user_name'];
        $userInfo = array('id' => $userId, 'user_name' => $userName);
        $list = $this->rpc->local('ContractNewService\getContProjectList', array($userId, $page,10,$role));

        $result = array('count'=>$list['count']);
        $result['list'] = array();
        foreach($list['list'] as $item){
            $tmp = array();
            $tmp['projectId'] = $item['project_id'];
            $tmp['projectName'] = $item['name'];
            $tmp['isHaveSign'] = $item['is_have_sign'];
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
