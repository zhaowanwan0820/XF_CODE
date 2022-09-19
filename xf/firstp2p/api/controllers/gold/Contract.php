<?php
/**
 * Contract.php
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
class Contract extends GoldBaseAction {

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'dealId' => array("filter" => "required", "message" => "dealId is required"),
            "token" => array("filter" => "required", "message" => "token is required"),
            "contractTitle" => array("filter" => "required", "message" => "contractTitle is required"),
            "buyAmount" => array("filter" => "float", "message" => "克数格式错误"),
            "buyPrice" => array("filter" => "float", "message" => "黄金价格参数错误"),
            "type" => array('filter' => 'int','message' => 'type must int','option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return $this->return_error();
        }


    }

    public function invoke() {
       $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_TOKEN_ERROR');
            return $this->return_error();
        }
        $data = $this->form->data;
        if(!empty($data['type'])){
            if($data['type'] != self::GOLD_CURRENT_TYPE){
                $this->setErr('ERR_PARAMS_ERROR');
                return false;
            }
            $this->tpl->assign('type', self::GOLD_CURRENT_TYPE);
            return;
        }
        if (empty($data['dealId'])) {
            $this->setErr("ERR_PARAMS_ERROR", "dealId is error");
            return $this->return_error();
        }
        $id = $this->form->data['id'];
        $contractTitle = $data['contractTitle'];
        $dealId = $this->form->data['dealId'];
        $buyAmount = $data['buyAmount'];
        $buyPrice = $data['buyPrice'];
        $contract = $this->rpc->local('ContractPreService\getGdbContractPre', array($dealId,$user['id'],$buyAmount,$buyPrice));
        if (empty($contract)) {
            $this->setErr("ERR_PARAMS_ERROR", "获取合同失败");
            return $this->return_error();
        }
        $this->tpl->assign('contractTitle', $contractTitle);
        $this->tpl->assign('contract', $contract);
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
