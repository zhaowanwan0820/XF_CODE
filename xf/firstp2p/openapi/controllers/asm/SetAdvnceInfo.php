<?php

/**
 * @abstract openapi  信分期——放款触发
 * @author liuzhenpeng <liuzhenpeng@ucfgroup.com>
 * @date 2016-05-04
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoRepayment;
use core\service\DealPrepayService;
use core\service\DealService;
use core\service\UserCarryService;
use core\service\DealLoanTypeService;
use core\dao\DealLoanTypeModel;

class SetAdvnceInfo extends BaseAction
{
    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "access_token" => array("filter" => "required", "message" => "access_token is required"),
            "deal_id" => array("filter" => "required", "message" => "deal_id is required"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $deal_id = (int) $this->form->data['deal_id'];
        if(empty($deal_id)){
            $this->errorCode = 1;
            $this->errorMsg  = '标的编号不正确';

            return false;
        }
        
        $deal_obj = new DealService();
        $deal_ret = $deal_obj->getDeal($deal_id);
        if(empty($deal_ret)){
            $this->errorCode = 2;
            $this->errorMsg = "该标的不存在！";
            return false;
        }

        $type_obj = new DealLoanTypeService();
        $type_id  = $type_obj->getIdByTag(DealLoanTypeModel::TYPE_XFFQ);
        if((int)$type_id !== (int)$deal_ret['type_id']){
            $this->errorCode = 3;
            $this->errorMsg  = '该标的不属于信分期业务';
            return false;
        }

        $carry_obj = new UserCarryService();
        $carry_ret = $carry_obj->doPassByDealId($deal_id);
        if($carry_ret == false ){
            $this->errorCode = 4;
            $this->errorMsg  = '受理提现申请失败';
            return false;
        }
    }

}
