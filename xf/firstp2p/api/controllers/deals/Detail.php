<?php

/**
 * Detail.php
 *
 * @date 2014-03-21
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\DealLoanTypeService;
use libs\utils\Aes;

/**
 * 订单详情页面接口
 *
 * Class Detail
 * @package api\controllers\deals
 */
class Detail extends AppBaseAction {

    private $_forbid_deal_status;

    const IS_H5 = true;

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "int", "message" => "id is error"),
            'token' => array("filter" => "string"),
            'is_disclosure' => array("filter" => "int"), //用于显示信息披露
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

        $deal = null;
        if (deal_belong_current_site($deal_id)) {
            $deal = $this->rpc->local('DealService\getDeal', array($deal_id, true));
        }

        if (empty($deal)) {
            // 网信查不到的直接转到普惠
            $phWapUrl = app_conf('NCFPH_WAP_HOST').'/deals/detail?dealid='.Aes::encryptForDeal($deal_id).'&token='.$data['token'];
            return app_redirect($phWapUrl);
        }

        // 检测当前标是否为满标状态
        if (in_array($deal->deal_status, $this->_forbid_deal_status)) {
            $this->tpl->assign('old_name', $deal["old_name"]);
            $this->tpl->assign('deal', $deal);
            if (isset($this->form->data['token'])) {
                $is_load = $this->rpc->local('DealLoadService\getUserDealLoad', array($loginUser['id'], $deal_id));
                if (!$is_load) {   //如果不是当前标的投资用户
                    $this->setViewVersion('_v10');
                    $this->template = $this->getTemplate('full');
                    return;
                }
            } else {
                $this->setViewVersion('_v10');
                $this->template = $this->getTemplate('full');
                return;
            }
        }

        //查询项目简介
        if ($deal['project_id']) {
            $project = $this->rpc->local('DealProjectService\getProInfo', array('id' => $deal['project_id'], 'deal_id' => $deal['id']));
        }

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

        $this->tpl->assign('project_intro', isset($project['intro_html']) ? str_replace('white-space: pre', '', $project['intro_html']) : '');
        // 项目风险承受能力
        $this->tpl->assign('project_risk', $project_risk);
        // 贷后信息披露 网贷才有该字段
        $this->tpl->assign('post_loan_message', $project['post_loan_message']);

        $this->getCompanyAndLoanList($deal);

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
            //$deal['min_loan'] = number_format($deal['min_loan_money'] / 10000, 2, '.', '');
            //$deal['min_loan'] = number_format(ceil(($deal['min_loan_money'] / 10000) * 100)/100,2);
            $deal['min_loan'] = number_format(bcdiv($deal['min_loan_money'] , 10000,5),2);
            if ($deal['deal_type'] == 1) {
                $dayRateSrc = $this->rpc->local('DealCompoundService\convertRateYearToDay', array($deal['int_rate'], $deal['redemption_period']));
                $deal['dayRateShow'] = number_format($dayRateSrc * 100, 5);
                $deal['dayRate'] = 1 + $dayRateSrc;
                $deal['defaultProfit'] = number_format(10000 * pow($deal['dayRate'], $deal['timeBegin']) - 10000, 2);
                $this->template = $this->getTemplate('compound_detail');
            }
            $deal['isDealZX'] = $this->rpc->local('DealService\isDealEx', array($deal['deal_type']));
            $deal['isDealExchange'] = $this->rpc->local('DealService\isDealExchange', array($deal['deal_type']));
        }
        $this->tpl->assign("deal", $deal);
        $this->tpl->assign("data", $data);
        $isDisclosure = isset($data['is_disclosure']) ? (int) $data['is_disclosure'] : 0;
        $this->tpl->assign("is_disclosure", $isDisclosure);
    }

    /**
     * 输出页面
     */
    public function _after_invoke() {
        $this->afterInvoke();
        $this->tpl->display($this->template);
    }

    /**
     * 错误输出
     */
    public function return_error() {
        parent::_after_invoke();
        return false;
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
        $this->tpl->assign("deal_user_info", $deal_user_info);

        //机构名义贷款信息
        $company = $this->rpc->local('DealService\getDealUserCompanyInfo', array($deal));
        $company['company_description_html'] = convert_upload($company['company_description_html']);
        $this->tpl->assign('company', $company);

        //借款列表
        $load_list = $this->rpc->local('DealLoadService\getDealLoanListByDealId', array($deal['id']));
        $load_list_count = count($load_list);
        $this->tpl->assign("load_list_count", $load_list_count);
        $this->tpl->assign("load_list", $load_list);
    }

}
