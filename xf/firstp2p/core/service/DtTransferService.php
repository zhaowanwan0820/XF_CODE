<?php
namespace core\service;
use app\models\dao\Deal;
use core\dao\DealAgencyModel;
use core\service\DealService;
use core\service\O2OService;
use core\service\DtBidCallbackService;
use core\service\IdempotentService;
use core\service\DealCompoundService;
use core\dao\DealModel;
use core\dao\UserModel;
use core\service\PaymentService;
use libs\utils\Logger;
use libs\utils\Rpc;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Protos\Duotou\RequestCommon;
use core\dao\FinanceQueueModel;
use core\dao\IdempotentModel;
use NCFGroup\Protos\Duotou\Enum\CommonEnum;
use core\service\duotou\DtMessageService;
use core\dao\JobsModel;
use core\dao\UserLoanRepayStatisticsModel;
use core\service\UserLoanRepayStatisticsService;
use core\service\P2pIdempotentService;
use core\service\P2pDepositoryService;

/**
 * 多投宝投标服务
 *
 * @author jinhaidong
 * @date 2015-10-29 22:50:13
 */
class DtTransferService {

    /**
     * 处理多投宝流标之后，解冻的金额操作冻结回待投本金，因为不知道以后会是什么样的方式处理这个资金，所以单独封一个方法
     * @param object $user
     * @param float $money
     * @param int $deal_load_id
     * @return bool
     */
    public function transferFailDT($user, $money, $deal_load_id) {
        $bizToken = array('dealLoadId'=>$deal_load_id);
        $res = $user->changeMoney($money, "智多鑫-流标冻结", "单号 " . $deal_load_id, 0, 0, UserModel::TYPE_LOCK_MONEY, 0,$bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 智多新投资转入冻结
     * @param objedct $user
     * @param float $money
     * @param string $note
     * @return bool
     */
    public function transferLockDT($user, $money, $note) {
        $bizToken = array();
        $res = $user->changeMoney($money, "智多鑫-转入本金冻结", $note, 0, 0, UserModel::TYPE_LOCK_MONEY,0,$bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 智多新回款成功后冻结
     * @param objedct $user
     * @param float $money
     * @param string $note
     * @return bool
     */
    public function transferRepayLockDT($user, $money, $note,$dealId) {
        $bizToken = array('dealId'=>$dealId);
        $res = $user->changeMoney($money, "智多鑫-本金回款并冻结", $note, 0, 0, UserModel::TYPE_LOCK_MONEY,0,$bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 智多新债权出让成功后冻结
     * @param objedct $user
     * @param float $money
     * @param string $note
     * @return bool
     */
    public function transferRedeemRepayLockDT($user, $money, $note) {
        $bizToken = array();
        $res = $user->changeMoney($money, "智多鑫-债权出让本金回款并冻结", $note, 0, 0, UserModel::TYPE_LOCK_MONEY,0,$bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 智多新投资失败解冻
     * @param objedct $user
     * @param float $money
     * @param string $note
     * @return bool
     */
    public function transferUnlockDT($user, $money, $note) {
        $bizToken = array();
        $res = $user->changeMoney(-$money, "智多鑫-投资失败解冻", $note, 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 多投消费红包转账
     **/
    public function bonusTransferBidDT($outOrderId, $payerId, $receiverId, $money) {
        $syncRemoteData[] = array(
             'outOrderId' => $outOrderId,
             'payerId' => $payerId,
             'receiverId' => $receiverId,
             'repaymentAmount' => bcmul($money, 100), // 以分为单位
             'curType' => 'CNY',
             'bizType' => 11,
        );
        $financeIdArr =  FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
        if(!$financeIdArr[0]) {
            throw new \Exception("FinanceQueueModel push 失败");
        };
    }

    /**
     * 多投宝本金账户资金转账 需要在事务中调用
     * 将投资的冻结金额扣除并同时向多投本金账户发起一笔转账
     * @param $user
     * @param $money
     * @param $dt_deal_id
     * @param $token
     * @return mixed
     */
    public function transferBidDT($user, $money, $dt_deal_id, $token,$dealName='') {
        return true;

        try{
            /*
            $res = $user->changeMoney($money, "投资扣款", "编号 {$dt_deal_id},{$dealName}", 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY);
            if(!$res) {
                throw new \Exception("投标扣款失败 编号 {$dt_deal_id},token:{$token}");
            }
            $user_dt = UserModel::instance()->find(app_conf('AGENCY_ID_DT_PRINCIPAL'));
            if (empty($user_dt)) {
                throw new \Exception("智多新本金账户未定义！");
            }
            $res = $user_dt->changeMoney($money, '资金转入', "编号 {$dt_deal_id},{$dealName}");
            if(!$res) {
                throw new \Exception("多投投资转账失败deal_id:".$dt_deal_id);
            }
            */

            $moneyInfo = array(
                UserLoanRepayStatisticsService::DT_LOAD_MONEY => $money,
            );
            $res = UserLoanRepayStatisticsService::updateUserAssets($user['id'], $moneyInfo);
            if(!$res) {
                throw new \Exception("多投资产同步失败 uid:".$user['id']." dt_deal_id:".$dt_deal_id);
            }

            /*
            $syncRemoteData[] = array(
                'outOrderId' => 'TRANSFER_DT|' . $token,
                'payerId' => $user['id'],
                'receiverId' => $user_dt['id'],
                'repaymentAmount' => bcmul($money, 100), // 以分为单位
                'curType' => 'CNY',
                'bizType' => 11,
            );
            $financeIdArr =  FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
            if(!$financeIdArr[0]) {
                throw new \Exception("FinanceQueueModel push 失败");
            };
            */
        }catch (\Exception $ex) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail ".$ex->getMessage())));
            return false;
        }
        return true;
    }

    /**
     * 多投宝利息账户转账给多投宝投资人
     * @param $user 赎回人对象
     * @param $money $money 结算总利息
     * @param $money $fee 收取管理费
     * @param $money $money 结算总利息
     * @param $manageId 管理方ID
     * @param $token 等幂token
     * @param $dealId 多投宝标的ID
     * @param $dealName 多投宝标的名称
     * @param $minLoanMoney 起投金额
     */
    public function transferInterestDT($user, $money, $fee, $manageId, $token,$dealId='',$dealName='',$minLoanMoney=0) {
        try{
            $couponGroupIds = app_conf('COUPON_GROUP_ID_REFERER_REBATE_DUOTOU');
            if(empty($couponGroupIds)) {//配置不存在
                throw new \Exception("按月结息券组配置不正确");
            }
            $o2oService = new O2OService();
            $res = $o2oService->acquireCoupons($user['id'], $couponGroupIds, $token, '', 0, true ,$money);
            if ($res === false) {// 异常情况，抛异常
                throw new \Exception($o2oService->getErrorMsg(), $o2oService->getErrorCode());
            }
        }catch (\Exception $ex) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail ".$ex->getMessage())));
            return false;
        }

        //结息发送邮件和短信等通知
        DtMessageService::sendMessage(DtMessageService::TYPE_INTEREST_SETTLEMENT, array(
            'id' => $dealId,
            'userId' => $user['id'],
            'name' => $dealName,
            'minLoanMoney' => $minLoanMoney,
            'siteId' => 1,//站点信息
            'money' => $money,//实际支付利息
        ));
        return true;
    }

    /**
     * 多投赎回
     * 持有天数小于配置天数时候需要收取管理费
     * @param $user 赎回人对象
     * @param $money 赎回金额
     * @param $token 等幂token
     * @param $fee 管理费
     * @param $manageId 管理方ID
     * @param $dealId 多投宝标的ID
     * @param $dealName 多投宝标的名称
     * @param $minLoanMoney 起投金额
     * @param $holdDays 实际持有天数
     * @param $dealLoanId 投资记录ID
     * @param $isClean 是不是清盘转账
     */
    public function transferRedeemDT($user, $money,$orderId,$fee,$manageId,$dealId='',$dealName='',$minLoanMoney=0,$holdDays=0,$dealLoanId=0,$isClean=0) {
        try{
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK){
                return true;
            }
            $startTrans = false; // 是否开启事务

            \FP::import("libs.common.dict");
            //在短期管理费免收白名单中的不收取短期管理费
            $duotouManagefeeFreeFroup = \dict::get('DUOTOU_MANAGEFEE_FREE_GROUP');
            if($duotouManagefeeFreeFroup && in_array($user['group_id'], $duotouManagefeeFreeFroup)) {
                $fee = 0;
            }

            // 如果有管理费
            if($fee > 0 && $manageId) {
                $manageInfo = DealAgencyModel::instance()->getDealAgencyById($manageId);
                if (!$manageInfo || !$manageInfo->user_id) {
                    throw new \Exception("管理机构未设置 user:{$user['id']},token:{$orderId},money:{$fee},manageId:{$manageId}");
                }
                $user_dt_fee = UserModel::instance()->find($manageInfo->user_id);
                if (!$user_dt_fee) {
                    throw new \Exception("DT管理账户未设置");
                }
            }


            $service = new \core\service\DtDepositoryService();
            $service->dtRedeemRequest($orderId,$user['id'],$money,$fee,$manageInfo->user_id);


            $startTrans = true;
            $GLOBALS['db']->startTrans();

            // 保存赎回订单信息
            $orderData = array(
                'order_id' => $orderId,
                'deal_id' => $dealId,
                'money' => $money,
                'type' => P2pDepositoryService::IDEMPOTENT_TYPE_REDEEM,
                'status' => P2pIdempotentService::STATUS_CALLBACK,
                'result' => P2pIdempotentService::RESULT_SUCC,
            );
            $orderRes = P2pIdempotentService::addOrderInfo($orderId,$orderData);
            if(!$orderRes){
                throw new \Exception("保存订单信息失败");
            }

            $note = "编号 {$dealId},{$dealName}";
            if($isClean == 1) {//清盘添加备注
                $note .= ',清盘完成';
            }

            $bizToken = array('dealId'=>dealId,'dealLoadId'=>$dealLoanId);
            $user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
            $res2 = $user->changeMoney(-$money, "智多鑫-转让本金到账", $note, 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
            if(!$res2) {
                throw new \Exception("智多新转让到账失败 user:{$user['id']},token:{$orderId}");
            }

            $moneyInfo = array(
                UserLoanRepayStatisticsService::DT_NOREPAY_PRINCIPAL => -$money,
            );
            $res3 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], $moneyInfo);
            if(!$res3) {
                throw new \Exception("多投资产同步失败 uid:".$user['id']." dt_deal_id".$dealId);
            }

            // 如果有管理费
            if($fee > 0 && $manageId) {
                $user_dt_fee->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
                $res3 = $user_dt_fee->changeMoney($fee, '智多鑫-转让服务费', "编号 {$dealId},{$dealName},收取管理费", 0, 0, 0, 0, $bizToken);
                if(!$res3) {
                    throw new \Exception("多投管理费收取 user:{$user['id']},token:{$orderId},money:{$fee}");
                }

                $user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
                $res4 =  $user->changeMoney(-$fee, "智多鑫-转让服务费", "编号 {$dealId},{$dealName},支付管理服务费", 0, 0, 0, 0, $bizToken);
                if(!$res4) {
                    throw new \Exception("多投扣除投资人管理费 user:{$user['id']},manage_user:{$manageInfo->user_id},token:{$orderId},money:{$fee}");
                }
            }

            //添加赎回调用优惠码接口jobs
            $jobs_model = new JobsModel();
            $param = array(
                'dealLoadId' => $dealLoanId,
                'dealRepayTime' => to_timespan(date("Y-m-d",time()-86400),'Y-m-d'),//结息日
            );

            $jobs_model->priority = 85;
            $res_job = $jobs_model->addJob('\core\service\duotou\DtCouponService::redeem', $param);
            if ($res_job === false) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $dealLoanId,$user['id'],$holdDays, "添加赎回调用优惠码Jobs失败")));
                $content = "投资记录ID:{$dealLoanId},用户ID:{$user['id']},持有天数：{$holdDays},添加赎回调用优惠码Jobs失败";
                \libs\utils\Alarm::push('deal', '添加赎回调用优惠码Jobs失败', $content);
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail ".$ex->getMessage())));
            if($startTrans){
                $GLOBALS['db']->rollback();
            }
            return false;
        }
        //赎回成功发送邮件和短信等通知
        DtMessageService::sendMessage(DtMessageService::TYPE_REDEMPTION_SUCCESS, array(
            'id' => $dealId,
            'userId' => $user['id'],
            'name' => $dealName,
            'minLoanMoney' => $minLoanMoney,
            'siteId' => 1,//站点信息
            'money' => $money,
            'holdDays' => $holdDays,
            'fee' => $fee,
            'isClean' => $isClean,
        ));
        return true;
    }

    /**
     * 多投资金撤回
     * 持有天数小于配置天数时候需要收取管理费
     * @param $user 赎回人对象
     * @param $money 赎回金额
     * @param $token 等幂token
     * @param $fee 管理费
     * @param $manageId 管理方ID
     * @param $dealId 多投宝标的ID
     * @param $dealName 多投宝标的名称
     * @param $minLoanMoney 起投金额
     * @param $holdDays 实际持有天数
     * @param $dealLoanId 投资记录ID
     */
    public function transferRevokeDT($user, $money,$orderId,$fee,$manageId,$dealId='',$dealName='',$minLoanMoney=0,$holdDays=0,$dealLoanId=0) {
        try{
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK){
                return true;
            }
            $startTrans = false; // 是否开启事务

            \FP::import("libs.common.dict");
            //在短期管理费免收白名单中的不收取短期管理费
            $duotouManagefeeFreeFroup = \dict::get('DUOTOU_MANAGEFEE_FREE_GROUP');
            if($duotouManagefeeFreeFroup && in_array($user['group_id'], $duotouManagefeeFreeFroup)) {
                $fee = 0;
            }

            // 如果有管理费
            if($fee > 0 && $manageId) {
                $manageInfo = DealAgencyModel::instance()->getDealAgencyById($manageId);
                if (!$manageInfo || !$manageInfo->user_id) {
                    throw new \Exception("管理机构未设置 user:{$user['id']},token:{$orderId},money:{$fee},manageId:{$manageId}");
                }
                $user_dt_fee = UserModel::instance()->find($manageInfo->user_id);
                if (!$user_dt_fee) {
                    throw new \Exception("DT管理账户未设置");
                }
            }

            $service = new \core\service\DtDepositoryService();
            $service->dtRedeemRequest($orderId,$user['id'],$money,$fee,$manageInfo->user_id);

            $startTrans = true;
            $GLOBALS['db']->startTrans();

            // 保存赎回订单信息
            $orderData = array(
                'order_id' => $orderId,
                'deal_id' => $dealId,
                'money' => $money,
                'type' => P2pDepositoryService::IDEMPOTENT_TYPE_REDEEM,
                'status' => P2pIdempotentService::STATUS_CALLBACK,
                'result' => P2pIdempotentService::RESULT_SUCC,
            );
            $orderRes = P2pIdempotentService::addOrderInfo($orderId,$orderData);
            if(!$orderRes){
                throw new \Exception("保存订单信息失败");
            }
            $bizToken = array('dealId'=>dealId,'dealLoadId'=>$dealLoanId);
            $note = "编号 {$dealId},{$dealName}";
            $user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
            $res2 = $user->changeMoney(-$money, "智多鑫-转让本金到账", $note, 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
            if(!$res2) {
                throw new \Exception("智多新转让到账失败 user:{$user['id']},token:{$orderId}");
            }

            $moneyInfo = array(
                UserLoanRepayStatisticsService::DT_NOREPAY_PRINCIPAL => -$money,
            );
            $res3 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], $moneyInfo);
            if(!$res3) {
                throw new \Exception("多投资产同步失败 uid:".$user['id']." dt_deal_id".$dealId);
            }

            // 如果有管理费
            if($fee > 0 && $manageId) {
                $user_dt_fee->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
                $res3 = $user_dt_fee->changeMoney($fee, '智多鑫-转让服务费', "编号 {$dealId},{$dealName},收取管理费", 0, 0, 0, 0, $bizToken);
                if(!$res3) {
                    throw new \Exception("多投管理费收取 user:{$user['id']},token:{$orderId},money:{$fee}");
                }

                $user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
                $res4 =  $user->changeMoney(-$fee, "智多鑫-转让服务费", "编号 {$dealId},{$dealName},支付管理服务费", 0, 0, 0, 0, $bizToken);
                if(!$res4) {
                    throw new \Exception("多投扣除投资人管理费 user:{$user['id']},manage_user:{$manageInfo->user_id},token:{$orderId},money:{$fee}");
                }
            }

            //添加赎回调用优惠码接口jobs
            $jobs_model = new JobsModel();
            $param = array(
                'dealLoadId' => $dealLoanId,
                'dealRepayTime' => to_timespan(date("Y-m-d",time()-86400),'Y-m-d'),//结息日
            );

            $jobs_model->priority = 85;
            $res_job = $jobs_model->addJob('\core\service\duotou\DtCouponService::redeem', $param);
            if ($res_job === false) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $dealLoanId,$user['id'],$holdDays, "添加赎回调用优惠码Jobs失败")));
                $content = "投资记录ID:{$dealLoanId},用户ID:{$user['id']},持有天数：{$holdDays},添加赎回调用优惠码Jobs失败";
                \libs\utils\Alarm::push('deal', '添加赎回调用优惠码Jobs失败', $content);
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail ".$ex->getMessage())));
            if($startTrans){
                $GLOBALS['db']->rollback();
            }
            return false;
        }
        //赎回成功发送邮件和短信等通知
        DtMessageService::sendMessage(DtMessageService::TYPE_REVOKE_SUCCESS, array(
            'id' => $dealId,
            'userId' => $user['id'],
            'name' => $dealName,
            'minLoanMoney' => $minLoanMoney,
            'siteId' => 1,//站点信息
            'money' => $money,
            'holdDays' => $holdDays,
            'fee' => $fee,
        ));
        return true;
    }

    /**
     * 多投还款调用转账
     * 持有天数小于配置天数时候需要收取管理费
     * @param $user 赎回人对象
     * @param $money 赎回金额
     * @param $token 等幂token
     * @param $p2pDealId p2p标的ID
     * @param $repayUserId 还款用户ID
     */
    public function transferRepayDT($user,$money,$token,$p2pDealId,$repayUserId=0) {
        try{
            $token = IdempotentModel::SOURCE_DTBP2PREPAY.'_'.$token ;
            $idempotentService = new IdempotentService();
            if($idempotentService->hasExists($token)){
                return true;
            }

            $GLOBALS['db']->startTrans();
            $data =  array('userId'=>$user['id'],'money'=>$money);
            $mark = CommonEnum::TOKEN_MARK_P2P_REPAY;
            $status = IdempotentModel::STATUS_SUCCESS;
            $res = $idempotentService->saveToken($token,$data,IdempotentModel::SOURCE_DTBREDEEM,$mark,$status);
            if(!$res) {
                throw new \Exception("token 保存失败");
            }


            //根据p2p标的 获取借款人
            $p2pDeal = DealModel::instance()->find($p2pDealId);
            if(empty($p2pDeal)){
                throw new \Exception("p2p标的信息不存在");
            }
            if($repayUserId == 0) {
                $repayUserId = $p2pDeal['user_id'];
            }
            $userBorrow = UserModel::instance()->find($repayUserId); //借款人
            $bizToken = array('dealId'=>$p2pDealId);
            // 借款人的减钱不在这里做，这里做投资人加钱、冻结、变更资产、转账
            $res = $user->changeMoney($money, '还本', "编号{$p2pDealId} {$p2pDeal['name']}", 0, 0, 0, 0, $bizToken);
            if (!$res) {
                throw new \Exception("回款本金失败 user:{$user['id']},token:{$token},money:{$money}");
            }

            // 投资人的余额冻结
            $res2 = $this->transferRepayLockDT($user, $money, "编号{$p2pDealId}");
            if(!$res2) {
                throw new \Exception("回款本金冻结失败 user:{$user['id']},token:{$token},money:{$money}");
            }

            // 资产变更
            $res3 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], array('dt_load_money' => -$money));
            if(!$res3) {
                throw new \Exception("多投资产同步失败 uid:".$user['id']." dt_deal_id".$dealId);
            }

            $syncRemoteData[] = array(
                'outOrderId' => 'TRANSFER_DT_REPAY|' . $token,
                'payerId' => $userBorrow['id'],
                'receiverId' => $user['id'],
                'repaymentAmount' => bcmul($money, 100), // 以分为单位
                'curType' => 'CNY',
                'bizType' => 15,
            );

            $financeIdArr =  FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
            if(!$financeIdArr[0]) {
                throw new \Exception("同步转账队列同步失败");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail ".$ex->getMessage())));
            $GLOBALS['db']->rollback();
            return false;
        }

        return true;
    }
}
