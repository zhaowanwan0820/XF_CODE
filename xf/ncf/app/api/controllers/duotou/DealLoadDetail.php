<?php

/**
 * DealLoadDetail.php
 *
 * @date 2016-08-01
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use libs\web\Form;
use core\service\contract\ContractService;
use core\service\duotou\DtEntranceService;
use core\enum\contract\ContractServiceEnum;
use core\enum\contract\ContractEnum;

/**
 * 已投项目详情
 *
 * status（可选）：状态；默认为0；0-全部 1-投资中 2-可转让 3-转让中  4-已转让 5-已结清
 *
 * Class LoadList
 * @package api\controllers\duotou
 */
class DealLoadDetail extends DuotouBaseAction {

    protected $redirectWapUrl = '/duotou/DealLoadDetail';

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                    'filter' => 'required',
                    'message' => 'token is required',
            ),
            "deal_loan_id" => array(
                    "filter" => "required",
                     "message" => "deal_loan_id is required"
            ),
        );
        if (!$this->form->validate()) {
            $this->assignError("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            $this->par_validate =false;
        }
    }

    public function invoke() {
        $userInfo = $this->user;
        $data = $this->form->data;
        $vars = array('id' => intval($data['deal_loan_id']),);
        $response = $this->callByObject(array('NCFGroup\Duotou\Services\DealLoan','getDealLoanDetail',$vars));
        if(!$response) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        if ($response['errCode'] !=0) {
            return $this->assignError('ERR_SYSTEM',$response['errMsg']);
        }

        //计算合同编号
        $userId = intval($userInfo['id']);
        $contractlist[] = array(
                'dealId' => intval($response['data']['projectInfo']['id']),
                'loanId' => intval($response['data']['dealLoan']['id']),
                'dealStatus' => intval($response['data']['dealLoan']['status']),
                'createTime' => intval($response['data']['dealLoan']['createTime']),
        );
        $contractResponse = ContractService::getLoansContract(ContractServiceEnum::TYPE_DT, $contractlist, $userId);

        if(!$contractResponse) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $contract = array_pop($contractResponse[intval($data['deal_loan_id'])]);
        $contract['number'] = str_pad($contract['number'],ContractEnum::LENGTH_DT_CONSULT_NUMBER,0,STR_PAD_LEFT);

        $res = array();
        $dealLoadDetail =  $response['data'];
        $dealLoadDetail['money'] = number_format($dealLoadDetail['dealLoan']['money'], 2);
        $dealLoadDetail['rateYear'] = number_format($dealLoadDetail['projectInfo']['rateYear'],2);
        $dealLoadDetail['rateYearBase'] = number_format($dealLoadDetail['projectInfo']['rateYearBase'],2);
        $dealLoadDetail['redemptionLockPeriod'] = $dealLoadDetail['projectInfo']['redemptionLockPeriod'];
        $dealLoadDetail['hasRepayInterest'] = number_format($dealLoadDetail['dealLoan']['hasRepayInterest'],2);
        $dealLoadDetail['norepayInterest'] = number_format($dealLoadDetail['norepayInterest'],2);
        $dealLoadDetail['feeRate'] = number_format($dealLoadDetail['projectInfo']['feeRate'],2);
        $dealLoadDetail['statusText'] = $this->status[$dealLoadDetail['dealLoan']['status']];
        $dealLoadDetail['activityId'] = $dealLoadDetail['dealLoan']['activityId'];
        $dealLoadDetail['ownDay'] = $dealLoadDetail['loadDays'];//在投天数
        $isOpen = $this->isOpen(strtotime($dealLoadDetail['projectInfo']['redemptionStartTime']), strtotime($dealLoadDetail['projectInfo']['redemptionEndTime']));
        $dealLoadDetail['isOpen'] = $isOpen ? 1 : 0;//是否在开放时间内
        //$dealLoadDetail['minTransferDays'] = intval($dealLoadDetail['projectInfo']['minTransferDays']);
        //$dealLoadDetail['maxTransferDays'] = intval($dealLoadDetail['projectInfo']['maxTransferDays']);
        $dealLoadDetail['transferPromptText'] = $dealLoadDetail['projectInfo']['transferPromptText'];

        $dealLoadDetail['openTime'] = $dealLoadDetail['dealLoan']['status'] == 2 ? '每日'.date('G:i',strtotime($dealLoadDetail['projectInfo']['redemptionStartTime'])).'-'.date('G:i',strtotime($dealLoadDetail['projectInfo']['redemptionEndTime'])).'开放转让' : '';
        $dealLoadDetail['quitTime'] = empty($dealLoadDetail['quitTime']) ? '-' : date('Y-m-d', $dealLoadDetail['quitTime']);
        if($dealLoadDetail['ownDay'] < $dealLoadDetail['projectInfo']['feeDays']){
            $res['feeNum'] = number_format($dealLoadDetail['manageFee'],2);
        }else{
            $res['fee'] = '免费';
        }

        $siteId = \libs\utils\Site::getId();
        $oDtEntranceService = new DtEntranceService();
        $activityInfo = $oDtEntranceService->getEntranceInfo($dealLoadDetail['activityId'], $siteId);
        if($dealLoadDetail['activityId'] > 0) {
            $dealLoadDetail['activityInfo'] = $activityInfo;
        }

        $res['contract'] = $contract;
        $res['deal'] = $dealLoadDetail;
        $this->json_data = $res;
    }
}
