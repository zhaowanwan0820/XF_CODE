<?php

namespace core\service;

use app\models\dao\Deal;
use core\dao\FinanceQueueModel;
use core\service\DealService;
use core\service\DtBidCallbackService;
use core\service\DtTransferService;
use core\service\duotou\DtActivityRulesService;
use core\service\IdempotentService;
use core\service\DealCompoundService;
use core\service\BonusService;
use core\service\TransferService;
use core\service\UserCarryService;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\IdempotentModel;
use core\dao\DealLoadModel;
use core\service\PaymentService;
use core\service\UserLoanRepayStatisticsService;
use core\dao\UserLoanRepayStatisticsModel;
use libs\utils\Logger;
use libs\utils\Rpc;
use NCFGroup\Protos\Duotou\RequestCommon;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Ptp\services\PtpDtTransferService;
use NCFGroup\Protos\Duotou\Enum\CommonEnum;
use libs\utils\Alarm;
use core\service\duotou\DtMessageService;
use core\data\DtDealData;
use core\dao\JobsModel;
use core\service\UserService;
use core\service\P2pDealBidService;
use core\service\P2pIdempotentService;
use core\service\DtEntranceService;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\service\O2OService;
use core\dao\EnterpriseModel;
use core\service\DiscountService;
use core\tmevent\discount\DiscountConsumeEvent;
use core\service\AccountAuthorizationService;
use core\dao\AccountAuthorizationModel;
use core\service\SupervisionService;
use core\service\P2pDepositoryService;
use core\service\duotou\DtP2pDealBidService;
use core\service\CouponService;
use core\service\CouponBindService;
use core\service\BwlistService;

/**
 * 多投宝投标服务
 *
 * @author jinhaidong
 * @date 2015-10-19 17:50:13
 */
class DtBidService {

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
     * 智多新投资前存管相关准备
     * @param $globalOrderId
     * @param $userId
     * @param $dealId
     * @param $money
     * @param $bidParams
     * @throws \Exception
     */
    public function beforeBid($globalOrderId,$userId,$dealId,$money,$bidParams){
        $user_service = new UserService();

        //多投标的非投资户不允许投资
        if(!$user_service->allowAccountLoan($GLOBALS['user_info']['user_purpose'])){
            throw new \Exception($GLOBALS['lang']['非投资账户不允许投资']);
        }

        // 检查是否有授权
        $as = new AccountAuthorizationService();
        $authRes = $as->checkAuth($userId,array(AccountAuthorizationModel::GRANT_TYPE_INVEST => 0,AccountAuthorizationModel::GRANT_TYPE_PAYMENT => 0));
        if($authRes['code'] != 0){
            throw new \Exception("用户未开通授权不能进行投资");
        }


        $user = UserModel::instance()->find($userId);
        $deal = new DealModel();
        $deal->report_status = DealModel::DEAL_REPORT_STATUS_YES;
        $deal->isDTB = true;
        $deal->id = $dealId;

        $service = new P2pDealBidService();
        return $service->beforeBid($globalOrderId,$user, $deal, $money, $bidParams,'duotou');
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
    public function bid($userId, $dealId, $money, $coupon_id = "",$optionParams=array()){
        $user_service = new UserService();

        //多投标的非投资户不允许投资
        if(!$user_service->allowAccountLoan($GLOBALS['user_info']['user_purpose'])){
            return array('errCode' => self::ONLY_INVESTMENT_USER_CAN_BID, 'errMsg' => $GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID'], 'data' => false);
        }

        $supService = new SupervisionService();
        $grantInfo = $supService->checkAuth($userId,SupervisionService::GRANT_TYPE_ZDX);
        if(!empty($grantInfo)){
            return array('errCode' => self::BID_FORBIDDEN,'errMsg'=>'未开通智多新投资权限','data' => false);
        }

        // 限制投资
        $userCarryService = new UserCarryService();
        $user_money_limit = $userCarryService->canWithdrawAmount($userId, $money, true);
        if ($user_money_limit === false){
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

        try{
            /** 资金记录非要求记录dealId,dealName 只能在走一次rpc调用了 */
            $rpc = new Rpc('duotouRpc');
            $dealRequest = new RequestCommon();
            $dealRequest->setVars(array('project_id' => $dealId));
            $response = $rpc->go('NCFGroup\Duotou\Services\Project', 'getProjectInfoById', $dealRequest);
            if (!$response) {
                throw new \Exception("系统繁忙，如有疑问，请拨打客服电话：95782", self::NET_ERROR);
            }
            if ($response['errCode']) {
                throw new \Exception("标的信息不存在", self::UNKNOW_ERROR);
            }
            $dealName = $response['data']['name'];

            $user = UserModel::instance()->find($userId);
            if (empty($user)) {
                throw new \Exception("查询不到当前用户" . $userId, self::USER_NO_EXISTS);
            }

            // 企业融资户交易拦截
            if ($user['user_purpose'] == EnterpriseModel::COMPANY_PURPOSE_FINANCE) {
                throw new \Exception('投资功能仅对企业投资户开放', self::BID_FORBIDDEN);
            }

            //检查资格
            $this->bidCheck($user->_row, $dealId, $money);

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
            $activityInfo = array();
            if($activityId > 0) { //参与了活动
                $dtEntranceService = new DtEntranceService();
                $activityInfo = $dtEntranceService->getEntranceInfo($activityId,$siteId);
                $isNewUser = DtActivityRulesService::instance()->isMatchRule('loadGte3',array('userId'=>$userId));
                if(!empty($activityInfo)) {
                    $activityRate = $activityInfo['max_rate'];//活动利率
                    $lockPeriod = $activityInfo['lock_day'];//锁定天数
                    $minInvestMoney = ($isNewUser && $activityInfo['new_user_min_invest_money'] > 0) ? $activityInfo['new_user_min_invest_money'] : $activityInfo['min_invest_money'];//最低起投金额
                    if (bccomp($minInvestMoney,$money,2) == 1) {
                        throw new \Exception("低于项目单笔投资限额",self::BID_CHECK_ERROR);
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
            $canDtUseBonus = $this->canDtUseBonus($activityInfo, $user);
            $dealBidService = new \core\service\P2pDealBidService();
            if(!empty($orderInfo)){
                $transferMoney = false;
                $globalOrderId = $orderInfo['order_id'];
                $orderParams = json_decode($orderInfo['params'],true);
                if (!$canDtUseBonus) {
                    $bonusInfo =  array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
                } else {
                    $bonusInfo = $orderParams['bonusInfo'];
                }
            }else{
                $globalOrderId = $gtm->getTid();
                $deal_model = new DealModel();
                $deal_model->report_status = DealMOdel::DEAL_REPORT_STATUS_YES;
                $transferMoney  = $dealBidService->needTransferMoney($user,$deal_model,$money);
            }

            // 3、资金划转
            if($transferMoney && bccomp($transferMoney,'0.00',2) ==1){
                $transferOrderId = Idworker::instance()->getId();
                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP," dealId:{$dealId},userId:{$userId} 开始资金划转 金额：{$transferMoney}")));
                $transferRes = $dealBidService->moneyTransfer($transferOrderId,$user['id'],$transferMoney,true);
                if(!$transferRes){
                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $coupon_id, "资金划转失败 orderId:".$globalOrderId.", transFerOrderId:".$transferOrderId)));
                    throw new \Exception("投资失败",self::NET_ERROR);
                }
                // 此处需要重新获取下用户信息,因为在资金划转后用户资金已经发生变化了
                $user = UserModel::instance()->find($userId);
            }

            // 4、余额验证 (余额验证不能放在前面,因为存管行有可能已经完成投资，这时候判断余额是不严谨的 所以需要单独摘出来) 银行验密投资不需要再次验证余额
            if(empty($orderInfo)){
                $moneyInfo = (new UserService())->getMoneyInfo($user,$money, $globalOrderId);
                if (!$canDtUseBonus) {
                    $moneyInfo['bonus'] = 0;
                    $moneyInfo['bonusInfo'] =  array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
                }
                $totalCanBidMoney = bcadd($moneyInfo['bank'],$moneyInfo['bonus'],2);
                if((bccomp($money,$totalCanBidMoney,2) == 1)){
                    throw new \Exception('余额不足，请先进行充值',self::ACCOUNT_NOT_ENOUGH);
                }
                $bonusInfo = $moneyInfo['bonusInfo'];
            }

            // 5、红包查询
            if(!isset($bonusInfo['accountInfo'])){
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $coupon_id, "未查询到红包信息 orderId:".$globalOrderId)));
                $bonusMoney = 0;
            }else{
                $bonusMoney = $bonusInfo['money'];
            }
        }catch (\Exception $ex){
            self::$fatal = 0;
            $deal_data->leavePool($dealId);
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, "fail userId:{$userId},dealId:{$dealId},money:{$money},errMsg:" . $ex->getMessage())));
            return array('errCode' => $ex->getCode(), 'errMsg' => $ex->getMessage(), 'data' => false);
        }
        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $coupon_id,"GTM start")));

        // 6、添加event
        try {

            // 银行免密投资
            if(empty($orderInfo)){
                $gtm->addEvent(new \core\tmevent\dtb\DtBankBidEvent($globalOrderId,$dealId,$userId,$money,$bonusInfo));
            }

            $extraInfo = array(
                'money' => $money,
                'lockDay' => $lockPeriod
            );
            // 消费投资券落记录
            if ($optionParams['discount_id'] > 0) {
                $gtm->addEvent(new DiscountConsumeEvent(
                    $userId,
                    $optionParams['discount_id'],
                    $globalOrderId,
                    $optionParams['discount_type'],
                    time(),
                    CouponGroupEnum::CONSUME_TYPE_DUOTOU,
                    $extraInfo
                ));
            }

            // 红包消费
            if(bccomp($bonusMoney,'0.00',2) == 1){
                $gtm->addEvent(new \core\tmevent\bid\BonusConsumeEvent($user['id'],$bonusInfo,$globalOrderId,$dealName, 3));
            }

            $bidParams = array(
                'couponId' => $coupon_id,
                'bonusInfo' => $bonusInfo,
            );
            $bidParams = array_merge($bidParams,$orderParams);
            $bidParams = array_merge($bidParams,$activityParams);

            // 智多新投资
            $gtm->addEvent(new \core\tmevent\dtb\DtBidEvent(
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

        if($orderInfo['load_id']){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $coupon_id,$orderInfo['load_id'], "智多新投资成功 succ")));
            return $this->handleRpcResponse($orderInfo,$dealName);
        }else{
            $gtmError = $gtm->getError();
            $errMsg = !empty($gtmError) ? $gtmError : '加入失败，请稍后查看资金记录';
            $return = array('errCode' => -1, 'errMsg' => $errMsg, 'data' => false);
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $coupon_id,$orderInfo['load_id'], "智多新加入失败 fail")));
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
    public function dtBid($orderId,$userId,$dealId,$dtDealName,$money,$bidParams){
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if($orderInfo['result'] == P2pIdempotentService::RESULT_FAIL){
            // 回调和回调同时发起 有可能其中某个进程已经处理为失败
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $orderId, "智多新订单已处理为失败 fail")));
            return false;
        }

        $userService = new UserService($userId);
        $rpc = new Rpc('duotouRpc');

        $bonusInfo = $bidParams['bonusInfo'];
        $couponId = $bidParams['couponId'];
        $activityId = $bidParams['activityId'];//活动Id
        $activityRate = $bidParams['activityRate'];//活动利率
        $lockPeriod = $bidParams['lockPeriod'];//锁定期
        $minInvestMoney = $bidParams['minInvestMoney'];//最低起投金额
        $siteId = $bidParams['siteId'];//分站ID
        $isNewUser = $bidParams['isNewUser'];

        $request = new RequestCommon();
        $request->setVars(array(
            'project_id' => $dealId,
            'token' => $orderId,
            'user_id' => $userId,
            'money' => $money,
            'isEnterprise' => $userService->isEnterpriseUser(),
            'activityId' => $activityId,
            'activityRate' => $activityRate,
            'lockPeriod' => $lockPeriod,
            'minInvestMoney' => $minInvestMoney,
            'siteId' => $siteId,
            'isNewUser' => $isNewUser,
        ));
        $response = $rpc->go('NCFGroup\Duotou\Services\Bid', 'doBid', $request, 2, 3);

        if(!$response){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "智多新投资异常 orderId:{$orderId},userId:{$userId},dealId:{$dealId},money:{$money}")));
            throw new \Exception("系统繁忙，请稍后再试");
        }

        if(isset($response['data']) && $response['data'] === false){
            if($response['errMsg']){
                throw new \Exception($response['errMsg']);
            }
        }

        if(!isset($response['data']['dealLoanId']) || intval($response['data']['dealLoanId']) == 0){
            throw new \Exception($response['errMsg']);
        }

        $isFirst = intval($response['data']['isFirst']);
        $params = json_decode($orderInfo['params'],true);
        $params['isFirst'] = $isFirst;
        $params['siteId'] = $siteId;


        $loadId = $response['data']['dealLoanId'];

        try{
            $GLOBALS['db']->startTrans();

            $orderData = array(
                'load_id' => $loadId,
                'params' => json_encode($params),
                'status' => P2pIdempotentService::STATUS_CALLBACK,
                'result' => P2pIdempotentService::RESULT_SUCC,
            );

            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($orderId,$orderData,P2pIdempotentService::RESULT_WAIT);
            if($affectedRows == 0){
                Alarm::push(P2pDepositoryService::ALARM_DT_DEPOSITORY,'智多新投资取消'," orderId:".$orderId);
                throw new \Exception("订单信息保存失败",self::BID_SAVE_ORDER_ERROR);
//                $newOrderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
//
//                if($newOrderInfo['result'] == P2pIdempotentService::RESULT_FAIL){
//                    //throw new \Exception("订单信息保存失败",self::BID_SAVE_ORDER_ERROR);
//                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $orderId, "智多新取消投资开始")));
//                    Alarm::push(P2pDepositoryService::ALARM_DT_DEPOSITORY,'智多新投资取消'," orderId:".$orderId);
//                    //取消智多新投资
//                    $request->setVars(array(
//                        'token' => $orderId,
//                        'userId' => $userId,
//                    ));
//
//                    $cancResponse = $rpc->go('NCFGroup\Duotou\Services\DealLoan', 'rollbackDealLoan', $request, 2, 3);
//                    if(!$cancResponse){
//                        throw new \Exception("系统繁忙，请稍后再试");
//                    }
//                    if(isset($cancResponse['data']) && $cancResponse['data'] === false){
//                        if($response['errMsg']){
//                            throw new \Exception("智多新投资取消失败");
//                        }
//                    }
//                    return false;
//                }
            }
            $this->bidBonusTransfer($orderId,$bonusInfo, $userId, $dtDealName, $dealId);


            $user = UserModel::instance()->find($userId);
            $user->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
            $bizToken = array('dealId'=>$dealId,'dealLoadId'=>$loadId);
            $note = "编号 {$dealId},{$dtDealName}";
            $logInfo = '智多鑫-转入本金冻结';
            $res = $user->changeMoney($money, $logInfo, $note, 0, 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
            if(!$res){
                throw new \Exception("智多新本金冻结失败");
            }
            $moneyInfo = array(UserLoanRepayStatisticsService::DT_NOREPAY_PRINCIPAL => $money);
            if (UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($userId, $moneyInfo) === false) {
                throw new \Exception("user loan repay statistic error");
            }

            //添加投资使用优惠码
            $jobs_model = new JobsModel();
            $accrueInterestTime = to_timespan(date("Y-m-d", time() + 86400), 'Y-m-d');
            $param = array(
                'dealLoadId' => $loadId,
                'dealId' => $dealId,
                'userId' => $userId,
                'money' => $money,
                'couponId' => $couponId, //优惠码
                'accrueInterestTime' => $accrueInterestTime,
            );
            $jobs_model->priority = JobsModel::PRIORITY_DTB_COUPON;
            $r = $jobs_model->addJob('\core\service\duotou\DtCouponService::bid', $param);
            if ($r === false) {
                throw new \Exception("添加投资使用优惠码失败");
            }

            // 触发投资券、礼券
            $annualizedAmount = round($money * $lockPeriod / 360, 2);
            $extra = array(
                'duotou_activity_id' => $activityId,
                'duotou_activity_rate' => $activityRate,
                'duotou_lock_period' => $lockPeriod,
                'dealBidDays' => $lockPeriod
            );

            // 对于带锁定期的智多新，按照投资时的实际金额触发；对于无锁定期的智多新，不触发礼券
            if ($activityId > 0) {
                // 邀请人在黑名单的延迟触发
                $referUserId = 0;
                $coupon_bind_service = new CouponBindService();
                $coupon_bind = $coupon_bind_service->getByUserId($userId);
                if (!empty($coupon_bind)) {
                    $short_alias = $coupon_bind['short_alias'];
                    $couponService = new CouponService();
                    $referUserId = $couponService->getReferUserId($short_alias);
                }
                if(!($referUserId && BwlistService::inList('DT_INVITER_BLACKLIST', $referUserId))) {
                    // 这里需要保证action值正确，这里默认值为多投复投，现在oto_acquire_log是用user_id, deal_load_id和action做唯一索引
                    $action = CouponGroupEnum::TRIGGER_DUOTOU_REPEAT_DOBID;
                    if ($response['data']['isFirst']) {
                        $action = CouponGroupEnum::TRIGGER_DUOTOU_FIRST_DOBID;
                    }
                    O2OService::triggerO2OOrder(
                        $userId,
                        $action,
                        $loadId,
                        0,
                        $money,
                        $annualizedAmount,
                        CouponGroupEnum::CONSUME_TYPE_DUOTOU,
                        CouponGroupEnum::TRIGGER_TYPE_P2P,
                        $extra
                    );
                }
            }

            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();

            if($ex->getCode() == self::BID_SAVE_ORDER_ERROR){
                $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
                if($orderInfo && $orderInfo['result'] == P2pIdempotentService::RESULT_SUCC){
                    return true;
                }
            }
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $dealId, $userId, $money, $couponId,$loadId, "智多新投资失败 fail:".$ex->getMessage())));
            throw new \Exception($ex->getMessage(),-1);
        }
        return true;
    }

    public function handleRpcResponse($orderInfo,$dtDealName=''){
        $return = array('errCode' => 0, 'errMsg' => "", 'data' => '');
        $dealId = $orderInfo['deal_id'];
        $userId = $orderInfo['loan_user_id'];
        $money = $orderInfo['money'];

        $params = json_decode($orderInfo['params'],true);
        //投资劵消费同步
//        $discountService = new DiscountService();
//        $discountService->consumeEvent($orderInfo['loan_user_id'], $orderInfo['discount_id'], $orderInfo['load_id'], 'duotou',
//            0, 0, CouponGroupEnum::CONSUME_TYPE_DUOTOU);

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
            'projectName' => $dtDealName);
        return $return;
    }

    public function errCatch($deal_id) {
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
    public function bidCheck(array $userInfo, $dealId, $money) {
        $userId = $userInfo['id'];
        $dealService = new DealService();
        $dcs = new DealCompoundService();

        if ($userInfo['idcardpassed'] == 3) {
            throw new \Exception("身份信息未审核", self::NOT_AUDIT_ID);
        }
        if (intval($userInfo['mobilepassed']) == 0 || intval($userInfo['idcardpassed']) != 1 || !$userInfo['real_name']) {
            throw new \Exception("未绑定手机", self::NOT_BIND_MOBILE);
        }
        if (app_conf('PAYMENT_ENABLE')) {
            $ps = new PaymentService();
            $res = $ps->register($userId);
            if ($res == PaymentService::REGISTER_FAILURE) {
                throw new \Exception("增加支付平台用户注册", self::PAYMENT_REGISTER__ERROR);
            }
        }

        $age_check = $dealService->allowedBidByCheckAge($userInfo);
        if ($age_check['error'] == true) {
            throw new \Exception("本项目仅限18岁及以上用户投资", self::NOT_ARRIVE_AGE);
        }

        // 限制投资
        $userCarryService = new UserCarryService();
        $user_money_limit = $userCarryService->canWithdrawAmount($userId, $money, true);
        if ($user_money_limit === false){
            throw new \Exception($GLOBALS['lang']['FORBID_BID']); // 账户无法投资
        }

        return true;
    }



    /**
     * 检查用户身份可以进行投资该标的
     * @param object $deal
     * @param object $user
     * @param float $money
     * @param int $source_type
     * @param string $coupon_id
     * @param int $site_id
     * @return bool
     */
    public function checkCanBid($deal, $user, $money) {
        $user = UserModel::instance()->find($user['id']);
        if(!$user){
            throw new \Exception("用户不存在");
        }

        if(!$deal){
            throw new \Exception($GLOBALS['lang']['PLEASE_SPEC_DEAL']); // 未指定投标
        }

        $user_service = new UserService();
        //多投标的非投资户不允许投资
        if(!$user_service->allowAccountLoan($user['user_purpose'])){
            return array('errCode' => self::ONLY_INVESTMENT_USER_CAN_BID, 'errMsg' => $GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID'], 'data' => false);
        }

        if ($user['idcardpassed'] == 3) {
            throw new \Exception("身份信息未审核", self::NOT_AUDIT_ID);
        }
        if (intval($user['mobilepassed']) == 0 || intval($user['idcardpassed']) != 1 || !$user['real_name']) {
            throw new \Exception("未绑定手机", self::NOT_BIND_MOBILE);
        }

        $supService = new SupervisionService();
        $grantInfo = $supService->checkAuth($user['id'],SupervisionService::GRANT_TYPE_ZDX);
        if(!empty($grantInfo)){
            throw new \Exception("未开通智多鑫投资权限", self::BID_FORBIDDEN);
        }

        // 限制投资
        $userCarryService = new UserCarryService();
        $isSupervision = ($deal['report_status'] == 1) ? true : false;
        $user_money_limit = $userCarryService->canWithdrawAmount($user['id'], $money, $isSupervision);
        if ($user_money_limit === false){
            throw new \Exception($GLOBALS['lang']['FORBID_BID']); // 账户无法投资
        }

         // 企业融资户交易拦截
        if ($user['user_purpose'] == EnterpriseModel::COMPANY_PURPOSE_FINANCE) {
            throw new \Exception('投资功能仅对企业投资户开放', self::BID_FORBIDDEN);
        }

        $deal_service = new DealService();
        $age_check = $deal_service->allowedBidByCheckAge($user);
        if($age_check['error'] == true){
            throw new \Exception("本项目仅限18岁及以上用户投资", self::NOT_ARRIVE_AGE);
        }

        return true;
    }


    /**
     * 智多新红包消费记录资金记录
     * @param $orderId
     * @param $bonusInfo
     * @param $userId
     * @param $dealName
     * @return bool
     * @throws \Exception
     */
    public function bidBonusTransfer($orderId,$bonusInfo, $receiverId, $dealName, $dealId){
        $receiverAmount = $bonusInfo['money'];
        $receiverInfo = UserModel::instance()->findViaSlave($receiverId);
        $receiverInfo->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
        $transferService = new TransferService();

        foreach ($bonusInfo['accountInfo'] as $payAccount) {
            $payerId = $payAccount['rpUserId'];
            $payerInfo = UserModel::instance()->findViaSlave($payerId);
            $payerInfo->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
            $payAmount = $payAccount['rpAmount'];

            $payerType = app_conf('NEW_BONUS_TITLE') . '充值';
            $payerNote = $receiverId ."使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$dealName}";
            $payerBizToken = ['dealId' => $dealId, 'orderId' => $orderId];
            $receiverType = '使用' . app_conf('NEW_BONUS_TITLE') . '充值';
            $receiverNote = "使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$dealName}";
            $receiverBizToken = ['dealId' => $dealId, 'orderId' => $orderId];
            $transferService->transferByUser($payerInfo, $receiverInfo, $payAmount, $payerType, $payerNote, $receiverType, $receiverNote, $orderId, $payerBizToken, $receiverBizToken);
        }
        return true;
    }

    /**
     * 多投投资P2p底层资产成功回调
     * @param $token 多投幂等token
     * @param $loadId p2p投资记录ID
     * @throws \Exception
     */
    public function bidSuccessCallback($orderId) {
        $rpc = new Rpc('duotouRpc');
        $request = new RequestCommon();
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if(!$orderInfo){
            throw new \Exception("订单信息不存在 orderId:".$orderId);
        }
        $loadId = $orderInfo['load_id'];
        $params = json_decode($orderInfo['params'],true);

        $transParams = $params['dtParams'];
        $request->setVars(array('token' => $orderId,'loadId' => $loadId,'transParams'=>$transParams));
        $response = $rpc->go('NCFGroup\Duotou\Services\LoanMappingContract', 'bidSuccessCallback', $request);

        if (!$response || ($response['data'] === false) ) {
            Logger::error(implode('|',  array(__CLASS__,__FUNCTION__, "通知智多新投资回调失败 orderId:{$orderId},loadId:{$loadId}")));
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
    public function canDtUseBonus($activityInfo, $userInfo = array()) {
        $lockPeriod = isset($activityInfo['lock_day']) ? intval($activityInfo['lock_day']) : 0;
        return  ($lockPeriod <= 1) ? intval(\libs\utils\ABControl::getInstance()->hit('useBonusForDt', $userInfo)) : 1;
    }

}
