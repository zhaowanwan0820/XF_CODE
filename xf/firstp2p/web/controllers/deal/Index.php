<?php
/**
 * Index.php
 *
 * @date 2014-03-19
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace web\controllers\deal;

use core\dao\UserModel;
use core\service\ncfph\DealService;
use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Aes;
use core\dao\DealModel;
use core\service\UserTrackService;
use core\service\UserService;

class Index extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'string'),
            'debug' => array('filter' => 'string'),
            'jg' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        if ($_REQUEST['ctl'] == 'deal') {//如果原先路由进入，跳转到首页
            return app_redirect(url("index"));
        }

        // jira 6158
        if(!$this->check_login()) return false;

        $ec_id = $this->form->data['id'];
        $id = Aes::decryptForDeal($ec_id);
        if(deal_belong_current_site($id)){
            if(app_conf('APP_SITE') == 'nongdan'){
                $deal = \core\service\ncfph\DealService::getDeal($id,true);
            }else{
                $deal = $this->rpc->local('DealService\getDeal', array($id, true));
            }
        }else{
            $deal = null;
        }
        //$deal = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDeal', array($id)), 3);
        $siteId = \libs\utils\Site::getId();
        if(empty($deal)){
            $deal =  \core\service\ncfph\DealService::getDeal($id,true);
        }
        if (empty($deal)) {
            return app_redirect(url("index"));
        } elseif (in_array($deal['deal_type'], array(DealModel::DEAL_TYPE_GENERAL, DealModel::DEAL_TYPE_COMPOUND)) && !$this->is_firstp2p){
            //记录来源站点，写到session
            $fromSite = [
                'id' => $siteId,
                'host' => $_SERVER['HTTP_HOST'],
            ];
            \es_session::set('from_site', $fromSite);
            $url = sprintf('//%s%s', app_conf('FIRSTP2P_CN_DOMAIN'), $_SERVER['REQUEST_URI']);
            return $this->redirectToP2P($url);
        }

        // JIRA#3006
        if ($deal['isBxt'] == 1) {
            if(!$this->check_login()) return false;
        }

        //从农担分站访问普惠标的详情页，返回农贷登陆
        $fromSite = \es_session::get('from_site');
        $fromSiteId = !empty($fromSite['id']) ? $fromSite['id'] : null;
        $fromSiteHost = !empty($fromSite['host']) ? $fromSite['host'] : null;
        if ( !empty($fromSiteId) && !empty($fromSiteHost) && is_nongdan_site($fromSiteId)
            && $this->is_firstp2p && empty($GLOBALS['user_info'])) {
                return $this->redirectToLogin($fromSiteHost);
        }

        //专享交易所逻辑
        if (in_array($deal['deal_type'], [DealModel::DEAL_TYPE_EXCLUSIVE, DealModel::DEAL_TYPE_EXCHANGE])) {
            //必须登录
            if(!$this->check_login()) return false;

            //验证实名绑卡
            $user_id = $GLOBALS['user_info']['id'];
            $userService = new UserService($user_id);
            $opts = ['check_validate' => false];
            $userCheck = $userService->isBindBankCard($opts);
            // 检查用户是否验卡成功
            if ($userCheck['ret'] !== true)
            {
                // 企业用户给提示
                if ($userService->isEnterprise() && ($userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND || $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID))
                {
                    return app_redirect(Url::gene('deal','promptCompany'));
                }

                //$hasPassport = \libs\db\Db::getInstance('firstp2p')->getOne("SELECT COUNT(*) FROM firstp2p_user_passport WHERE uid = '{$GLOBALS['user_info']['id']}'");
                $hasPassport = $this->rpc->local('AccountService\hasPassport', array($user_id));
                // 白名单中的分站 大陆用户和已绑卡未验证的港澳台用户跳转到先锋支付绑卡/验卡
                if (($siteId == 1 || \libs\web\Open::checkOpenSwitch()) && (empty($hasPassport) || (!empty($hasPassport) && $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID)))
                {
                    return $this->show_payment_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
                }
                return $this->show_error($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
            }
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
        $isDealZx = $this->rpc->local("DealService\isDealEx", array($deal['deal_type']));
        $this->tpl->assign("is_deal_zx", $isDealZx);

        $this->tpl->assign("deal", $deal);

        //借款人信息
        //$deal_user_info = $this->rpc->local('UserService\getUser', array($deal['user_id'], true, true));
        $deal_user_info = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('UserService\getUser', array($deal['user_id'])), 600);
        //$deal_user_info = $this->rpc->local('UserService\getExpire', array($deal_user_info)); //工作认证是否过期
        //$deal_user_info = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('UserService\getExpire', array($deal_user_info)), 600);

        // 出借人风险提示
        $riskWarning = ['person' => "经风险评估该项目您的出借金额上限为20万元", 'enterprise' => "经风险评估该项目您的出借金额上限为100万元"];
        $riskWarningReminder = $deal_user_info['user_type'] == UserModel::USER_TYPE_NORMAL ? $riskWarning['person'] : $riskWarning['enterprise'];
        $this->tpl->assign('riskWarningReminder', $riskWarningReminder);

        //机构名义贷款类型
        //$company = $this->rpc->local('DealService\getDealUserCompanyInfo', array($deal));
        $company = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealUserCompanyInfo', array(array('user_id' => $deal['user_id'], 'contract_tpl_type' => $deal['contract_tpl_type']))), 600);

        //查询项目简介
        if($deal['project_id']){
            //$project = $this->rpc->local('DealProjectService\getProInfo', array('id' => $deal['project_id']));
            $project = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealProjectService\getProInfo', array('id' => $deal['project_id'], 'deal_id' => $deal['id'])), 600);
        }
        // 项目风险承受能力
        $project_risk = isset($project['risk']) ?$project['risk'] : [];
        $project_risk['is_check_risk'] = $project_risk['needForceAssess'] = 0;
        if (!empty($GLOBALS['user_info'])) {
            $user_risk = $this->rpc->local('RiskAssessmentService\getUserRiskAssessmentData', array($GLOBALS['user_info']['id']));
            $project_risk['needForceAssess'] = $user_risk['needForceAssess'];
            $user_info = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('UserService\getUser', array($GLOBALS['user_info']['id'])), 600);
            if (!empty($user_info) && $user_info['is_enterprise_user'] == 0){
                // 检查项目风险承受和个人评估 (企业会员不受限制)
                $project_risk_ret = $this->rpc->local("DealProjectRiskAssessmentService\checkRiskBid", array(intval($deal['project_id']),$user_info['id'], true, $user_risk));
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
        //$load_list = $this->rpc->local('DealLoadService\getDealLoanListByDealId', array($deal['id']));
        $load_list = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealLoadService\getDealLoanListByDealId', array($deal['id'])), 30);
        $this->tpl->assign("load_list", $load_list);

        //还款计划
        if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
            //$deal_repay_list = $this->rpc->local('DealRepayService\getDealRepayListByDealId', array($deal['id']));
            $deal_repay_list = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealRepayService\getDealRepayListByDealId', array($deal['id'])), 10);
            $this->tpl->assign("deal_repay_list", $deal_repay_list);
        }

        $total_money = '0.00';
        $total_money_real = '0.00';//实际总金额
        //当前登录用户
        if ($GLOBALS['user_info']) {

            // 用户银行卡信息
            $bankcard = $this->rpc->local('AccountService\getUserBankInfo', array($GLOBALS['user_info']['id']));
            $this->tpl->assign('bankcard', $bankcard);
            //是否开户
            $GLOBALS['user_info']['isSvUser'] = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($GLOBALS['user_info']['id']));

            //资产中心余额
            $balanceResult = $this->rpc->local('UserThirdBalanceService\getUserSupervisionMoney', array($GLOBALS['user_info']['id']));
            $GLOBALS['user_info']['svCashMoney'] = $balanceResult['supervisionBalance'];

            //$bonus = $this->rpc->local('BonusService\get_useable_money', array($GLOBALS['user_info']['id']));
            $this->tpl->assign('bonus', $deal['bonus_money']);
            $total_money = bcadd($GLOBALS['user_info']['money'], $deal['bonus_money'], 2);
            $total_money = bcadd($total_money,$GLOBALS['user_info']['svCashMoney'], 2);
            $total_money_real = $total_money;

            //网贷标的显示网贷p2p账户余额，非网贷标的显示网信账户余额
            if ($deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL) {
                $total_money = bcadd($GLOBALS['user_info']['svCashMoney'], $bonus['money'], 2);
            } else {
                $total_money = bcadd($GLOBALS['user_info']['money'], $deal['bonus_money'], 2);
            }

            $this->tpl->assign('user_info', $GLOBALS['user_info']);

            //是否主站登录
            $userTrackService = new UserTrackService();
            $isFromWxlc = $userTrackService->isWxlcLogin($GLOBALS['user_info']['id']);
            $this->tpl->assign('isFromWxlc', $isFromWxlc);
        }
        // 存管账户开户弹窗，显示[0:开通]还是[1:升级]
        $userId = isset($GLOBALS['user_info']['id']) ? (int)$GLOBALS['user_info']['id'] : 0;
        $openSvButton = $this->rpc->local('SupervisionService\isUpgradeAccount', array($userId));
        $this->tpl->assign('openSvButton', (int)$openSvButton);

        $this->tpl->assign('total_money', $total_money);
        $this->tpl->assign('total_money_real', $total_money_real);

        // 判断是否为盈益
        $this->tpl->assign('is_yingyi', (DealModel::DEAL_TYPE_EXCLUSIVE == $deal['deal_type'] && strpos($deal['name'],'盈益') !== false));

        // 判断是否为小贷
        $this->tpl->assign('is_pettyloan', DealModel::DEAL_TYPE_PETTYLOAN == $deal['deal_type']);
        $cookie_inverst_value = isset($_COOKIE["invest_result"]) ? (int)$_COOKIE["invest_result"] : 0;
        $cookie_money_value   = isset($_COOKIE["investInput"]) ? (int)$_COOKIE["investInput"] : 0;
        setcookie('invest_result', '', time()-86400, '/');
        setcookie('investInput', '', time()-86400, '/');

        $this->tpl->assign('invest_value', $cookie_inverst_value);
        $this->tpl->assign('invest_money', $cookie_money_value);

        //认证信息
        $credit_file = get_user_credit_file($deal['user_id']);
        $this->tpl->assign("credit_file", $credit_file);

        //seo信息
        $seo = $this->getSeo($deal);
        $this->tpl->assign("page_title", $seo['seo_title']);
        $this->tpl->assign("page_keyword", $seo['seo_keyword'] . ",");

        //18岁以上投资限制
        $age_check = $this->rpc->local('DealService\allowedBidByCheckAge', array($GLOBALS['user_info']));
        $this->tpl->assign("age_check", $age_check['error'] ? 0 : 1);
        $this->tpl->assign("age_min", \core\dao\DealLoadModel::BID_AGE_MIN);

        if ($this->is_firstp2p) {
            $touziliebiaonav = array(msubstr($deal['old_name'], 0, 25));
            $result= $this->rpc->local('SupervisionAccountService\memberStandardRegisterPage', [$GLOBALS['user_info']['id']]);
            $this->tpl->assign('formString', $result['data']['form']);
            $this->tpl->assign('formId', $result['data']['formId']);
        } else {
            $touzilicainav = array("专享理财" => url("index", "touzi"), msubstr($deal['old_name'], 0, 25));
            $touziliebiaonav = array("投资列表" => url("index", "deals"), msubstr($deal['old_name'], 0, 25));
        }

        if (!empty($this->form->data["jg"])) {
            $AgencyInfo = json_decode(base64_decode($this->form->data["jg"]));
            $this->set_nav(array("信息披露" => url("index", "disclosure"), $AgencyInfo->name => "/disclosure/AgencyInfo?jg=" . base64_encode(json_encode(array("id" => $AgencyInfo->id))), msubstr($deal['old_name'], 0, 25)));

        } else {
            $this->set_nav($deal["isBxt"] == 1 ? $touzilicainav : $touziliebiaonav);
        }
        //来源站点数据
        $fromSite = \es_session::get('from_site');
        !isset($fromSite['host']) && $fromSite['host'] = '';
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
                $user = $GLOBALS['user_info'];
                if($user){
                    $have_bid = $this->rpc->local('DealLoadService\getUserDealLoad', array($user['id'], $deal_id));
                    if(!$have_bid){
                        return $this->show_error(($deal_type == DealModel::DEAL_TYPE_GENERAL ? '仅允许出借人查看。' :'仅允许投资人查看。'), "", 0, 0, url("index"));
                    }
                }else{
                    return $this->show_error('请登录后查看。', "", 0, 0, url("user/login"));
                }
            }
        }
        return true;
    }
}
