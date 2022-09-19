<?php
/**
 * Bid class file.
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/

namespace web\controllers\deal;

use libs\web\Form;
use libs\web\Url;
use web\controllers\BaseAction;
use app\models\service\LoanType;
use core\service\PaymentService;
use core\dao\DealLoanTypeModel;
use core\dao\DealModel;

use core\service\ContractPreService;
use core\dao\DealLoadModel;
use core\service\CouponService;
use core\service\UserService;
use core\service\DealTagService;
use core\service\UserCarryService;
use core\service\DealProjectService;
use core\service\UserTrackService;
use libs\utils\Aes;
use libs\payment\supervision\Supervision;
use core\service\vip\VipService;
use core\dao\EnterpriseModel;
use core\service\DealProjectRiskAssessmentService;

/**
 * 投资确认页
 *
 * @author 杨晓恒<yangxiaoheng@ucfgroup.com>
 **/
class Bid extends BaseAction {

    public function init() {
        //检测分站广告跳转
        $this->checkAdRedirect();
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

        if(deal_belong_current_site($id)){
            $deal = $this->rpc->local('DealService\getDeal', array($id, true));
        }else{
            $deal = null;
        }

        if (empty($deal)) {
            return app_redirect(url("Bid"));
        }

        //是否主站登录
        $userTrackService = new UserTrackService();
        $isFromWxlc = $userTrackService->isWxlcLogin($GLOBALS['user_info']['id']);
        $this->tpl->assign('isFromWxlc', $isFromWxlc);

        //P2P只允许投资户投资
        $isP2p = $this->rpc->local('DealService\isP2pPath', array($deal));
        if($isP2p){
            if(!$this->rpc->local('UserService\allowAccountLoan', array($GLOBALS['user_info']['user_purpose']))){
                return $this->show_error($GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID']);
            }
        }

        //强制风险评测
        $needForceAssess = 0;
        $limitMoneyData = array();
        if($GLOBALS['user_info']['idcardpassed'] == 1){
            $riskData = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array(intval($GLOBALS['user_info']['id'])));
            $needForceAssess = $riskData['needForceAssess'];
            $limitMoneyData = !empty($riskData['limitMoneyData']) ? $riskData['limitMoneyData'] : array();
        }


        if($deal['deal_status'] != 1){
            return $this->show_error($GLOBALS['lang']['DEAL_BID_FULL'],'',0,0,APP_ROOT."/");        //已满标
        }
        if ($deal['isDtb'] == 1) {
            return app_redirect(url("index"));
        }
        if($deal['deal_type'] != 0){
            $limitMoneyData = array();
        }

        if($GLOBALS['user_info']['idcardpassed'] == 3){
            $info = $this->rpc->local('UserPassportService\getPassport', array($GLOBALS['user_info']['id']));
            return $this->show_error('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站内信或者电子邮件等方式通知您。', "", 0);
        }
        // 限制投资
        $userCarryService = new UserCarryService();
        $user_money_limit = $userCarryService->canWithdrawAmount($GLOBALS['user_info']['id'], $money, $isP2p);
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

                $loan_money = ((int)$deal['min_loan_money'] < 1000) ? (int)$deal['min_loan_money'] : (int)$deal['min_loan'];
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

        /* if($deal_info['is_update'] == 1){
            return app_redirect(url("index"));
        } */

        //如果未绑定手机
        $user_id = $GLOBALS['user_info']['id'];
        $userService = new UserService($user_id);
        $opt = [];
        if ($this->is_firstp2p) {
            $opt = ['check_validate' => false];
        }
        $userCheck = $userService->isBindBankCard($opt);
        $siteId = \libs\utils\Site::getId();
        // 检查用户是否绑卡开户成功
        if ($userCheck['ret'] !== true)
        {
            if($userService->isEnterprise() && $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND)
            {
                return app_redirect(Url::gene('deal','promptCompany'));
            }

            $hasPassport = $this->rpc->local('AccountService\hasPassport', array($user_id));
            if ($siteId == 1 && (empty($hasPassport) || (!empty($hasPassport) && $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID)))
            {
                return $this->show_payment_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
            }

            return $this->show_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
        }

        // 存管标的，如果未激活，走存管激活
        if ($isP2p) {
            $hasUnactivatedTag = $this->rpc->local('UserTagService\getTagByConstNameUserId', array('SV_UNACTIVATED_USER', $user_id));
            if ($hasUnactivatedTag) {
                return $this->show_error('请先升级网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=register&return_url=%s', get_domain()), 3);
            }
        }

        if (app_conf('PAYMENT_ENABLE'))
        {
            //增加支付平台用户注册，
            if(empty($GLOBALS['user_info']['payment_user_id'])){
              //showErr('无法进行投保');
              return showErr('无法投标',0,'/account',0);
            }
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
        $user_service = new UserService($GLOBALS['user_info']['id']);

        if($user_service->isEnterprise()){
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
            $project_risk_ret = $this->rpc->local("DealProjectRiskAssessmentService\checkRiskBid", array(intval($deal['project_id']),intval($GLOBALS['user_info']['id']),false ));

            if ($project_risk_ret['result'] == false){
                $user_risk_tips = 1;
                $project_risk_info = array('remaining_assess_num' => $project_risk_ret['remaining_assess_num'],'user_risk_assessment' => $project_risk_ret['user_risk_assessment']);
            }
        }
        if($deal['bid_restrict'] == 1){

        }else if($deal['bid_restrict'] == 2){

        }

        //18岁以上投资限制
        $age_check = $this->rpc->local('DealService\allowedBidByCheckAge', array($GLOBALS['user_info']));
        if($age_check['error'] == true){
            return $this->show_error($age_check['msg'], "", 0, 1);
        }

        $deal_load_count = $this->rpc->local("DealLoadService\getCountByUserIdInSuccess", array($GLOBALS['user_info']['id']));

        //新手标
        if ($deal['deal_crowd'] == '1' && $deal_load_count > 0) {
            return $this->show_error($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '新手专享标为平台初次出借用户推荐的优惠项目，只有第一次出借的用户才可以投标' : '新手专享标为平台初次投资用户推荐的优惠项目，只有第一次投资的用户才可以投标');
        //特定用户组
        } elseif ($deal['deal_crowd'] == '2') {
            $group_check = $this->rpc->local("DealGroupService\checkUserDealGroup", array($deal['id'], $GLOBALS['user_info']['id']));
            if (!$group_check) {
                return $this->show_error($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '专享标为平台为特定用户推荐的优惠项目，只有特定用户才可以出借' :'专享标为平台为特定用户推荐的优惠项目，只有特定用户才可以投资');
            }
        }elseif($deal['deal_crowd'] == '16' && $GLOBALS['user_info']['id'] !=$deal->deal_specify_uid) {
            return $this->show_error($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? '该项目为专享标，只有特定用户才可出借' :'该项目为专享标，只有特定用户才可投资');
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
        } elseif ($deal['deal_crowd'] == DealModel::DEAL_CROWD_VIP ){
            //指定vip专享
            $vipService = new VipService();
            $vipInfo = $vipService->getVipInfo($GLOBALS['user_info']['id']);
            if (empty($vipInfo) || ($deal->deal_specify_uid > $vipInfo['service_grade'])) {
                $vipBidMsg = $vipService->getVipBidErrMsg($deal->deal_specify_uid);
                return $this->show_error($vipBidMsg);
            }
        }

        // 手机专享标
        $allowdBid = $this->rpc->local("DealService\allowedBidBySourceType", array(0, $deal['deal_crowd'] ,$GLOBALS['user_info']));
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
                // \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "fail", $res['msg'])));
                // return $res;
            }
        }

        // add by wangyiming 20140226
        //$company = get_deal_borrow_info($deal);
        $company = $this->rpc->local("DealService\getDealUserCompanyInfo", array($deal));
        $this->tpl->assign('company',$company);

        $deal_user = $this->rpc->local("UserService\getUserViaSlave", array($deal['user_id']));
        $this->tpl->assign('deal_user', $deal_user);
        $user = $this->rpc->local("UserService\getUserViaSlave", array($GLOBALS['user_info']['id']));
        $this->tpl->assign('user', $user);

        // 项目风险承受能力
        $project_risk = $this->rpc->local("DealProjectService\getProRisk", array($deal['project_id']));
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
        $bankcard_info = $this->rpc->local("UserBankcardService\getBankcard", array($GLOBALS['user_info']['id']));
        if(!$bankcard_info || $bankcard_info['status'] != 1){
            return $this->show_error('请先填写银行卡信息', "", 0,0,url("account/addbank"),3);
        }
        $bank = $this->rpc->local("BankService\getBank", array($bankcard_info['bank_id']));
        $this->tpl->assign("bank",$bank);

        //地区列表
        $region_lv1 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv1'])), 3600);
        $this->tpl->assign("region_lv1",$region_lv1['name']);
        $region_lv2 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv2'])), 3600);
        $this->tpl->assign("region_lv2",$region_lv2['name']);
        $region_lv3 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv3'])), 3600);
        $this->tpl->assign("region_lv3",$region_lv3['name']);
        $region_lv4 = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DeliveryRegionService\getRegion', array($bankcard_info['region_lv4'])), 3600);
        $this->tpl->assign("region_lv4",$region_lv4['name']);

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

        // 如果是融资租赁使用单独模板
        /* if (LoanType::getLoanTagByTypeId($deal['type_id']) == LoanType::TYPE_ZCZR) {
            $cont_service = new ContractPreService;
            if($cont_service->getAssetsContTpl($id)){
                $this->tpl->assign("show_buyback", 1);
            }
            $this->tpl->assign("is_lease", 1);
        } */

        //jira 1219 合同预签和回购通知书 逻辑的变更
        /*$type_tag = LoanType::getLoanTagByTypeId($deal['type_id']);
        $is_lease = 0;
        $show_buyback = 0;
        if ($type_tag == DealLoanTypeModel::TYPE_ZCZR || $type_tag == DealLoanTypeModel::TYPE_YSD) {
            $is_lease = 1;
            $cont_service = new ContractPreService;
            if($cont_service->getAssetsContTpl($id)){
                $show_buyback = 1;
            }
        }*/
        $isZX = $this->rpc->local('DealService\isDealEx', array($deal['deal_type']));
        $isExchange = $this->rpc->local('DealService\isDealExchange', array($deal['deal_type']));

        $contpre = $this->rpc->local("ContractPreService\getDealContPreTemplate", array($id));

        $this->tpl->assign("contpre", $contpre);
        // 判断是否为盈益
        $this->tpl->assign('is_yingyi', (DealModel::DEAL_TYPE_EXCLUSIVE == $deal['deal_type'] && strpos($deal['name'],'盈益') !== false));

        $this->tpl->assign('is_xiaodai', DealModel::DEAL_TYPE_PETTYLOAN == $deal['deal_type']);

        //$this->tpl->assign("is_lease", $is_lease);
        //$this->tpl->assign("show_buyback", $show_buyback);

        // 优惠券信息加载
        $turn_on_coupon = CouponService::isShowCoupon($id); // 是否显示优惠码
        $this->tpl->assign("turn_on_coupon", $turn_on_coupon);
        if ($turn_on_coupon) {
            $this->getCouponLatest();
        }

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($GLOBALS['user_info']['id']));
        $o2oDiscountSwitch = intval(get_config_db('O2O_DISCOUNT_SWITCH', $siteId));
        if ($deal['deal_type'] == 1 || $deal['isBxt'] == 1 || $deal['loantype'] == 7) {
            // 通知贷、变现通、公益标不展示
            $o2oDiscountSwitch = 0;
        }

        /****** 存管逻辑  ********/
        // 是否开通协议
        $supervisionAccountService = new \core\service\SupervisionAccountService();
        $supervisionAccountService->ignoreReqExc = true;//忽略请求异常
        $isOwnBankAuth = $supervisionAccountService->isQuickBidAuthorization($GLOBALS['user_info']['id']);
        $this->tpl->assign("isOwnBankAuth", $isOwnBankAuth);

        $user = $this->rpc->local("UserService\getUserViaSlave", array($GLOBALS['user_info']['id']));
        //$isSuperUser = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($user));
        //$moneyInfo = $this->rpc->local('UserService\getMoneyInfo',array($user,$money));

        $this->tpl->assign('bonusMoney', $bonus['money']);

        //$this->tpl->assign('isSuperUser',$isSuperUser);
        //$this->tpl->assign('lcMoney',$moneyInfo['lc']);
        //$this->tpl->assign('bonusMoney',$moneyInfo['bonus']);
        //$this->tpl->assign('bankMoney',$moneyInfo['bank']);


        //是否开户
        //$GLOBALS['user_info']['isSvUser'] = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($GLOBALS['user_info']['id']));
        //资产中心余额
        //$balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($GLOBALS['user_info']['id']));
        $accountInfo = (new \core\service\ncfph\AccountService())->getInfoByUserIdAndType($user['id'],$user['user_purpose']);
        $GLOBALS['user_info']['isSvUser'] = $accountInfo['isSupervisionUser'];

        $GLOBALS['user_info']['svCashMoney'] = $accountInfo['money'];

        $lxTotalMoney = bcadd($GLOBALS['user_info']['money'], $bonus['money'], 2);

        //网贷标的显示网贷p2p账户余额，非网贷标的显示网信账户余额
        if ($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL) {
            $totalMoney = bcadd($GLOBALS['user_info']['svCashMoney'], $bonus['money'], 2);
        } else {
            $totalMoney = $lxTotalMoney;
        }

        //存管降级
        $isSvDown = Supervision::isServiceDown();
        $this->tpl->assign('isSvDown', $isSvDown);

        //是否显示开通快捷投资授权链接
        $this->tpl->assign('isShowQuickBidAuthLink', $this->isSvOpen && !$isSvDown && $GLOBALS['user_info']['isSvUser'] && !$isOwnBankAuth);

        $bankcard = $this->rpc->local('AccountService\getUserBankInfo',array($GLOBALS['user_info']['id']));
        $this->tpl->assign('bankcard',$bankcard);

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
        $this->tpl->assign("p2p_api_url_is_ok", $GLOBALS['sys_config']['P2P_API_URL_iS_OK']);
        $this->tpl->assign("needForceAssess", $needForceAssess);
        $this->tpl->assign("backurl", '//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        $this->tpl->assign("limitMoneyData", $limitMoneyData);
        $this->tpl->assign("user_risk_tips", $user_risk_tips);
        $this->tpl->assign("project_risk_info", $project_risk_info);

        //来源站点数据
        $fromSite = \es_session::get('from_site');
        $this->tpl->assign('from_site', \es_session::get('from_site'));

        //来源是农贷分站
        $fromSiteId = !empty($fromSite['id']) ? $fromSite['id'] : null;
        $this->tpl->assign('is_from_nongdan', is_nongdan_site($fromSiteId));
        if ($this->is_firstp2p) {
            // 存管账户开户弹窗，显示[0:开通]还是[1:升级]
            $openSvButton = $this->rpc->local('SupervisionService\isUpgradeAccount', array($user_id));
            $this->tpl->assign('openSvButton', (int)$openSvButton);
            $this->template = 'web/views/v3/deal/bid_firstp2p.html';
        }
    }

    /**
     * 获取最近使用优惠码
     * 优先级：1.被绑定的优惠码；2.邀请链接的优惠码；3.最近一次使用的优惠码
     */
    protected function getCouponLatest() {
        if (!$this->is_wxlc && !$this->is_firstp2p && $this->appInfo['inviteCode']) { //分站
            $this->tpl->assign("siteCoupon", $this->appInfo['inviteCode']);
            $this->tpl->assign("showSiteCoupon", true);
            return true;
        }

        $this->tpl->assign("showSiteCoupon", false);
        \FP::import("libs.utils.logger");
        $consumeUserId = $GLOBALS['user_info']['id'];
        $couponLatest = $this->rpc->local('CouponService\getCouponLatest', array($consumeUserId));
        \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, __LINE__, $consumeUserId, 'getCouponLatest result',
                                           json_encode($couponLatest))));
        $coupon = $couponLatest['coupon'];
        $coupon['is_fixed'] = $couponLatest['is_fixed'];

        \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, __LINE__, $consumeUserId, 'result', json_encode($coupon))));
        $this->tpl->assign("coupon", $coupon);
    }
}
