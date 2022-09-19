<?php

namespace api\controllers\deals;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\deal\DealService;
use core\enum\DealEnum;
use core\dao\deal\DealModel;

class Zone extends AppBaseAction {

    protected $needAuth = false;
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            "count" => array("filter" => "int"),
            'token' => array('filter' => 'string', "option" => array('optional' => true)),
        );

        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $count = intval($data['count']);

        $result = \core\dao\ConfModel::instance()->get(DealService::ZONE_KEY)['value'];
        $tagsName = explode(',', $result);

        $flipTagsName = array_flip($tagsName);
        $deals = $this->rpc->local('DealService\getZoneList', [], 'deal');
        $result = $dealsGroup = [];
        foreach ($deals as $deal) {
            $deal['deal_tag_name_arr'] = explode(',', $deal['deal_tag_name']);
            $deal = DealModel::instance()->handleDealNew($deal);
            foreach ($deal['deal_tag_name_arr'] as $tag) {
                if (isset($flipTagsName[$tag])) {
                    $dealsGroup[$tag][] = $this->handleDeal($deal);
                }
            }
            unset($deal['deal_tag_name_arr']);
        }
        $dealList = [];
        foreach ($tagsName as $tagName) {
            $sortTagDeals = $this->array_sort($dealsGroup[$tagName], 'days', 'asc');
            foreach($sortTagDeals as $item) {
                $dealList[] = $item;
            }
        }

        //多标签去重
        $tmpDealList = [];
        foreach ($dealList as $item) {
            if (isset($tmpDealList[$item['productID']])) {
                continue;
            }
            $tmpDealList[$item['productID']] = $item;
        }
        $dealList = array_values($tmpDealList);

        $total = count($dealList);
        if ($count > 0 && $total >= $count) {
            $dealList = array_slice($dealList, 0, $count);
        }

        $this->json_data = ['total' => $total, 'list' => $dealList];
    }

    private function handleDeal($dealInfo) {
        $ret['productID'] = $dealInfo['id'];
        $ret['type'] = $dealInfo['type_match_row'];

        if (!empty($dealInfo['product_class']) || empty($dealInfo['deal_name_prefix'])) {
            $ret['title'] = $dealInfo['old_name'];
        } else {
            $ret['title'] = $dealInfo['deal_name_prefix'] . $dealInfo['project_name'];
        }

        $ret['timelimit'] = ($dealInfo['deal_type'] == 1 ? ($dealInfo['lock_period'] + $dealInfo['redemption_period']) . '~' : '') . $dealInfo['repay_time'] . ($dealInfo['loantype'] == 5 ? "天" : "个月");
        $ret['total'] = $dealInfo['borrow_amount_wan_int'];
        $ret['avaliable'] = $dealInfo['need_money_detail'];
        $ret['mini'] = $dealInfo['min_loan_money_format'];
        $ret['repayment'] = $dealInfo['deal_type'] == 1 ? '提前' . $dealInfo['redemption_period'] . '天申赎' : $dealInfo['loantype_name'];
        $ret['loantype'] = $dealInfo['loantype'];
        $ret['stats'] = $dealInfo['deal_status'];
        $ret['crowd_str'] = $dealInfo['crowd_str'];
        $ret['deal_crowd'] = $dealInfo['deal_crowd'];
        $ret['start_loan_time'] = isset($dealInfo['start_loan_time_format']) ? $dealInfo['start_loan_time_format'] : "";
        $ret['income_base_rate'] = $dealInfo['income_base_rate'];
        $ret['income_ext_rate'] = $dealInfo['income_ext_rate'];

        $ret['rate'] = $ret['max_rate'] = $dealInfo['income_total_show_rate'];
        // jira:4080 显示的时候只显示基本利率app 时间base_rate + ext_rate 加起来了 app端改的话需要发版，所以在api这里进行修改
        $ret['income_ext_rate'] = 0;

        if (in_array($dealInfo['deal_crowd'], array(DealEnum::DEAL_CROWD_NEW, DealEnum::DEAL_CROWD_MOBILE_NEW))) {
            $ret['money_loan'] = number_format($dealInfo['min_loan_money'], 2, ".", "");
        } else {
            $ret['money_loan'] = number_format($dealInfo['need_money_decimal'], 2, ".", "");
        }
        $ret['daren'] = ($dealInfo['min_loan_total_count'] > 0 || $dealInfo['min_loan_total_amount'] > 0) ? 1 : 0;
        $ret['deal_tag_name'] = $dealInfo['deal_tag_name'];
        $ret['deal_tag_name1'] = $dealInfo['deal_tag_name1'];

        // IOS客户端不支持type=3 因为着急上线 所以暂时返回type=0来兼容 以后IOS需要升级来支持不同type类型 jira:4156
        $ret['deal_type'] = ($dealInfo['deal_type'] == 3 || $dealInfo['deal_type'] == 2) ? 0 : $dealInfo['deal_type'];
        $ret['needLogin'] = in_array($dealInfo['deal_type'], [2, 3]) ? 1 : 0;

        $ret['product_name'] = $dealInfo['product_name'];
        $ret['holiday_repay_type'] = 0;//暂时改成0值 $dealInfo['holiday_repay_type'];

        $ret['days'] = intval($dealInfo['repay_time']);
        if ($dealInfo['loantype'] != 5) {
            $ret['days'] = $ret['days'] * 30;
        }

        return $ret;
    }

    private function array_sort($array, $keys, $type='asc') {
        $keysvalue = $new_array = array();
        foreach ($array as $k => $v){
            $keysvalue[$k] = $v[$keys];
        }

        if($type == 'asc'){
            asort($keysvalue);
        }else{
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v){
            $new_array[$k] = $array[$k];
        }

        return $new_array;
    }

}
