<?php

/**
 * @abstract openapi  信分期——提前还款
 * @author liuzhenpeng <liuzhenpeng@ucfgroup.com>
 * @date 2016-05-04
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\ProtoRepayment;
use core\service\DealPrepayService;
use core\service\DealService;
use core\service\DealLoanTypeService;
use core\dao\DealLoanTypeModel;

class SetPrepaymentInfo extends BaseAction
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
        $type_id = (int)$deal_ret['type_id'];

        $type_obj = new DealLoanTypeService();
        $xffq_type_id  = (int)$type_obj->getIdByTag(DealLoanTypeModel::TYPE_XFFQ);
        $zzjr_type_id  = (int)$type_obj->getIdByTag(DealLoanTypeModel::TYPE_ZHANGZHONG);
        $xsjk_type_id  = (int)$type_obj->getIdByTag(DealLoanTypeModel::TYPE_XSJK);
        if(!in_array($type_id,array($xffq_type_id, $zzjr_type_id, $xsjk_type_id))){
            $this->errorCode = 3;
            $this->errorMsg  = '该标的不能提前还款';
            return false;
        }
        $prepay_obj = new DealPrepayService();
        $prepay_ret = $prepay_obj->prepayForFenqi($deal_id, 1);
        if($prepay_ret == false){
            $this->errorCode = 4;
            $this->errorMsg  = '提前还款失败';
            return false;
        }


    }

}
