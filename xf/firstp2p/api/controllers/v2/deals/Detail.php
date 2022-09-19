<?php

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\DealLoanTypeService;

/**
 * 订单详情页面接口
 *
 * Class Detail
 * @package api\controllers\deals
 */
class Detail extends AppBaseAction {

    private $_forbid_deal_status;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "int", "message" => "id is error"),
            'token' => array("filter" => "string"),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return $this->return_error();
        }
        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return $this->return_error();
        }

        $this->form->data['id'] = intval($this->form->data['id']);
        $this->_forbid_deal_status = array(2, 3, 4, 5);
    }

    public function invoke() {
        $deal_id = $this->form->data['id'];
        $data = $this->form->data;
        $log_info = implode(' | ', array(__CLASS__, __FUNCTION__, json_encode($data)));
        $loginUser = $this->getUserByToken();

        if (deal_belong_current_site($deal_id)) {
            $deal = $this->rpc->local('DealService\getDeal', array($deal_id, true));
        } else {
            $deal = null;
        }

        if (empty($deal)) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return $this->return_error();
        }
        $result = array();
        // 检测当前标是否为满标状态
        if (in_array($deal->deal_status, $this->_forbid_deal_status)) {
            $result['old_name'] = $deal["old_name"];
            if (isset($this->form->data['token'])) {
                $is_load = $this->rpc->local('DealLoadService\getUserDealLoad', array($loginUser['id'], $deal_id));
                if (!$is_load) {
                    $result['isFull'] = 1;
                    return false;
                }
            } else {
                 $result['isFull'] = 1;
                 return false;
            }
        } else {
           $result['isFull'] = 0;
        }

        //查询项目简介
        if ($deal['project_id']) {
            $project = $this->rpc->local('DealProjectService\getProInfo', array('id' => $deal['project_id'], 'deal_id' => $deal['id']));
        }
        $deal['project_intro'] = isset($project['intro_html']) ? str_replace('white-space: pre', '', $project['intro_html']) : '';

        // 项目风险承受能力
        $project_risk = isset($project['risk']) ?$project['risk'] : [];
        $project_risk['is_check_risk'] = $project_risk['needForceAssess'] = 0;
        if (!empty($loginUser)) {
            $user_risk = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array($loginUser['id']));
            $project_risk['needForceAssess'] = $user_risk['needForceAssess'];
            if ($loginUser['is_enterprise_user'] == 0){
                // 检查项目风险承受和个人评估 (企业会员不受限制)
                $project_risk_ret = $this->rpc->local("DealProjectRiskAssessmentService\checkRiskBid", array(intval($deal['project_id']),$loginUser['id'], true, $user_risk));
                if ($project_risk_ret['result'] == false){
                    $project_risk['is_check_risk'] = 1;
                    $project_risk['remaining_assess_num'] = $project_risk_ret['remaining_assess_num'];
                    $project_risk['user_risk_assessment'] = $project_risk_ret['user_risk_assessment'];
                }
            }
        }

        // 贷后信息披露 网贷才有该字段
        $deal['post_loan_message'] = $project['post_loan_message'];

        $res = $this->getCompanyAndLoanList($deal);
        //借款人信息
        $deal['deal_user_info'] = $res['deal_user_info'];

        //机构名义贷款信息
        $deal['company'] = $res['company'];

        //借款列表
        $load_list = $res['load_list'];
        $result['deal']['load_list_count'] = $res['load_list_count'];
       foreach ($load_list as $k => $v){
            $result['deal']['load_list'][$k]['user_deal_name'] = $v['user_deal_name'];
            $result['deal']['load_list'][$k]['create_time'] = $v['create_time'];
            $result['deal']['load_list'][$k]['money'] = number_format($v['money'], 2);

         }

        $deal['timelimit'] = ($deal['deal_type'] == 1 ? ($deal['lock_period'] + $deal['redemption_period']) . '~' : '') . $deal['repay_time'] . ($deal['loantype'] == 5 ? "天" : "个月");
        $deal['timeBegin'] = $deal['loantype'] == 5 ? $deal['lock_period'] : $deal['lock_period'] * 30;
        $deal['timeEnd'] = $deal['loantype'] == 5 ? $deal['repay_time'] : $deal['repay_time'] * 30;
        $deal['repayment'] = $deal['deal_type'] == 1 ? '提前' . $deal['redemption_period'] . '天申赎' : $deal['loantype_name'];
        $deal['maxRate'] = number_format($deal['max_rate'], 2);
        $deal['createTime'] = to_date($deal['create_time']);

        if ($this->app_version >= 200) { //app2.0
            if ($deal['income_ext_rate'] > 0) {
                $deal['income_all_rate'] = ($deal['income_base_rate'] + $deal['income_ext_rate']) . '%';
            } else {
                $deal['income_all_rate'] = $deal['income_base_rate'] . '%';
            }
            //万元收益 0420四舍五入更改为舍余
            $deal['income_by_wan'] = $deal->floorfix(bcmul($deal['expire_rate'], 10000,5)/100);
            $deal['min_loan_money'] = number_format($deal['min_loan_money'], 2, '.', '');
            $deal['min_loan'] = number_format($deal['min_loan_money'] / 10000, 2, '.', '');
            if ($deal['deal_type'] == 1) {
                $dayRateSrc = $this->rpc->local('DealCompoundService\convertRateYearToDay', array($deal['int_rate'], $deal['redemption_period']));
                $deal['dayRateShow'] = number_format($dayRateSrc * 100, 5);
                $deal['dayRate'] = 1 + $dayRateSrc;
                $deal['defaultProfit'] = number_format(10000 * pow($deal['dayRate'], $deal['timeBegin']) - 10000, 2);
                $this->template = $this->getTemplate('compound_detail');
            }
            $deal['isDealZX'] = $this->rpc->local('DealService\isDealEx', array($deal['deal_type']));
        }

        $result['deal']['old_name'] = $deal['old_name'];
        $result['deal']['deal_tag_name'] = $deal['deal_tag_name'];
        $result['deal']['deal_tag_name1'] = $deal['deal_tag_name1'];
        $result['deal']['loantype'] = $deal['loantype'];
        $result['deal']['deal_type'] = $deal['deal_type'];
        $result['deal']['income_base_rate'] = $deal['income_base_rate'];
        $result['deal']['isBxt'] = $deal['isBxt'];
        $result['deal']['maxRate'] = $deal['maxRate'];
        $result['deal']['deal_status'] = $deal['deal_status'];
        $result['deal']['need_money_detail'] = $deal['need_money_detail'];
        $result['deal']['repay_time'] = $deal['repay_time'];
        $result['deal']['is_entrust_zx'] = $deal['is_entrust_zx'];
        $result['deal']['formated_start_time'] = $deal['formated_start_time'];
        $result['deal']['repay_start_time_name'] = $deal['repay_start_time_name'];
        $result['deal']['formated_repay_start_time'] = $deal['formated_repay_start_time'];
        $result['deal']['formated_diff_time'] = $deal['formated_diff_time'];
        $result['deal']['min_loan_money'] = $deal['min_loan_money'];
        $result['deal']['min_loan'] = $deal['min_loan'];
        $result['deal']['is_crowdfunding'] = $deal['is_crowdfunding'];
        $result['deal']['income_by_wan'] = $deal['income_by_wan'];
        $result['deal']['income_subsidy_rate'] = $deal['income_subsidy_rate'];
        $result['deal']['loantype_name'] = $deal['loantype_name'];
        $result['deal']['project_risk'] = $project_risk;
        $result['deal']['warrant'] = $deal['warrant'];
        $result['deal']['isDealZX'] = $deal['isDealZX'];
        $result['deal']['project_intro'] = $deal['project_intro'];
        $result['deal']['post_loan_message'] = $deal['post_loan_message'];
        $result['deal']['company'] = $deal['company'];
        $result['deal']['deal_user_info']['info'] = $deal['deal_user_info']['info'];
        $result['deal']['agency_info']['brief'] = $deal['agency_info']['brief'];
        $result['deal']['load_money'] = format_price($deal['load_money']);
        $result['deal']['total'] = $deal['borrow_amount_wan_int'];
        $result['deal']['remain_time_format'] = $deal['remain_time_format'];
        $result['deal']['start_loan_time_format'] = isset($deal['start_loan_time_format'])?$deal['start_loan_time_format']:null;
        $result['deal']['is_update'] = $deal['is_update'];
        $result['deal']['guarantor_status'] = $deal['guarantor_status'];
        $result['deal']['formated_end_time'] = $deal['formated_end_time'];

        $this->json_data = $result;
    }

    /**
     * 获取订单的融资方信息和投资人列表
     *
     * @param $deal
     */
    protected function getCompanyAndLoanList($deal) {
        //借款人信息
        $deal_user_info = $this->rpc->local('UserService\getUserViaSlave', array($deal['user_id'], true, true));
        $deal_user_info = $this->rpc->local('UserService\getExpire', array($deal_user_info)); //工作认证是否过期
        $res = array();
        $res['deal_user_info'] = $deal_user_info;
        //机构名义贷款信息
        $company = $this->rpc->local('DealService\getDealUserCompanyInfo', array($deal));
        $company['company_description_html'] = convert_upload($company['company_description_html']);
        $res['company'] = $company;

        //借款列表
        $load_list = $this->rpc->local('DealLoadService\getDealLoanListByDealId', array($deal['id']));
        $load_list_count = count($load_list);
        $res['load_list_count'] = $load_list_count;
        $res['load_list'] = $load_list;
        return $res;
    }

     public function return_error() {
         parent::_after_invoke();
         return false;
     }

}
