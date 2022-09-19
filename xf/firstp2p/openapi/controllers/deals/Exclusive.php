<?php

namespace openapi\controllers\deals;

use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestDealList;
use libs\utils\Aes;

class Exclusive extends BaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            "offset" => array("filter" => "int"),
            "count"  => array("filter" => "int"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $request = new RequestDealList();

        $offset  = $data['offset'] <= 0 ? 1 : intval($data['offset']);
        $request->setPage($offset);

        $count = $data['count'] <= 0 ? 3 : intval($data['count']);
        $request->setPageSize($count);

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDeal',
            'method' => 'getExclusiveDeals',
            'args' => $request
        ));

        if ($response->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get dealList failed";
            return false;
        }

        $result = array();
        foreach ($response->getList() as $key => $val) {
            $result[$key]['productID'] = $val['id'];
            $result[$key]['productECID'] = Aes::encryptForDeal($val['id']);
            $result[$key]['deal_type'] = $val['deal_type'];
            $result[$key]['type']      = $val['type_match_row'];
            $result[$key]['title']     = $val['old_name'];
            $result[$key]['mini']      = $val['min_loan_money_format'];
            $result[$key]['timelimit'] = ($val['deal_type'] == 1 ? ($val['lock_period'] + $val['redemption_period']) .'~' : '').$val['repay_time'] . ($val['loantype']==5 ? "天" : "个月");

            $result[$key]['compound_from'] = $val['deal_type'] == 1 ? $val['lock_period']+$val['redemption_period'] : 0;
            $result[$key]['repay_time']    = $val['repay_time'];
            $result[$key]['total']      = $val['borrow_amount_wan_int'];
            $result[$key]['avaliable']  = $val['need_money_detail'];
            $result[$key]['repayment']  = $val['loantype_name'];
            $result[$key]['stats']      = $val['deal_status'];
            $result[$key]['crowd_str']  = $val['crowd_str'];
            $result[$key]['deal_crowd'] = $val['deal_crowd'];

            $result[$key]['start_loan_time']  = !empty($val['start_loan_time_format']) ? $val['start_loan_time_format'] : "";
            $result[$key]['income_base_rate'] = $val['income_base_rate'];
            $result[$key]['income_ext_rate']  = $val['income_ext_rate'];
            $result[$key]['rate']  = $val['income_total_show_rate'];
            $result[$key]['daren'] = ($val['min_loan_total_count'] > 0 || $val['min_loan_total_amount'] > 0) ? 1 : 0;
            $result[$key]['deal_tag_name'] = $val['deal_tag_name'];
            $result[$key]['max_rate'] = $val['max_rate'];

            if (in_array($val['deal_crowd'], array(\core\dao\DealModel::DEAL_CROWD_NEW, \core\dao\DealModel::DEAL_CROWD_MOBILE_NEW))) {
                $result[$key]['money_loan'] = number_format($val['min_loan_money'], 2, ".", "");
            } else {
                $result[$key]['money_loan'] = number_format($val['need_money_decimal'], 2, ".", "");
            }
        }

        $this->json_data = $result;
    }

}
