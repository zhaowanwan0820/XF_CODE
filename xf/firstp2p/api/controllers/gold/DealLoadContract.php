<?php
/**
 * DealLoadContract.php
 *
 * @date 2017-05-25
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\gold;

use libs\web\Form;
use api\controllers\GoldBaseAction;

/**
 * 输出投标合同
 *
 * Class Contract
 * @package api\controllers\deals
 */
class DealLoadContract extends GoldBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            'id' => array('filter' => 'required',"message" => "id is required"),
            'dealId' => array('filter' => 'required',"message" => "dealId is required"),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return $this->return_error();
        }
    }

    public function invoke() {

        $data = $this->form->data;
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_TOKEN_ERROR');
            return $this->return_error();
        }

        $id = intval($data['id']);
        $dealId = intval($data['dealId']);
        //$contract = $this->rpc->local("ContractNewService\showContract", array($id,$dealId,0,100));
        $contract = $this->rpc->local("ContractNewService\showCont", array($id,$dealId,$user['id']));
        if (empty($contract)) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return $this->return_error();
        }

        $this->tpl->assign('contract_title', $contract['title']);
        $this->tpl->assign('contract_content', $contract['content']);
    }

    public function _after_invoke() {
        
        $this->tpl->display($this->template);
    }

    /**
     * 错误输出
     */
    public function return_error() {
        parent::_after_invoke();
        return false;
    }


}
