<?php
/**
 * 已投项目查看合同-合同内容
 */
namespace api\controllers\deals;

use libs\web\Form;
use api\conf\Error;
use api\controllers\AppBaseAction;
use core\service\ncfph\AccountService;
use NCFGroup\Protos\Ptp\RequestDealsContract;

class ContractContent extends AppBaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'dealId' => array('filter' => 'string', 'option' => array('optional' => true)),
            "token" => array("filter" => "required", "message" => "token is required"),
            "type" => array("filter" => "int", 'option' => array('optional' => true)),  // 不传或者为0，是网信；为1，是普惠
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }
    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "参数错误");
            return false;
        }
        $contract_id = intval($data['id']);
        $deal_id = intval($data['dealId']);
        if (empty($contract_id) || empty($deal_id)) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
        if (isset($data['type']) && $data['type'] == 1) {
            // 访问普惠的接口
            $contract = (new AccountService())->getContractContent($contract_id, $deal_id, $userInfo->getRow());
        } else {
            $contract = $this->rpc->local('ContractInvokerService\getOneFetchedContract', array('viewer', $contract_id, $deal_id));
        }

        if (empty($contract['content'])) {
            throw new \exception('合同不存在');
        } else {
            $this->json_data = array('content' => $contract['content']);
            return true;
        }
    }
}
