<?php
/**
 * 多投宝披露底层资产
 * @author wangchuanlu@ucfgroup.com
 **/

namespace api\controllers\duotou;

use libs\web\Form;
use api\controllers\DuotouBaseAction;
use core\service\deal\DealService;
use core\service\user\UserService;
use core\service\dealload\DealLoadService;
use core\service\repay\DealRepayService;
use core\service\project\ProjectService;

class Publishptp extends DuotouBaseAction
{
    const TYPE_PUBLISH_DETAIL   = 1; //披露页详情
    const TYPE_LOAD_DETAIL      = 2; //投资页详情

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'deal_id' => array( //p2p标的id
                'filter' => 'required',
                'message' => 'deal_id is required',
            ),
            'type' => array( //获取类型
                'filter' => 'required',
                'message' => 'type is required',
            ),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke() {
        $user = $this->user;

        $dealId = intval($this->form->data['deal_id']);
        $type = intval($this->form->data['type']); //类型 根据类型返回不同的值

        $oDealService = new DealService();
        $deal = $oDealService->getDeal($dealId, true);
        if (empty($deal)) {
            return $this->setErr(-1, '披露信息不存在');
        }

        if ($deal['isDtb'] != 1) { //不是多投宝标的不让查看
            return $this->setErr(-1, '披露信息不存在');
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

        $deal['point_percent_show'] = bcmul(strval($deal['point_percent']),'100.00',2);
        $ret = array('deal' => $deal->getRow());
        //借款人信息
        $deal_user_info = \SiteApp::init()->dataCache->call('core\service\user\UserService', 'getUserById', array($deal['user_id']), 600);

        //机构名义贷款类型
        $company = \SiteApp::init()->dataCache->call($oDealService, 'getDealUserCompanyInfo', array(array('user_id' => $deal['user_id'], 'contract_tpl_type' => $deal['contract_tpl_type'])), 600);

        //查询项目简介
        if($deal['project_id']){
            $oProjectService = new ProjectService();
            $project = \SiteApp::init()->dataCache->call($oProjectService, 'getProInfo', array('id' => $deal['project_id'], 'deal_id' => $deal['id']), 600);
        }
        $ret['project_intro'] = isset($project['intro_html']) ? $project['intro_html'] : '';
        // 项目风险承受要求
        $ret['project_risk'] = isset($project['risk']) ? $project['risk'] : '';
        $ret['company'] = $company;
        $ret['deal_user_info'] = $deal_user_info;
        //贷后披露信息
        $ret['post_loan_message'] = isset($project['post_loan_message']) ? $project['post_loan_message'] : '';
        //借款列表
        $oDealLoadService = new DealLoadService();
        $ret['load_list'] = \SiteApp::init()->dataCache->call($oDealLoadService, 'getDealLoanListByDealId', array($deal['id']), 30);

        //回款计划
        //这应该没用到，测试时确认下,参数没有值，返回上万行记录
        //$ret['loan_repay_list'] = $this->rpc->local('DealLoanRepayService\getLoanRepayListByLoanId', array($load_id));

        //还款计划
        $deal_repay_list = array();
        if ($deal['deal_status'] == 4 || $deal['deal_status'] == 5) {
            $oDealRepayService = new DealRepayService();
            $deal_repay_list = \SiteApp::init()->dataCache->call($oDealRepayService, 'getDealRepayListByDealId', array($deal['id']), 10);
        }
        $ret['deal_repay_list'] = $deal_repay_list;

        //认证信息
        $credit_file = UserService::getUserCreditFile($deal['user_id']);
        $ret['credit_file'] = $credit_file;
        $ret['type'] = $type;
        $this->json_data = $ret;
    }

}
