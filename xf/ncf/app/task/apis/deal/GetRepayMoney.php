<?php

namespace task\apis\deal;

use core\dao\deal\DealModel;
use core\dao\repay\DealLoanRepayModel;
use core\enum\DealEnum;
use core\enum\DealExtEnum;
use libs\web\Form;
use libs\utils\Page;
use libs\utils\Logger;
use core\service\deal\DealService;
use task\lib\ApiAction;

class GetRepayMoney extends ApiAction
{

    public function invoke()
    {
        $params = $this->getParam();
        Logger::info(implode(' | ', array(__FILE__, __LINE__, 'params:' . json_encode($params))));

        $loanUserId = intval($params['userId']);
        $dealId =  $params['dealId'];
        $dealRepayId = $params['dealRepayId'];
        $dealLoanRepayType = $params['dealRepayType'];


        if (intval($loanUserId)<=0) {
            throw new \Exception('用户ID参数不正确！');
        }

        if (intval($dealId)<=0) {
            throw new \Exception('标的ID参数不正确！');
        }
        $deal = \core\dao\deal\DealModel::instance()->find($dealId);
        if(!$deal){
            throw new \Exception('标的信息不存在！');
        }

        $repayType = ($dealLoanRepayType == 3) ? 3 : 1;
        if($dealLoanRepayType == 3 && $deal['is_during_repay'] == DealEnum::DEAL_DURING_REPAY){
            // 提前还款需要验证是否完成还款
            $this->errorCode = 10001;
            $this->errorMsg = '标的未完成还款';
        }else{
            $loanInfo['money'] = (new DealLoanRepayModel())->getSumMoneyOfUserByDealIdRepayId($dealId,$loanUserId,$dealRepayId,$repayType);
            $loanInfo['money'] = bcmul($loanInfo['money'], 100);
            $dealInfo['deal_type'] = 0;
            $dealInfo['report_status'] = 1;

            $data['loanInfo'] = $loanInfo;
            $data['dealInfo'] = $dealInfo;
        }
        $this->json_data = $data;
    }
}
