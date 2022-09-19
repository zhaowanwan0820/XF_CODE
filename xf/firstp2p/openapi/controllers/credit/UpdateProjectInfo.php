<?php

/**
 * @abstract openapi  用款项目变更
 * @author gengkuan <gengkuan@ucfgroup.com>
 * @date 2018-12-27
 */

namespace openapi\controllers\credit;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\dao\DealModel;
use NCFGroup\Protos\Life\RequestCommon as LifeRequestCommon;
use libs\utils\Alarm;

/**
 *
 *用款项目变更
 * Class UpdateProjectInfo
 * @package openapi\controllers\asm
 */
class UpdateProjectInfo extends BaseAction
{

    public static $paramsConf = array(
        0 => array('borrow_amount','loan_type','repay_period','repay_period_type','prepay_days_limit','leasing_contract_num','lessee_real_name',
            'leasing_money','entrusted_loan_entrusted_contract_num', 'entrusted_loan_borrow_contract_num','base_contract_repay_time','manage_fee_rate',
            'loan_fee_rate_type','consult_fee_rate','consult_fee_rate_type','guarantee_fee_rate', 'guarantee_fee_rate_type','leasing_contract_title',
            'contract_transfer_type','loan_application_type','rate_yields','loan_money_type','card_name','bankzone','bankid','bankcard',
            'assets_desc','clearingType','ext_loan_type','contract_tpl_type','jys_record_number','project_info_url','rate'),
        1=>array( 'loan_money_type','card_name','bankzone','bankid','bankcard','clearingType'),
        2 =>array('borrow_amount','loan_money_type','card_name','bankzone','bankid','bankcard','clearingType','ext_loan_type') ,
        4 => array('card_name','bankzone','bankid','bankcard','clearingType')
    );

    public function init() {
       parent::init();
       $this->form = new Form();
       $this->form->rules = array(
            "approve_number" => array("filter" => "required", "message" => "approve_number is required"),
             "project_info" => array("filter" => "required", "message" => "real_name is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
       if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $params = $this->form->data;
       if (empty($params['approve_number'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'approveNumber不能为空');
            return false;
        }
        if (empty($params['project_info'])) {
            $this->setErr("ERR_PARAMS_ERROR", 'project_info不能为空');
            return false;
        }
        $project = $this->rpc->local('DealProjectService\getDealProjectByApproveNumber', array($params['approve_number']));
        if (empty($project)) {
            $this->setErr("ERR_MANUAL_REASON", '没有查询到相关项目的记录');
            return false;
        }
        $dealinfo = $this->rpc->local('DealService\getDealByProId', array($project['id']));
        if (count($dealinfo) >1 ) {
            $this->setErr("ERR_MANUAL_REASON", '拆标项目不允许项目变更');
            return false;
        }
        $ret = $this->rpc->local('DealService\getDealByApproveNumber', array($params['approve_number']));
        if (empty($ret)) {
            $this->setErr("ERR_MANUAL_REASON", '没有查询到相关的标的记录');
            return false;
        }
        $paramConf = self::$paramsConf[$ret['deal_status']];
        if(empty($paramConf)){
            $this->setErr("ERR_MANUAL_REASON", '该状态没有可修改字段');
            return false;
        }
        $list = json_decode(base64_decode($params['project_info']), true);
        foreach ($list as $key => $v){
            if(!in_array(trim($key),$paramConf)){
                $this->setErr("ERR_MANUAL_REASON", '该状态不能修改'.$key.'字段');
                return false;
            }
        }
        if(!empty($list['project_info_url'])){
            $list['project_info_url'] = base64_decode(urldecode(str_replace('!_!', '%',$list['project_info_url'])));
        }
        if(!empty($list['base_contract_repay_time'])){
            $list['base_contract_repay_time'] =intval($list['base_contract_repay_time']);
        }
        $request = new LifeRequestCommon();
        $request->setVars(array("approveNumber"=>$params['approve_number'],"project_info"=>$list));
        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpProjectDeal',
            'method' => 'updateProjectDeal',
            'args' => $request
        ));
        $codeMsgList  = array(
            '1'=>'insert dealProject failed',
            '-3'=>'该咨询机构的上标金额已超出平台限额，不能更新',
            '-4'=>'该产品的上标金额已超出平台限额，不能更新',
        );
        $code = $response->resCode;
        while(in_array($code, array_keys($codeMsgList))){
            $this->errorCode = $code;
            $this->errorMsg  = $codeMsgList[$code];
            Alarm::push('UpdateProjectInfo', '更新项目信息失败', $this->errorMsg . ', approve_number:' . $params['approve_number']);
            return false;
        }
        $this->errorCode = 0;
        $this->json_data = $response;


    }
}
