<?php

/**
 * @abstract openapi  获取信分期还款金额
 * @author liuzhenpeng <liuzhenpeng@ucfgroup.com>
 * @date 2016-06-22
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\DealPrepayService;
use core\service\DealService;

/**
 * 获取信分期还款金额
 *
 * @package openapi\controllers\asm
 */
class FindRepaymentMoney extends BaseAction
{

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "approve_number" => array("filter" => "required", "message" => "approve_number is required"),
            "repayment_time" => array("filter" => "required", "message" => "repayment_time is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $success = false;
        $data = $this->form->data;
        if (!is_numeric($data['repayment_time'])){
            $this->setErr("ERR_PARAMS_ERROR", 'param error number');
        }

        $deal_obj = new DealService();
        $deal_ret = $deal_obj->findApproveNumberDealId($data['approve_number']);
        if(empty($deal_ret)){
            $this->errorCode = 1;
            $this->errorMsg = "该审批单号不存在！";
            return false;
        }

        try{
            $repayment_time = date("Y-m-d", $data['repayment_time']);
            $calcInfo = (new DealPrepayService())->prepayTryCalc($deal_ret['id'],$repayment_time);
            unset($calcInfo['deal_id'], $calcInfo['user_id']);
            $res = $calcInfo;
            $success = true;
        }catch(\Exception $ex){
            $res = $ex->getMessage();
        }

        $this->errorCode = ($success == true) ? 0 : 3;
        $this->errorMsg  = ($success == true) ? 'ok' : $res;
        $this->json_data = ($success == true) ? $res : '';
    }

}
