<?php
/**
 * 更新贷后披露信息接口
 * User: duxuefeng
 * Date: 2018/1/2
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\project\ProjectService;

class SetPostLoanMessage extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "approve_number" => array("filter" => "required", "message" => "approve_number is required"), //放款审批单号
            "post_loan_message" => array("filter" => "required", "message" => "post_loan_message is required"), //贷后信息-html

        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $params = $this->form->data;
        // 1.检查项目是否存在
        $projectService = new ProjectService();
        $dealProject = $projectService->getDealProjectByApproveNumber($params['approve_number']);
        if (empty($dealProject)){
            $this->setErr("ERR_NO_DEAL_PROJECT");
            return false;
        }
        // 2.更新项目的贷后信息
        $param = array('post_loan_message' => base64_decode($params['post_loan_message']));
        $result = $dealProject->updateOne($param);
        if(!$result){
            $this->setErr("ERR_UPDATE_POST_LOAN_MESSAGE");
            return false;
        }

        // 成功
        $this->errorCode = 0;
        $this->errorMsg = '';
        $this->json_data = '';
        return true;
    }
}

