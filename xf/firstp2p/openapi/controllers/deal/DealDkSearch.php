<?php

namespace openapi\controllers\deal;

use core\dao\UserBankcardModel;
use core\service\DealDkService;
use core\service\P2pDealRepayService;
use libs\web\Form;
use libs\utils\Logger;
use openapi\conf\Error;
use openapi\controllers\BaseAction;


/**
 * 代扣结果查询
 * Class DealDkSearch
 * @package openapi\controllers\deal
 */
class DealDkSearch extends BaseAction {

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'deal_id' => ['filter' => 'required', "message" => "deal_id is error"],
            'repay_id' => ['filter' => 'required', "message" => "repay_id is error"],
            'approve_number' => ['filter' => 'string', 'option' => array('optional' => true)],
            'outer_order_id' => ['filter' => 'string', 'option' => array('optional' => true)],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data = $this->form->data;
        $dealId = intval($data['deal_id']);
        $repayId = intval($data['repay_id']);
        $approveNumber = isset($data['approve_number'])?$data['approve_number']:'';
        $outerOrderId = isset($data['outer_order_id'])?$data['outer_order_id']:'';
        $orderId = '';

        if(!empty($outerOrderId)){
            $outOrderInfo =  $this->rpc->local('ThirdpartyDkService\getThirdPartyByOutOrderId', array($outerOrderId,$data['client_id']));
            //外部订单不属于此笔还款
            if(!empty($outOrderInfo)){
                if(($outOrderInfo['deal_id'] != $dealId)||($outOrderInfo['repay_id'] != $repayId)){
                    $this->setErr("ERR_OUTER_ID_USED");
                    return false;
                }
            }
            if(!empty($outOrderInfo['order_id'])){
                $orderId = $outOrderInfo['order_id'];
                $outOrderParams = json_decode($outOrderInfo['params'],true);
                $dkAccount = formatBankcard($outOrderParams['bankCardNo']);
            }else{
                $this->setErr("ERR_OUTER_ID");
                return false;
            }
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"代扣还款方式查询","deal_id:{$dealId},repay_id:{$repayId},approveNumber:{$approveNumber}")));
        try{
            $dkService = new \core\service\DealDkService();
            $result = $dkService->getDkResult($dealId,$repayId,$approveNumber,$orderId);
            //如果是批扣，则获取用户绑定银行卡
            if(empty($outerOrderId)){
                $bankcardInfo = $this->rpc->local('AccountService\getUserBankInfo',array($result['user_id']));
                if(empty($bankcardInfo['bankcard'])){
                    $this->setErr("ERR_USER_BANKCARD_FAIL");
                    return false;
                }
                $dkAccount = formatBankcard($bankcardInfo['bankcard']);
            }
        }catch (\Exception $ex){
            if($ex->getCode() == DealDkService::ERR_DEAL_REPAY_SELF){
                //主动代扣和批扣，如果都使用网贷账户还款，并且已经成功，则返回已完成
                $status = DealDkService::DK_STATUS_SUCC;
                $this->json_data = array(
                    'status' => $status,
                    'err_msg' =>  '',
                    'repay_account' => '存管账户',
                );
                return true;
            }
            if(!empty($outerOrderId) && ($ex->getCode() == DealDkService::ERR_CODE_NORESULT)){
                //如果是第三方订单,并且已落第三方订单库,但代扣没有落库的情况下,返回代扣中的状态
                $status = DealDkService::DK_STATUS_DOING;
                $this->json_data = array(
                    'status' => $status,
                    'err_msg' =>  '',
                    'repay_account' => !empty($dkAccount) ? $dkAccount : '存管账户',
                );
                return true;
            }
            $this->errorCode = $ex->getCode();
            $this->errorMsg = $ex->getMessage();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"代扣还款方式查询 errMsg:".$ex->getMessage(),"deal_id:{$dealId},repay_id:{$repayId},approveNumber:{$approveNumber}")));
            return false;
        }
        $resultParams = json_decode(stripslashes($result['params']), true);
        $this->json_data = array(
            'status' => $result['dk_status'],
            'err_msg' => ($result['dk_status'] == DealDkService::DK_STATUS_FAIL) ? $resultParams['errMsg'] : '',
            'repay_account' => !empty($dkAccount) ? $dkAccount : '存管账户',
        );
        return true;
    }

}
