<?php

/**
 * DealLoadDetail.php
 *
 * @date 2016-08-01
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */

namespace api\controllers\duotou;

use api\controllers\DuotouBaseAction;
use app\models\service\Finance;
use libs\web\Form;
use libs\utils\Rpc;
use NCFGroup\Protos\Contract\RequestGetLoansContract;
use NCFGroup\Protos\Duotou\Enum\DealLoanEnum;


/**
 * 已投项目详情
 *
 * status（可选）：状态；默认为0；0-全部 1-投资中 2-可转让 3-转让中  4-已转让 5-已结清
 *
 * Class LoadList
 * @package api\controllers\duotou
 */
class DealLoadDetail extends DuotouBaseAction {

    const IS_H5 = true;

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
        $data = $this->form->data;
        // 智多鑫直接跳普惠 其他逻辑会在普惠进行
        $phWapUrl = app_conf('NCFPH_WAP_HOST').'/duotou/DealLoadDetail?deal_loan_id='.$data['deal_loan_id'].'&token='.$data['token'];
        return app_redirect($phWapUrl);
        // END

        if (!$this->dtInvoke())
            return false;
        $userInfo = $this->getUserByToken();
        if (!$userInfo) {
            return $this->assignError('ERR_GET_USER_FAIL');//获取oauth用户信息失败
        }
        $data = $this->form->data;
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();

        $rpc = new Rpc('duotouRpc');
        if(!$rpc){
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $vars = array(
                'id' => intval($data['deal_loan_id']),
        );

        $request->setVars($vars);
        $response = $rpc->go('NCFGroup\Duotou\Services\DealLoan','getDealLoanDetail',$request);
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
        $contractRpc = new Rpc('contractRpc');
        $requestContract = new RequestGetLoansContract;
        $requestContract->setType(1);
        $requestContract->setUserId($userId);
        $requestContract->setDealInfo($contractlist);
        $contractResponse = $contractRpc->go('NCFGroup\Contract\Services\Contract','getLoansContract',$requestContract);
        if(!$contractResponse) {
            return $this->assignError('ERR_SYSTEM_CALL_CUSTOMER');
        }
        $contract = array_pop($contractResponse->data[intval($data['deal_loan_id'])]);
        $contract['number'] = str_pad($contract['number'],32,0,STR_PAD_LEFT);

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
        $dealLoadDetail['transferPromptText'] = $dealLoadDetail['projectInfo']['transferPromptText'];
        $dealLoadDetail['openTime'] = $dealLoadDetail['dealLoan']['status'] == 2 ? '每日'.date('G:i',strtotime($dealLoadDetail['projectInfo']['redemptionStartTime'])).'-'.date('G:i',strtotime($dealLoadDetail['projectInfo']['redemptionEndTime'])).'开放转让' : '';
        $dealLoadDetail['quitTime'] = empty($dealLoadDetail['quitTime']) ? '-' : date('Y-m-d', $dealLoadDetail['quitTime']);
        if($dealLoadDetail['ownDay'] < $dealLoadDetail['projectInfo']['feeDays']){
            $this->tpl->assign('feeNum',number_format($dealLoadDetail['manageFee'],2));
        }else{
            $this->tpl->assign('fee','免费');
        }
        $siteId = \libs\utils\Site::getId();
        $activityInfo = $this->rpc->local('DtEntranceService\getEntranceInfo', array($dealLoadDetail['activityId'], $siteId));
        if($dealLoadDetail['activityId'] > 0) {
            $dealLoadDetail['activityInfo'] = $activityInfo;
        }

        $this->tpl->assign('contract',$contract);
        $this->tpl->assign('deal', $dealLoadDetail);
        $this->tpl->assign('token',$data['token']);
    }
    public function _after_invoke() {
        $this->afterInvoke();
        if($this->errno != 0){
            parent::_after_invoke();
        }
        $this->tpl->display($this->template);
    }
}
