<?php

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\conf\Error;
use openapi\controllers\BaseAction;
use libs\utils\Logger;


/**
 * 代扣结果查询
 * Class DealDkSearch
 * @package openapi\controllers\deal
 */
class DealDkUpdate extends BaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'deal_id' => ['filter' => 'required',"message" => "deal_id is error"],
            'repay_id' => ['filter' => 'required', "message" => "repay_id is error"],
            'repay_type' => ['filter' => 'required', "message" => "repay_type is error"],
            'approve_number' => ['filter' => 'required',"message" => "approve_number is error"],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        // 此接口已关闭
        $this->setErr("ERR_SYSTEM_ACTION_CLOSE");
        return false;
        $data = $this->form->data;
        $dealId = intval($data['deal_id']);
        $repayId = intval($data['repay_id']);
        $repayType = intval($data['repay_type']);
        $approveNumber = $data['approve_number'];


        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"代扣还款方式更改","deal_id:{$dealId},repay_id:{$repayId},repayType:{$repayType}")));

        try{
            $dkService = new \core\service\DealDkService();
            $res = $dkService->updateDkRepayType($dealId,$repayId,$repayType);
        }catch (\Exception $ex){
            $this->errorCode = $ex->getCode();
            $this->errorMsg = $ex->getMessage();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"代扣还款方式更改 errMsg:".$ex->getMessage(),"deal_id:{$dealId},repay_id:{$repayId},repayType:{$repayType}")));
            return false;
        }

        $this->json_data = $res;
        return true;
    }
}
