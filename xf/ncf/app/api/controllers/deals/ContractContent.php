<?php
/**
 * 已投项目查看合同-合同内容
 */
namespace api\controllers\deals;

use libs\web\Form;
use api\conf\Error;
use api\controllers\AppBaseAction;
use core\service\contract\ContractViewerService;

class ContractContent extends AppBaseAction{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'dealId' => array('filter' => 'string', 'option' => array('optional' => true)),
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR");
        }
    }
    public function invoke() {
        $data = $this->form->data;
        $userInfo = $this->user;
        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "参数错误");
        }

        $contract_id = intval($data['id']);
        $deal_id = intval($data['dealId']);
        if (empty($contract_id) || empty($deal_id)) {
            $this->setErr('ERR_PARAMS_ERROR');
        }

        $contract = ContractViewerService::getOneFetchedContract($contract_id, $deal_id);
        if(!empty($contract['userId']) && $contract['userId'] != $userInfo['id'] && $contract['borrowUserId'] != $userInfo['id']) {
            $this->setErr("ERR_PARAMS_ERROR", "合同不存在");
        }

        if (empty($contract['content'])) {
            $this->setErr("ERR_PARAMS_ERROR", "合同不存在");
        }

        $this->json_data = array('content' => $contract['content']);
    }
}
