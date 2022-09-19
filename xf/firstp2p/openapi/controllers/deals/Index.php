<?php

/**
 * @abstract openapi 标列表页
 * @date 2014-11-26
 * @author 于涛<yutao@ucfgroup.com>
 *
 */

namespace openapi\controllers\deals;

use libs\rpc\Rpc;
use libs\web\Form;
use openapi\controllers\BaseAction;
use NCFGroup\Protos\Ptp\RequestDealList;
use libs\utils\Aes;

class Index extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "offset" => array("filter" => "int"),
            "count" => array("filter" => "int"),
            "type" => array("filter" => "int"),
            "sort" => array("filter" => "int"),
            "field" => array("filter" => "int"),
            "site_id" => array("filter" => "int"),
            "tag_name" => array("filter" => "string"),
            "dealListType" => array("filter" => "string"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        $this->form->validate();
    }

    public function invoke() {

        $data = $this->form->data;
        $site_id = $data['site_id'] ? $data['site_id'] : 1;
        $GLOBALS['sys_config']['DEAL_SITE_ALLOW'] = get_config_db('DEAL_SITE_ALLOW', $site_id);
        $p = $data['offset'] && $data['count'] ? intval($data['offset'] / $data['count']) + 1 : 1;
        $request = new RequestDealList();
        try {
            $request->setCate($data['type']);
            $request->setType($data['sort']);
            $request->setPage($p);
            $request->setPageSize($data['count']);
            $request->setSiteId(intval($site_id));
            $request->setTagName($data['tag_name']);
            $request->setDealListType($data['dealListType']);
        } catch (\Exception $exc) {
            $this->errorCode = -99;
            $this->errorMsg = "param set ERROR";
            return false;
        }
        //echo "<pre>";
        $dealsResponse = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpDeal',
            'method' => 'getDealListWithTag',
            'args' => $request
        ));
        if ($dealsResponse->resCode) {
            $this->errorCode = -1;
            $this->errorMsg = "get dealList failed";
            return false;
        }
        $result = array();
        foreach ($dealsResponse->getList() as $k => $v) {
            $result[$k]['productID'] = $v['id'];
            $result[$k]['productECID'] = Aes::encryptForDeal($v['id']);
            $result[$k]['deal_type'] = $v['deal_type'];
            $result[$k]['loan_type'] = $v['loantype'];
            $result[$k]['type'] = $v['type_match_row'];
            $result[$k]['typeId'] = $v['type_id'];
            $result[$k]['title'] = $v['old_name'];
            //$result[$k]['rate'] = $v['rate'];
            $result[$k]['timelimit'] = ($v['deal_type'] == 1 ? ($v['lock_period'] + $v['redemption_period']) . '~' : '') . $v['repay_time'] . ($v['loantype'] == 5 ? "天" : "个月");
            $result[$k]['compound_from'] = $v['deal_type'] == 1 ? $v['lock_period'] + $v['redemption_period'] : 0;
            $result[$k]['repay_time'] = $v['repay_time'];
            $result[$k]['total'] = $v['borrow_amount_wan_int'];
            $result[$k]['avaliable'] = $v['need_money_detail'];
            $result[$k]['mini'] = $v['min_loan_money_format'];
            $result[$k]['repayment'] = $v['loantype_name'];
            $result[$k]['stats'] = $v['deal_status'];
            $result[$k]['crowd_str'] = $v['crowd_str'];
            $result[$k]['deal_crowd'] = $v['deal_crowd'];
            $result[$k]['start_loan_time'] = !empty($v['start_loan_time_format']) ? $v['start_loan_time_format'] : "";
            $result[$k]['income_base_rate'] = $v['income_base_rate'];
            $result[$k]['income_ext_rate'] = $v['income_ext_rate'];
            $result[$k]['rate'] = $v['income_total_show_rate'];
            $result[$k]['max_rate'] = $v['max_rate'];
            if (in_array($v['deal_crowd'], array(\core\dao\DealModel::DEAL_CROWD_NEW, \core\dao\DealModel::DEAL_CROWD_MOBILE_NEW))) {
                $result[$k]['money_loan'] = number_format($v['min_loan_money'], 2, ".", "");
            } else {
                $result[$k]['money_loan'] = number_format($v['need_money_decimal'], 2, ".", "");
            }
            $result[$k]['daren'] = ($v['min_loan_total_count'] > 0 || $v['min_loan_total_amount'] > 0) ? 1 : 0;
            $result[$k]['deal_tag_name'] = $v['deal_tag_name'];
            $result[$k]['product_name'] = $v['product_name'];
        }

        $this->json_data = $result;
    }

}
