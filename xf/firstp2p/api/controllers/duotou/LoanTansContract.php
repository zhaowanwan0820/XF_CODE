<?php

/**
 * LoanTansContract.php
 *
 * @date 2016-08-01
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use libs\web\Form;
use libs\utils\Rpc;
use core\service\ContractService;
use core\service\ContractPreService;
use api\conf\Error;
use core\service\ContractNewService;

/**
 * 债权转让/借款协议
 *
 *
 * Class LoanTansContract
 * @package api\controllers\duotou
 */
class LoanTansContract extends DuotouBaseAction {

    const IS_H5 = true;

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
        if (!$this->dtInvoke())
            return false;
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            return $this->assignError('ERR_GET_USER_FAIL');//获取oauth用户信息失败
        }
        $data = $this->form->data;
        $type = (isset($data['type']) && intval($data['type']) == 0) ? intval($data['type']) : 1;
        $number = trim($data['number']);
        $ctype = intval($data['ctype']);
        $rpc = new Rpc('duotouRpc');
        if(!$rpc){
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }

        if($type <> 0){
            $number = str_pad($number, 32, 0, STR_PAD_LEFT);
        }

        $contractPre = new ContractPreService();
        //解析合同信息
        if ($ctype == 1) {
            $data['title'] = empty($data['title']) ? '智多新协议' : $data['title'];
            $dtDealId = intval(substr($number, 0, 8));
            $type = intval(substr($number, 8, 2));
            $contractType = intval(substr($number, 10, 2));
            $userId = intval(substr($number, 12, 10));
            $dtLoanId = intval(substr($number, 22, 10));

            if (($type === 1) && ($contractType === 10)) {
                $request = new \NCFGroup\Protos\Duotou\RequestCommon();
                $vars = array(
                    'id' => $dtLoanId,
                );
                $request->setVars($vars);
                $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan', 'getDealLoanById', $request);
                if (!$response) {
                    return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
                }
                $money = $response['data']['money'];
                $result = $contractPre->getDtbContractInvest($dtDealId, $userInfo['id'], $money, $number,$response['data']['createTime']);
            }
        } else {
            if($type == 0){
                if(strlen($number) < 30){
                    $p2pDealId = intval(substr($number, 0, 6));
                }else{
                    $p2pDealId = intval(substr($number, 0, 8));
                }

                $deal_load_id = intval(substr($number, -10));
                $contractRecord = $this->rpc->local('ContractInvokerService\getLoanContractByDealLoadId',array('remoter', $deal_load_id));
                if($contractRecord){
                    $contractInfo = $this->rpc->local("ContractInvokerService\getOneFetchedContract", array('viewer', $contractRecord['id'], $p2pDealId));
                    $result = $contractInfo['content'];
                }
            } else{
                $contractType = intval(substr($number,0,2));
                $dtDealId = intval(substr($number,2,6));
                $p2pDealId = intval(substr($number,8,7));
                $redemptionLoanId = intval(substr($number,15,8));
                $dtLoanId = intval(substr($number,23,10));

                $request = new \NCFGroup\Protos\Duotou\RequestCommon();

                $vars = array(
                    'id' => $redemptionLoanId,
                );
                $request->setVars($vars);
                $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanDetail',$request);

                $userId = $response['data']['dealLoan']['userId'];

                $vars = array(
                        'loanId' => $dtLoanId,
                );
                $request->setVars($vars);
                $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanMapping',$request);

                if (!$response) {
                    return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
                }
                if(is_array($response['data']['data']) && count($response['data']['data'])>0){
                    foreach($response['data']['data'] as $mapping){
                        foreach($mapping['contracts'] as $contract) {
                            if(($contract['redemption_user_id'] == $userId)&&($contract['p2p_deal_id'] == $p2pDealId)&&($contract['redemption_loan_id']==$redemptionLoanId)){
                                $money = $contract['money'];
                                $dtRecordId = $contract['id'];
                                $dtLoanId = $dtLoanId;
                                $time = $contract['create_time'];
                            }
                        }
                    }

                    $result = $contractPre->getDtbLoanTransfer($dtDealId,$userInfo['id'],$userId,$p2pDealId,$money,$number,$time,$dtRecordId,$dtLoanId);
                }
            }
        }

        $this->tpl->assign('title',$data['title']);
        $this->tpl->assign('token',$data['token']);
        $this->tpl->assign('result', $result);
    }
    public function _after_invoke() {
        $this->afterInvoke();
        if($this->errno != 0){
            parent::_after_invoke();
        }
        $this->tpl->display($this->template);
    }

}
