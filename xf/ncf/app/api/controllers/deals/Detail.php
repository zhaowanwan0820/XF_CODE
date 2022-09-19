<?php

/**
 * Detail.php
 *
 * @date 2014-03-21
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\deals;

use libs\web\Form;
use libs\utils\Aes;
use api\controllers\AppBaseAction;
use core\service\DealLoanTypeService;
use core\service\deal\DealService;
use core\service\dealload\DealLoadService;
use core\service\project\ProjectService;
use core\service\risk\RiskAssessmentService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\user\UserService;

/**
 * 订单详情页面接口
 *
 * Class Detail
 * @package api\controllers\deals
 */
class Detail extends AppBaseAction {
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/deals/detail';

    // 是否需要登陆授权
    protected $needAuth = false;
    private $_forbid_deal_status;

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
            return false;
        }

        if (empty($this->form->data['id'])) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return false;
        }

        $this->form->data['id'] = intval($this->form->data['id']);
        if (!$this->isWapCall()) {
            // wap跳转需要加密的id参数
            $dealId = Aes::encryptForDeal($this->form->data['id']);
            $token = $this->form->data['token'];
            $this->redirectWapUrl .= "?dealid={$dealId}&token={$token}";
        }

        $this->_forbid_deal_status = array(2, 3, 4, 5);
    }

    public function invoke() {
        $deal_id = $this->form->data['id'];
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();

        $dealService = new DealService();
        if (deal_belong_current_site($deal_id)) {
            $deal = $dealService->getDeal($deal_id, true);
        } else {
            $deal = null;
        }

        if (empty($deal)) {
            $this->setErr("ERR_PARAMS_ERROR", "id is error");
            return $this->return_error();
        }

        // 检测当前标是否为满标状态
        $result['isFull'] = 0;
        if (in_array($deal->deal_status, $this->_forbid_deal_status)) {
            $result['old_name'] =  $deal["old_name"];
            $result['deal'] = $deal;
            $result['isFull'] = 1;
            if (isset($this->form->data['token'])) {
                $dealLoadService = new DealLoadService();
                $is_load = $dealLoadService->getUserDealLoad($loginUser['id'], $deal_id);
                //如果不是当前标的投资用户
                $result['isFull'] = !$is_load ? 1 : 0;
            }
        }

        //查询项目简介
        $projectService = new ProjectService();
        if ($deal['project_id']) {
            $project = $projectService->getProInfo($deal['project_id'], $deal['id']);
        }

        // 项目风险承受能力
        $project_risk = isset($project['risk']) ?$project['risk'] : [];
        $project_risk['is_check_risk'] = $project_risk['needForceAssess'] = 0;
        if (!empty($loginUser)) {
            $riskAssessmentService = new RiskAssessmentService();
            $user_risk = $riskAssessmentService->getUserRiskAssessmentData($loginUser['id']);
            $project_risk['needForceAssess'] = $user_risk['needForceAssess'];
            if ($loginUser['is_enterprise_user'] == 0){
                // 检查项目风险承受和个人评估 (企业会员不受限制)
                $dealProjectRiskAssessmentService = new DealProjectRiskAssessmentService();
                $project_risk_ret = $dealProjectRiskAssessmentService->checkRiskBid(intval($deal['project_id']),$loginUser['id'], true, $user_risk);
                if ($project_risk_ret['result'] == false){
                    $project_risk['is_check_risk'] = 1;
                    $project_risk['remaining_assess_num'] = $project_risk_ret['remaining_assess_num'];
                    $project_risk['user_risk_assessment'] = $project_risk_ret['user_risk_assessment'];
                }
            }
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
            $deal['income_by_wan'] = floorfix(bcmul($deal['expire_rate'], 10000,5)/100);
            $deal['min_loan_money'] = number_format($deal['min_loan_money'], 2, '.', '');
            //$deal['min_loan'] = number_format($deal['min_loan_money'] / 10000, 2, '.', '');
            //$deal['min_loan'] = number_format(ceil(($deal['min_loan_money'] / 10000) * 100)/100,2);
            $deal['min_loan'] = number_format(bcdiv($deal['min_loan_money'] , 10000,5),2);
            if ($deal['deal_type'] == 1) {
                $dealCompoundService = new DealCompoundService();
                $dayRateSrc = $dealCompoundService->convertRateYearToDay($deal['int_rate'], $deal['redemption_period']);
                $deal['dayRateShow'] = number_format($dayRateSrc * 100, 5);
                $deal['dayRate'] = 1 + $dayRateSrc;
                $deal['defaultProfit'] = number_format(10000 * pow($deal['dayRate'], $deal['timeBegin']) - 10000, 2);
            }
        }

        $res = array(
            'old_name' => $deal['old_name'],
            'deal_tag_name' => $deal['deal_tag_name'],
            'deal_tag_name1' => $deal['deal_tag_name1'],
            'loantype' => $deal['loantype'],
            'deal_type' => $deal['deal_type'],
            'income_base_rate' => $deal['income_base_rate'],
            'isBxt' => $deal['isBxt'],
            'maxRate' => $deal['maxRate'],
            'deal_status' => $deal['deal_status'],
            'need_money_detail' => $deal['need_money_detail'],
            'repay_time' => $deal['repay_time'],
            'is_entrust_zx' => $deal['is_entrust_zx'],
            'formated_start_time' => $deal['formated_start_time'],
            'repay_start_time_name' => $deal['repay_start_time_name'],
            'formated_repay_start_time' => $deal['formated_repay_start_time'],
            'formated_diff_time' => $deal['formated_diff_time'],
            'min_loan_money' => $deal['min_loan_money'],
            'min_loan' => $deal['min_loan'],
            'is_crowdfunding' => $deal['is_crowdfunding'],
            'income_by_wan' => $deal['income_by_wan'],
            'income_subsidy_rate' => $deal['income_subsidy_rate'],
            'loantype_name' => $deal['loantype_name'],
            'project_risk' => $project_risk, // 项目风险承受能力
            'warrant' => $deal['warrant'],
            'project_intro' => isset($project['intro_html']) ? str_replace('white-space: pre', '', $project['intro_html']) : '',
            'post_loan_message' => $project['post_loan_message'], // 贷后信息披露 网贷才有该字段
            'load_money' => format_price($deal['load_money']),
            'total' => $deal['borrow_amount_wan_int'],
            'remain_time_format' => $deal['remain_time_format'],
            'start_loan_time_format' => isset($deal['start_loan_time_format']) ? $deal['start_loan_time_format'] : null,
            'is_update' => $deal['is_update'],
            'guarantor_status' => $deal['guarantor_status'],
            'formated_end_time' => $deal['formated_end_time'],
        );

        $res = array_merge($res, $this->getCompanyAndLoanList($deal));

        $res['deal_user_info']['info'] = $deal['deal_user_info']['info'];
        $res['agency_info']['brief'] = $deal['agency_info']['brief'];

        $result['deal'] = $res;
        $result['data'] = $data;
        $isDisclosure = isset($data['is_disclosure']) ? (int) $data['is_disclosure'] : 0;
        $result['is_disclosure'] = $isDisclosure;
        $this->json_data = $result;
    }

    /**
     * 获取订单的融资方信息和投资人列表
     *
     * @param $deal
     */
    protected function getCompanyAndLoanList($deal) {
        //借款人信息
        $deal_user_info = UserService::getDealUserInfo($deal['user_id'], true, true);

        //机构名义贷款信息
        $dealService = new DealService();
        $company = $dealService->getDealUserCompanyInfo($deal);
        $company['company_description_html'] = convert_upload($company['company_description_html']);

        //借款列表
        $dealLoadService = new DealLoadService();
        $loadRes = $dealLoadService->getDealLoanListByDealId($deal['id']);
        $load_list = array();
        foreach ($loadRes as $key => $value){
            $load_list[$key]['user_deal_name'] = $value['user_deal_name'];
            $load_list[$key]['create_time'] = $value['create_time'];
            $load_list[$key]['money'] = number_format($value['money'], 2);
        }
        $load_list_count = count($load_list);
        return array(
            'company' => $company,
            'deal_user_info' => $deal_user_info,
            'load_list_count' => $load_list_count,
            'load_list' => $load_list
        );
    }

    public function return_error() {
        parent::_after_invoke();
        return false;
    }

}
