<?php
/**
 * 列表详细信息接口
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Info extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "offset" => array("filter"=>"int"),
            "count" => array("filter"=>"int"),
            "type" => array("filter"=>"int"),
            "sort" => array("filter"=>"int"),
            "field" => array("filter"=>"int"),
            "site_id" => array("filter"=>"int"),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;

        $site_id = $data['site_id'] ? $data['site_id'] : $this->defaultSiteId;
        $p = $data['offset'] && $data['count'] ? intval($data['offset'] / $data['count']) + 1 : 1 ;
        $deals = $this->rpc->local('DealService\getList', array($data['type'], $data['sort'], $data['field'], $p, $data['count'], false, $site_id));
        $result = array();
        foreach ($deals['list']['list'] as $k => $v) {
            $result[$k]['productID'] = $v['id'];
            $result[$k]['type'] = $v['type_match_row'];
            $result[$k]['title'] = $v['old_name'];
            //$result[$k]['rate'] = $v['rate'];
            $result[$k]['timelimit'] = $v['repay_time'] . ($v['loantype']==5 ? "天" : "个月");
            $result[$k]['total'] = $v['borrow_amount_format_detail'] . "万";
            $result[$k]['avaliable'] = $v['need_money_detail'];
            $result[$k]['mini'] = $v['min_loan_money_format'] . "万";
            $result[$k]['repayment'] = $v['loantype_name'];
            $result[$k]['stats'] = $v['deal_status'];
            $result[$k]['crowd_str'] = $v['crowd_str'];
            $result[$k]['deal_crowd'] = $v['deal_crowd'];
            $result[$k]['start_loan_time'] = $v['start_loan_time_format'] ? $v['start_loan_time_format'] : "";
            $result[$k]['income_base_rate'] = $v['income_base_rate'];
            $result[$k]['income_ext_rate'] = $v['income_ext_rate'];
            $result[$k]['rate'] = $v['income_total_show_rate'];
            if ($v['deal_crowd'] == 1) {
                $result[$k]['money_loan'] = number_format($v['min_loan_money'], 2, ".", "");
            } else {
                $result[$k]['money_loan'] = number_format($v['need_money_decimal'], 2, ".", "");
            }

            // 详细信息开始
            $result[$k]['agency'] = $v['agency_info']['name'];
            $result[$k]['start_time'] = date("Y-m-d H:i", $v['start_time']);
            $result[$k]['end_time'] = date("Y-m-d H:i", $v['start_time'] + $v['enddate']*24*3600);
            $result[$k]['warrant'] = $v['warrant'];
            $result[$k]['description'] = $v['description'];
            $result[$k]['buy_count'] = $v['buy_count'];
            $result[$k]['point_percent'] = $v['point_percent'];
            $result[$k]['remain_time'] = $v['remain_time_format'];
        }

        $this->json_data = $result;
    }

}
