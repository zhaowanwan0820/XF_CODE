<?php
/**
 * 已投项目查看合同
 *
 * Date: 2015/6/10
 * Time: 15:56
 */
namespace openapi\controllers\deals;

use libs\web\Form;
use openapi\controllers\BaseAction;

use NCFGroup\Protos\Contract\Enum\ContractServiceEnum;

class ProjectContract extends BaseAction{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'projectId' => array('filter' => 'string', 'option' => array('optional' => true)),
            "oauth_token" => array("filter" => "required", "message" => "token is required"),
        );
        /*
         * 与父类系统鉴权验证规则合并
         */
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "参数错误");
            return false;
        }
    }
    public function invoke() {
        $data = $this->form->data;
        $user_info = $this->getUserByAccessToken();
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
        if (empty($contract['content'])) {
            $this->setErr('ERR_CONTRACT_EMPTY');
            return false;
        } else {
            $this->json_data = array('content' => $contract['content']);
            return true;
        }
    }
}
