<?php

namespace api\controllers\deal;

use core\dao\DealModel;
use core\dao\UserModel;
use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\DealLoanTypeService;
use core\service\UserTrackService;
use libs\payment\supervision\Supervision;
use core\dao\EnterpriseModel;
use core\dao\DealLoadModel;
use libs\utils\Aes;

class Confirm extends AppBaseAction {

    const IS_H5 = true;

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
            'forceCodeEmpty' => array('filter' => 'string'),
            'discount_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_group_id' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_sign' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'discount_bidAmount' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
        $this->_forbid_deal_status = array(2, 3, 4, 5);
    }

    public function invoke() {

        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return app_redirect('/error/tkTimeout');
        }

        // 强制风险评测
        $needForceAssess = 0;
        $limitMoneyData = array();
        // 非企业用户才需要进行风险评测
        if ($loginUser['is_enterprise_user'] != 1 && $loginUser['idcardpassed'] == 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($loginUser['id'])));
            $needForceAssess = $riskData['needForceAssess'];
            $limitMoneyData = !empty($riskData['limitMoneyData']) ? $riskData['limitMoneyData'] : array();
        }

        $remain = $loginUser['money'];
        $dealId = $data['id'];

        $riskBackurl = sprintf('/deal/confirm/?id=%u&token=%s', $dealId, $data['token']);

        if (deal_belong_current_site($dealId)) {
            $deal = $this->rpc->local('DealService\getDeal', array($dealId, true));
        } else {
            $deal = null;
        }
        if (!$deal) {
            //网信查不到的直接转到普惠
            if (is_numeric($dealId)) {
                $dealId = Aes::encryptForDeal($dealId);
            }
            $phWapUrl = app_conf('NCFPH_WAP_HOST').'/deal/confirm?dealid='. $dealId.'&token='.$data['token'];
            return app_redirect($phWapUrl);
        }

        $allow_bid = 1;
        if($this->rpc->local('DealService\isP2pPath', array($deal))){
            if(!$this->rpc->local('UserService\allowAccountLoan', array($loginUser['user_purpose']))){
                $allow_bid = 0;
            }
        }

        if($deal['deal_type'] != 0){
            $limitMoneyData = array();
        }

        // 检测当前标是否为满标状态
        if (in_array($deal->deal_status, $this->_forbid_deal_status)) {
            $this->tpl->assign('deal', $deal);
            $this->tpl->assign('isFull', 1);
            $this->template = 'api/views/_v10/deals/full.html';
            return;
        }

        if ($deal['deal_type'] == 1) {
            //$compound = $this->rpc->local('DealCompoundService\getDealCompound', array($dealId));
            $compound = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealCompoundService\getDealCompound', array($dealId)), 600);
        }

        $result = array();
        $result['cash'] = $loginUser['money'];
        $result['deal_type'] = $deal['deal_type'];
        $result['report_status'] = $deal['report_status'];
        $result['productID'] = $deal['id'];
        $result['type_tag'] = $deal['type_tag'];
        $result['type'] = 0; // 页面上保留标签
        $result['title'] = $deal['name'];
        $result['rate'] = number_format($deal['income_base_rate'] + $deal['income_float_rate'] + $deal['income_subsidy_rate'], 2);
        $result['timelimit'] = ($deal['deal_type'] == 1 ? ($compound['lock_period'] + $compound['redemption_period']) . '~' : '') . $deal['repay_time'] . ($deal['loantype'] == 5 ? "天" : "个月");
        $result['total'] = format_price($deal['borrow_amount'] / 10000, false) . "万";
        $avaliable = $deal['borrow_amount'] - $deal['load_money'];
        $result['avaliable'] = format_price($avaliable, false);
        $result['repayment'] = $deal['deal_type'] == 1 ? '提前' . $compound['redemption_period'] . '天申赎' : (isDealP2P($deal['deal_type']) ? str_replace('收益', '利息', $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']]) : $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']]);
        $result['mini'] = format_price($deal['min_loan_money']);
        $result['income_base_rate'] = $deal['income_base_rate'];

        //借款人信息
        $dealUserInfo = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('UserService\getUser', array($deal['user_id'])), 600);

        //出借人风险提示
        $riskWarning = ['person' => "经风险评估该项目您的出借金额上限为20万元", 'enterprise' => "经风险评估该项目您的出借金额上限为100万元"];
        $riskWarningReminder = $dealUserInfo['user_type'] == UserModel::USER_TYPE_NORMAL ? $riskWarning['person'] : $riskWarning['enterprise'];
        $this->tpl->assign('riskWarningReminder', $riskWarningReminder);
        // 项目风险承受
        $deal_project_risk = $this->rpc->local("DealProjectService\getProRisk", array(intval($deal['project_id'])));
        $result['project_risk'] = $deal_project_risk['risk'];
        $result['project_risk']['is_check_risk'] = 0;
        if ($loginUser['is_enterprise_user'] == 0){
            // 检查项目风险承受和个人评估 (企业会员不受限制)
            $project_risk_ret = $this->rpc->local("DealProjectRiskAssessmentService\checkRiskBid", array(intval($deal['project_id']),$loginUser['id'] ));
            if ($project_risk_ret['result'] == false){
                $result['project_risk']['is_check_risk'] = 1;
                $result['project_risk']['remaining_assess_num'] = $project_risk_ret['remaining_assess_num'];
                $result['project_risk']['user_risk_assessment'] = $project_risk_ret['user_risk_assessment'];
            }
        }



        $bonus = $this->rpc->local('BonusService\get_useable_money', array($loginUser['id']));
        $result['bonus'] = strval($bonus['money']);
        //$deal['deal_crowd'] = \core\dao\DealModel::DEAL_CROWD_NEW;
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
        //if ($deal['max_loan_money'] > 0) {
        //    $result['remainSrc'] = min($result['remainSrc'], $deal['max_loan_money']);
        //}
        $result['remain'] = number_format($remain, 2);
        $result['userMoneyTtl'] = $remain;
        $result['dealMoneyLeft'] = round($avaliable, 2);
        //$result['period_rate'] = number_format($deal['period_rate'], 2) . "%";
        //$result['expire_earning'] = number_format($earning, 2);
        //增加标的是否属于专项标标识
        $result['isDealZX'] = $this->rpc->local('DealService\isDealEx', array($deal['deal_type']));
        $result['isDealExchange'] = $this->rpc->local('DealService\isDealExchange', array($deal['deal_type']));


        $result['contract'] = array();
        if ($deal['contract_tpl_type']) {
            //$contpre = $this->rpc->local("ContractPreService\getDealContPreTemplate", array($deal['id']));
            $contpre = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('ContractPreService\getDealContPreTemplate', array($deal['id'])), 600);
            $cont_url = 'https://' . get_host() . "/deal/contractpre?token={$data['token']}&money={$result['remainSrc']}&id={$deal['id']}";

            //zx
            if ($result['isDealZX']) {
                $contract = array(
                    array(
                        "nameSrc" => $contpre['entrust_cont']['contract_title'],
                        "name" => urlencode($contpre['entrust_cont']['contract_title']),
                        "url" => urlencode($cont_url . '&type=8')
                    )
                );
            } elseif ($result['isDealExchange']){
                $contract = array(
                        array(
                                "nameSrc" => !empty($contpre['subscribe_cont']['contractTitle']) ? $contpre['subscribe_cont']['contractTitle'] : '',
                                "name" => !empty($contpre['subscribe_cont']['contractTitle']) ? urlencode($contpre['subscribe_cont']['contractTitle']) : '',
                                "url" => urlencode($cont_url . '&type=20')
                        ),
                        array(
                                "nameSrc" => !empty($contpre['perception_cont']['contractTitle']) ? $contpre['perception_cont']['contractTitle'] : '',
                                "name" => !empty($contpre['perception_cont']['contractTitle']) ? urlencode($contpre['perception_cont']['contractTitle']) : '',
                                "url" => urlencode($cont_url . '&type=21')
                        ),
                        array(
                                "nameSrc" => !empty($contpre['raise_cont']['contractTitle']) ? $contpre['raise_cont']['contractTitle'] : '',
                                "name" => !empty($contpre['raise_cont']['contractTitle']) ? urlencode($contpre['raise_cont']['contractTitle']) : '',
                                "url" => urlencode($cont_url . '&type=22')
                        ),
                        array(
                                "nameSrc" => !empty($contpre['qualified_cont']['contractTitle']) ? $contpre['qualified_cont']['contractTitle'] : '',
                                "name" => !empty($contpre['qualified_cont']['contractTitle']) ? urlencode($contpre['qualified_cont']['contractTitle']) : '',
                                "url" => urlencode($cont_url . '&type=23')
                        ),
                );
            }else {
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

                $coupon['remark'] = isset($coupon['remark']) ? $coupon['remark'] : '';
                $tmp = array();
                if (isset($coupon['rebate_ratio_show']) && ($coupon['rebate_ratio_show'] > 0) ) {
                    $tmp[] = '+' . $coupon['rebate_ratio_show'] . '%';
                }
                if (isset($coupon['rebate_amount']) &&  $coupon['rebate_amount'] > 0) {
                    $tmp[] = '+' . number_format($coupon['rebate_amount'], 2) . '元';
                }
                $result['couponProfitStr'] = implode(',', $tmp);
                $result['rebateRatio'] = $coupon['rebate_ratio_show'];
                $result['couponStr'] = $coupon['short_alias'];
                if (isset($coupon['valid_begin']) && isset($coupon['valid_end'])) {
                    $result['couponRemark'] = "<p>有效期：" . date('Y年m月d日', strtotime($coupon['valid_begin']))
                        . "至" . date('Y年m月d日', strtotime($coupon['valid_end'])) . "</p><p>"
                        . str_replace(array("\r", "\n"), "", $coupon['remark']) . "</p>";
                }
            }
        }
        $result['getCouponUrl'] = urlencode(get_http() . get_host() . '/help/coupon/'); // 如何获取优惠码
        // 优惠码校验
        if (isset($data['code']) && !empty($data['code'])) {
            $code = $data['code'];
            $codeInfo = $this->rpc->local('CouponService\queryCoupon', array($code, true));
            $tmp = array();
            if ($codeInfo['rebate_ratio_show'] > 0) {
                $tmp[] = '+' . $codeInfo['rebate_ratio_show'] . '%';
            }
            if ($codeInfo['rebate_amount'] > 0) {
                $tmp[] = '+' . number_format($codeInfo['rebate_amount'], 2) . '元';
            }
            $codeInfo['couponProfitStr'] = implode(',', $tmp);
            $this->tpl->assign('codeInfo', $codeInfo);
        }

        //$divBase = ($deal['loantype'] == 5)?360:12;
        //$periodRate = bcdiv(bcmul($deal['rate'],$deal['repay_time']),$divBase);
        //$result['period_rate'] = $this->rpc->local('EarningService\getEarningRate', array($dealId, false));
        $result['period_rate'] = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('EarningService\getEarningRate', array($dealId)), 600);
        $result['loantype'] = $deal['loantype'];
        $result['isBxt'] = $deal['isBxt'];
        $result['maxRate'] = number_format($deal['max_rate'], 2);

        $siteId = \libs\utils\Site::getId();
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        if ($deal['deal_type'] == 1 || $deal['isBxt'] == 1 || $deal['loantype'] == 7) {
            // 通知贷、变现通、公益标不展示
            $o2oDiscountSwitch = 0;
        }

        $result['expected_repay_start_time'] = $deal['expected_repay_start_time']; // 预计起息日
        $result['is_entrust_zx'] = $deal['is_entrust_zx']; // 是否为专享1.75

        //账户类型名称
        $accountInfo = $this->rpc->local('ApiConfService\getAccountNameConf');
        $result['wxAccountConfig'] = $accountInfo[0];
        $result['p2pAccountConfig'] = $accountInfo[1];

        //是否主站登录
        $userTrackService = new UserTrackService();
        $isFromWxlc = $userTrackService->isWxlcLogin($loginUser['id']);
        $this->tpl->assign('isFromWxlc', $isFromWxlc);

        $this->tpl->assign('deal', $result);
        $this->tpl->assign('dealId', $dealId);
        $this->tpl->assign('siteId', $siteId);
        $this->tpl->assign('allowBid', $allow_bid);
        $this->tpl->assign('data', $data);
        $this->tpl->assign('userId', $loginUser['id']);
        $this->tpl->assign('userName', $loginUser['user_name']);
        $this->tpl->assign('usertoken', $data['token']);
        $this->tpl->assign('discount_id', isset($data['discount_id']) ? $data['discount_id'] : '');
        // 默认是1，其中1表示返现券，2表示加息券
        $this->tpl->assign('discount_type', isset($data['discount_type']) ? $data['discount_type'] : 1);
        $this->tpl->assign('discount_group_id', isset($data['discount_group_id']) ? $data['discount_group_id'] : null);

        $discountSign = isset($data['discount_sign']) ? $data['discount_sign'] : '';
        if(!empty($data['discount_id']) && empty($data['discount_sign'])){
            $params = array('user_id'=> $loginUser['id'], 'deal_id'=> $dealId, 'discount_id' => $data['discount_id'], 'discount_group_id' => $data['discount_group_id']);
            $discountSign = $this->rpc->local('DiscountService\getSignature', array($params));
        }
        $this->tpl->assign('discount_sign', $discountSign);

        $this->tpl->assign('discount_bidAmount', isset($data['discount_bidAmount']) ? $data['discount_bidAmount'] : '');
        $this->tpl->assign('appversion', isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '');
        $this->tpl->assign('o2oDiscountSwitch', $o2oDiscountSwitch);
        $this->tpl->assign('isFull', 0);

        // $showWxb = isset($data['wxb']) && $data['wxb'] == 'true' ? true : false ;
        // if ($showWxb) {
        //     $this->template = $this->getTemplate('confirm_new');
        // }

        $this->tpl->assign('needForceAssess', $needForceAssess);
        $this->tpl->assign('backurl', $riskBackurl);
        $this->tpl->assign('limitMoneyData', $limitMoneyData);

        //存管相关
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $loginUser['id']));
        $this->tpl->assign('isBankcard', empty($bankcard) ? 0 : 1);
        $cnMoneyTtl = $bonus['money'];
        $userMoneyTtl = $result['userMoneyTtl'];
        $avaliableBalance = $result['userMoneyTtl'];
        $supervisionService = new \core\service\SupervisionService();
        $supervisionService->ignoreReqExc = true; //忽略请求异常
        $svInfo = $supervisionService->svInfo($loginUser['id']);
        $this->tpl->assign('wxMoney', number_format($loginUser['money'], 2));
        if (isset($svInfo['isSvUser']) && $svInfo['isSvUser']) {
            $userMoneyTtl += $svInfo['svBalance'];
            $this->tpl->assign('totalMoney', number_format(bcadd($remain, $svInfo['svBalance'], 2), 2));

            $cnMoneyTtl += $svInfo['svBalance'];//普惠余额只显示存管和红包余额

            $svInfo['svBalance'] = number_format($svInfo['svBalance'], 2);
        }
        $this->tpl->assign('svInfo', $svInfo);
        $this->tpl->assign('userMoneyTtl', $userMoneyTtl);
        $this->tpl->assign('cnMoneyTtl', number_format($cnMoneyTtl, 2));

        //用户可用余额
        if ($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL) {
            $this->tpl->assign('avaliableBalance',number_format($cnMoneyTtl,2));
        }
        if (in_array($deal['deal_type'], [DealModel::DEAL_TYPE_EXCLUSIVE, DealModel::DEAL_TYPE_EXCHANGE])) {
            $this->tpl->assign('avaliableBalance',number_format($avaliableBalance,2));
        }
        //是否是存管升级用户
        $isUpgradeAccount = $this->rpc->local('SupervisionService\isUpgradeAccount', array($loginUser['id']));
        $this->tpl->assign('isUpgradeAccount', intval($isUpgradeAccount));

        //存管服务降级
        $this->tpl->assign('isServiceDown', Supervision::isServiceDown() ? 1 : 0);

        //会员信息
        $isShowVip = 0;
        if ($this->rpc->local("VipService\isShowVip", array($loginUser['id']), "vip")) {
            $vipInfo = $this->rpc->local("VipService\getVipGrade",array($loginUser['id']), "vip");
            $vip['vipGradeName'] = $vipInfo['name'];
            $vip['raiseInterest'] = $vipInfo['raiseInterest'];
            $isShowVip = $vipInfo['service_grade'] != 0 ? 1 : 0;
            $this->tpl->assign('vipInfo', $vip);
        }
        $this->tpl->assign('isShowVip', $isShowVip);

        //来源
        $sourceType = ($this->getOs() == 1) ? DealLoadModel::$SOURCE_TYPE['ios'] : DealLoadModel::$SOURCE_TYPE['android'];
        $this->tpl->assign('source_type', $sourceType);
    }

    public function _after_invoke() {
        $this->afterInvoke();
        $this->tpl->display($this->template);
    }

    public function valid_money($value) {
        if ($value == null) {
            return true;
        }
        if (floatval($value) == 0) {
            return false;
        }
        if (!preg_match("/^[-]{0,1}[\d]*(\.\d{1,2})?$/", $value)) {
            return false;
        }
        return true;
    }

}
