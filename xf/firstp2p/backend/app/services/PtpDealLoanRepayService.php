<?php

namespace NCFGroup\Ptp\services;

use core\dao\DealLoanRepayModel;
use NCFGroup\Common\Extensions\Base\ServiceBase;
use NCFGroup\Protos\Ptp\RPCErrorCode;
use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use \Assert\Assertion as Assert;
use NCFGroup\Protos\Ptp\ProtoUser;
use NCFGroup\Protos\Ptp\RequestRepayPlan;
use core\service\DealLoanRepayService;
use NCFGroup\Common\Extensions\Base\SimpleRequestBase;
use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * PtpDealLoanRepayService
 * coupon相关service
 * @uses ServiceBase
 * @package default
 */
class PtpDealLoanRepayService extends ServiceBase {

    /**
     * 根据userId,时间获取用户的回款计划
     * @param \NCFGroup\Protos\Ptp\RequestRepayPlan $request
     * @return \NCFGroup\Protos\Ptp\ResponseRepayPlan
     */
    public function getRepayList(RequestRepayPlan $request) {
        $userId = $request->getUserId();
        $beginTime = $request->getBeginTime();
        $endTime = $request->getEndTime();
        $offset = $request->getOffset();
        $count = $request->getCount();
        $type = $request->getType();
        $result = (new DealLoanRepayService())->getRepayList($userId, $beginTime, $endTime, array($offset, $count), 'newapi', NULL, $type);
        return $result;
    }

    public function getRepayMoney(SimpleRequestBase $request){
        $loanUserId = $request->getParam('userId');
        if (intval($loanUserId)<=0) {
            throw new \Exception('用户ID参数不正确！');
        }
        $dealId = $request->getParam('dealId');
        if (intval($dealId)<=0) {
            throw new \Exception('标的ID参数不正确！');
        }
        $deal = \core\dao\DealModel::instance()->find($dealId);
        if(!$deal){
            throw new \Exception('标的信息不存在！');
        }
        $response = new ResponseBase();

        $dealRepayId   = $request->getParam('dealRepayId');
        //$dealLoanRepayType = (new DealLoanRepayModel())->getTypeByDealRepayId($dealId,$dealRepayId);
        $dealLoanRepayType = $request->getParam('dealRepayType');

        $repayType = ($dealLoanRepayType == 3) ? 3 : 1;
        if($dealLoanRepayType == 3 && $deal['is_during_repay'] == \core\dao\DealModel::DURING_REPAY){
            // 提前还款需要验证是否完成还款
            $response->resCode = 10001;
            $response->resMsg = '标的未完成还款';
        }else{
            $loanInfo['money'] = (new DealLoanRepayModel())->getSumMoneyOfUserByDealIdRepayId($dealId,$loanUserId,$dealRepayId,$repayType);
            $loanInfo['money'] = bcmul($loanInfo['money'], 100);
            $dealInfo['deal_type'] = $deal['deal_type'];
            $dealInfo['report_status'] = $deal['report_status'];

            $response->loanInfo = $loanInfo;
            $response->dealInfo = $dealInfo;
            $response->resCode = RPCErrorCode::SUCCESS;
        }
        return $response;
    }
}