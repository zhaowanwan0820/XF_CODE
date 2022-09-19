<?php

namespace api\controllers\deal;

use core\dao\deal\DealModel;
use core\service\coupon\CouponService;
use libs\web\Form;
use libs\utils\Aes;
use api\controllers\AppBaseAction;
use core\service\risk\RiskAssessmentService;
use core\service\account\AccountService;
use core\service\deal\DealService;
use core\enum\UserAccountEnum;
use core\enum\UserEnum;
use core\enum\DealEnum;
use core\service\user\UserService;
use core\service\project\ProjectService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\bonus\BonusService;
use core\service\contract\ContractPreService;
use core\service\deal\EarningService;
use core\service\conf\ApiConfService;
use core\service\user\BankService;
use core\service\user\VipService;

class Confirm extends AppBaseAction {
    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    protected $redirectWapUrl = '/deal/confirm';

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

        if (!$this->isWapCall()) {
            // wap跳转需要加密的id参数
            // wap选券后回跳，防止dealId被二次加密
            $wapData = $_GET;
            if (is_numeric($wapData['id'])) {
                $dealId = Aes::encryptForDeal($wapData['id']);
            } else {
                $dealId = $wapData['id'];
            }

            $wapData['dealid'] = $dealId;
            // 去除没有用的字段
            unset($wapData['1']);
            unset($wapData['2']);
            unset($wapData['id']);
            unset($wapData['signature']);
            $this->redirectWapUrl .= "?".http_build_query($wapData, '', '&');
        }

        $this->_forbid_deal_status = array(2, 3, 4, 5);
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        // 强制风险评测
        $needForceAssess = 0;
        $limitMoneyData = array();
        $totalLimitMoneyData = array(); //总出借限额
        // 非企业用户才需要进行风险评测
        $isRiskValid = 1;//风险评估是否在有效期内 1为在有效期内
        if ($loginUser['is_enterprise_user'] != 1 && $loginUser['idcardpassed'] == 1){
            $RiskAssessmentService = new RiskAssessmentService();
            $riskData = $RiskAssessmentService->getUserRiskAssessmentData($loginUser['id']);
            $needForceAssess = $riskData['needForceAssess'];
            $isRiskValid = $riskData['isRiskValid'];
            $limitMoneyData = !empty($riskData['limitMoneyData']) ? $riskData['limitMoneyData'] : array();
            $totalLimitMoneyData = !empty($riskData['totalLimitMoneyData']) ? $riskData['totalLimitMoneyData'] : array();
        }

        $remain = isset($loginUser['money']) ? $loginUser['money'] : 0;
        $dealId = intval($data['id']);

        $riskBackurl = sprintf('/deal/confirm/?id=%u&token=%s', $dealId, $data['token']);

        // 判断是否当前站点
        if (deal_belong_current_site($dealId)) {
            $dealService = new DealService();
            $deal = $dealService->getDeal($dealId,true);
        } else {
            $deal = null;
        }
        if (!$deal) {
            $this->setErr('ERR_DEAL_NOT_EXIST');
            return false;
        }

        $allow_bid = 1;
        if(!AccountService::getUserAccountId($loginUser['id'],UserAccountEnum::ACCOUNT_INVESTMENT)){
            $allow_bid = 0;
        }

        if($deal['deal_type'] != 0){
            $limitMoneyData = array();
        }

        // 检测当前标是否为满标状态
        if (in_array($deal->deal_status, $this->_forbid_deal_status)) {

            // processor 里面会对deal 是否设置作为满标依据，所以不传deal值
            $this->json_data = array(
                            // 'deal' => $deal,
                            'isFull' => 1
                            );
            return;
        }

        $result = array();
        $json_data = array();
        $result['cash'] = isset($loginUser['money']) ? $loginUser['money'] : 0;
        $result['deal_type'] = $deal['deal_type'];
        $result['report_status'] = $deal['report_status'];
        $result['productID'] = $deal['id'];
        $result['type_tag'] = $deal['type_tag'];
        $result['type'] = 0; // 页面上保留标签
        $result['title'] = $deal['name'];
        $result['rate'] = number_format($deal['income_base_rate'] + $deal['income_float_rate'] + $deal['income_subsidy_rate'], 2);
        $result['timelimit'] =  $deal['repay_time'] . ($deal['loantype'] == 5 ? "天" : "个月");
        $result['total'] = format_price($deal['borrow_amount'] / 10000, false) . "万";
        $avaliable = $deal['borrow_amount'] - $deal['load_money'];
        $result['avaliable'] = format_price($avaliable, false);
        $result['repayment'] = str_replace('收益', '利息', $GLOBALS['dict']['LOAN_TYPE'][$deal['loantype']]);
        $result['mini'] = format_price($deal['min_loan_money']);
        $result['income_base_rate'] = $deal['income_base_rate'];

        // 借款人信息
        $dealUserInfo = \SiteApp::init()->dataCache->call((new UserService()), 'getUserById', array($deal['user_id']), 600);
        //出借人风险提示
        $riskWarning = ['person' => "经风险评估该项目您的出借金额上限为20万元", 'enterprise' => "经风险评估该项目您的出借金额上限为100万元"];
        $riskWarningReminder = $dealUserInfo['user_type'] == UserEnum::USER_TYPE_NORMAL ? $riskWarning['person'] : $riskWarning['enterprise'];
        $json_data['riskWarningReminder'] = $riskWarningReminder;
        // 项目风险承受
        $projectService = new ProjectService();
        $deal_project_risk = $projectService->getProRisk(intval($deal['project_id']));
        $result['project_risk'] = $deal_project_risk['risk'];
        $result['project_risk']['is_check_risk'] = 0;
        if ($loginUser['is_enterprise_user'] == 0){
            // 检查项目风险承受和个人评估 (企业会员不受限制)
            $dealProejctrriskService = new DealProjectRiskAssessmentService();
            $project_risk_ret = $dealProejctrriskService->checkRiskBid(intval($deal['project_id']),$loginUser['id'] );
            if ($project_risk_ret['result'] == false){
                $result['project_risk']['is_check_risk'] = 1;
                $result['project_risk']['remaining_assess_num'] = $project_risk_ret['remaining_assess_num'];
                $result['project_risk']['user_risk_assessment'] = $project_risk_ret['user_risk_assessment'];
            }
        }


        $bonus = BonusService::getUsableBonus($loginUser['id'], false, 0, false, $loginUser['is_enterprise_user']);

        if (isset($loginUser['canUseBonus']) && empty($loginUser['canUseBonus'])){
            $bonus['money'] = 0;
        }
        // 红包使用总开关
        $isBonusEnable = BonusService::isBonusEnable();
        if (empty($isBonusEnable)){
            $bonus['money'] = 0;
        }
        $result['bonus'] = strval($bonus['money']);
        //$deal['deal_crowd'] = \core\dao\DealModel::DEAL_CROWD_NEW;
        if (in_array($deal['deal_crowd'], array(DealEnum::DEAL_CROWD_NEW, DealEnum::DEAL_CROWD_MOBILE_NEW))) {
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
        $result['isDealZX'] = false;
        $result['isDealExchange'] = false;


        $result['contract'] = array();
        if ($deal['contract_tpl_type']) {
            $contractpreService = new ContractPreService();
            $contpre = \SiteApp::init()->dataCache->call($contractpreService, 'getDealContPreTemplate', array($deal['id']), 600);

            $cont_url = 'https://' . get_host() . "/deal/contractpre?token={$data['token']}&money={$result['remainSrc']}&id={$deal['id']}";
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
            if (isset($contpre['buyback_cont'])) {
                $contract[] = array(
                    "nameSrc" => $contpre['buyback_cont']['contract_title'],
                    "name" => urlencode($contpre['buyback_cont']['contract_title']),
                    "url" => urlencode($cont_url . '&type=7')
                );
            }

            $result['contract'] = $contract;
        }
        $couponLatest = CouponService::getCouponLatest($loginUser['id']);
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
                if (isset($coupon['rebate_ratio_show']) &&  ($coupon['rebate_ratio_show'] > 0)) {
                    $tmp[] = '+' . $coupon['rebate_ratio_show'] . '%';
                }
                if (isset($coupon['rebate_amount']) &&  ($coupon['rebate_amount'] > 0) ) {
                    $tmp[] = '+' . number_format($coupon['rebate_amount'], 2) . '元';
                }
                $result['couponProfitStr'] = implode(',', $tmp);
                $result['rebateRatio'] = isset($coupon['rebate_ratio_show']) ? $coupon['rebate_ratio_show'] : '';
                $result['couponStr'] = $coupon['short_alias'];
                if (!empty($coupon['remark'])){
                    $result['couponRemark'] = "<p>". str_replace(array("\r", "\n"), "", $coupon['remark']) . "</p>";
                }

            }
        }
        $result['getCouponUrl'] = urlencode(get_http() . get_host() . '/help/coupon/'); // 如何获取优惠码
        // 优惠码校验
        if (isset($data['code']) && !empty($data['code'])) {
            $code = $data['code'];
            $codeInfo = CouponService::queryCoupon($code, true);
            $tmp = array();
            if ($codeInfo['rebate_ratio_show'] > 0) {
                $tmp[] = '+' . $codeInfo['rebate_ratio_show'] . '%';
            }
            if ($codeInfo['rebate_amount'] > 0) {
                $tmp[] = '+' . number_format($codeInfo['rebate_amount'], 2) . '元';
            }
            $codeInfo['couponProfitStr'] = implode(',', $tmp);
            $json_data['codeInfo'] = $codeInfo;
        }

        $earningService = new EarningService();
        $result['period_rate'] = \SiteApp::init()->dataCache->call($earningService, 'getEarningRate', array($dealId), 600);
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
        $result['is_entrust_zx'] = false; // 是否为专享1.75

        //账户类型名称
        $apiservice = new ApiConfService();
        $accountInfo = $apiservice->getAccountNameConf();
        $result['wxAccountConfig'] = $accountInfo[0];
        $result['p2pAccountConfig'] = $accountInfo[1];

        //是否主站登录
        $json_data['isFromWxlc'] = false;
        $json_data['deal'] = $result;
        $json_data['dealId'] = $dealId;
        $json_data['siteId'] = $siteId;
        $json_data['allowBid'] = $allow_bid;
        $json_data['data'] = $data;
        $json_data['userId'] = $loginUser['id'];
        $json_data['userName'] = $loginUser['user_name'];
        $json_data['usertoken'] = $data['token'];
        $json_data['discount_id'] = isset($data['discount_id']) ? $data['discount_id'] : '';
        // 默认是1，其中1表示返现券，2表示加息券
        $json_data['discount_type'] = isset($data['discount_type']) ? $data['discount_type'] : 1;
        $json_data['discount_group_id'] = isset($data['discount_group_id']) ? $data['discount_group_id'] : null;


        $discountSign = isset($data['discount_sign']) ? $data['discount_sign'] : '';

        // 保持前端兼容为空，这个值从卷列表中读取
        $json_data['discount_sign'] = '';
        $json_data['discount_bidAmount'] =  isset($data['discount_bidAmount']) ? $data['discount_bidAmount'] : '';
        $json_data['appversion'] = isset($_SERVER['HTTP_VERSION']) ? $_SERVER['HTTP_VERSION'] : '';
        $json_data['o2oDiscountSwitch'] =  $o2oDiscountSwitch;
        $json_data['isFull'] = 0;

        $json_data['needForceAssess'] = $needForceAssess;
        $json_data['isRiskValid'] = $isRiskValid;
        $json_data['backurl'] = $riskBackurl;
        $json_data['limitMoneyData'] = $limitMoneyData;
        $json_data['totalLimitMoneyData'] = $totalLimitMoneyData;

        //存管相关
        $bankcard = BankService::getNewCardByUserId($loginUser['id']);
        $json_data['isBankcard'] = empty($bankcard) ? 0 : 1;
        $cnMoneyTtl = $bonus['money'];
        $userMoneyTtl = $result['userMoneyTtl'];
        $avaliableBalance = $result['userMoneyTtl'];
        $supervisionService = new \core\service\supervision\SupervisionService();
        $supervisionService->ignoreReqExc = true; //忽略请求异常
        $svInfo = $supervisionService->svInfo($loginUser['id']);
        $loginUser['money'] = isset($loginUser['money']) ? $loginUser['money'] : 0;
        $json_data['wxMoney'] = number_format($loginUser['money'], 2);
        if (isset($svInfo['isSvUser']) && $svInfo['isSvUser']) {
            $userMoneyTtl += $svInfo['svBalance'];
            $json_data['totalMoney'] = number_format(bcadd($remain, $svInfo['svBalance'], 2), 2);

            $cnMoneyTtl += $svInfo['svBalance'];//普惠余额只显示存管和红包余额

            $svInfo['svBalance'] = number_format($svInfo['svBalance'], 2);
        }
        $json_data['svInfo'] = $svInfo;
        $json_data['userMoneyTtl'] = $userMoneyTtl;
        $json_data['cnMoneyTtl'] = number_format($cnMoneyTtl, 2);

        //用户可用余额
        $json_data['avaliableBalance'] = number_format($cnMoneyTtl,2);
        $json_data['isServiceDown'] = $supervisionService::isServiceDown() ? 1 : 0;

        $json_data['new_bonus_title'] = app_conf('NEW_BONUS_TITLE');
        $json_data['new_bonus_unit'] = app_conf('NEW_BONUS_UNIT');

        //会员信息
        $isShowVip = 1;
        $vipInfo = VipService::getVipGrade($loginUser['id']);
        $vip['vipGradeName'] = $vipInfo['name'];
        $vip['raiseInterest'] = $vipInfo['raiseInterest'];
        $isShowVip = $vipInfo['service_grade'] != 0 ? 1 : 0;
        $json_data['isShowVip'] = $isShowVip;
        $json_data['vipInfo'] = $vip;

        $this->json_data = $json_data;
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
