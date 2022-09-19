<?php

namespace api\controllers\deal;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;

class Confirmation extends AppBaseAction
{
    private $_forbid_deal_status;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'id' => array('filter' => 'required', 'message' => 'ERR_DEAL_NOT_EXIST'),
            'money' => array(
                'filter' => 'reg',
                'message' => 'ERR_MONEY_FORMAT',
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true
                ),
            ),
            'code' => array('filter' => 'string', 'option' => array('optional' => true)),
            'forceCodeEmpty' => array('filter' => 'string')
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
        $this->_forbid_deal_status = array(2,3,4,5);
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $remain = $loginUser['money'];
        $dealId = intval($data['id']);

        $dealColumnsStr = 'id, name, rate, repay_time, loantype, borrow_amount, load_money, deal_status, min_loan_money, deal_crowd, contract_tpl_type, deal_type';
        $extColumnsStr = 'income_base_rate, income_float_rate, income_subsidy_rate';
        $deal = $this->rpc->local('DealService\getManualColumnsVal', array($dealId, $dealColumnsStr));

        // 检测当前标是否为满标状态
        if (in_array($deal->deal_status,$this->_forbid_deal_status) ) {
            //$this->template = 'api/views/_v10/deals/full.html';
            $this->setErr('满标');
            return false;
        }

        $dealExt = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getExtManualColumnsVal', array($dealId, $extColumnsStr)), 600);
        if ($deal['deal_type'] == 1) {
            $compound = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealCompoundService\getDealCompound', array($dealId)), 600);
        }

        if (!$deal) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        $result = array();
        $result['cash'] = $loginUser['money'];
        $result['deal_type'] = $deal['deal_type'];
        $result['productID'] = $deal['id'];
        $result['type'] = 0; // 页面上保留标签
        $result['title'] = $deal['name'];
        $result['rate'] = number_format($dealExt['income_base_rate'] + $dealExt['income_float_rate'] + $dealExt['income_subsidy_rate'], 2);
        $result['timelimit'] = ($deal['deal_type'] == 1 ? ($compound['lock_period'] + $compound['redemption_period']) . '~' : '') . $deal['repay_time'] . ($deal['loantype'] == 5 ? "天" : "个月");
        $result['total'] = format_price($deal['borrow_amount'] / 10000, false) . "万";
        $avaliable = $deal['borrow_amount'] - $deal['load_money'];
        $result['avaliable'] = format_price($avaliable, false);
        $result['repayment'] = $deal['deal_type'] == 1 ? '提前' . $compound['redemption_period'] . '天申赎' : $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']];
        $result['mini'] = format_price($deal['min_loan_money']);

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($loginUser['id']));
        $result['bonus'] = strval($bonus['money']);
        if (in_array($deal['deal_crowd'], array(\core\dao\DealModel::DEAL_CROWD_NEW, \core\dao\DealModel::DEAL_CROWD_MOBILE_NEW))) {
            $result['remainSrc'] = min($remain, $avaliable);
            // 新手标一般是定额min=max，避免最大值设置了0不限制的情况
            $result['remainSrc'] = min($result['remainSrc'], $deal['min_loan_money']);
            $remain += $bonus['money']; // 页面展示的还是包含红包
            $result['isNew'] = 1;
        } else {
            $remain += $bonus['money'];
            $result['remainSrc'] = min($remain, $avaliable);
            $result['isNew'] = 0;
        }
        $result['remainSrc'] = number_format($result['remainSrc'], 2, '.', '');
        $result['remain'] = number_format($remain, 2);
        $result['userMoneyTtl'] = $remain;
        $result['dealMoneyLeft'] = round($avaliable, 2);
        $result['contract'] = array();
        if ($deal['contract_tpl_type']) {
            //$contpre = $this->rpc->local("ContractPreService\getDealContPreTemplate", array($deal['id']));
            $contpre = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ContractPreService\getDealContPreTemplate', array($deal['id'])), 600);
            $cont_url = get_http() . get_host() . "/deal/contractpre?token={$data['token']}&money={$result['remainSrc']}&id={$deal['id']}";
            $contract = array(
                array(
                    "nameSrc" => $contpre['loan_cont']['contract_title'],
                    "name" => urlencode($contpre['loan_cont']['contract_title']),
                    "url" => urlencode($cont_url . '&type=1')
                ),
                array(
                    "nameSrc" => $contpre['warrant_cont']['contract_title'],
                    "name" => urlencode($contpre['warrant_cont']['contract_title']),
                    "url" => urlencode($cont_url . '&type=4')
                ),
                array(
                    "nameSrc" => $contpre['lender_cont']['contract_title'],
                    "name" => urlencode($contpre['lender_cont']['contract_title']),
                    "url" => urlencode($cont_url . '&type=5')
                ),
            );
            if ($contpre['buyback_cont']) {
                $contract[] = array(
                    "nameSrc" => $contpre['buyback_cont']['contract_title'],
                    "name" => urlencode($contpre['buyback_cont']['contract_title']),
                    "url" => urlencode($cont_url . '&type=7')
                );
            }
            $result['contract'] = $contract;
        }
        $couponLatest = $this->rpc->local('CouponService\getCouponLatest', array($loginUser['id']));
        if (empty($couponLatest)) {
            $couponLatest['is_fixed'] = true;
        }
        $result['couponStr'] = '';
        $result['couponRemark'] = '';
        $result['couponIsFixed'] = $couponLatest['is_fixed'] ? 1 : 0;
        if (isset($couponLatest['coupon'])) {
            $coupon = $couponLatest['coupon'];
            if (!empty($coupon)) {
                $tmp = array();
                if ($coupon['rebate_ratio'] > 0) {
                    $tmp[] = '+' . number_format($coupon['rebate_ratio'], 2) . '%';
                }
                if ($coupon['rebate_amount'] > 0) {
                    $tmp[] = '+' . number_format($coupon['rebate_amount'], 2) . '元';
                }
                $result['couponProfitStr'] = implode(',', $tmp);
                $result['couponStr'] = $coupon['short_alias'];
                $result['couponRemark'] = "<p>". str_replace(array("\r", "\n"), "", $coupon['remark']) . "</p>";
            }
        }
        $result['getCouponUrl'] = urlencode(get_http() . get_host() . '/help/coupon/'); // 如何获取优惠码
        // 优惠码校验
        if (isset($data['code']) && !empty($data['code'])) {
            $code = $data['code'];
            $codeInfo = $this->rpc->local('CouponService\queryCoupon', array($code, true));
            $tmp = array();
            if ($codeInfo['rebate_ratio'] > 0) {
                $tmp[] = '+' . number_format($codeInfo['rebate_ratio'], 2) . '%';
            }
            if ($codeInfo['rebate_amount'] > 0) {
                $tmp[] = '+' . number_format($codeInfo['rebate_amount'], 2) . '元';
            }
            $codeInfo['couponProfitStr'] = implode(',', $tmp);
        }

        $result['period_rate'] = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('EarningService\getEarningRate', array($dealId)), 600);
        $res_data = array();
        $res_data['dealInfo'] = $result;
        $res_data['codeInfo'] = $codeInfo;
        $this->json_data = $res_data;
    }

}
