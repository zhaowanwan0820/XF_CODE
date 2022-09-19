<?php
/**
 * 合同预签
 * @author wenyanlei@ucfgroup.com
 **/

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\contract\ContractViewerService;
use core\service\deal\DealService;

class Contractpre extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "token" => array("filter"=>"required", "message"=>"token不能为空"),
            "money" => array("filter"=>"float", "message"=>"金额格式错误", "option"=>array("optional"=>true)),
            "id" => array("filter"=>"int", "message"=>"id参数错误"),
            "tplId" => array("filter"=>"int", "message"=>"合同模板id参数错误"),
        );


        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $id = intval($data['id']);
        $money = floatval($data['money']);
        $tpl_id = intval($data['tplId']);

        $loginUser = $this->user;
        if($money < 0){
            $this->setErr('ERR_PARAMS_ERROR', "金额格式错误");
        }

        if($id <= 0){
            $this->setErr('ERR_PARAMS_ERROR', "id参数错误");

        }

        $dealService = new DealService();
        $deal_info = $dealService->getDeal($id, true, false, true);
        if(empty($deal_info) || $deal_info['deal_status'] != 1){
            $this->setErr('ERR_PARAMS_ERROR', "id参数错误");
        }

        $fetched_contract = ContractViewerService::getOneFetchedContractByTplId($deal_info['id'], $tpl_id, $loginUser['id'], $money);
        $this->json_data = ['contractpre' => $fetched_contract['content']];
    }

}
