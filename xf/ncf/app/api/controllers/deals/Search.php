<?php
/**
 * 搜索接口
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;

class Search extends AppBaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "version" => array("filter" => "int", 'option' => array('optional' => true)),
            "deal_id" => array("filter" => "int", 'option' => array('optional' => true)),
            "total_min" => array("filter" => "float", 'option' => array('optional' => true)),
            "total_max" => array("filter" => "float", 'option' => array('optional' => true)),
            "region" => array("filter" => "string", 'option' => array('optional' => true)),
            "rate_min" => array("filter" => "float", 'option' => array('optional' => true)),
            "rate_max" => array("filter" => "float", 'option' => array('optional' => true)),
            "timelimit_min" => array("filter" => "int", 'option' => array('optional' => true)),
            "timelimit_max" => array("filter" => "int", 'option' => array('optional' => true)),
            "offset" => array("filter"=>"int", 'option' => array('optional' => true)),
            "count" => array("filter"=>"int", 'option' => array('optional' => true)),
            "type" => array("filter"=>"string", 'option' => array('optional' => true)),
            "timelimit" => array("filter"=>"int", 'option' => array('optional' => true)),
            "rate" => array("filter"=>"int", 'option' => array('optional' => true)),
            "site_id" => array("filter"=>"int", 'option' => array('optional' => true)),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $version = empty($data["version"]) ? 0 : $data["version"];
        $dealId = empty($data['deal_id']) ? 0 : $data['deal_id'];
        $types = explode(",", $data['type']);
        $site_id = $data['site_id'] ? $data['site_id'] : $this->defaultSiteId;
        $p = $data['offset'] && $data['count'] ? intval($data['offset'] / $data['count']) + 1 : 1 ;
        if ($dealId > 0) {
            $deals = $this->rpc->local('DealService\searchDealById', array($dealId), 'deal');
        } elseif ($version == 1) {
            $total['min'] = (isset($data['total_min']) && $data['total_min'] > 0) ? $data['total_min'] : -1;
            $total['max'] = (isset($data['total_max']) && $data['total_max'] > 0) ? $data['total_max'] : -1;
            $rate['min'] = (isset($data['rate_min']) && $data['rate_min'] > 0) ? $data['rate_min'] : -1;
            $rate['max'] = (isset($data['rate_max']) && $data['rate_max'] > 0) ? $data['rate_max'] : -1;
            $timelimit['min'] = (isset($data['timelimit_min']) && $data['timelimit_min'] > 0) ? $data['timelimit_min'] : -1;
            $timelimit['max'] = (isset($data['timelimit_max']) && $data['timelimit_max'] > 0) ? $data['timelimit_max'] : -1;
            $region = isset($data['region']) ? $data['region'] : '0';

            $deals = $this->rpc->local('DealService\searchDealsBySections', array($types, $total, $rate, $timelimit, $region, $p, $data['count'], false, $site_id, false), 'deal');
        } else {
            $deals = $this->rpc->local('DealService\searchDeals', array($types, $data['timelimit'], $data['rate'], $p, $data['count'], false, $site_id, false), 'deal');
        }
        $result = array();
        foreach ($deals as $k => $v) {
            $result[$k]['productID'] = $v['id'];
            $result[$k]['type'] = $v['type_match_row'];
            $result[$k]['title'] = $v['old_name'];
            //$result[$k]['rate'] = $v['rate'];
            $result[$k]['timelimit'] = $v['repay_time'] . ($v['loantype']==5 ? "天" : "个月");
            $result[$k]['total'] = $v['borrow_amount_wan_int'];
            $result[$k]['avaliable'] = $v['need_money_detail'];
            $result[$k]['mini'] = $v['min_loan_money_format'];
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
            $result[$k]['daren'] = ($v['min_loan_total_count'] > 0 || $v['min_loan_total_amount'] > 0) ? 1 : 0;
            $result[$k]['deal_tag_name'] = $v['deal_tag_name'];
            $result[$k]['deal_tag_name1'] = $v['deal_tag_name1'];
            $result[$k]['deal_type'] = $v['deal_type'];
        }

        $this->json_data = $result;
    }
}
