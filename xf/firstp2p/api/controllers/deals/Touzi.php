<?php

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Touzi extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'string', "option" => array('optional' => true)),
            "offset" => array("filter"=>"int"),
            "count" => array("filter"=>"int"),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        $site_id = !empty($data['site_id']) ? (int) $data['site_id'] : 1 ;
        $GLOBALS['sys_config']['DEAL_SITE_ALLOW'] = get_config_db('DEAL_SITE_ALLOW',$site_id);
        $p = $data['offset'] && $data['count'] ? intval($data['offset'] / $data['count']) + 1 : 1 ;
        $count = empty($count)?10:$data['count'];
        $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getBXTList', array($p,$count)), 30);
        $result = array();
        foreach ($deals['list'] as $k => $v) {
            $result[$k]['productID'] = $v['id'];
            $result[$k]['type'] = $v['type_match_row'];
            $result[$k]['title'] = $v['old_name'];
            //$result[$k]['rate'] = $v['rate'];
            $result[$k]['timelimit'] = ($v['deal_type'] == 1 ? ($v['lock_period'] + $v['redemption_period']) .'~' : '').$v['repay_time'] . ($v['loantype']==5 ? "天" : "个月");
            $result[$k]['total'] = $v['borrow_amount_wan_int'];
            $result[$k]['avaliable'] = $v['need_money_detail'];
            $result[$k]['mini'] = $v['min_loan_money_format'];
            $result[$k]['repayment'] = $v['deal_type'] == 1 ? '提前'.$v['redemption_period'].'天申赎' : $v['loantype_name'];
            $result[$k]['stats'] = $v['deal_status'];
            $result[$k]['crowd_str'] = $v['crowd_str'];
            $result[$k]['deal_crowd'] = $v['deal_crowd'];
            $result[$k]['start_loan_time'] = isset($v['start_loan_time_format']) ? $v['start_loan_time_format'] : "";
            $result[$k]['income_base_rate'] = $v['income_base_rate'];
            // jira:4080 为了app不发版 只能改成这个鸟样
            //$result[$k]['income_ext_rate'] = $v['income_ext_rate'];
            $result[$k]['income_ext_rate'] = 0;

            $result[$k]['rate'] = $v['income_total_show_rate'];
            $result[$k]['max_rate'] = $v['max_rate'];
            if (in_array($v['deal_crowd'], array(\core\dao\DealModel::DEAL_CROWD_NEW, \core\dao\DealModel::DEAL_CROWD_MOBILE_NEW))) {
                $result[$k]['money_loan'] = number_format($v['min_loan_money'], 2, ".", "");
            } else {
                $result[$k]['money_loan'] = number_format($v['need_money_decimal'], 2, ".", "");
            }
            $result[$k]['daren'] = ($v['min_loan_total_count'] > 0 || $v['min_loan_total_amount'] > 0) ? 1 : 0;
            $result[$k]['deal_tag_name'] = $v['deal_tag_name'];
            $result[$k]['deal_type'] = ($v['deal_type'] == 3) ? 0 : $v['deal_type'];
            $v['deal_compound_status'] = isset($v['deal_compound_status']) ? strval($v['deal_compound_status']) : '';
            $result[$k]['deal_compound_status'] = $v['deal_status'] == 4 && $v['deal_compound_status'] === '0' ? '3' : $v['deal_compound_status'];
        }
        $this->json_data = $result;
    }
}
