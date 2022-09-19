<?php

/**
 * DealLoadDetail.php
 *
 * @date 2014-03-21
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\account;

use libs\web\Form;
use core\service\dealload\DealLoadService;
use core\service\contract\ContractRemoterService;
use core\service\deal\DealLoanRepayService;
use core\service\project\ProjectService;
use core\service\DealLoanTypeService;
use core\enum\DealEnum;

/**
 * 用户已投资的详情页
 * 复用订单详情页部分信息
 *
 * Class DealLoadDetail
 * @package api\controllers\account
 */
class DealLoadDetail extends \api\controllers\deals\Detail {

    protected $needAuth = true;
    protected $redirectWapUrl = '/account/deal_load_detail';

    public function init() {

        // 因不是直接继承BaseAction，获取主init

        $grandParent = self::getRoot();
        $grandParent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "int", "message" => "id is error"),
            "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return false;
        }
        $this->form->data['id'] = intval($this->form->data['id']);
    }

    public function invoke() {
        $loginUser = $this->user;
        $load_id = $this->form->data['id'];
        $dealLoadService = new DealLoadService();
        $deal_load = $dealLoadService->getDealLoadDetail($load_id,true,true);
        if (empty($deal_load)) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return false;
        }

        if ($deal_load['user_id'] != $loginUser['id']) {
            $this->setErr('ERR_GET_USER_FAIL'); // 获取oauth用户信息失败
            return false;
        }

        $deal = $deal_load['deal'];
        $deal['repay_time'] = ($deal['deal_type'] == 1 ? ($deal['lock_period'] + $deal['redemption_period']) . '~' : '') . $deal['repay_time'];
        $deal['loantype_name'] = $deal['deal_type'] == 1 ? '提前' . $deal['redemption_period'] . '天申赎' : $deal['loantype_name'];
        $deal['deal_compound_day_interest'] = 0;
        $deal['compound_time'] = '';
        $deal['maxRate'] = number_format($deal['max_rate'], 2);
        $deal['createTime'] = to_date($deal['create_time']);

        //增加标的是否属于专项标标识
        $deal['isDealZX'] = false;
        //状态为投资中或者状态已经还清但是在上线该规则之后还清的显示提示信息
        if($deal['deal_type']==0 && ($deal['deal_status']==4 ||($deal['deal_status']==5 &&($deal['last_repay_time']+28800-strtotime('2017-03-09'))>0 ))){
            $fankuan_days = floor((time() - $deal['repay_start_time']-28800) / 86400)+1;
            if($fankuan_days>7){
                $deal['p2p_show']=1;
                if($deal['borrow_amount']>10000){
                    $deal['p2p_show_detail']='借款人已按照既定的资金用途使用资金。';
                }else{
                    $deal['p2p_show_detail']='该项目金额低于1万元（含），不对资金用途进行复核。';
                }
            }
        }
        //$this->tpl->assign("deal", $deal);
        //$this->tpl->assign("deal_load", $deal_load);
        $result['deal']['name'] = $deal['name'];
        $result['deal']['deal_type'] = $deal['deal_type'];
        $result['deal']['type_match_row'] = $deal['type_match_row'];
        $result['deal']['old_name'] = $deal['old_name'];
        $result['deal']['repay_time'] = $deal['repay_time'];
        $result['deal']['repay_start_time_name'] = $deal['repay_start_time_name'];
        $result['deal']['formated_repay_start_time'] = $deal['formated_repay_start_time'];
        $result['deal_load']['money'] = number_format($deal_load['money'], 2);
        $result['deal']['loantype'] = $deal['loantype'];
        $result['deal']['loantype_name'] = $deal['loantype_name'];
        $result['deal']['rate'] = $deal['rate'];
        $result['deal']['repay_start_time'] = $deal['repay_start_time'];
        $result['deal']['income_base_rate'] = $deal['income_base_rate'];
        $result['deal']['user_deal_name'] = $deal['user_deal_name'];
        $result['deal_load']['real_income'] = number_format($deal_load['real_income'], 2);
        $result['deal']['min_loan_money_format'] = $deal['min_loan_money_format'];
        $result['deal_load']['total_income'] = number_format($deal_load['total_income'], 2);
        $result['deal']['is_crowdfunding'] = $deal['is_crowdfunding'];
        $result['deal']['warrant'] = $deal['warrant'];
        $result['deal_load']['income'] = number_format($deal_load['income'], 2);
        $result['deal']['deal_status'] = $deal['deal_status'];
        $result['deal']['is_update'] = $deal['is_update'];
        $result['deal']['guarantor_status'] = $deal['guarantor_status'];
        $result['deal']['agency_info']['brief'] = $deal['agency_info']['brief'];
        $result['deal']['isBxt'] = $deal['isBxt'];
        $result['deal']['maxRate'] = $deal['maxRate'];
        $result['deal']['isDealZX'] = $deal['isDealZX'];
        $result['deal_load']['is_lease'] = $deal_load['is_lease'];
        if (isset($deal['p2p_show'])) {
            $result['deal']['p2p_show'] = $deal['p2p_show'];
            $result['deal']['p2p_show_detail'] = $deal['p2p_show_detail'];
        }

        //合同信息
        list($contract_info['is_attachment'], $contract_info['cont_list']) = ContractRemoterService::getContractListByDealLoadId($load_id);
        //$this->tpl->assign("is_attachment", $contract_info['is_attachment']);
        //$this->tpl->assign("contract_list", $contract_info['cont_list']);
        $result['is_attachment'] = $contract_info['is_attachment'];
        $result['contract_list'] = $contract_info['cont_list'];

        $loan_repay_list = array();
        // 还款中 已还清 才有回款计划
        if ($deal['deal_status'] == DealEnum::DEAL_STATUS_REPAY || $deal['deal_status'] == DealEnum::DEAL_STATUS_REPAID) {
            //回款计划
            $DealLoanRepayService = new DealLoanRepayService();
            $loan_repay_list = $DealLoanRepayService->getLoanRepayListByLoanId($load_id);
        }
        $result['loan_repay_list'] = array();
        //$this->tpl->assign("loan_repay_list", $loan_repay_list);
        foreach ($loan_repay_list as $k => $v) {
            $result['loan_repay_list'][$k]['money_type'] = $v['money_type'];
            $result['loan_repay_list'][$k]['money_status'] = $v['money_status'];
            $result['loan_repay_list'][$k]['time'] = $v['time'];
            $result['loan_repay_list'][$k]['money'] = number_format($v['money'], 2);
        }
        // 重新索引防止wap 解析不了
        if (!empty($result['loan_repay_list'])){

            $result['loan_repay_list'] = array_values($result['loan_repay_list']);

        }
        //$this->tpl->assign("token", $this->form->data['token']);

        //查询项目简介
        if ($deal['project_id']) {
            $project_service = new ProjectService();
            $project = $project_service->getProInfo($deal['project_id'], $deal['id']);
        }
        //$this->tpl->assign('project_intro', isset($project['intro_html']) ? $project['intro_html'] : '');
        $result['project_intro'] = isset($project['intro_html']) ? $project['intro_html'] : '';

        //贷后信息
        $result['post_loan_message'] = $project['post_loan_message'];

        $res = $this->getCompanyAndLoanList($deal); //复用订单详情页部分信息

        $result['deal_user_info']['info'] = isset($res['deal_user_info']['info']) ? $res['deal_user_info']['info'] : array();
        $result['company'] = $res['company'];
        $result['load_list'] = array();
        foreach ($res['load_list'] as $k => $v){
            $result['load_list'][$k]['user_deal_name'] = $v['user_deal_name'];
            $result['load_list'][$k]['create_time'] = $v['create_time'];
            $result['load_list'][$k]['money'] = $v['money'];
        }

        $result['load_list_count'] = $res['load_list_count'];
        $this->json_data = $result;
        /* if ($this->app_version < 320) {
             if ($deal['deal_type'] == 1) {
                 $this->template = $this->getTemplate('deal_load_detail_v2_tzd');
             }
         } else {
             if ($deal['deal_type'] == 1) {
                 $this->setViewVersion('_v32');
                 $this->template = $this->getTemplate('deal_load_detail_tzd');
             }
         }*/
    }

}
