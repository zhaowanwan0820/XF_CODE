<?php
/**
 * 已投项目查看合同-合同内容
 6
 */
namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;

use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

class ProjectContractContent extends AppBaseAction{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'projectId' => array('filter' => 'string', 'option' => array('optional' => true)),
            "token" => array("filter" => "required", "message" => "token is required"),
        );
     
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        }
    }
    public function invoke() {
        $data = $this->form->data;
        $user_info = $this->getUserByToken();
        if (!$user_info) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        $contract_id = intval($data['id']);
        $project_id = intval($data['projectId']);
        if (empty($contract_id) || empty($project_id)) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }

        $contract = $this->rpc->local('ContractInvokerService\getOneFetchedContract', array('viewer', $contract_id, $project_id, ContractServiceEnum::SERVICE_TYPE_PROJECT));
        if(!empty($contract['userId']) && $contract['userId'] != $userInfo['id']) {
             throw new \exception('合同不存在');
        }

        if (empty($contract['content'])) {
            throw new \exception('合同不存在');
        } else {
            $this->json_data = array('content' => $contract['content']);
            return true;
        }
    }
}
