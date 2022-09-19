<?php

/**
 * @abstract openapi  获取标的提现状态
 * @author xiaoan <xiaoan@ucfgroup.com>
 * @date 2016-05-03
 */

namespace openapi\controllers\asm;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\UserCarryService;
use core\dao\UserCarryModel;
use core\service\DealLoanTypeService;
use core\service\DealService;
use core\dao\DealLoanTypeModel;
use core\service\SupervisionWithdrawService;

/**
 * 获取标的提现状态
 *
 * @package openapi\controllers\asm
 */
class FindCarryInfo extends BaseAction
{

    public function init() {
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
        $data = $this->form->data;
        if (!is_numeric($data['deal_id'])){
            $this->setErr("ERR_PARAMS_ERROR", 'param error');
        }

        $deal_obj = new DealService();
        $deal_ret = $deal_obj->getDeal($data['deal_id']);
        if(empty($deal_ret)){
            $this->errorCode = 1;
            $this->errorMsg = "该标的不存在！";
            return false;
        }

        $type_id = (int)$deal_ret['type_id'];

        $type_obj = new DealLoanTypeService();
        $xffq_type_id  = (int)$type_obj->getIdByTag(DealLoanTypeModel::TYPE_XFFQ);
        $zzjr_type_id  = (int)$type_obj->getIdByTag(DealLoanTypeModel::TYPE_ZHANGZHONG);
        $xsjk_type_id  = (int)$type_obj->getIdByTag(DealLoanTypeModel::TYPE_XSJK);
        if(!in_array($type_id,array($xffq_type_id, $zzjr_type_id, $xsjk_type_id))){
            $this->errorCode = 2;
            $this->errorMsg  = '该标的类型暂不提供查询';
            return false;
        }

        $isP2p = $deal_obj->isP2pPath(intval($data['deal_id']));
        if ($isP2p) {
            $svWithdraw = new SupervisionWithdrawService();
            $userP2pCarryInfo = $svWithdraw->getLatestByDealId(intval($data['deal_id']));
            if (empty($userP2pCarryInfo)) {
                $this->errorCode = 3;
                $this->errorMsg = "没有查到提现信息";
                return false;
            }
            if ($userP2pCarryInfo['withdraw_status'] == 1) { //成功
                $this->errorCode = 0;
                $this->errorMsg = '提现成功';
                return false;
            } elseif ($userP2pCarryInfo['withdraw_status'] == 2) { //失败
                $this->errorCode = 8;
                $this->errorMsg = '提现失败';
                return false;
            } else {
                $this->errorCode = 5;
                $this->errorMsg = '处理中';
                return false;
            }
        }

        try{
            $userCarryService = new UserCarryService();
            $userCarrayInfo = $userCarryService->getByDealIdStatus($data['deal_id']);
        }catch (\Exception $e){
            $this->setErr("ERR_PARAMS_ERROR", '参数不完整');
            return false;
        }
        if(empty($userCarrayInfo)){
            $this->errorCode = 3;
            $this->errorMsg = "没有查到提现信息";
            return false;
        }

        $carray_status = (int) $userCarrayInfo['status'];
        while($carray_status >= 0 || $carray_status <=4){
            $status_key_list = array(0=> 4, 1=> 5, 2=> 6, 3=> 7, 4=> 8);
            $status_val_list = array(4=> '运营待处理', 5=> '财务待处理', 6=> '运营拒绝', 7=> '批准', 8=> '财务拒绝');
            $code = $status_key_list[$carray_status];
            $msg  = $status_val_list[$code];
            break;
        }
        if($carray_status !== 3){
            $this->errorCode = $code;
            $this->errorMsg  = array($msg, '未处理');
            return false;
        }

        switch($userCarrayInfo['withdraw_status']){
            case UserCarryModel::WITHDRAW_STATUS_CREATE:
                $this->errorCode = $code;
                $this->errorMsg  = array($msg, "未处理");
            break;
            case UserCarryModel::WITHDRAW_STATUS_PROCESS:
                $this->errorCode = $code;
                $this->errorMsg  = array($msg, "处理中");
            break;
            case UserCarryModel::WITHDRAW_STATUS_PAY_PROCESS:
                $this->errorCode = $code;
                $this->errorMsg  = array($msg, "银行处理中");
            break;
            case UserCarryModel::WITHDRAW_STATUS_FAILED:
                $this->errorCode = $code;
                $this->errorMsg  = array($msg, "提现失败");
            break;
            case UserCarryModel::WITHDRAW_STATUS_SUCCESS:
                $this->errorCode = 0;
            break;
            default:
                $this->errorCode = '发生异常';
                $this->errorCode = 9;
        }
    }

}
