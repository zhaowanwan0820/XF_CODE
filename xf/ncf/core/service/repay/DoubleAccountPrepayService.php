<?php

namespace core\service\repay;

use libs\utils\Alarm;
use libs\utils\Logger;
use core\enum\JobsEnum;
use core\enum\UserAccountEnum;
use core\dao\jobs\JobsModel;
use core\service\BaseService;
use core\enum\P2pDepositoryEnum;
use core\enum\P2pIdempotentEnum;
use core\enum\DealRepayEnum;
use core\dao\repay\DealPrepayModel;
use core\dao\deal\DealAgencyModel;
use core\service\account\AccountService;
use NCFGroup\Common\Library\Idworker;
use core\service\deal\P2pIdempotentService;
use core\service\repay\P2pDealRepayService;

class DoubleAccountPrepayService extends BaseService
{
    private function getUserMoney($userId, $accountType = UserAccountEnum::ACCOUNT_FINANCE)
    {
        //查询存管余额
        $userMoney = AccountService::getAccountMoney($userId, UserAccountEnum::ACCOUNT_FINANCE);
        $userMoney = isset($userMoney['money']) ? $userMoney['money'] : 0;

        return $userMoney;
    }

    private function getRechargeUserMoney($generationRechargeId)
    {
        $rechargeUserMoney = AccountService::getAccountMoney($recgargeUserId, UserAccountEnum::ACCOUNT_RECHARGE);
        $rechargeUserMoney = isset($rechargeUserMoney['money']) ? $rechargeUserMoney['money'] : 0;

        return $rechargeUserMoney;
    }

    private function getPrincipalAndFee($prepayId)
    {
        $prepay = new DealPrepayModel();
        $prepay = $prepay->find($prepayData['prepayId']);

        //总计支付费用 包含：平台手续费、借款咨询费、借款担保费
        $totalFee = bcadd($prepay->loan_fee, $prepay->consult_fee,2); //手续费 + 咨询费
        $totalFee = bcadd($totalFee,$prepay->guarantee_fee, 2); //担保费
        $totalFee = bcadd($totalFee, $prepay->prepay_compensation, 2); //提前还款罚息，补偿金
        $totalFee = bcadd($totalFee, $prepay->pay_fee, 2); //支付服务费
        $totalFee = bcadd($totalFee, $prepay->management_fee, 2); //管理服务费
        $totalFee = bcadd($totalFee, $prepay->prepay_interest, 2); //提前还款利息
        $totalFee = bcadd($totalFee, $prepay->canal_fee, 2); //渠道服务费

        return ['principal' => $prepay->remain_principal, 'fee' => $totalFee];
    }

    public function sendPrepayRequest($orderId, $prepayData, $requestData)
    {
        $agencyInfo = DealAgencyModel::instance()->getDealAgencyById($repayData['generationRechargeId']);
        if(!isset($agencyInfo['user_id']) || empty($agencyInfo['user_id'])){
            throw new \Exception('担保方-代充值机构用户不存在');
        }
        $recgargeUserId = $agencyInfo['user_id']; //代充值账户

        $prepayUserMoney   = $this->getUserMoney($prepayData['prepayUserId'], UserAccountEnum::ACCOUNT_FINANCE);
        $rechargeUserMoney = $this->getUserMoney($recgargeUserId, UserAccountEnum::ACCOUNT_RECHARGE);

        $repayMoneyInfo = $this->getPrincipalAndFee($prepayData['prepayId']);
        $totalPrincipalMoney = $repayMoneyInfo['principal'];
        $totalFeeMoney = $repayMoneyInfo['fee'];

        if (bccomp($prepayUserMoney, $totalPrincipalMoney, 2) == -1) {
            throw new \Exception("归还本金账户余额不足");  
        }

        if (bccomp($rechargeUserMoney, $totalFeeMoney, 2) == -1) {
            throw new \Exception("归还费用账户余额不足");
        }

        $borrowerPrepayOrderId = Idworker::instance()->getId(); //用户还款订单号 - 本金
        $rechargePrepayOrderId = Idworker::instance()->getId(); //代偿还款订单号 - 费用

        $requestPrincipalRepayOrderList = [
            'orderId'           => $borrowerPrepayOrderId,
            'bidId'             => $prepayData['prepayId'],
            'payUserId'         => $prepayData['prepayAccountId'],
            'totalNum'          => 0,
            'totalAmount'       => bcmul($totalPrincipalMoney, 100),
            'currency'          => 'CNY',
            'repayOrderList'    => [],
            'originalPayUserId' => $requestData['originalPayUserId'],
        ];

        $rechargeAccountId = AccountService::getUserAccountId($repayUserId, UserAccountEnum::ACCOUNT_RECHARGE);
        if(!$rechargeAccountId){
            throw new \Exception("未获取到账户ID userId:{$repayUserId}");
        }
        $requestFeeRepayOrderList = [
            'orderId'           => $rechargePrepayOrderId,
            'bidId'             => $prepayData['prepayId'],
            'payUserId'         => $rechargeAccountId,
            'totalNum'          => 0,
            'totalAmount'       => bcmul($totalFee, 100),
            'currency'          => 'CNY',
            'repayOrderList'    => [],
            'originalPayUserId' => $requestData['originalPayUserId'],
        ];

        $totalPrincipalNum = $totalFeeNum = 0;
        foreach ($requestData['repayOrderList'] as $item) {
            if ($item['type'] == 'P') {
                $principalRepayOrderList[] = $item;
                $totalPrincipalNum++;
            } else {
                $feeRepayOrderList[] = $item;
                $totalFeeNum++;
            }
        }

        $repayParams = $repayData['repayParams'];
        $repayParams['orderId'] = $orderId;
        $repayParams['borrowerPrepayOrderId'] = $borrowerPrepayOrderId;
        $repayParams['rechargePrepayOrderId'] = $rechargePrepayOrderId;

    $p2pDealRepayService = new P2pDealRepayService();
        try {
            $GLOBALS['db']->startTrans();

            //更新DK表子订单
            $outerOrderRecord = ThirdpartyDkModel::instance()->find($outerOrder['id']);
            if (empty($outerOrderInfo)) {
                throw new \Exception("第三方订单不存在|orderId={$orderId}");
            }
            $subOrderIds = ['borrowerPrepayOrderId' => $borrowerPrepayOrderId, 'rechargePrepayOrderId' => $rechargePrepayOrderId];
            $dkParams = json_decode($outerOrderRecord->params);
            $dkParams['borrowerPrepayOrderId'] = $borrowerPrepayOrderId;
            $dkParams['rechargePrepayOrderId'] = $rechargePrepayOrderId;
            $outerOrderRecord->params = json_encode($dkParams);
            $outerOrderRecord->update_time = time();
            $dkRes = $outerOrderRecord->save();
            if (!$dkRes) {
                throw new \Exception("更新订单表失败|orderId={$orderId}");
            }

            if ($totalPrincipalNum > 0) { //偿还本金
                $requestPrincipalRepayOrderList['totalNum'] = $totalPrincipalNum;
                $requestPrincipalRepayOrderList['repayOrderList'] = $principalRepayOrderList;
                $repayPrincipalRes = $p2pDealRepayService->sendRepayRequest(
                    $borrowerRepayOrderId,
                    $prepayData['dealId'],
                    DealRepayEnum::DEAL_REPAY_TYPE_SELF,
                    $prepayData['repayOpType'],
                    $prepayData['prepayId'],
                    $requestPrincipalRepayOrderList,
                    $repayParams
                );
                if(!$repayPrincipalRes) {
                    throw new \Exception("提前结清本金还款失败 orderId:".$borrowerRepayOrderId);
                }
            }

            if ($totalFeeNum > 0) { //偿还费用+利息
                $requestFeeRepayOrderList['totalNum'] = $totalFeeNum;
                $requestFeeRepayOrderList['repayOrderList'] = $feeRepayOrderList;
                $repayPrincipalRes = $p2pDealRepayService->sendRepayRequest(
                    $borrowerRepayOrderId,
                    $prepayData['dealId'],
                    DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI,
                    $prepayData['repayOpType'],
                    $prepayData['prepayId'],
                    $requestFeeRepayOrderList,
                    $repayParams
                );
                if(!$repayPrincipalRes) {
                    throw new \Exception("提前结清费用还款失败 orderId:".$borrowerRepayOrderId);
                }
            }

            //添加检查还款存管是否都回调成功jobs
            $jobs_model = new JobsModel();
            $jobs_model->priority = JobsEnum::PRIORITY_ND_REPAY_CALLBACK;
            $jobsData = array(
                'repayOrderId' => $orderId,
                'checkOrderIds' => [$borrowerPrepayOrderId, $rechargePrepayOrderId], //检查还款订单号
            );
            $startTime = get_gmtime() + 180;
            $res = $jobs_model->addJob('\core\service\repay\DoubleAccountRepayService::bankRepayAllCallBack', $jobsData, $startTime, 1000);
            if($res === false){
                throw new \Exception("添加提前结清请求银行jobs失败");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ . " ". $ex->getMessage());
            Alarm::push(P2pDepositoryEnum::ALARM_BANK_CALLBAK,'发送提前结清请求失败'," orderId:{$orderId}, 错误信息:".$ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * 检查还款存管是否都回调成功
     * @param $repayOrderId
     * @param $checkOrderIds
     * @return bool
     * @throws \Exception
     */
    public function bankRepayAllCallBack($repayOrderId, $checkOrderIds)
    {
        $canRepay = true;
        foreach ($checkOrderIds as $orderId) {
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if(empty($orderInfo) || ($orderInfo['result'] != P2pIdempotentEnum::RESULT_SUCC)) {
                $canRepay = false;
                break;
            }
        }

        if(!$canRepay) {
            throw new \Exception("还款结果尚未获取，稍等执行");
        }

        $jobs_model = new JobsModel();
        // 正常还款逻辑
        $function = '\core\service\repay\DealPrepayService::prepay';
        $param = json_decode($repayOrderInfo['params'],true);
        $repayParams = $param['repayParams'];
        $jobs_model->priority = 90;
        $orderInfo['orderId'] = $repayOrderId;

        $res = $jobs_model->addJob($function, $orderInfo);
        if ($res === false) {
            throw new \Exception("还款加入jobs失败");
        }

        return true;
    }
}
