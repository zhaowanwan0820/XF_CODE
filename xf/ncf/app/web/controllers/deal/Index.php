<?php
/**
 * Index.php
 *
 * @date 2014-03-19
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace web\controllers\deal;

use core\enum\AccountEnum;
use libs\web\Form;
use libs\utils\Logger;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\dao\deal\DealModel;
use core\service\UserTrackService;
use core\service\deal\DealService;
use core\service\user\UserService;
use core\dao\repay\DealRepayModel;
use core\enum\DealEnum;
use core\enum\UserEnum;
use core\enum\DealLoadEnum;
use core\enum\UserAccountEnum;
use core\service\risk\RiskAssessmentService;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\project\ProjectService;
use core\service\dealload\DealLoadService;
use core\service\supervision\SupervisionAccountService;
use core\service\account\AccountService;
use core\service\user\BankService;
use core\service\repay\DealRepayService;
use core\service\bonus\BonusService;

class Index extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'string'),
            'debug' => array('filter' => 'string'),
            'jg' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            //return app_redirect(url("index"));
        }
    }

    public function invoke() {

        if (isset($_REQUEST['clt']) && $_REQUEST['ctl'] == 'deal') {//如果原先路由进入，跳转到首页
            //return app_redirect(url("index"));
        }
        $ec_id = $this->form->data['id'];
        $id = Aes::decryptForDeal($ec_id);
        $dealService = new DealService();
        if(deal_belong_current_site($id)){
            $deal = $dealService->getDeal($id, true);
        }else{
            $deal = null;
        }
        //$deal = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDeal', array($id)), 3);
        $siteId = \libs\utils\Site::getId();
        if (empty($deal)) {
            return app_redirect(url("index"));
        }

        //从农担分站访问普惠标的详情页，返回农贷登陆
        $fromSite = \es_session::get('from_site');
        $fromSiteId = !empty($fromSite['id']) ? $fromSite['id'] : null;
        $fromSiteHost = !empty($fromSite['host']) ? $fromSite['host'] : null;
        if ( !empty($fromSiteId) && !empty($fromSiteHost) && is_nongdan_site($fromSiteId)
            && $this->is_firstp2p && empty($GLOBALS['user_info'])) {
                return $this->redirectToLogin($fromSiteHost);
        }


        if ($deal['isDtb'] == 1) {
            return app_redirect(url("index"));
        }

        $deal['ecid'] = Aes::encryptForDeal($deal['id']);


        //不允许其他用户产看的标;
        $this->isView($id, $deal['deal_status'], $this->form->data['debug'], $deal['deal_type']);
        $deal['show_name'] = msubstr($deal['old_name'], 0, 25);
        $deal['show_tips'] = get_wordnum($deal['old_name']) > 25 ? 1 : 0;

        //状态为投资中或者状态已经还清但是在上线该规则之后还清的显示提示信息
        if($deal['deal_type']==0 && ($deal['deal_status']==4 ||($deal['deal_status']==5 &&($deal['last_repay_time']+28800-strtotime('2017-03-09'))>0 ))){
            $fankuan_days = floor((time() - $deal['repay_start_time']-28800) / 86400)+1;
            if($fankuan_days>7){
                $deal['p2p_show']=1;
                if($deal['borrow_amount']>10000){
                    $deal['p2p_show_detail']='借款人已按照既定的资金用途使用资金。';
                }else{
                    $deal['p2p_show_detail']='该项目金额低于1万元（含），不对资金用途进行复核。';
                }
            }
        }
        // JIRA#5410
        $this->tpl->assign("is_deal_zx", false);

        $this->tpl->assign("deal", $deal);

        //借款人信息
        $deal_user_info = \SiteApp::init()->dataCache->call((new UserService()), 'getUserById', array($deal['user_id']), 600);

        //$deal_user_info = $this->rpc->local('UserService\getExpire', array($deal_user_info)); //工作认证是否过期
        //$deal_user_info = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('UserService\getExpire', array($deal_user_info)), 600);

        // 出借人风险提示
        $riskWarning = ['person' => "经风险评估该项目您的出借金额上限为20万元", 'enterprise' => "经风险评估该项目您的出借金额上限为100万元"];
        $riskWarningReminder = $deal_user_info['user_type'] == UserEnum::USER_TYPE_NORMAL ? $riskWarning['person'] : $riskWarning['enterprise'];
        $this->tpl->assign('riskWarningReminder', $riskWarningReminder);

        //机构名义贷款类型
        $company_deal = !empty($deal) ? $deal->getRow() : array();
        $company = \SiteApp::init()->dataCache->call($dealService, 'getDealUserCompanyInfo', array($company_deal), 600);

        //查询项目简介
        if($deal['project_id']){
            $projectService = new ProjectService();
            $project = \SiteApp::init()->dataCache->call($projectService,  'getProInfo', array('id' => $deal['project_id'], 'deal_id' => $deal['id']), 600);
        }
        // 项目风险承受能力
        $project_risk = isset($project['risk']) ?$project['risk'] : [];
        $project_risk['is_check_risk'] = $project_risk['needForceAssess'] = 0;
        if (!empty($GLOBALS['user_info'])) {
            $RiskAssessmentService = new RiskAssessmentService();
            $user_risk = $RiskAssessmentService->getUserRiskAssessmentData($GLOBALS['user_info']['id']);
            $project_risk['needForceAssess'] = $user_risk['needForceAssess'];
            $user_info = UserService::getUserById($GLOBALS['user_info']['id']);
            if (!empty($user_info) && $user_info['is_enterprise_user'] == 0){
                // 检查项目风险承受和个人评估 (企业会员不受限制)
                $DealProjectRiskAssessmentService = new DealProjectRiskAssessmentService();
                $project_risk_ret = $DealProjectRiskAssessmentService->checkRiskBid($deal['project_id'],$user_info['id'], true, $user_risk);
                if ($project_risk_ret['result'] == false){
                    $project_risk['is_check_risk'] = 1;
                    $project_risk['remaining_assess_num'] = $project_risk_ret['remaining_assess_num'];
                    $project_risk['user_risk_assessment'] = $project_risk_ret['user_risk_assessment'];
                }
            }
        }
        // 项目风险承受要求
        $this->tpl->assign('project_risk', $project_risk);

        $this->tpl->assign('project_intro', isset($project['intro_html']) ? $project['intro_html'] : '');
        // 贷后信息披露 网贷才有该字段
        $this->tpl->assign('post_loan_message', $project['post_loan_message']);

        $this->tpl->assign('company', $company);
        $this->tpl->assign("deal_user_info", $deal_user_info);
        $this->tpl->assign("backurl", '//'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

        //借款列表
        $dealloadServcie = new DealLoadService();
        $load_list = \SiteApp::init()->dataCache->call($dealloadServcie, 'getDealLoanListByDealId', array($deal['id']), 30);
        $this->tpl->assign("load_list", $load_list);

        //还款计划
        if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
            $DealRepayService = new DealRepayService();
            $deal_repay_list = \SiteApp::init()->dataCache->call($DealRepayService,'getDealRepayListByDealId', array($deal['id']), 10);
            $this->tpl->assign("deal_repay_list", $deal_repay_list);
        }

        $total_money = '0.00';
        $total_money_real = '0.00';//实际总金额
        $isCanUseBonus = DealEnum::CAN_USE_BONUS;
        //当前登录用户
        if (isset($GLOBALS['user_info']) && $GLOBALS['user_info']) {

            // 是否使用红包
            if (isset($GLOBALS['user_info']['canUseBonus']) && $GLOBALS['user_info']['canUseBonus']!= DealEnum::CAN_USE_BONUS){
                $deal['bonus_money'] = 0;
                $isCanUseBonus = 0;
                Logger::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$GLOBALS['user_info']['canUseBonus']);
            }

            // 红包使用总开关
            $isBonusEnable = BonusService::isBonusEnable();
            if (empty($isBonusEnable)){
                Logger::info(__CLASS__.' | '.__FUNCTION__.' | '.__LINE__.' canUseBonus '.$isBonusEnable);
                $deal['bonus_money'] = 0;
                $isCanUseBonus = $GLOBALS['user_info']['canUseBonus'];
            }
            $accountId = AccountService::getUserAccountId($GLOBALS['user_info']['id'],$GLOBALS['user_info']['user_purpose']);
            // 用户银行卡信息
            $bankcard = BankService::getNewCardByUserId($GLOBALS['user_info']['id']);
            $this->tpl->assign('bankcard', $bankcard);
            //是否开户
            $SupervisionAccountService = new SupervisionAccountService();
            $GLOBALS['user_info']['isSvUser'] = $SupervisionAccountService->isSupervisionUser($GLOBALS['user_info']['id']);

            //资产中心余额
            $balanceResult = AccountService::getAccountMoneyById($accountId);
            $GLOBALS['user_info']['svCashMoney'] = $balanceResult['money'];

            $this->tpl->assign('bonus', $deal['bonus_money']);
            //网贷标的显示网贷p2p账户余额，非网贷标的显示网信账户余额
            $total_money = bcadd($deal['bonus_money'],$GLOBALS['user_info']['svCashMoney'], 2);
            $total_money_real = $total_money;
            $this->tpl->assign('user_info', $GLOBALS['user_info']);

            //是否主站登录
            $this->tpl->assign('isFromWxlc', false);
        }
        $this->tpl->assign('canUseBonus',$isCanUseBonus);
        // 存管账户开户弹窗，显示[0:开通]还是[1:升级]
        $userId = isset($GLOBALS['user_info']['id']) ? (int)$GLOBALS['user_info']['id'] : 0;


        $this->tpl->assign('total_money', $total_money);
        $this->tpl->assign('total_money_real', $total_money_real);

        // 判断是否为盈益
        $this->tpl->assign('is_yingyi', false);

        // 判断是否为小贷
        $this->tpl->assign('is_pettyloan', DealEnum::DEAL_TYPE_PETTYLOAN == $deal['deal_type']);
        $cookie_inverst_value = isset($_COOKIE["invest_result"]) ? (int)$_COOKIE["invest_result"] : 0;
        $cookie_money_value   = isset($_COOKIE["investInput"]) ? (int)$_COOKIE["investInput"] : 0;
        setcookie('invest_result', '', time()-86400, '/');
        setcookie('investInput', '', time()-86400, '/');

        $this->tpl->assign('invest_value', $cookie_inverst_value);
        $this->tpl->assign('invest_money', $cookie_money_value);


        // sunxuefeng@ucfgroup.com 认证信息
        $credit_file = UserService::getUserCreditFile($deal['user_id']);
        $this->tpl->assign("credit_file", $credit_file);
        //seo信息
        $seo = $this->getSeo($deal);
        $this->tpl->assign("page_title", $seo['seo_title']);
        $this->tpl->assign("page_keyword", $seo['seo_keyword'] . ",");

        $GLOBALS['user_info'] = isset($GLOBALS['user_info']) ? $GLOBALS['user_info'] : array();
        //18岁以上投资限制
        $age_check = $dealService->allowedBidByCheckAge($GLOBALS['user_info']);

        $this->tpl->assign("age_check", $age_check['error'] ? 0 : 1);
        $this->tpl->assign("age_min", DealLoadEnum::BID_AGE_MIN);


        $touziliebiaonav = array(msubstr($deal['old_name'], 0, 25));
        $SupervisionAccountService = new SupervisionAccountService();
        $GLOBALS['user_info']['id'] = isset($GLOBALS['user_info']['id']) ? $GLOBALS['user_info']['id'] :  0;
        $result = $SupervisionAccountService->memberStandardRegisterPage($GLOBALS['user_info']['id']);
        $this->tpl->assign('formString', $result['data']['form']);
        $this->tpl->assign('formId', $result['data']['formId']);


        if (!empty($this->form->data["jg"])) {
            $AgencyInfo = json_decode(base64_decode($this->form->data["jg"]));
            $this->set_nav(array("信息披露" => url("index", "disclosure"), $AgencyInfo->name => "/disclosure/AgencyInfo?jg=" . base64_encode(json_encode(array("id" => $AgencyInfo->id))), msubstr($deal['old_name'], 0, 25)));

        } else {
            $this->set_nav($touziliebiaonav);
        }
        //来源站点数据
        $fromSite = \es_session::get('from_site');
        $this->tpl->assign('from_site', \es_session::get('from_site'));

        //来源是农贷分站
        $fromSiteId = !empty($fromSite['id']) ? $fromSite['id'] : null;
        $this->tpl->assign('is_from_nongdan', is_nongdan_site($fromSiteId));
    }

    /**
     * 获取seo信息
     *
     * @param $deal
     * @return array
     */
    private function getSeo($deal) {
        $seo = array();
        if ($deal['type_match_row']) {
            $seo['seo_title'] = $deal['seo_title'] != '' ? $deal['seo_title'] : $deal['type_match_row'] . " - " . $deal['name'];
        } else {
            $seo['seo_title'] = $deal['seo_title'] != '' ? $deal['seo_title'] : $deal['name'];
        }
        $seo['seo_keyword'] = $deal['seo_keyword'] != '' ? $deal['seo_keyword'] : $deal['type_match_row'] . "," . $deal['name'];
        return $seo;
    }

    /**
     * 该标详情页是否可以查看
     *
     * @param $deal_id
     * @param $deal_status
     * @return boolean
     */
    protected function isView($deal_id, $deal_status, $debug, $deal_type){

        //状态为2,3,4的标直接跳转首页（add by wangyiming 20140701）
        if (app_conf("SWITCH_DEAL_INFO_DISPLAY") == 1) {
            if ( in_array($deal_status, array(2,3,4)) ) {
                return app_redirect(url("index"));
            }
        }

        if (!in_array($deal_status, array(0, 1)) ) {
            $code = app_conf("DEAL_DETAIL_VIEW_CODE");
            $debug = empty($debug) ? '' : rtrim(urldecode($debug), '?');
            if(empty($code) || $debug != $code){
                $user = isset($GLOBALS['user_info']) ? $GLOBALS['user_info'] : false;
                if($user){
                    $dealloadservice = new DealLoadService();
                    $have_bid = $dealloadservice->getUserDealLoad($user['id'], $deal_id);
                    if(!$have_bid){
                        return $this->show_error('仅允许出借人查看。', "", 0, 0, url("index"));
                    }
                }else{
                    return $this->show_error('请登录后查看。', "", 0, 0, url("user/login"));
                }
            }
        }
        return true;
    }
}
