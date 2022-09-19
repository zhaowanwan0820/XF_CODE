<?php
namespace core\service\duotou;

use core\dao\deal\DealAgencyModel;
use core\service\o2o\CouponService;
use core\service\deal\IdempotentService;
use core\dao\deal\DealModel;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;
use core\dao\deal\IdempotentModel;
use core\enum\duotou\CommonEnum;
use core\enum\JobsEnum;
use core\service\duotou\DtMessageService;
use core\dao\jobs\JobsModel;
use core\dao\user\UserLoanRepayStatisticsModel;
use core\service\user\UserLoanRepayStatisticsService;
use core\service\deal\P2pIdempotentService;
use core\service\deal\P2pDepositoryService;
use core\enum\P2pIdempotentEnum;
use core\enum\P2pDepositoryEnum;
use core\enum\AccountEnum;
use core\enum\UserAccountEnum;
use core\service\account\AccountService;
use core\service\user\UserService;
use core\enum\UserLoanRepayStatisticsEnum;

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
    public function transferFailDT($userIdAccountId,$money, $deal_load_id,$deal_id) {
        $note = "单号 " . $deal_load_id;
        $logInfo = '智多鑫-流标冻结';
        //outOrderId 底层资产投资记录Id，deal_load，dealId 底层标Id 
        $bizToken = array('dealId'=> $deal_id,'dealLoadId'=>$deal_load_id, 'outOrderId' => $deal_load_id);
        $res = AccountService::changeMoney($userIdAccountId,$money, $logInfo,$note, AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 方法废弃
     * 智多鑫投资转入冻结
     * @param objedct $user
     * @param float $money
     * @param string $note
     * @return bool
     */
    public function transferLockDT($user, $money, $note) {
        $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
        if (!$userIdAccountId) {
            throw new \Exception("未开通出借户[".$user['id']."]");
        }
        $bizToken = array();
        $logInfo = '智多鑫-转入本金冻结';
        $res = AccountService::changeMoney($userIdAccountId,$money, $logInfo,$note, AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 方法废弃
     * 智多鑫回款成功后冻结
     * @param objedct $user
     * @param float $money
     * @param string $note
     * @return bool
     */
    public function transferRepayLockDT($user, $money, $note,$deal_id,$load_id) {
        $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
        if (!$userIdAccountId) {
            throw new \Exception("未开通出借户[".$user['id']."]");
        }
        $logInfo = '智多鑫-本金回款并冻结';
        $bizToken = array('dealId'=>$deal_id,'dealLoadId'=>$load_id);
        $res = AccountService::changeMoney($userIdAccountId,$money, $logInfo,$note, AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 方法废弃
     * 智多鑫债权出让成功后冻结
     * @param objedct $user
     * @param float $money
     * @param string $note
     * @return bool
     */
    public function transferRedeemRepayLockDT($user, $money, $note) {
        $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
        if (!$userIdAccountId) {
            throw new \Exception("未开通出借户");
        }
        $logInfo = '智多鑫-债权出让本金回款并冻结';
        $bizToken = array();
        $res = AccountService::changeMoney($userIdAccountId,$money, $logInfo,$note, AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 方法废弃
     * 智多鑫投资失败解冻
     * @param objedct $user
     * @param float $money
     * @param string $note
     * @return bool
     */
    public function transferUnlockDT($user, $money, $note) {
        $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
        if (!$userIdAccountId) {
            throw new \Exception("未开通出借户");
        }
        $logInfo = '智多鑫-投资失败解冻';
        $bizToken = array();
        $res = AccountService::changeMoney($userIdAccountId,$money, $logInfo,$note, AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken);
        if (!$res) {
            return false;
        }
        return true;
    }


    /**
     * 方法废弃
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
                throw new \Exception("智多鑫本金账户未定义！");
            }
            $res = $user_dt->changeMoney($money, '资金转入', "编号 {$dt_deal_id},{$dealName}");
            if(!$res) {
                throw new \Exception("多投投资转账失败deal_id:".$dt_deal_id);
            }
            */

            $moneyInfo = array(
                UserLoanRepayStatisticsEnum::DT_LOAD_MONEY => $money,
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
            $res = CouponService::acquireCoupons($user['id'], $couponGroupIds, $token, '', 0, true ,$money);
            if ($res === false) {// 异常情况，抛异常
                throw new \Exception('礼券返利异常');
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
            if($orderInfo['status'] == P2pIdempotentEnum::STATUS_CALLBACK){
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
                $user_dt_fee = UserService::getUserById($manageInfo->user_id);
                if (!$user_dt_fee) {
                    throw new \Exception("DT管理账户未设置");
                }
            }


            $service = new \core\service\duotou\DtDepositoryService();
            $service->dtRedeemRequest($orderId,$user['id'],$money,$fee,$manageInfo->user_id);


            $startTrans = true;
            $GLOBALS['db']->startTrans();

            // 保存赎回订单信息
            $orderData = array(
                'order_id' => $orderId,
                'deal_id' => $dealId,
                'money' => $money,
                'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_REDEEM,
                'status' => P2pIdempotentEnum::STATUS_CALLBACK,
                'result' => P2pIdempotentEnum::RESULT_SUCC,
            );
            $orderRes = P2pIdempotentService::addOrderInfo($orderId,$orderData);
            if(!$orderRes){
                throw new \Exception("保存订单信息失败");
            }

            $note = "编号 {$dealId},{$dealName}";
            if($isClean == 1) {//清盘添加备注
                $note .= ',清盘完成';
            }
            //dealLoanId是投资记录表的主键ID，dealId智多新项目ID
            $bizToken = array('dealId'=>$dealId,'dealLoadId'=>$dealLoanId,'outOrderId'=>$dealLoanId);
            $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!$userIdAccountId) {
                throw new \Exception("未开通出借户[{$user['id']}]");
            }
            $logInfo = '智多鑫-转让本金到账';
            $res2 = AccountService::changeMoney($userIdAccountId,$money, $logInfo,$note, AccountEnum::MONEY_TYPE_UNLOCK,false,true,0,$bizToken);
            if(!$res2) {
                throw new \Exception("智多新转让到账失败 user:{$user['id']},token:{$orderId}");
            }

            $moneyInfo = array(
                UserLoanRepayStatisticsEnum::DT_NOREPAY_PRINCIPAL => -$money,
            );
            $res3 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], $moneyInfo);
            if(!$res3) {
                throw new \Exception("多投资产同步失败 uid:".$user['id']." dt_deal_id".$dealId);
            }

            // 如果有管理费
            if($fee > 0 && $manageId) {
                $manageIdAccountId  = AccountService::getUserAccountId($manageInfo->user_id,UserAccountEnum::ACCOUNT_MANAGEMENT);
                if (!$manageIdAccountId) {
                    throw new \Exception("未开通管理户[{$manageInfo->user_id}]");
                }
                $res3 = AccountService::changeMoney($manageIdAccountId,$fee,"智多鑫-转让服务费","编号 {$dealId},{$dealName},收取管理服务费", AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken);
                if(!$res3) {
                    throw new \Exception("多投管理费收取 user:{$user['id']},token:{$orderId},money:{$fee}");
                }

                $logInfo = '智多鑫-转让本金到账';
                $res4 = AccountService::changeMoney($userIdAccountId,$fee, "智多鑫-转让服务费","编号 {$dealId},{$dealName},支付管理服务费", AccountEnum::MONEY_TYPE_REDUCE,false,true,0,$bizToken);
                if(!$res4) {
                    throw new \Exception("多投扣除出借人管理费 user:{$user['id']},manage_user:{$manageInfo->user_id},token:{$orderId},money:{$fee}");
                }
            }

            //添加赎回调用优惠码接口jobs
            $jobs_model = new JobsModel();
            $param = array(
                'dealLoadId' => $dealLoanId,
                'dealRepayTime' => to_timespan(date("Y-m-d",time()-86400),'Y-m-d'),//结息日
            );

            $jobs_model->priority = JobsEnum::PRIORITY_DTB_COUPON;
            $res_job = $jobs_model->addJob('\core\service\duotou\DtCouponService::redeem', $param);
            if ($res_job === false) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $dealLoanId,$user['id'],$holdDays, "添加转让/退出调用优惠码Jobs失败")));
                $content = "出借记录ID:{$dealLoanId},用户ID:{$user['id']},持有天数：{$holdDays},添加转让/退出调用优惠码Jobs失败";
                \libs\utils\Alarm::push('deal', '添加转让/退出调用优惠码Jobs失败', $content);
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
            if($orderInfo['status'] == P2pIdempotentEnum::STATUS_CALLBACK){
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

                $user_dt_fee = UserService::getUserById($manageInfo->user_id);
                if (!$user_dt_fee) {
                    throw new \Exception("DT管理账户未设置");
                }
            }

            $service = new \core\service\duotou\DtDepositoryService();
            $service->dtRedeemRequest($orderId,$user['id'],$money,$fee,$manageInfo->user_id);

            $startTrans = true;
            $GLOBALS['db']->startTrans();

            // 保存赎回订单信息
            $orderData = array(
                'order_id' => $orderId,
                'deal_id' => $dealId,
                'money' => $money,
                'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_REDEEM,
                'status' => P2pIdempotentEnum::STATUS_CALLBACK,
                'result' => P2pIdempotentEnum::RESULT_SUCC,
            );
            $orderRes = P2pIdempotentService::addOrderInfo($orderId,$orderData);
            if(!$orderRes){
                throw new \Exception("保存订单信息失败");
            }

            $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!$userIdAccountId) {
                throw new \Exception("未开通出借户{$user['id']}");
            }
            $note = "编号 {$dealId},{$dealName}";
            //$dealLoanId是deal_loan表的主键id,dealId 智多新项目ID
            $bizToken = array('dealId'=>$dealId,'dealLoadId'=>$dealLoanId,'outOrderId'=>$dealLoanId);
            $res2 = AccountService::changeMoney($userIdAccountId,$money,"智多鑫-转让本金到账",$note, AccountEnum::MONEY_TYPE_UNLOCK,false,true,0,$bizToken);
            if(!$res2) {
                throw new \Exception("智多新转让到账失败 user:{$user['id']},token:{$orderId}");
            }

            $moneyInfo = array(
                UserLoanRepayStatisticsEnum::DT_NOREPAY_PRINCIPAL => -$money,
            );
            $res3 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], $moneyInfo);
            if(!$res3) {
                throw new \Exception("多投资产同步失败 uid:".$user['id']." dt_deal_id".$dealId);
            }

            // 如果有管理费
            if($fee > 0 && $manageId) {
                $manageIdAccountId  = AccountService::getUserAccountId($manageInfo->user_id,UserAccountEnum::ACCOUNT_GUARANTEE);
                if (!$manageIdAccountId) {
                    throw new \Exception("未开通管理户{$manageInfo->user_id}");
                }
                $res3 = AccountService::changeMoney($manageIdAccountId,$fee,"智多鑫-转让服务费","编号 {$dealId},{$dealName},收取管理服务费", AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken);
                if(!$res3) {
                    throw new \Exception("多投管理费收取 user:{$user['id']},token:{$orderId},money:{$fee}");
                }

                $res4 = AccountService::changeMoney($userIdAccountId,$fee, "智多鑫-转让服务费","编号 {$dealId},{$dealName},支付管理服务费", AccountEnum::MONEY_TYPE_REDUCE,false,true,0,$bizToken);
                if(!$res4) {
                    throw new \Exception("多投扣除出借人管理费 user:{$user['id']},manage_user:{$manageInfo->user_id},token:{$orderId},money:{$fee}");
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
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, $dealLoanId,$user['id'],$holdDays, "添加转让/退出调用优惠码Jobs失败")));
                $content = "出借记录ID:{$dealLoanId},用户ID:{$user['id']},持有天数：{$holdDays},添加转让/退出调用优惠码Jobs失败";
                \libs\utils\Alarm::push('deal', '添加转让/退出调用优惠码Jobs失败', $content);
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
     * 方法废弃
     * 多投还款调用转账
     * 持有天数小于配置天数时候需要收取管理费
     * @param $user 赎回人对象
     * @param $money 赎回金额
     * @param $token 等幂token
     * @param $p2pDealId p2p标的ID
     * @param $repayUserId 还款用户ID
     */
    public function transferRepayDT($user,$money,$token,$p2pDealId,$repayUserId=0,$loadId=0) {
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
            $userBorrow = UserService::getUserById($repayUserId); //借款人

            // 借款人的减钱不在这里做，这里做投资人加钱、冻结、变更资产、转账
            $userIdAccountId  = AccountService::getUserAccountId($user['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!$userIdAccountId) {
                throw new \Exception("未开通出借户{$user['id']}");
            }
            $bizToken = array('dealId'=>$p2pDealId,'dealLoadId'=>$loadId);
            $res = AccountService::changeMoney($userIdAccountId,$money,"还本","编号{$p2pDealId} {$p2pDeal['name']}", AccountEnum::MONEY_TYPE_INCR,false,true,0,$bizToken);
            if (!$res) {
                throw new \Exception("回款本金失败 user:{$user['id']},token:{$token},money:{$money}");
            }

            // 投资人的余额冻结
            $res2 = $this->transferRepayLockDT($user, $money, "编号{$p2pDealId}",$p2pDealId,$loadId);
            if(!$res2) {
                throw new \Exception("回款本金冻结失败 user:{$user['id']},token:{$token},money:{$money}");
            }

            // 资产变更
            $res3 = UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($user['id'], array('dt_load_money' => -$money));
            if(!$res3) {
                throw new \Exception("多投资产同步失败 uid:".$user['id']." dt_deal_id".$p2pDealId);
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
