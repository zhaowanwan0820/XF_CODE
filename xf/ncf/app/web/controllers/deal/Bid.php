<?php
/**
 * 投资确认
 *
 **/

namespace web\controllers\deal;

use libs\web\Form;
use libs\web\Url;
use libs\utils\Logger;
use web\controllers\BaseAction;

use core\enum\UserAccountEnum;
use core\enum\DealEnum;
use core\service\contract\ContractPreService;
use core\dao\deal\DealLoadModel;
use core\service\coupon\CouponService;
use core\service\user\UserService;
use core\service\user\UserCarryService;
use core\service\project\ProjectService;
use libs\utils\Aes;
use core\service\user\VipService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\deal\DealService;
use core\service\account\AccountService;
use core\service\risk\RiskAssessmentService;
use core\service\dealload\DealLoadService;
use core\service\user\BankService;
use core\service\supervision\SupervisionService;
use core\service\bonus\BonusService;
use core\service\dealgroup\DealGroupService;
use core\service\user\UserBindService;
use core\service\payment\PaymentUserAccountService;

class Bid extends BaseAction {

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'string'),
            'money' => array(
                'filter' => 'reg',
                'message' => "金额格式错误",
                'option' => array(
                    'regexp' => '/^\d+(\.\d{1,2})?$/',
                    'optional' => true,
                ),
            )
        );
        if (!$this->form->validate()) {
            return app_redirect(url("Bid"));
        }
    }

    public function invoke() {

        $ec_id = $this->form->data['id'];
        $id = Aes::decryptForDeal($ec_id);
        $money = $this->form->data['money'];

        $dealService = new DealService();
        if(deal_belong_current_site($id)){
            $deal = $dealService->getDeal($id, true);
        }else{
            $deal = null;
        }

        if (empty($deal)) {
            return app_redirect(url("Bid"));
        }

        //是否主站登录

        $this->tpl->assign('isFromWxlc', false);

        //P2P只允许投资户投资
        // 通过userid 转换成账户信息id
        $checkAccountType = AccountService::allowAccountLoan($GLOBALS['user_info']['user_purpose']);
        if(empty($checkAccountType)){
            return $this->show_error($GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID']);
        }

        //强制风险评测
        $needForceAssess = 0;
        $limitMoneyData = array();
        $totalLimitMoneyData = array();
        $isRiskValid = 1;//风险评估是否在有效期内 1为在有效期内
        if($GLOBALS['user_info']['idcardpassed'] == 1){

            $RiskAssessmentService = new RiskAssessmentService();
            $riskData = $RiskAssessmentService->getUserRiskAssessmentData(intval($GLOBALS['user_info']['id']));
            $needForceAssess = $riskData['needForceAssess'];
            $isRiskValid = $riskData['isRiskValid'];
            $limitMoneyData = !empty($riskData['limitMoneyData']) ? $riskData['limitMoneyData'] : array();
            $totalLimitMoneyData = !empty($riskData['totalLimitMoneyData']) ? $riskData['totalLimitMoneyData'] : array();
        }


        if($deal['deal_status'] != 1){
            return $this->show_error($GLOBALS['lang']['DEAL_BID_FULL'],'',0,0,APP_ROOT."/");        //已满标
        }
        if ($deal['isDtb'] == 1) {
            return app_redirect(url("index"));
        }
        if($deal['deal_type'] != 0){
            $limitMoneyData = array();
            $totalLimitMoneyData = array();
        }

        if($GLOBALS['user_info']['idcardpassed'] == 3){
            return $this->show_error('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站内信或者电子邮件等方式通知您。', "", 0);
        }
        // 限制投资
        $userCarryService = new UserCarryService();
        $user_money_limit = $userCarryService->canWithdrawAmount($GLOBALS['user_info']['id'], $money, true);
        if ($user_money_limit === false){
            return $this->show_error($GLOBALS['lang']['FORBID_BID']);
        }

        $detail_url = "d/".Aes::encryptForDeal($id);

        if(empty($money)){
            $cookie_money_value = isset($_COOKIE["investInput"]) ? (int)$_COOKIE["investInput"] : 0;
            $account_money = bcadd($GLOBALS['user_info']['money'], $deal['bonus_money']);
            if($deal['deal_status'] == 1 && $account_money <=0){
                setcookie('invest_result', '1', time()+86400, '/');
                return app_redirect(url($detail_url));
            }
            if($deal['deal_status'] == 1 && $cookie_money_value > $deal['need_money_decimal']){
                setcookie('invest_result', '1', time()+86400, '/');
                return app_redirect(url($detail_url));
            }

            if($deal['deal_status'] == 1 && empty($cookie_money_value)){
                if($deal['need_money_decimal'] < $account_money){
                    $money = $deal['need_money_decimal'];
                }else{
                    $money = $account_money;
                }
            }
            if($deal['deal_status'] == 1 && !empty($cookie_money_value)){
                $loan_money = ((int)$deal['min_loan_money'] <1000) ? (int)$deal['min_loan_money'] : (int)$deal['min_loan'];
                if($cookie_money_value < $loan_money){
                    setcookie('invest_result', '1', time()+86400, '/');
                    return app_redirect(url($detail_url));
                }
                if($cookie_money_value > $deal['need_money_decimal']){
                    $money = $deal['need_money_decimal'];
                }else{
                    $money = $cookie_money_value;
                }
            }
            setcookie('investInput', '', time()-86400, '/');
        }

        //如果未绑定手机
        $user_id = $GLOBALS['user_info']['id'];
        $opt = [];

        $opt = ['check_validate' => false];

        $userCheck = UserBindService::isBindBankCard($user_id,$opt);
        $isEnterprise = UserService::isEnterprise($user_id);
        $siteId = \libs\utils\Site::getId();
        // 检查用户是否绑卡开户成功
        if ($userCheck['ret'] !== true)
        {
            if($isEnterprise && $userCheck['respCode'] == UserBindService::STATUS_BINDCARD_UNBIND)
            {
                return app_redirect(Url::gene('deal','promptCompany'));
            }
            //判断用户是否是港澳台、军官证、护照用户
            $hasPassport = PaymentUserAccountService::hasPassport($user_id, $GLOBALS['user_info']);
            if ($siteId == 1 && (empty($hasPassport) || (!empty($hasPassport) && $userCheck['respCode'] == UserBindService::STATUS_BINDCARD_UNVALID)))
            {
                return $this->show_payment_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
            }

            return $this->show_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
        }

        // 存管标的，如果未激活，走存管激活
        $hasUnactivatedTag = UserService::checkUserTag('SV_UNACTIVATED_USER',$user_id);
        if ($hasUnactivatedTag) {
            return $this->show_error('请先升级网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=register&return_url=%s', get_domain()), 3);
        }

        //增加支付平台用户注册，
        if(empty($GLOBALS['user_info']['payment_user_id'])){
            //showErr('无法进行投保');
            return showErr('无法投标',0,'/account',0);
        }

        if(empty($deal['need_money_decimal'])){
            return app_redirect(url("index"));
        }
        if($deal['is_visible'] != 1){
            return $this->show_error($GLOBALS['lang']['DEAL_FAILD_OPEN']);        //已不在投标状态
        }

        if($deal['user_id'] == $GLOBALS['user_info']['id']){
            return $this->show_error($GLOBALS['lang']['CANT_BID_BY_YOURSELF']);
        }

        if($isEnterprise){
            if($deal['bid_restrict'] == 1){
                return $this->show_error("本产品为个人会员专享，点击此处<a href='http://".$GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p']."'>了解更多优质理财项目！</a>",'',0,1);
            }
        }else{
            if($deal['bid_restrict'] == 2){
                return $this->show_error("本产品为企业用户专享，点击此处<a href='http://".$GLOBALS['sys_config']['SITE_DOMAIN']['firstp2p']."'>了解更多优质理财项目！</a>",'',0,1);
            }

            // 弹窗项目风险
            $user_risk_tips = 0;
            // 检查项目风险承受和个人评估
            $DealProjectRiskAssessmentService = new DealProjectRiskAssessmentService();
            $project_risk_ret = $DealProjectRiskAssessmentService->checkRiskBid($deal['project_id'],intval($GLOBALS['user_info']['id']),false);

            if ($project_risk_ret['result'] == false){
                $user_risk_tips = 1;
                $project_risk_info = array('remaining_assess_num' => $project_risk_ret['remaining_assess_num'],'user_risk_assessment' => $project_risk_ret['user_risk_assessment']);
            }
        }

        $dealService = new DealService();
        //18岁以上投资限制
        $age_check = $dealService->allowedBidByCheckAge($GLOBALS['user_info']);
        if($age_check['error'] == true){
            return $this->show_error($age_check['msg'], "", 0, 1);
        }

        $dealoadService = new DealLoadService();
        $deal_load_count = $dealoadService->getCountByUserIdInSuccess($GLOBALS['user_info']['id']);

        //新手标
        if ($deal['deal_crowd'] == '1' && $deal_load_count > 0) {
            return $this->show_error('新手专享标为平台初次出借用户推荐的优惠项目，只有第一次出借的用户才可以投标');
        //特定用户组
        } elseif ($deal['deal_crowd'] == '2') {
            $dealgroupservcie = new DealGroupService();
            $group_check = $dealgroupservcie->checkUserDealGroup($deal['id'], $GLOBALS['user_info']['id']);
            if (!$group_check) {
                return $this->show_error('专享标为平台为特定用户推荐的优惠项目，只有特定用户才可以出借');
            }
        }elseif($deal['deal_crowd'] == '16' && $GLOBALS['user_info']['id'] !=$deal->deal_specify_uid) {
            return $this->show_error('该项目为专享标，只有特定用户才可出借');
        } elseif ($deal['deal_crowd'] == 32) { // 老用户专享逻辑
            $rule = app_conf('RULE_OLD_USER');
            if (!empty($rule)) {
                $arr = explode(';', $rule);
                if (2 == count($arr)) {
                    if (to_date($GLOBALS['user_info']['create_time'], 'Ymd') >= $arr[0]) {
                        return $this->show_error($arr[1]);
                    }
                }
            }
        } elseif ($deal['deal_crowd'] == DealEnum::DEAL_CROWD_VIP ){
            //指定vip专享
            $vip = VipService::getVipInfoAndBidErrMsg( $GLOBALS['user_info']['id'],$deal['deal_specify_uid']);
            if (empty($vip['vipInfo']) || $deal['deal_specify_uid'] > $vip['vipInfo']['service_grade']) {
                return $this->show_error($vip['vipBidMsg']);
            }
        }

        // 手机专享标
        $allowdBid = $dealService->allowedBidBySourceType(0, $deal['deal_crowd'] ,$GLOBALS['user_info']);
        if ($allowdBid['error'] == true) {
            return $this->show_error($allowdBid['msg'].'<br />如尚未安装客户端可扫描下方二维码下载安装：' .
                             '<br /><br /><img src="'.PRE_HTTP.APP_HOST.'/static/v1/images/common/app_01.png" />' .
                             '<br /><br /><a href="'.PRE_HTTP.APP_HOST.'" style="font-size: 14px;">返回首页>></a>', "手机用户专享", 0, 1);
        }

        // 达人专享标
        if ($deal['min_loan_total_count'] > 0 || $deal['min_loan_total_amount'] > 0) {

            $dealLoadModel = new DealLoadModel();

            $totalCount = $dealLoadModel->getCountByUserIdInSuccess($GLOBALS['user_info']['id']);
            $totalAmount = $dealLoadModel->getAmountByUserIdInSuccess($GLOBALS['user_info']['id']);

            $loanFlag = true;
            $res = array();
            $res['msg'] = "达人专享标是平台为有经验的投资用户推荐的优惠项目，只有%s的用户才可以投标。";
            if( $deal['min_loan_total_count'] > 0 && $deal['min_loan_total_amount'] == 0 ){
                if($totalCount < $deal['min_loan_total_count']){
                    $loanFlag = false;
                    $res['msg'] = sprintf($res['msg'], '投资超过'. $deal['min_loan_total_count'] .'次');
                }

            }else if( $deal['min_loan_total_count'] == 0 && $deal['min_loan_total_amount'] > 0 ){

                if($totalAmount < $deal['min_loan_total_amount']){
                    $loanFlag = false;
                    $res['msg'] = sprintf($res['msg'], '累计投资超过'. $deal['min_loan_total_amount'] .'元');
                }

            }else if ( $deal['min_loan_total_count'] > 0 && $deal['min_loan_total_amount'] > 0 && $deal['min_loan_total_limit_relation'] == 0 ){

                if(($totalCount <= $deal['min_loan_total_count']) && ($totalAmount <= $deal['min_loan_total_amount'])){
                    $loanFlag = false;
                    $res['msg'] = sprintf($res['msg'], '投资超过'. $deal['min_loan_total_count'] .'次，或者累计投资超过'. $deal['min_loan_total_amount'] .'元');
                }

            }else if ( $deal['min_loan_total_count'] > 0 && $deal['min_loan_total_amount'] > 0 && $deal['min_loan_total_limit_relation'] == 1 ){

                if(!(($totalCount >= $deal['min_loan_total_count']) && ($totalAmount >= $deal['min_loan_total_amount']))){
                    $loanFlag = false;
                    $res['msg'] = sprintf($res['msg'], '投资超过'. $deal['min_loan_total_count'] .'次，并且累计投资超过'. $deal['min_loan_total_amount'] .'元');
                }

            }

            if(!$loanFlag){
                return $this->show_error($res['msg']);
            }
        }


        $company = $dealService->getDealUserCompanyInfo($deal);
        $this->tpl->assign('company',$company);

        $deal_user = UserService::getUserById($deal['user_id']);
        $this->tpl->assign('deal_user', $deal_user);
        $user = $GLOBALS['user_info'];
        $this->tpl->assign('user', $user);

        // 项目风险承受能力
        $project_risk_service = new ProjectService();
        $project_risk = $project_risk_service->getProRisk($deal['project_id']);
        $this->tpl->assign('project_risk', $project_risk['risk']);

        $seo_title = $deal['seo_title']!=''?$deal['seo_title']:$deal['type_match_row'] . " - " . $deal['name'];
        $this->tpl->assign("page_title",$seo_title);
        $seo_keyword = $deal['seo_keyword']!=''?$deal['seo_keyword']:$deal['type_match_row'].",".$deal['name'];
        $this->tpl->assign("page_keyword",$seo_keyword.",");
        $seo_description = $deal['seo_description']!=''?$deal['seo_description']:$deal['name'];

        $deal['min_loan_money_format'] = number_format(round($deal['min_loan_money'] / 10000, 2), 2);

        $deal['ecid'] = Aes::encryptForDeal($deal['id']);

        $this->tpl->assign("deal",$deal);
        $this->tpl->assign("deal_left_load_money", $deal["need_money"]);

        $default_manage_fee_rate = $deal['manage_fee_rate'];

        if($deal['manage_fee_rate']==0  && empty($deal['manage_fee_text'])){
            $default_manage_fee_text = '（'.app_conf('DEFAULT_MANAGE_FEE_TEXT').'）';
        }

        if($deal['manage_fee_text']){
            $default_manage_fee_text = '（'.$deal['manage_fee_text'].'）';
        }

        //用户银行卡信息
        $bankcard_info =  BankService::getNewCardByUserId($GLOBALS['user_info']['id']);
        if(!$bankcard_info || $bankcard_info['status'] != 1){
            return $this->show_error('请先填写银行卡信息', "", 0,0,url("account/addbank"),3);
        }
        $bank = BankService::getBankInfoByBankId($bankcard_info['bank_id']);
        $this->tpl->assign("bank",$bank);

        $this->tpl->assign('bankcard_info',$bankcard_info);

        if($money == 0){
            $money = floatval($user['money']);
            if($money == 0 || $money > $deal['need_money_decimal']){
                $money = $deal['need_money_decimal'];
            }
            if($deal['deal_crowd']=='1'){
                $money = $deal['min_loan_money'];
            }
        }

        $isZX = false;
        $isExchange = false;
        $ContractPreService = new ContractPreService();
        $contpre = $ContractPreService->getDealContPreTemplate($id);

        $this->tpl->assign("contpre", $contpre);
        // 判断是否为盈益
        $this->tpl->assign('is_yingyi', false);

        $this->tpl->assign('is_xiaodai', DealEnum::DEAL_TYPE_PETTYLOAN == $deal['deal_type']);

        $bonus = BonusService::getUsableBonus($GLOBALS['user_info']['id'], false, 0, false, $GLOBALS['user_info']['is_enterprise_user']);
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        if ($deal['deal_type'] == 1 || $deal['isBxt'] == 1|| $deal['loantype'] == 7) {
            // 通知贷、变现通、公益标不展示
            $o2oDiscountSwitch = 0;
        }
        $isCanUseBonuse = DealEnum::CAN_USE_BONUS;
        // 是否可以使用红包
        if (isset($GLOBALS['user_info']['canUseBonus']) && empty($GLOBALS['user_info']['canUseBonus'])){
            $bonus['money'] = 0;
            $isCanUseBonuse = 0;
            Logger::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$GLOBALS['user_info']['canUseBonuse']);
        }
        // 红包使用总开关
        $isBonusEnable = BonusService::isBonusEnable();
        if (empty($isBonusEnable)){
            Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.__LINE__.' canUseBonus '.$isBonusEnable.' '.$GLOBALS['user_info']['canUseBonuse']);
            $bonus['money'] = 0;
            // 开关开的情况下，没在黑名单里的可以使用优惠券不能使用红包
            $isCanUseBonuse = $GLOBALS['user_info']['canUseBonuse'];
        }
        $this->tpl->assign('canUseBonus',$isCanUseBonuse);
        /****** 存管逻辑  ********/
        // 是否开通协议
        $supervisionAccountService = new \core\service\supervision\SupervisionAccountService();
        $supervisionAccountService->ignoreReqExc = true;//忽略请求异常
        $isOwnBankAuth = $supervisionAccountService->isQuickBidAuthorization($GLOBALS['user_info']['id']);
        $this->tpl->assign("isOwnBankAuth", $isOwnBankAuth);
        $this->tpl->assign('bonusMoney', $bonus['money']);
        //是否开户
        $GLOBALS['user_info']['isSvUser'] = $supervisionAccountService->isSupervisionUser($GLOBALS['user_info']['id']);
        //资产中心余额
        $accountId = AccountService::getUserAccountId($GLOBALS['user_info']['id'],$GLOBALS['user_info']['user_purpose']);
        $balanceResult = AccountService::getAccountMoneyById($accountId);
        $GLOBALS['user_info']['svCashMoney'] = $balanceResult['money'];

        $lxTotalMoney = bcadd($GLOBALS['user_info']['money'], $bonus['money'], 2);

        //网贷标的显示网贷p2p账户余额，非网贷标的显示网信账户余额

        $totalMoney = bcadd($GLOBALS['user_info']['svCashMoney'], $bonus['money'], 2);


        //存管降级
        $isSvDown = SupervisionService::isServiceDown();
        $this->tpl->assign('isSvDown', $isSvDown);

        //是否显示开通快捷投资授权链接
        $this->tpl->assign('isShowQuickBidAuthLink', $this->isSvOpen && !$isSvDown && $GLOBALS['user_info']['isSvUser'] && !$isOwnBankAuth);


        $this->tpl->assign('total_money', $totalMoney);
        $this->tpl->assign('bank_money', $GLOBALS['user_info']['svCashMoney']);
        $this->tpl->assign('lc_money', $GLOBALS['user_info']['money']);

        $this->tpl->assign('o2oDiscountSwitch', $o2oDiscountSwitch);
        $this->tpl->assign("isZX", $isZX);
        $this->tpl->assign("isExchange", $isExchange);
        $this->tpl->assign("user_info", $GLOBALS['user_info']);
        $this->tpl->assign("money",$money);
        $this->tpl->assign("default_loan_fee_rate",app_conf('DEFAULT_LOAN_FEE_RATE'));
        $this->tpl->assign("default_manage_fee_rate",$default_manage_fee_rate);
        $this->tpl->assign("default_manage_fee_text",$default_manage_fee_text);
        $this->tpl->assign("needForceAssess", $needForceAssess);
        $this->tpl->assign("backurl", '//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        $this->tpl->assign("limitMoneyData", $limitMoneyData);
        $this->tpl->assign("totalLimitMoneyData", $totalLimitMoneyData);
        $this->tpl->assign("isRiskValid", $isRiskValid);
        $this->tpl->assign("user_risk_tips", $user_risk_tips);
        $this->tpl->assign("project_risk_info", $project_risk_info);

        //来源站点数据
        $fromSite = \es_session::get('from_site');
        $this->tpl->assign('from_site', \es_session::get('from_site'));

        //来源是农贷分站
        $fromSiteId = !empty($fromSite['id']) ? $fromSite['id'] : null;
        $this->tpl->assign('is_from_nongdan', is_nongdan_site($fromSiteId));


    }
}
