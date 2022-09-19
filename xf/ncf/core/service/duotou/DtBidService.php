<?php

namespace core\service\duotou;

use core\service\deal\DealService;
use core\service\duotou\DtActivityRulesService;
use core\service\user\UserCarryService;
use core\dao\deal\DealModel;
use core\dao\user\UserLoanRepayStatisticsModel;
use libs\utils\Logger;
use libs\utils\Alarm;
use core\service\duotou\DtMessageService;
use core\data\DtDealData;
use core\dao\jobs\JobsModel;
use core\service\User\UserService;
use core\service\deal\P2pDealBidService;
use core\service\deal\P2pIdempotentService;
use core\service\duotou\DtEntranceService;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\service\o2o\CouponService;
use core\service\coupon\CouponService as CCouponService;
use core\tmevent\discount\DiscountConsumeEvent;
use core\service\duotou\DtP2pDealBidService;
use core\service\account\AccountService;
use core\service\account\AccountAuthService;
use core\enum\DealEnum;
use core\enum\AccountAuthEnum;
use core\enum\UserAccountEnum;
use core\enum\P2pIdempotentEnum;
use core\enum\AccountEnum;
use core\enum\UserLoanRepayStatisticsEnum; 
use core\enum\contract\ContractTplIdentifierEnum;
use core\enum\contract\ContractServiceEnum;
use core\enum\JobsEnum;
use core\enum\P2pDepositoryEnum;
use core\enum\CouponGroupEnum;
use core\enum\MsgbusEnum;
use core\service\msgbus\MsgbusService;
use core\service\bwlist\BwlistService;
use core\service\bonus\BonusService;

/**
 * 多投宝投标服务
 *
 * @author jinhaidong
 * @date 2015-10-19 17:50:13
 */
class DtBidService extends DuotouService
{
    const UNKNOW_ERROR = '90000'; // 服务器错误
    const NET_ERROR = '90001'; // 网络错误
    const NOT_BIND_MOBILE = '90002'; // 未绑定手机
    const NOT_ARRIVE_AGE = '90003'; // 未达到最小投资年龄
    const NOT_AUDIT_ID = '90004'; // 身份信息未审核
    const PAYMENT_REGISTER__ERROR = '90005'; // 支付平台注册失败
    const ACCOUNT_NOT_ENOUGH = '90006'; // 账户资金不足
    const HOLIDAY_FORBIDEN = '90007'; // 节假日不可投标
    const USER_NO_EXISTS = '90008'; // 用户信息不存在
    const BID_MAX_USER = '90009'; // 投标用户太多
    const BID_SAVE_ORDER_ERROR = '90010'; // 投资保存订单错误
    const BID_FORBIDDEN = '90011';  // 禁止投资
    const ONLY_INVESTMENT_USER_CAN_BID = '90012'; //非投资账户不允许投资
    const BID_CHECK_ERROR = '90013';// 投资校验失败

    public static $fatal = 0;

    /**
     * 智多鑫投资前存管相关准备
     * @param $globalOrderId
     * @param $userId
     * @param $dealId
     * @param $money
     * @param $bidParams
     * @throws \Exception
     */
    public function beforeBid($globalOrderId, $userId, $dealId, $money, $bidParams)
    {

        //多投标的非投资户不允许投资
        if (!AccountService::allowAccountLoan($GLOBALS['user_info']['user_purpose'])) {
            throw new \Exception('非出借账户不允许出借');
        }

        // 检查是否有授权
        $accountId  = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_INVESTMENT);
        if (!AccountAuthService::checkAuth($accountId, array(AccountAuthEnum::GRANT_TYPE_INVEST => 0,AccountAuthEnum::GRANT_TYPE_PAYMENT => 0))) {
            return array('errCode' => self::BID_FORBIDDEN,'errMsg'=>'未开通智多新出借权限','data' => false);
        }

        $user = UserService::getUserById($userId);
        $deal = new DealModel();
        $deal->report_status = DealEnum::DEAL_REPORT_STATUS_YES;
        $deal->isDTB = true;
        $deal->id = $dealId;

        $service = new P2pDealBidService();
        return $service->beforeBid($globalOrderId, $user, $deal, $money, $bidParams, 'duotou');
    }

    /**
     * 新的投资方法兼容存管
     * @param $userId
     * @param $dealId
     * @param $money
     * @param string $coupon_id
     * @param array $optionParams 验密投资回调时候的订单信息
     * @return array
     */
    public function bid($userId, $dealId, $money, $coupon_id = "", $optionParams=array())
    {
        $accountId  = AccountService::getUserAccountId($userId, UserAccountEnum::ACCOUNT_INVESTMENT);
        if (empty($accountId)) {
            return array('errCode' => self::BID_FORBIDDEN,'errMsg'=>'未开出借户','data' => false);
        }

        $grantInfo = AccountAuthService::checkAccountAuth($accountId, AccountAuthEnum::BIZ_TYPE_ZDX);
        if (!empty($grantInfo)) {
            return array('errCode' => self::BID_FORBIDDEN,'errMsg'=>'未开通智多新出借权限','data' => false);
        }

        // 限制投资
        $user_money_limit = UserCarryService::canWithdrawAmount($userId, $money, true);
        if ($user_money_limit === false) {
            return array('errCode' => self::BID_FORBIDDEN, 'errMsg' => '您的账户暂时无法使用，请拨打95782与客服联系', 'data' => false);
        }

        $deal_data = new DtDealData();
        $lock = $deal_data->enterPool($dealId);

        self::$fatal = 1;
        register_shutdown_function(array($this, "errCatch"), $dealId);
        if ($lock === false) {
            self::$fatal = 0;
            $res['msg'] = "抢标人数过多，请稍后再试";
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, "fail", $res['msg'])));
            return array('errCode' => BID_MAX_USER, 'errMsg' => "抢标人数过多，请稍后再试！", 'data' => false);
        }

        $return = array('errCode' => 0, 'errMsg' => '', 'data' => '');

        try {
            /** 资金记录非要求记录dealId,dealName 只能在走一次rpc调用了 */

            $dealRequest = array('project_id' => $dealId);
            $response = self::callByObject(array('service'=>'NCFGroup\Duotou\Services\Project', 'method'=>'getProjectInfoById', 'args'=>$dealRequest));

            if (!$response) {
                throw new \Exception("系统繁忙，如有疑问，请拨打客服电话：95782", self::NET_ERROR);
            }
            if ($response['errCode']) {
                throw new \Exception("标的信息不存在", self::UNKNOW_ERROR);
            }
            $dealName = $response['data']['name'];

            $user = UserService::getUserById($userId);
            if (empty($user)) {
                throw new \Exception("查询不到当前用户" . $userId, self::USER_NO_EXISTS);
            }

            //检查资格
            $this->bidCheck($user, $dealId, $money);
            // 2、是否免密的投资判断 非免密投资银行已经完成投资处理不需要在进行资金划转
            $orderInfo = isset($optionParams['orderInfo']) ? $optionParams['orderInfo'] : array();
            $orderParams = (isset($orderInfo['params']) && !empty($orderInfo['params'])) ? json_decode($orderInfo['params'],true) : array();

            //参与活动判断
            $activityParams = array();
            $activityId = isset($optionParams['activityId']) ? intval($optionParams['activityId']) : 0;
            $siteId = \libs\utils\Site::getId();
            $lockPeriod = 0;//锁定期
            $activityRate = 0;//活动利率
            $minInvestMoney = 0;//最低起投金额
            if ($activityId > 0) { //参与了活动
                $dtEntranceService = new DtEntranceService();
                $activityInfo = $dtEntranceService->getEntranceInfo($activityId, $siteId);
                $isNewUser = DtActivityRulesService::instance()->isMatchRule('loadGte3', array('userId'=>$userId));
                if (!empty($activityInfo)) {
                    $activityRate = $activityInfo['max_rate'];//活动利率
                    $lockPeriod = $activityInfo['lock_day'];//锁定天数
                    $minInvestMoney = ($isNewUser && $activityInfo['new_user_min_invest_money'] > 0) ? $activityInfo['new_user_min_invest_money'] : $activityInfo['min_invest_money'];//最低起投金额
                    if (bccomp($minInvestMoney, $money, 2) == 1) {
                        throw new \Exception("低于项目单笔出借限额", self::BID_CHECK_ERROR);
                    }
                }
            }

            $activityParams['activityId'] = $activityId;
            $activityParams['activityRate'] = $activityRate;//活动利率
            $activityParams['lockPeriod'] = $lockPeriod;//锁定天数
            $activityParams['minInvestMoney'] = $minInvestMoney;//最低起投金额
            $activityParams['siteId'] = $siteId;//分站ID
            $activityParams['isNewUser'] = $isNewUser;//是否新用户

            //基于TM 的投资逻辑
            $gtm = new GlobalTransactionManager();
            $gtm->setName('dtbid');
            // 灵活投是否可用红包
            $canDtUseBonus = $this->canDtUseBonus($activityId, $userId);
            $globalOrderId = $gtm->getTid();

            //$orderInfo不为空的话是存管回调，订单信息
            if(!empty($orderInfo)){
                $globalOrderId = $orderInfo['order_id'];
                $orderParams = json_decode($orderInfo['params'],true);
                if (!$canDtUseBonus) {
                    $bonusInfo =  array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
                } else {
                    $bonusInfo = $orderParams['bonusInfo'];
                }
            }else{
                $moneyInfo = AccountService::getAccountMoneyInfo($accountId, $money, $globalOrderId);
                if (!$canDtUseBonus) {
                    $bonusInfo =  array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
                }else{
                    $bonusInfo = $moneyInfo['bonusInfo'];
                }

                //$orderInfo为空说明是pc端或者wap站投资，并不是先存管验证，然后投资
                $totalCanBidMoney = bcadd($moneyInfo['bankMoney'],$moneyInfo['bonusMoney'],2);
                if((bccomp($money,$totalCanBidMoney,2) == 1)){
                    throw new \Exception('余额不足，请先进行充值',self::ACCOUNT_NOT_ENOUGH);
                }
            }

            if ($bonusInfo['money'] > 0) {
                $limitBonus = app_conf('BONUS_USE_MAX_VALUES');
                if ($limitBonus > 0 && $bonusInfo['money'] > $limitBonus) {
                    $limitBonus = $limitBonus / 10000;
                    throw new \Exception("单笔投资使用红包金额最多{$limitBonus}万~");
                }
            }


        } catch (\Exception $ex) {
            self::$fatal = 0;
            $deal_data->leavePool($dealId);
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail userId:{$userId},dealId:{$dealId},money:{$money},errMsg:" . $ex->getMessage())));
            return array('errCode' => $ex->getCode(), 'errMsg' => $ex->getMessage(), 'data' => false);
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $coupon_id,"GTM start")));

        // 6、添加event
        try {

            // 银行免密投资，$orderInfo 不为空说明不是直接调用存管
            if (empty($orderInfo)) {
                $gtm->addEvent(new \core\tmevent\dtb\DtBankBidEvent($globalOrderId, $dealId, $userId, $money, $bonusInfo));
            }

            $extraInfo = array(
                'money' => $money,
                'lockDay' => $lockPeriod
            );
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, '消费出借券：'.$optionParams['discount_id'])));
            // 消费投资券落记录
            if ($optionParams['discount_id'] > 0) {
                $gtm->addEvent(new DiscountConsumeEvent(
                    $userId,
                    $optionParams['discount_id'],
                    $globalOrderId,
                    $optionParams['discount_type'],
                    time(),
                    CouponGroupEnum::CONSUME_TYPE_DUOTOU_ORDER,
                    $extraInfo
                ));
            }

            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__,'bonusInfo ：'.json_encode($bonusInfo))));
            // 红包消费
            if (bccomp($bonusInfo['money'], '0.00', 2) == 1) {
                $gtm->addEvent(new \core\tmevent\bid\BonusConsumeEvent($user['id'], $bonusInfo, $globalOrderId, $dealName, 3));
            }

            $bidParams = array_merge($orderParams, $activityParams);
            $bidParams['bonusInfo'] = $bonusInfo;
            $bidParams['couponId'] = $coupon_id;

            // 智多鑫投资
            $gtm->addEvent(new \core\tmevent\dtb\DtBidEvent(
                $globalOrderId,
                $dealId,
                $dealName,
                $userId,
                $money,
                $bidParams
            ));

            $gtm->addEvent(new \core\tmevent\dtb\DtUserEvent(
                $globalOrderId,
                $dealId,
                $dealName,
                $userId,
                $money,
                $bidParams
            ));
            $bidRes = $gtm->execute(); // 同步执行
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $coupon_id, "GTM Error " . $e->getMessage())));
            //return  array('errCode' => -1, 'errMsg' => '投资进行中，请稍后查看资金记录', 'data' => false);
        }

        $orderInfo = P2pIdempotentService::getInfoByOrderId($globalOrderId);
        $orderInfo['discount_id'] = $optionParams['discount_id'];

        // 释放锁
        $dts = new DtP2pDealBidService();
        $dts->delBidLock($globalOrderId);

        if ($orderInfo['load_id']) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP,$globalOrderId,$dealId, $userId, $money, $coupon_id,$orderInfo['load_id'], "智多新出借成功 succ")));
            return $this->handleRpcResponse($orderInfo, $dealName);
        } else {
            $gtmError = $gtm->getError();
            $errMsg = !empty($gtmError) ? $gtmError : '加入失败，请稍后查看资金记录';
            $return = array('errCode' => -1, 'errMsg' => $errMsg, 'data' => false);
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP,$globalOrderId,$dealId, $userId, $money, $coupon_id,$orderInfo['load_id'], "智多新加入失败 fail")));
        }
        return $return;
    }


    /**
     * rpc调用多投投资
     * @param $orderId
     * @param $userId
     * @param $dealId
     * @param $dtDealName
     * @param $money
     * @param $bidParams  投资参数
     * @return bool
     * @throws \Exception
     */
    public function dtBid($orderId, $userId, $dealId, $dtDealName, $money, $bidParams)
    {
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'orderId : '.$orderId,'dealId : '.$dealId, 'userId : '.$userId, 'money : '.$money, 'dtDealName : '.$dtDealName,'bidParams : '.json_encode($bidParams),"智多新出借开始")));
        try {

            $activityId = $bidParams['activityId'];//活动Id
            $activityRate = $bidParams['activityRate'];//活动利率
            $lockPeriod = $bidParams['lockPeriod'];//锁定期
            $minInvestMoney = $bidParams['minInvestMoney'];//最低起投金额
            $siteId = $bidParams['siteId'];//分站ID
            $isNewUser = $bidParams['isNewUser'];

            $request = array(
                        'project_id' => $dealId,
                        'token' => $orderId,
                        'user_id' => $userId,
                        'money' => $money,
                        'isEnterprise' => UserService::isEnterprise($userId),
                        'activityId' => $activityId,
                        'activityRate' => $activityRate,
                        'lockPeriod' => $lockPeriod,
                        'minInvestMoney' => $minInvestMoney,
                        'siteId' => $siteId,
                        'isNewUser' => $isNewUser,
                        );
            $response = self::callByObject(array('NCFGroup\Duotou\Services\Bid', 'doBid', $request));

            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'response : ' . json_encode($response))));

            if (!$response) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "智多新出借异常 orderId:{$orderId},userId:{$userId},dealId:{$dealId},money:{$money}")));
                throw new \Exception("系统繁忙，请稍后再试");
            }

            if (isset($response['data']) && $response['data'] === false) {
                if ($response['errMsg']) {
                    throw new \Exception($response['errMsg']);
                }
            }

            if (!isset($response['data']['dealLoanId']) || intval($response['data']['dealLoanId']) == 0) {
                throw new \Exception($response['errMsg']);
            }

        }catch(\Exception $e){
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'data:'.json_encode($bidParams),'orderId : '.$orderId,'dealId : '.$dealId, 'userId : '.$userId, 'money : '.$money, 'dtDealName : '.$dtDealName,"error:".$e->getMessage())));
            $bidParams['orderId'] = $orderId;
            $bidParams['userId'] = $userId;
            \libs\utils\Alarm::push('dt_bid','errMsg:'.$e->getMessage(),$bidParams);
            return false;
        }
        return true;
    }


    public function dtUser($orderId,$userId,$dealId,$dtDealName,$money,$bidParams){
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'orderId : '.$orderId,'dealId : '.$dealId, 'userId : '.$userId, 'money : '.$money, 'dtDealName : '.$dtDealName,'bidParams : '.json_encode($bidParams),"智多新处理用户资产开始")));
        try{
            $GLOBALS['db']->startTrans();
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if($orderInfo['result'] == P2pIdempotentEnum::RESULT_FAIL){
                // 回调和回调同时发起 有可能其中某个进程已经处理为失败
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $orderId, "智多新订单已处理为失败 fail")));
                throw new \Exception("存管处理失败");
            }
            $bonusInfo = $bidParams['bonusInfo'];
            $request = array(
                        'token' => $orderId,
                        'user_id' => $userId,
                        );
            $response = self::callByObject(array('NCFGroup\Duotou\Services\Bid', 'getSpecialBidInfo', $request));

            $isFirst = intval($response['data']['isFirst']);
            $loadId = $response['data']['dealLoanId'];
            if(empty($loadId)){
                throw new \Exception("获取用户出借ID失败");
            }
            $params = json_decode($orderInfo['params'],true);
            $params['isFirst'] = $isFirst;
            $params['siteId'] = $bidParams['siteId'];//分站ID

            $orderData = array(
                    'load_id' => $loadId,
                    'params' => json_encode($params),
                    'status' => P2pIdempotentEnum::STATUS_CALLBACK,
                    'result' => P2pIdempotentEnum::RESULT_SUCC,
                    );

            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($orderId,$orderData,P2pIdempotentEnum::RESULT_WAIT);
            if($affectedRows == 0){
                Alarm::push(P2pDepositoryEnum::ALARM_DT_DEPOSITORY,'智多新出借取消'," orderId:".$orderId);
                throw new \Exception("订单信息保存失败",self::BID_SAVE_ORDER_ERROR);
            }
            $this->bidBonusTransfer($orderId,$bonusInfo, $userId, $dtDealName, $dealId);

            $userIdAccountId  = AccountService::getUserAccountId($userId,UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!$userIdAccountId) {
                throw new \Exception("未开通出借户[".$userId."]");
            }
            $note = "编号 {$dealId},{$dtDealName}";
            $logInfo = '智多新-转入本金冻结';
            //dealId 智多新项目ID，outOrderId 智多新 投资记录表主键Id deal_loan
            $bizToken = array('dealId'=>$dealId,'dealLoadId'=>$loadId,'outOrderId'=>$loadId);
            $res = AccountService::changeMoney($userIdAccountId,$money, $logInfo,$note, AccountEnum::MONEY_TYPE_LOCK,false,true,0,$bizToken,false,true,0,$bizToken);
            if (!$res) {
                throw new \Exception("智多新本金冻结失败");
            }
            $moneyInfo = array(UserLoanRepayStatisticsEnum::DT_NOREPAY_PRINCIPAL => $money);
            if (UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($userId, $moneyInfo) === false) {
                throw new \Exception("user loan repay statistic error");
            }

            $couponId = $params['couponId'];
            $accrueInterestTime = to_timespan(date("Y-m-d", time() + 86400), 'Y-m-d');
            $jobs_model = new JobsModel();
            $param = array(
                'dealLoadId' => $loadId,
                'dealId' => $dealId,
                'userId' => $userId,
                'money' => $money,
                'couponId' => $couponId, //优惠码
                'accrueInterestTime' => $accrueInterestTime,
            );
            $jobs_model->priority = JobsEnum::PRIORITY_DTB_COUPON;
            $r = $jobs_model->addJob('\core\service\duotou\DtCouponService::bid', $param);
            if ($r === false) {
                throw new \Exception("添加出借使用优惠码失败");
            }

            $param = array(
                'dealId' => $loadId,
                'borrowUserId' => $userId,
                'projectId' => $dealId,
                'dealLoadId' => 0,
                'type' => ContractServiceEnum::TYPE_DT,
                'lenderUserId' => 0,
                'sourceType' => ContractServiceEnum::SOURCE_TYPE_DT_CONTRACT,
                'createTime' => time(),
                'tplPrefix' =>ContractTplIdentifierEnum::DTB_CONT,
                'uniqueId' => 0,
            );

            $jobs_model->priority = JobsEnum::PRIORITY_DTB_CONTRACT;
            $r = $jobs_model->addJob('\core\service\contract\SendContractService::sendDtContractJob', array('requestData'=>$param));
            if ($r === false) {
                throw new \Exception("添加顾问协议jobs失败");
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();

            if($ex->getCode() == self::BID_SAVE_ORDER_ERROR){
                $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
                if($orderInfo && $orderInfo['result'] == P2pIdempotentEnum::RESULT_SUCC){
                    return true;
                }
            }
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'$params:'.json_encode($params),'orderId : '.$orderId,'dealId : '.$dealId, 'userId : '.$userId, 'money : '.$money, 'dtDealName : '.$dtDealName,"error:".$ex->getMessage())));
            $params['errMsg'] = $ex->getMessage();
            \libs\utils\Alarm::push('dt_bid','投资异常',$params);
            return false;
        }

        // 触发投资券、礼券
        $duotou_activity_rate = $bidParams['activityRate'];//活动利率
        $duotou_activity_id = $bidParams['activityId'];//活动Id
        $lockPeriod = $bidParams['lockPeriod'];//锁定期
        $annualizedAmount = round($money * $lockPeriod / 360, 2);
        $siteId = $params['siteId'];//分站ID
        $extra = array(
            'duotou_activity_id' => $duotou_activity_id,
            'duotou_activity_rate' => $duotou_activity_rate,
            'duotou_lock_period' => $lockPeriod,
            'dealBidDays' => $lockPeriod,
            'dealTag' => array('DEAL_DUOTOU'),
        );

        // 对于带锁定期的智多鑫，按照投资时的实际金额触发；对于无锁定期的智多鑫，不触发礼券
        if ($duotou_activity_id > 0) {
            try{
                // 邀请人在黑名单的延迟触发
                $coupon_bind = CCouponService::getByUserId($userId);
                $referUserId = intval($coupon_bind['refer_user_id']);

                if(!($referUserId && BwlistService::inList('DT_INVITER_BLACKLIST', $referUserId))) {
                    // 这里需要保证action值正确，这里默认值为多投复投，现在oto_acquire_log是用user_id, deal_load_id和action做唯一索引
                    $action = CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID;
                    if ($isFirst) {
                        $action = CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID;
                    }
                    CouponService::triggerO2OOrder(
                        $userId,
                        $action,
                        $loadId,
                        $siteId,
                        $money,
                        $annualizedAmount,
                        CouponGroupEnum::CONSUME_TYPE_DUOTOU,
                        1,
                        $extra
                    );
                }
            }catch (\Exception $ex){
                // 同步调用失败后放入msgbus通知
                $o2oParam = array(
                    'userId' => $userId,
                    'action' => $action,
                    'dealLoadId' => $loadId,
                    'siteId' => $siteId,
                    'money' => $money,
                    'annualizedAmount' => $annualizedAmount,
                    'consumeType' => CouponGroupEnum::CONSUME_TYPE_DUOTOU,
                    'triggerType' => 1,
                    'extra' => $extra,
                );
                MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_BID_TRIGGER_O2O, $o2oParam);
            }
        }

        return true;
    }


    public function bidFail($orderId,$userId){
        try {
            $request = array(
                'token' => $orderId,
                'userId' => $userId
            );
            $response = self::callByObject(array('NCFGroup\Duotou\Services\DealLoan', 'rollbackDealLoan', $request));

            if (!$response) {
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "智多新回滚异常 orderId:{$orderId}")));
                throw new \Exception("系统繁忙，请稍后再试");
            }

            if ($response['errCode'] != 0) {
                $errMsg = empty($response['errMsg']) ?  '匹配中不允许回滚出借' : $response['errMsg'];
                throw new \Exception($errMsg);
            }

        }catch(\Exception $e){
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'orderId'.$orderId,"error:".$e->getMessage())));
            \libs\utils\Alarm::push('dt_bid','errMsg:'.$e->getMessage(),$orderId);
            return false;
        }
        return true;
    }

    public function handleRpcResponse($orderInfo, $dtDealName='')
    {
        $return = array('errCode' => 0, 'errMsg' => "", 'data' => '');
        $dealId = $orderInfo['deal_id'];
        $userId = $orderInfo['loan_user_id'];
        $money = $orderInfo['money'];

        $params = json_decode($orderInfo['params'], true);
        $o2oParam = array(
            'user_id' =>$orderInfo['loan_user_id'],
            'discount_id'=>$orderInfo['discount_id'],
            'load_id'=>$orderInfo['load_id'],
            'deal_name'=>$dtDealName,
            'coupon_id'=>$orderInfo['coupon_id'],
            'consumeType' => CouponGroupEnum::CONSUME_TYPE_DUOTOU,
            'annualizedAmount' =>0,
        );

        MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_BID_SUCESS, $o2oParam);
        //投标完成 发送邮件和短信等通知
        DtMessageService::sendMessage(DtMessageService::TYPE_BID_SUCCESS, array(
            'id' => $dealId,
            'userId' => $userId,
            'name' => $dtDealName,
            'siteId' => intval($params['siteId']) ? intval($params['siteId']) : 1, //站点信息
            'money' => $money,
        ));

        $return['data'] = array(
            'token' => $orderInfo['order_id'],
            'loadId' => $orderInfo['load_id'],
            'money' => $orderInfo['money'],
            'isFirst' => $params['isFirst'],
            'projectName' => $dtDealName
        );
        return $return;
    }

    public function errCatch($deal_id)
    {
        $fatal = self::$fatal;
        if (!empty($deal_id) && !empty($fatal)) {
            $deal_data = new DtDealData();
            $deal_data->leavePool($deal_id);
            $lastErr = error_get_last();
            Logger::info("dtbid err catch" . " lastErr: " . json_encode($lastErr) . " trace: " . json_encode(debug_backtrace()));
        }
    }


    /**
     * 检查用户是否可投标
     * @param array $userInfo
     * @param $siteId
     * @param $dealId
     * @param $money
     * @param $orderId
     * @throws \Exception
     */
    public function bidCheck(array $userInfo, $dealId, $money)
    {
        $userId = $userInfo['id'];
        $dealService = new DealService();

        if ($userInfo['idcardpassed'] == 3) {
            throw new \Exception("身份信息未审核", self::NOT_AUDIT_ID);
        }
        if (intval($userInfo['mobilepassed']) == 0 || intval($userInfo['idcardpassed']) != 1 || !$userInfo['real_name']) {
            throw new \Exception("未绑定手机", self::NOT_BIND_MOBILE);
        }

        $age_check = $dealService->allowedBidByCheckAge($userInfo);
        if ($age_check['error'] == true) {
            throw new \Exception("本项目仅限18岁及以上用户出借", self::NOT_ARRIVE_AGE);
        }

        // 限制投资
        $user_money_limit = UserCarryService::canWithdrawAmount($userId, $money, true);
        if ($user_money_limit === false) {
            throw new \Exception($GLOBALS['lang']['FORBID_BID']); // 账户无法投资
        }

        return true;
    }


    /**
     * 智多鑫红包消费记录资金记录
     * @param $orderId
     * @param $bonusInfo
     * @param $userId
     * @param $dealName
     * @return bool
     * @throws \Exception
     */
    public function bidBonusTransfer($orderId, $bonusInfo, $receiverId, $dealName, $dealId)
    {
        $receiverAmount = $bonusInfo['money'];
        $receiverAccountId  = AccountService::getUserAccountId($receiverId,UserAccountEnum::ACCOUNT_INVESTMENT);
        if (!$receiverAccountId) {
            throw new \Exception("未开通出借户[{$receiverId}]");
        }

        foreach ($bonusInfo['accountInfo'] as $payAccount) {
            $payerId = $payAccount['rpUserId'];
            $payerAccountId  = AccountService::getUserAccountId($payerId,UserAccountEnum::ACCOUNT_BONUS);
            if (!$payerAccountId) {
                //兼容逻辑，后期去掉
                $payerAccountId  = AccountService::getUserAccountId($payerId,UserAccountEnum::ACCOUNT_COUPON);
                if (!$payerAccountId) {
                    throw new \Exception("未开通红包户权限[{$payerId}]");
                }
            }
            //outOrderId 投资记录表(deal_loan)表token 字段,dealId 智多新项目ID
            $payAmount = $payAccount['rpAmount'];
            $payerType = app_conf('NEW_BONUS_TITLE') . '充值';
            $payerNote = $receiverId ."使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$dealName}";
            $payerBizToken = ['dealId' => $dealId, 'orderId' => $orderId, 'outOrderId'=>$orderId];
            $receiverType = '使用' . app_conf('NEW_BONUS_TITLE') . '充值';
            $receiverNote = "使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$dealName}";
            $receiverBizToken = ['dealId' => $dealId, 'orderId' => $orderId, 'outOrderId'=>$orderId];
            AccountService::transferMoney($payerAccountId, $receiverAccountId, $payAmount, $payerType, $payerNote, $receiverType, $receiverNote,false, false, $payerBizToken, $receiverBizToken);
        }
        return true;
    }

    /**
     * 多投投资P2p底层资产成功回调
     * @param $token 多投幂等token
     * @param $loadId p2p投资记录ID
     * @throws \Exception
     */
    public function bidSuccessCallback($orderId)
    {
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if (!$orderInfo) {
            throw new \Exception("订单信息不存在 orderId:".$orderId);
        }
        $loadId = $orderInfo['load_id'];
        $params = json_decode($orderInfo['params'], true);

        $transParams = $params['dtParams'];
        $request = array('token' => $orderId,'loadId' => $loadId,'transParams'=>$transParams);
        $response = self::callByObject(array('NCFGroup\Duotou\Services\LoanMappingContract', 'bidSuccessCallback', $request));

        if (!$response || ($response['data'] === false)) {
            Logger::error(implode('|', array(__CLASS__,__FUNCTION__, "通知智多新出借回调失败 orderId:{$orderId},loadId:{$loadId}")));
            return false;
        }
        return true;
    }

    /**
     * canDtUseBonus 灵活投是否可用红包
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2018-07-07
     * @param mixed $activityInfo
     * @param mixed $userInfo
     * @access public
     * @return void
     */
    public function canDtUseBonus($activityId,$userId) {
        $isBonusEnable = BonusService::isBonusEnable();
        $result = false;
        if(!empty($isBonusEnable)){
            //黑名单拦截
            $result = UserService::checkBwList('USE_BONUS_BLACK', $userId) ? false : true;
            if($result){
                $result = $this->bonusLock($userId);
            }
            if($result){
                $siteId = \libs\utils\Site::getId();
                $dtEntranceService = new DtEntranceService();
                $activityInfo = $dtEntranceService->getEntranceInfo($activityId, $siteId);
                $lockPeriod = isset($activityInfo['lock_day']) ? intval($activityInfo['lock_day']) : 0;
                if ($lockPeriod <=1) {
                    $user = UserService::getUserById($userId);
                    $result = BwlistService::inList('USE_BONUS_FOR_DT', $user['group_id']);
                }
            }
            Logger::info(implode(' | ',array(__CLASS__,__FUNCTION__,'activityId:'.$activityId,'lockPeriod:'.$lockPeriod,'userId: '.$userId,'isBonusEnable: '.$isBonusEnable,'result: '.$result)));
        }
        return $result;
    }

    /*
     * 红包套现锁定判断
     * */
    public function bonusLock($userId){
        $request = array('userId' => $userId);
        //获取红包限制判定
        $bonusEnable = self::callByObject(array('NCFGroup\Duotou\Services\Bid','userBidLimitation', $request));
        return $bonusEnable['data'];
    }

}
