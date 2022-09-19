<?php
/**
 * Contract.php
 *
 * @date 2014-03-21
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\contract\ContractViewerService;

/**
 * 输出投标合同
 *
 * Class Contract
 * @package api\controllers\deals
 */
class Contract extends AppBaseAction {

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'dealId' => array('filter' => 'int'),
            "token" => array("filter" => "required", "message" => "token is required"),
            "attchment" => array("filter" => "string", "message" => "附件合同地址有误"),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return false;
        }
    }

    public function invoke() {
        $user = $this->user;
        $data = $this->form->data;
        // 如果是附件合同
        $is_attachment = false;
        if (!empty($data['attchment'])) {
            $is_attachment = true;
            $result['attchment'] = urldecode($data['attchment']);
        } else {
            $id = $this->form->data['id'];
            $dealId = $this->form->data['dealId'];
            $contract = ContractViewerService::getOneFetchedContract($id, $dealId,1, $user);
            if (empty($contract)) {
                $this->setErr("ERR_PARAMS_ERROR", "id is error");
            }

            $contract['content'] = hide_message($contract['content']);
            $result['contract'] = $contract;
        }
        $result['is_attachment'] = $is_attachment;

        $this->json_data = $result;
    }
}
