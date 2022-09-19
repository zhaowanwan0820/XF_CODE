<?php

/**
 * InvestList.php
 *
 * @date 2016-08-01
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use libs\web\Form;
use core\service\contract\ContractService;
use core\dao\deal\DealModel;
use core\service\user\UserService;

/**
 * 投资列表
 * 智多新-成交记录
 *
 * Class InvestList
 * @package api\controllers\duotou
 */
class InvestListData extends DuotouBaseAction {

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
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $userInfo = $this->user;

        $data = $this->form->data;
        $loanId = intval($data['deal_loan_id']);
        $projectId = intval($data['project_id']);
        $page = isset($data['page']) ? intval($data['page']) : 1;
        $pageSize =isset($data['page_size']) ? intval($data['page_size']) : 7;
        $ret = array();
        $request = array(
            'pageNum' => $page,
            'pageSize' => $pageSize,
            'loanId' => $loanId,
        );

        $response = $this->callByObject(array('NCFGroup\Duotou\Services\DealLoan','getDealLoanMapping',$request));
        if (!$response) {
            $this->setErr('ERR_SYSTEM_CALL_CUSTOMER');
        }

        $res = $response['data']['data'];
        $contractService = new ContractService();
        $list = array();
        foreach ($res as $item) {
            $dealInfo =  DealModel::instance()->find($item['p2p_deal_id']);
            $user = UserService::getUserById($dealInfo['user_id']);

            //智多鑫标的层面数据
            $dtDealInfo = array();
            $dtDealInfo['name'] = $dealInfo['name'];
            $dtDealInfo['project_id'] = $dealInfo['project_id'];
            $dtDealInfo['p2p_deal_id'] = $item['p2p_deal_id'];
            $dtDealInfo['money'] = number_format($item['remain_money'], 2);
            //$dtDealInfo['loanUsername'] = $GLOBALS['user_info']['real_name'];
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
                    $formatContract['contractType'] = 1;
                    $uniqueId = str_pad($contract['tableIndex'],2,0,STR_PAD_LEFT).str_pad($contract['id'],20,0,STR_PAD_LEFT);
                    $formatContract['contractNo'] = ContractService::createDtNumber($loanId, $uniqueId);
                } else {
                    $dealLoadId = $contract['p2p_load_id'];
                    $formatContract['contractType'] = 0;//借款
                    $formatContract['contractNo'] = ContractService::createDealNumber(
                        $contract['p2p_deal_id'],
                        13,
                        $userInfo['id'],
                        $dealLoadId
                    );
                }
                $contracts[] = $formatContract;
            }
            $dtDealInfo['contracts'] = $contracts;
            $list[] = $dtDealInfo;
        }

        $this->json_data = $list;
    }

}
