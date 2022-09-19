<?php

/**
 * ApplyTrans.php
 *
 * @date 2016-08-01
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use libs\web\Form;
use core\service\DealCompoundService;
use core\service\UserService;

/**
 * 申请转让
 *
 *
 * Class ApplyTrans
 * @package api\controllers\duotou
 */
class ApplyTrans extends DuotouBaseAction {


    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array(
                    'filter' => 'required',
                    'message' => 'token is required',
            ),
            'deal_loan_id' => array(
                    'filter' => 'required',
                    "message" => "deal_loan_id is required",
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
        $dealLoanId = intval($data['deal_loan_id']);
        $response = $this->callByObject(array('NCFGroup\Duotou\Services\RedemptionApply','getRedeemTotalRemainMoney',array()));
        $redeemTotalRemainMoney = $response['data']['redeemTotalRemainMoney'];

        $request = array('id' => $dealLoanId);
        $response = $this->callByObject(array('NCFGroup\Duotou\Services\DealLoan','getDealLoanDetail',$request));
        if(!$response || !$redeemTotalRemainMoney) {
            $this->setErr('ERR_SYSTEM_CALL_CUSTOMER');
            return false;
        }
        $dealLoad = $response['data'];

        if($dealLoad['dealLoan']['userId'] != $userInfo['id']){
            $this->setErr('ERR_DEAL_NOT_EXIST','不能申请');
            return false;
        }

        $res = array();
        $res['money'] = format_price($dealLoad['dealLoan']['money'],false);//转让金额
        $res['name'] = $dealLoad['projectInfo']['name'];
        $expiryInterestArray = explode(',',$dealLoad['projectInfo']['expiryInterest']);
        $res['expiryInterestText'] = !empty($dealLoad['projectInfo']['expiryInterest']) ?  '每月'.implode('、', $expiryInterestArray).'日': '';//结息日

        $res['dealLoanId'] = $dealLoad['dealLoan']['id'];
        $res['manageFee'] = number_format($dealLoad['manageFee'],2);//转让服务费

        $res['norepayInterest'] = number_format($dealLoad['norepayInterest'],2);
//         $dcs = new DealCompoundService();
//         $isHoliday = $dcs->checkIsHoliday(date('Y-m-d'));
//         $res['isHoliday'] = $isHoliday ? 1 : 0;//节假日顺延

        $res['predictRedeemFinishTime'] = date("Y-m-d",strtotime("+1 day"));
        $res['redeemTotalRemainMoney'] = number_format($redeemTotalRemainMoney['data']['redeemTotalRemainMoney'],2);
        $res['token'] = $data['token'];
        //$res['minTransferDays'] = intval($dealLoad['projectInfo']['minTransferDays']);
        //$res['maxTransferDays'] = intval($dealLoad['projectInfo']['maxTransferDays']);
        $res['transferPromptText'] = $dealLoad['projectInfo']['transferPromptText'];

        $this->json_data = $res;
    }
}
