<?php
/**
 * 随心约信息披露 标的详情
 *
 * @date 2018-01-12
 * @author weiwei12@ucfgroup.com
 */

namespace api\controllers\deal;

use libs\web\Form;
use api\controllers\ReserveBaseAction;

class ReserveDisclosureDetail extends ReserveBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array("filter" => "required", "message" => "id is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->isOpenReserve()) {
            return false;
        }
        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $data = $this->form->data;
        $dealId = $data['id'];
        $deal = $this->rpc->local('DealService\getDeal', array($dealId, true));
        if (empty($deal)) {
            $this->setErr("ERR_PARAMS_ERROR", "披露信息不存在");
            return $this->return_error();
        }

        //查询项目简介
        if ($deal['project_id']) {
            $project = $this->rpc->local('DealProjectService\getProInfo', array('id' => $deal['project_id'], 'deal_id' => $deal['id']));
        }
        $this->tpl->assign('project_intro', isset($project['intro_html']) ? str_replace('white-space: pre', '', $project['intro_html']) : '');
        // 项目风险承受能力
        $this->tpl->assign('project_risk', isset($project['risk']) ?$project['risk'] : '');

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
        $deal['point_percent_show'] = bcmul(strval($deal['point_percent']),'100.00',2);
        $this->tpl->assign("deal", $deal);

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
