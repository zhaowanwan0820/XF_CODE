<?php

/**
 * 多投宝披露底层资产
 * Publishptp.php
 * @author wangchuanlu@ucfgroup.com
 */
namespace web\controllers\finplan;

use libs\web\Form;
use web\controllers\BaseAction;

class Publishptp extends BaseAction {

    const TYPE_PUBLISH_DETAIL   = 1; //披露页详情
    const TYPE_LOAD_DETAIL      = 2; //投资页详情

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'deal_id' => array('filter' => 'string'), //p2pdealId
            'type' => array('filter' => 'string'), //类型
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        //强调网信普惠
        if(!$this->is_firstp2p){
            header(sprintf('location://%s%s', app_conf('FIRSTP2P_CN_DOMAIN'), $_SERVER['REQUEST_URI']));
            exit;
        }

        $dealId = intval($this->form->data['deal_id']);
        $type = intval($this->form->data['type']); //类型 根据类型返回不同的值

        $deal = $this->rpc->local('DealService\getDeal', array($dealId, true));
        if (empty($deal)) {
            return app_redirect(url("index"));
        }

        if ($deal['isDtb'] != 1) { //不是多投宝标的不让查看
            return app_redirect(url("index"));
        }

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

        $dealInfo = $deal->getRow();
        $dealInfo['point_percent_show'] = bcmul(strval($deal['point_percent']),'100.00',2);
        $dealInfo['loantype_show'] = $GLOBALS['dict']['LOAN_TYPE'][$dealInfo['loantype']];
        $this->tpl->assign("deal", $dealInfo);

        //借款人信息
        $deal_user_info = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('UserService\getUser', array($deal['user_id'])), 600);

        //机构名义贷款类型
        $company = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealUserCompanyInfo', array(array('user_id' => $deal['user_id'], 'contract_tpl_type' => $deal['contract_tpl_type']))), 600);

        //查询项目简介
        if($deal['project_id']){
            $project = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealProjectService\getProInfo', array('id' => $deal['project_id'], 'deal_id' => $deal['id'])), 600);
        }
        $this->tpl->assign('project_intro', isset($project['intro_html']) ? $project['intro_html'] : '');
        // 项目风险承受要求
        $this->tpl->assign('project_risk', isset($project['risk']) ? $project['risk'] : '');
        $this->tpl->assign('company', $company);
        $this->tpl->assign("deal_user_info", $deal_user_info);

        //贷后披露信息
        $this->tpl->assign('post_loan_message', isset($project['post_loan_message']) ? $project['post_loan_message'] : '');

        //借款列表
        $load_list = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealLoadService\getDealLoanListByDealId', array($deal['id'])), 30);
        $this->tpl->assign("load_list", $load_list);

        //回款计划
        $loan_repay_list = $this->rpc->local('DealLoanRepayService\getLoanRepayListByLoanId', array($load_id));
        $this->tpl->assign("loan_repay_list", $loan_repay_list);

        //还款计划
        $deal_repay_list = array();
        if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
            $deal_repay_list = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealRepayService\getDealRepayListByDealId', array($deal['id'])), 10);
        }
        $this->tpl->assign("deal_repay_list", $deal_repay_list);


        //认证信息
        $credit_file = get_user_credit_file($deal['user_id']);
        $this->tpl->assign("credit_file", $credit_file);
        $this->tpl->assign("type", $type);

        if ($this->is_firstp2p) {
            $touziliebiaonav = array(msubstr($deal['old_name'], 0, 25));
        } else {
            $touzilicainav = array("专享理财" => url("index", "touzi"), msubstr($deal['old_name'], 0, 25));
            $touziliebiaonav = array("投资列表" => url("index", "deals"), msubstr($deal['old_name'], 0, 25));
        }

    }
}
