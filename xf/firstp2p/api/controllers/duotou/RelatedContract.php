<?php

/**
 * RelatedContract.php
 *
 * @date 2017-06-27
 * @author wangchuanlu
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use app\models\service\Finance;
use libs\web\Form;
use libs\utils\Rpc;
use core\service\ContractService;
use core\dao\UserModel;
use core\dao\DealModel;
use api\conf\Error;
use libs\utils\Logger;

/**
 * 底层标的相关合同
 *
 * Class RelatedContract
 * @package api\controllers\duotou
 */
class RelatedContract extends DuotouBaseAction {

    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'ERR_PARAMS_VERIFY_FAIL',
            ),
            'page' => array(
                'filter' => 'int',
                'option' => array('optional' => true)
            ),
            'page_size' => array(
                'filter' => 'int',
                'option' => array('optional' => true)
            ),
            'deal_loan_id' => array(
                'filter' => 'required',
                'message' => "deal_loan_id is required"
            ),
            'project_id' => array(
                'filter' => 'required',
                'message' => "project_id is required"
            ),
            'p2p_deal_id' => array(
                'filter' => 'required',
                'message' => "p2p_deal_id is required"
            ),
        );
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (!$this->dtInvoke())
            return false;
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }

        $data = $this->form->data;
        $loanId = intval($data['deal_loan_id']);
        $p2pDealId = intval($data['p2p_deal_id']);
        $projectId = intval($data['project_id']);
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $pageSize =isset($data['page_size']) ? intval($data['page_size']) : 7;
        $ret = array();
        $rpc = new Rpc('duotouRpc');
        if(!$rpc){
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();

        $vars = array(
            'pageNum' => $page,
            'pageSize' => $pageSize,
            'loanId' => $loanId,
            'p2pDealId' => $p2pDealId,
        );
        $request->setVars($vars);
        $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanMapping',$request);
        if (!$response || empty($response['data']['data'])) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $res = $response['data']['data'];
        $contractService = new ContractService();
        $item = array_pop($res);
        $dealInfo =  DealModel::instance()->find($item['p2p_deal_id']);
        $user = UserModel::instance()->find($dealInfo['user_id']);

        //智多鑫标的层面数据
        $dtDealInfo = array();
        $dtDealInfo['name'] = $dealInfo['name'];
        $dtDealInfo['p2p_deal_id'] = $dealInfo['p2p_deal_id'];
        $dtDealInfo['project_id'] = $dealInfo['project_id'];
        $dtDealInfo['money'] = number_format($item['money'], 2);
        $dtDealInfo['loanUsername'] = $GLOBALS['user_info']['real_name'];
        $dtDealInfo['borrowUsername'] = $user['real_name'];
        $dtDealInfo['loanTime'] = date('Y-m-d',$item['time']);
        $dtDealInfo['dealLoanId'] = $loanId;
        $dtDealInfo['token'] = $data['token'];
        $dtDealInfo['repayInterest'] = $item['repay_interest']; //已到账收益
        $dtDealInfo['noRepayInterest'] = $item['no_repay_interest']; //未到账收益

        //底层资产对应合同
        $contracts = array();
        foreach ($item['contracts'] as $contract) {
            $formatContract = array();
            $formatContract['money'] = $contract['money'];
            $formatContract['loanTime'] = date('Y-m-d H:i:s',$contract['time']);
            if($contract['redemption_user_id'] > 0) {//转让
                $userRedeem = UserModel::instance()->find($contract['redemption_user_id']);
                $formatContract['redeemUserName'] = $userRedeem['real_name'];
                $formatContract['contractType'] = 1;
                $contractType = 11;
                $formatContract['contractNo'] = $contractService->createDtDealNumber($contractType,$projectId,$contract['p2p_deal_id'],$contract['redemption_loan_id'],$loanId);//合同编号
            } else {
                $dealLoadId = $contract['p2p_load_id'];
                $formatContract['contractType'] = 0;//借款
                if($contract['p2p_deal_id'] >= 1000000){
                    $formatContract['contractNo'] = str_pad($contract['p2p_deal_id'],8,"0",STR_PAD_LEFT).'01'.str_pad(1,2,"0",STR_PAD_LEFT).str_pad($GLOBALS['user_info']['id'],8,"0",STR_PAD_LEFT).str_pad($dealLoadId,10,"0",STR_PAD_LEFT);
                }else{
                    $formatContract['contractNo'] = str_pad($contract['p2p_deal_id'],6,"0",STR_PAD_LEFT).'01'.str_pad(1,2,"0",STR_PAD_LEFT).str_pad($GLOBALS['user_info']['id'],8,"0",STR_PAD_LEFT).str_pad($dealLoadId,10,"0",STR_PAD_LEFT);
                }
            }
            $contracts[] = $formatContract;
        }
        $dtDealInfo['contracts'] = $contracts;
        
        Logger::info("RelatedContract: ".json_encode($dtDealInfo,JSON_UNESCAPED_UNICODE));

        $this->tpl->assign('dtDealInfo',json_encode($dtDealInfo,JSON_UNESCAPED_UNICODE));
    }

    public function _after_invoke() {
        $this->afterInvoke();
        if($this->errno != 0){
            parent::_after_invoke();
        }
        $this->tpl->display($this->template);
    }

}
