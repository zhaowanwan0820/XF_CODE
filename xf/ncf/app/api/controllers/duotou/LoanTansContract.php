<?php

/**
 * LoanTansContract.php
 *
 * @date 2016-08-01
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use core\service\contract\ContractService;
use libs\web\Form;
use core\service\contract\ContractPreService;
use core\service\contract\ContractInvokerService;
use core\service\duotou\DuotouService;

use core\enum\contract\ContractEnum;
use core\enum\contract\ContractServiceEnum;

/**
 * 债权转让/借款协议
 *
 *
 * Class LoanTansContract
 * @package api\controllers\duotou
 */
class LoanTansContract extends DuotouBaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                'filter' => 'required',
                'message' => 'token  is required',
            ),
            'number' => array(
                'filter' => 'required',
                "message" => "number is required"
            ),
            'ctype' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
            ),
            'title' => array(
                'filter' => 'string',
                'option' => array('optional' => true),
            ),
            'type' => array(
                'filter' => 'int',
                'option' => array('optional' => true),
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
        $type = (isset($data['type']) && intval($data['type']) == 0) ? intval($data['type']) : 1;
        $number = trim($data['number']);
        $ctype = intval($data['ctype']);

        $contractPre = new ContractPreService();
        //解析合同信息
        if ($ctype == 1) {
            // 顾问协议
            $data['title'] = empty($data['title']) ? '智多新协议' : $data['title'];
            $numberInfo = ContractService::getInfoFromDtConsultNumber($number);
            $dtDealId = intval($numberInfo['dtDealId']);
            $type = intval($numberInfo['type']);
            $contractType = intval($numberInfo['contractType']);
            $userId = intval($numberInfo['userId']);
            $dtLoanId = intval($numberInfo['dtLoanId']);

            if (($type === 1) && ($contractType === 10)) {
                // 通过 $dtLoanId  $user_info['id']  获取合同记录
                $contract = ContractService::getContractByLoadId($dtLoanId, 0, 0, ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT, false);
                $contractId = isset($contract[0]['id']) ? $contract[0]['id'] : 0;
                $dealId = isset($contract[0]['deal_id']) ? $contract[0]['deal_id'] : 0;
                $number = isset($contract[0]['number']) ? $contract[0]['number'] : $number;
                // contractId为空，则获取落库合同内容
                if (!empty($contractId)) {
                    $contractInfo = ContractInvokerService::getOneFetchedDtContract('dt', $contractId, $dtLoanId,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                    $result = isset($contractInfo['content']) ? $contractInfo['content'] : '';
                } else {
                    $vars = array(
                        'id' => $dtLoanId,
                    );
                    $response = $this->callByObject(array('NCFGroup\Duotou\Services\DealLoan', 'getDealLoanById', $vars));
                    if (!$response) {
                        return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
                    }
                    $money = $response['data']['money'];
                    $result = $contractPre->getDtbContractInvest($dtDealId, $userInfo['id'], $money, $number, $response['data']['createTime']);
                }
            }
        } else {
            if($type == 0){
                // 智多新-底层标的-借款合同
                $deal_load_id = intval(substr($number, -10));

                $contractRecord = ContractInvokerService::getLoanContractByDealLoadId('remoter', $deal_load_id);
                if($contractRecord){
                    $contractInfo = ContractInvokerService::getOneFetchedContract('viewer', $contractRecord['id'], $contractRecord['deal_id']);
                    $result = $contractInfo['content'];
                }
            } else{
                // 智多新-债权转让协议
                $numberInfo = ContractService::getInfoFromDtNumber($number);
                $loanId = $numberInfo['loanId'];
                $duotouLoanMappingContractId = intval($numberInfo['duotouLoanMappingContractId']);

                // 通过lmcId获取唯一一条多投记录
                $dealRequest = array( 'loanId' => $loanId, 'lmcId'=> $duotouLoanMappingContractId);
                $response = DuotouService::callByObject(array('service' => 'NCFGroup\Duotou\Services\LoanMappingContract', 'method' => 'getByLoanId', 'args' => $dealRequest));

                if(is_array($response['data']) && count($response['data'])>0){
                    $money = bcdiv($response['data']['money'], 100, 2);
                    $dtRecordId = $response['data']['id'];
                    $time = $response['data']['create_time'];
                    $userId = $response['data']['redemption_user_id'];
                    $redemptionLoanId = $response['data']['redemption_loan_id'];
                    $p2pDealId = $response['data']['p2p_deal_id'];
                    $projectId = $response['data']['project_id'];

                    // 通过 dealId 和number 查数据
                    $contract = ContractService::getContractByNumber($loanId,$number, ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                    $contractId =  isset($contract[0]['id']) ? $contract[0]['id'] : 0;

                    // contractId为空，则获取落库合同内容
                    if (!empty($contractId)) {
                        $contractInfo = ContractInvokerService::getOneFetchedDtContract('dt',$contractId,$loanId,ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT);
                        $result = isset($contractInfo['content']) ? $contractInfo['content'] : '';
                    } else {
                        $result = $contractPre->getDtbLoanTransfer($projectId,$userInfo['id'],$userId,$p2pDealId,$money,$number,$time,$dtRecordId,$loanId);
                    }
                }
            }
        }

        $this->json_data = array("title" => $data['title'], "result" => $result);
    }

}
