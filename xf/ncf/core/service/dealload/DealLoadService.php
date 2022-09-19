<?php
/**
 * 投资相关
 * @date 2018-07-06
 */

namespace core\service\dealload;

use core\enum\DealEnum;
use core\enum\DealLoanTypeEnum;
use core\enum\P2pIdempotentEnum;
use core\enum\P2pDepositoryEnum;
use core\enum\SupervisionEnum;
use core\enum\CouponEnum;
use core\enum\JobsEnum;
use core\enum\UserAccountEnum;
use core\enum\MsgBoxEnum;
use core\enum\DealLoanRepayEnum;
use core\enum\CouponGroupEnum;
use core\service\BaseService;
use core\data\DealData;
use core\service\user\UserService;
use core\service\account\AccountService;
use libs\utils\Logger;
use libs\utils\Monitor;
use core\dao\deal\DealLoadModel;
use core\dao\jobs\JobsModel;
use core\dao\deal\DealLoanTypeModel;
use core\dao\dealqueue\DealQueueModel;
use core\service\project\DealProjectRiskAssessmentService;
use core\service\deal\DealService;
use core\service\dealgroup\DealGroupService;
use core\service\deal\P2pDealBidService;

use libs\utils\Risk;
use NCFGroup\Common\Library\GTM\GlobalTransactionEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;

use core\service\user\UserCarryService;

use core\tmevent\bid\BankbidEvent;
use core\tmevent\bid\P2pbidEvent;
use core\tmevent\discount\DiscountConsumeEvent;
use core\tmevent\reserve\ProcEvent as ReserveProcEvent;
use core\service\coupon\CouponService;
use core\service\deal\P2pIdempotentService;
use core\service\user\VipService;
use core\dao\deal\DealModel;
use core\service\msgbus\MsgbusService;
use core\enum\MsgbusEnum;
use core\service\contract\SendContractService;
use core\service\o2o\DiscountService;
use core\service\duotou\DuotouService;
use core\service\msgbox\MsgboxService;
use core\service\bonus\BonusService;
use web\controllers\rss\Deal;


class DealLoadService extends BaseService {

    // 错误标识
    public static $fatal = 0;

    public static $bidTime = 0;

    //行为跟踪ID
    public $track_id = 0;
    //渠道标签
    public $euid = '';

    /**
     *  投资逻辑
     * @param $user_id
     * @param $deal
     * @param $money
     * @param $coupon_id
     * @param int $source_type
     * @param int $site_id
     * @param string $discount_id
     * @param int $discount_type
     * @param array $optionParams
     */
    public function bid($user_id, $deal, $money, $coupon_id, $source_type = 0, $site_id = 1, $discount_id = '', $discount_type = 1,$optionParams=array()) {

        $deal_id = $deal['id'];
        $log_info = array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id);
        Logger::info(implode(" | ", array_merge($log_info,array("start"))));
        $res = array(
            'error' => true,
            'msg' => '',
        );
        self::$fatal = 1;
        self::$bidTime = microtime(true);
        register_shutdown_function(array($this, "errCatch"),$deal_id);
        $deal_data = new DealData();
        \libs\utils\Monitor::add('PH_DOBID_START');
        $lock = $deal_data->enterPool($deal_id);
        if ($lock === false) {
            // 正常退出
            self::$fatal = 0;
            $res['msg'] = "抢标人数过多，请稍后再试";
            \libs\utils\Monitor::add('PH_DOBID_FAILED_LOCKED');
            Logger::info(implode(" | ", array_merge($log_info,array("bid lock fail", $res['msg'],"line:" . __LINE__))));
            return $res;
        }

        // 随心约的产品检查是实时的，pc app wap 是根据当时上标的项目评级检查
        if(!in_array($source_type,array(DealLoadModel::$SOURCE_TYPE['dtb'],DealLoadModel::$SOURCE_TYPE['reservation']))){
            $deal_project_riskAssessment_service = new DealProjectRiskAssessmentService();
            $deal_project_riskAssessment_ret = $deal_project_riskAssessment_service->checkRiskBid($deal['project_id'], $user_id, true);
            if ($deal_project_riskAssessment_ret['result'] == false) {
                $res['msg'] = '当前您的风险承受能力为"' . $deal_project_riskAssessment_ret['user_risk_assessment'] . '"';
                $res['remaining_assess_num'] = $deal_project_riskAssessment_ret['remaining_assess_num'];
                $deal_data->leavePool($deal_id);
                \libs\utils\Logger::info(implode(" | ", array_merge($log_info,array("fail", $res['msg'], "line:" . __LINE__))));
                return $res;
            }
        }
        // 网络请求用户信息
        $user = UserService::getUserById($user_id,'id,user_name,real_name,email,idno,country_code,mobile_code,mobile,idcardpassed,user_purpose,
    group_id,id_type,user_type,supervision_user_id,payment_user_id,is_effect,mobilepassed,byear,bmonth,bday,
    force_new_passwd,is_dflh,sex,mobiletruepassed,create_time');
        if (empty($user)){
            self::$fatal = 0;
            $res['msg'] = "用户信息不存在";
            $deal_data->leavePool($deal_id);
            Logger::error(implode(" | ", array_merge($log_info,array(" user info exist fail", $res['msg']))));
            return $res;
        }

        // 检查用户账户类型
        $checkAccountType = AccountService::allowAccountLoan($user['user_purpose']);
        if(empty($checkAccountType)){
            self::$fatal = 0;
            $res['msg'] = '用户账户类型错误';
            $deal_data->leavePool($deal_id);
            Logger::error(implode(" | ", array_merge($log_info,array($res['msg']) )));
            return $res;
        }
        // 通过userid 转换成账户信息id
        $accountId = AccountService::getUserAccountId($user_id,UserAccountEnum::ACCOUNT_INVESTMENT);
        if(empty($accountId)){
            self::$fatal = 0;
            $res['msg'] = '用户账户类型错误';
            $deal_data->leavePool($deal_id);
            Logger::error(implode(" | ", array_merge($log_info,array($res['msg'],'accountid empty') )));
            return $res;
        }

        //BID_MORE 判断
        $bidMore = UserService::checkUserTag('BID_MORE',$user_id);
        $deal_service = new DealService();
        // 不需要查已还清的
        $deal_model = DealModel::instance()->getDealInfo($deal_id);

        if(\es_cookie::is_set('euid')){
            $this->euid = \es_cookie::get('euid');
        }
        if(\es_session::get('track_id')){
            $this->track_id = \es_session::get('track_id');
        }

        //app接口调用，使用optionParams传递euid
        $euid = isset($optionParams['euid']) ? $optionParams['euid'] : "";
        if (!empty($euid)){
            $this->euid = $euid;
        }
        $isCanUseBonus = DealEnum::CAN_USE_BONUS;
        try {
            // 1、判断是否符合投资条件 不能包含余额
            $this->checkCanBid($deal, $user, $money, $source_type, $coupon_id, $site_id);

            // 是否走存管
            $isP2pBid = true;
            $deal['isDTB'] = $deal_service->isDealDT($deal_id);


            // 2、是否免密的投资判断 非免密投资银行已经完成投资处理不需要在进行资金划转
            $orderInfo = isset($optionParams['orderInfo']) ? $optionParams['orderInfo'] : array();
            $orderParams = (isset($orderInfo['params']) && !empty($orderInfo['params'])) ? json_decode($orderInfo['params'],true) : array();

            //基于TM 的投资逻辑 先声明因为要用到事务ID
            $gtm = new GlobalTransactionManager();
            $gtm->setName('phbid');
            if(!empty($orderInfo)){
                $transferMoney = false;
                $globalOrderId = $orderInfo['order_id'];
                $orderParams = json_decode($orderInfo['params'],true);
                $bonusInfo = isset($orderParams['bonusInfo']) ? $orderParams['bonusInfo'] : array();
            }else{
                $globalOrderId = $gtm->getTid();
                // 投资不在有余额划转--直接屏蔽
                $transferMoney  = false;
            }

            if (isset($optionParams['canUseBonus']) && empty($optionParams['canUseBonus'])){
                $isCanUseBonus = 0;
            }
            // 红包使用总开关
            $isBonusEnable = BonusService::isBonusEnable();
            if (empty($isBonusEnable)){
                $isCanUseBonus = 0;
            }
            // 3、余额验证 (余额验证不能放在前面,因为存管行有可能已经完成投资，这时候判断余额是不严谨的 所以需要单独摘出来) 银行验密投资不需要再次验证余额
            if(empty($orderInfo)){
                $moneyInfo = AccountService::getAccountMoneyInfo($accountId,$money,$globalOrderId);

                //多投投资时候不使用红包
                if ($deal['isDTB'] === true || empty($isCanUseBonus)){
                    $moneyInfo['bonus'] = 0;
                    $moneyInfo['bonusInfo'] = array();
                    $moneyInfo['bonusInfo']['accountInfo'] = array();
                    Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "多投投资不能使用红包 orderId:".$globalOrderId.' isCanUseBonus '.$isCanUseBonus)));
                }
                $totalCanBidMoney = bcadd($moneyInfo['bankMoney'],$moneyInfo['bonusMoney'],2);
                if((bccomp($money,$totalCanBidMoney,2) == 1)){
                    throw new \Exception('余额不足，请先进行充值');
                }
                $bonusInfo = $moneyInfo['bonusInfo'];
            }


            // 5、红包查询
            if(!isset($bonusInfo['accountInfo']) || empty($isCanUseBonus)){
                Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "未查询到红包信息 orderId:".$globalOrderId.' | isCanUseBonuse '.$isCanUseBonus)));
                $bonusMoney = 0;
            }else{
                $bonusMoney = $bonusInfo['money'];
            }

            if ($bonusMoney > 0) {
                $limitBonus = app_conf('BONUS_USE_MAX_VALUES');
                if ($limitBonus > 0 && $bonusMoney > $limitBonus) {
                    $limitBonus = $limitBonus / 10000;
                    throw new \Exception("单笔投资使用红包金额最多{$limitBonus}万~");
                }
            }


        } catch (\Exception $e) {
            $res['msg'] = $e->getMessage();
            $deal_data->leavePool($deal_id);
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,"fail", $e->getMessage())));
            return $res;
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,"GTM start")));

        // 6、添加event
        try {
            //预约处理
            if (!empty($optionParams['reserveInfo'])) {
                $reserveInfo = $optionParams['reserveInfo'];
                $gtm->addEvent(new ReserveProcEvent($reserveInfo['id'], $reserveInfo['user_id'], $money, $deal_id, $globalOrderId));
            }

            // 银行免密投资
            if(empty($orderInfo) && $isP2pBid && $deal['isDTB'] == false){
                $gtm->addEvent(new BankbidEvent($globalOrderId,$deal_id,$user['id'],$money,$bonusInfo));
            }

            // 红包消费
            if(bccomp($bonusMoney,'0.00',2) == 1){
                $gtm->addEvent(new \core\tmevent\bid\BonusConsumeEvent($user['id'],$bonusInfo,$globalOrderId,$deal_model['name'], 10 + $deal_model['deal_type']));
            }
            // 投资卷
            if ($discount_id > 0) {
                //加息券需要依赖透传的年化额
                $extraInfo = array(
                    'annualizedAmount' => DealService::getAnnualizedAmountByDealIdAndAmount($deal_id, $money),
                    'isP2p' => 1
                );
                $gtm->addEvent(new DiscountConsumeEvent(
                    $user['id'],
                    $discount_id,
                    $globalOrderId,
                    $discount_type,
                    time(),
                    1,
                    $extraInfo
                ));
            }

            $bidParams = array(
                'couponId' => $coupon_id,
                'sourceType' => $source_type,
                'siteId' => $site_id,
                'discountId' => $discount_id,
                'discountType' => $discount_type,
                'bidMore' => $bidMore,
                'bonusInfo' => $bonusInfo,
                'euid' => $this->euid,
                'trackId' => $this->track_id,
            );
            $bidParams = array_merge($bidParams,$orderParams);

            $gtm->addEvent(new P2pbidEvent(
                $globalOrderId,
                $deal_id,
                $user['id'],
                $money,
                $bidParams
            ));  // p2p投资

            $bidRes = $gtm->execute(); // 同步执行
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id, "GTM Error" . $e->getMessage())));
            return $this->getBidResult($deal_data,$deal_id,$discount_id,$globalOrderId);
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,"GTM end")));

        $loadId = P2pIdempotentService::getLoadIdByOrderId($globalOrderId);
        if($bidRes === true && $loadId !== false){
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,$loadId,$globalOrderId, "succ")));
        }else{
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $coupon_id, $site_id,$loadId,$globalOrderId, "fali")));
        }
        return $this->getBidResult($deal_data,$deal_id,$discount_id,$globalOrderId,$loadId,$bidRes,$money,$bonusInfo);
    }
    /**
     * 银行验密投资 回跳时调用此方法
     * @param $orderId
     * @param $uid
     * @param $amount
     * @param $status 回调状态 'S' or 'F'
     */
    public function bidForBankSecret($orderId,$uid,$status){
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        $res['error'] = true;
        $res['msg'] = "出借失败，请稍后再试";
        $logParams = "uid:{$uid},orderId:{$orderId},status:{$status}";

        if(!in_array($status,array(SupervisionEnum::RESPONSE_FAILURE,SupervisionEnum::RESPONSE_SUCCESS))){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "fail","存管行返回的回调状态值错误 params:".$logParams)));
            return $res;
        }

        if(!$orderInfo){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "fail","存管行返回的订单不存在 params:".$logParams)));
            return $res;
        }

        $userId = $orderInfo['loan_user_id'];
        if($userId != $uid){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "fail","投资用户与实际用户不符 orderId:".$orderId)));
            return $res;
        }

        if($status == SupervisionEnum::RESPONSE_FAILURE){
            return $res;
        }

        $dealService = new DealService();

        $dealId = $orderInfo['deal_id'];
        $deal = $dealService->getDeal($dealId);
        if(!$deal){
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "fail","投资标的不存在 orderId:".$orderId." dealId:".$dealId)));
            return $res;
        }


        $money = $orderInfo['money'];
        $orderParams = json_decode($orderInfo['params'],true);
        $couponId = isset($orderParams['couponId']) ? $orderParams['couponId'] : false;
        $sourceType = isset($orderParams['sourceType']) ? $orderParams['sourceType'] : false;
        $siteId = isset($orderParams['siteId']) ? $orderParams['siteId'] : false;
        $jfOrderId = isset($orderParams['jforderId']) ? $orderParams['jforderId'] : false;
        $discountId = isset($orderParams['discountId']) ? $orderParams['discountId']:false;
        $discountType = isset($orderParams['discountType']) ? $orderParams['discountType'] : false;
        $orderInfo['canUseBonus'] = isset($orderParams['canUseBonus']) ? $orderParams['canUseBonus'] : DealEnum::CAN_USE_BONUS;;
        $orderInfo['fingerprint'] = isset($orderParams['fingerprint']) ? $orderParams['fingerprint'] : Risk::getFinger();
        if($orderInfo['status'] == P2pIdempotentEnum::STATUS_CALLBACK && $orderInfo['result'] == P2pIdempotentEnum::RESULT_FAIL) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $orderId, "免密投资订单已通过回调处理失败 orderId:" . $orderId)));
            return $res;
        }elseif($orderInfo['status'] == P2pIdempotentEnum::STATUS_CALLBACK && $orderInfo['result'] == P2pIdempotentEnum::RESULT_SUCC) {
            // 投资成功
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $orderId, "免密投资订单已通过回调处理成功 orderId:" . $orderId)));

            $loadId = $orderInfo['load_id'];
            $res['error'] = false;
            $res['msg'] = "投资成功";
            $res['load_id'] = $loadId;
            $res['deal_status'] = $deal['deal_status'];
            $res['deal_id'] = $deal['id'];
            $res['money']   = $orderInfo['money'];
            $res['discountId'] = $discountId;
            $res['discountType'] = $discountType;
            $res['discountGoodsPrice'] = isset($orderParams['discountGoodsPrice']) ? $orderParams['discountGoodsPrice']: '';
            return $res;
        }
        UserCarryService::$checkWithdrawLimit = false; //验密投资回调绕过限制提现
        $bidRes = $this->bid($userId,$deal,$money,$couponId,$sourceType,$siteId,$discountId,$discountType,array('orderInfo'=>$orderInfo));


        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        // 再次更新deal信息
        $deal = DealModel::instance()->find($dealId);

        if($bidRes['error'] === true){
            if($orderInfo['result'] == P2pIdempotentEnum::RESULT_SUCC){
                $res['error']   = false;
                $res['msg']     = "投资成功";
                $res['load_id'] = $orderInfo['load_id'];
                $res['deal_status'] = $deal['deal_status'];
                $res['deal_id'] = $deal['id'];
                $res['money']   = $orderInfo['money'];
                $res['discountId'] = $discountId;
                $res['discountType'] = $discountType;
                $res['discountGoodsPrice'] = isset($orderParams['discountGoodsPrice']) ? $orderParams['discountGoodsPrice']: '';
            }
        }else{
            $res['error']   = false;
            $res['msg']     = "投资成功";
            $res['load_id'] = $orderInfo['load_id'];
            $res['deal_status'] = $deal['deal_status'];
            $res['deal_id'] = $deal['id'];
            $res['money']   = $orderInfo['money'];
            $res['discountId'] = $discountId;
            $res['discountType'] = $discountType;
            $res['discountGoodsPrice'] = isset($orderParams['discountGoodsPrice']) ? $orderParams['discountGoodsPrice']: '';
        }
        return $res;
    }
    /**
     * 投资成功返回信息
     * @param DealData $dealData
     * @param $dealId
     * @param $discountId
     * @param $globalOrderId
     * @param $loadId
     * @param bool|false $bidRes 投资结果
     * @param bool|false $bidMoney 投资金额
     * @param bool|false $bonusInfo 投资使用红包信息
     * @return mixed
     */
    public function getBidResult(DealData $dealData,$dealId,$discountId,$globalOrderId,$loadId=false,$bidRes=false,$bidMoney=false,$bonusInfo=false){
        // 不需要查询已还清的
        $dealModel = DealModel::instance()->find($dealId);
        if(!$loadId){
            $dealService = new DealService();
            $isDTB = $dealService->isDealDT($dealId);
            // 出现异常 实际理财没有投资成功而存管投资成功 智多鑫逻辑特殊不走正常投资取消
            if($bidRes === true && $dealModel['report_status'] == DealEnum::DEAL_REPORT_STATUS_YES && $isDTB === false){
                $cnacService = new P2pDealBidService();
                $cancRes = $cnacService->dealBidCancelRequest($globalOrderId);
                // 保证一定要通知到
                if(!$cancRes){
                    $function = '\core\service\deal\P2pDealBidService::dealBidCancelRequest';
                    $param = array($globalOrderId);
                    $job_model = new JobsModel();
                    $job_model->priority = JobsEnum::BID_CANCEL_REQUEST;
                    $add_job = $job_model->addJob($function, $param,false,10);
                    if (!$add_job) {
                        $log_info = array(__CLASS__, __FUNCTION__, APP, $dealId, $globalOrderId);
                        Logger::info(implode(" | ", array_merge($log_info,array(" dealBidCancelRequest fail"))));
                        \libs\utils\Alarm::push(P2pDepositoryEnum::ALARM_BANK_CALLBAK,'投资取消通知银行jobs添加失败'," dealId:{$dealId}, orderId:".$globalOrderId);
                    }
                }
            }
            $res['error'] = true;
            $res['msg'] = "投资失败，请稍后再试";
            $dealData->leavePool($dealId);
        }else{
            \libs\utils\Monitor::add('PH_DOBID_SUCCESS');
            // 投资成功，此时可以释放资源
            $dealData->leavePool($dealId);
            self::$fatal = 0;
            // 投资卷相关
           // \SiteApp::init()->cache->set(DiscountService::CACHE_CONSUME_PREFIX.$discountId, 1, 3600);//投资劵消费缓存
            $res['error'] = false;
            $res['msg'] = "投资成功";
            $res['order_id'] = $globalOrderId;
            $res['load_id'] = $loadId;
            $res['deal_status'] = $dealModel['deal_status'];
            $res['deal_id'] = $dealModel['id'];
            $res['money'] = $bidMoney;
            $res['use_bonus_money'] = $bonusInfo['money'] ?: 0;
        }
        // 银行回调并发锁解锁
        $dealBidService = new P2pDealBidService();
        $dealBidService->delBidLock($globalOrderId);

        $cost = round(microtime(true) - self::$bidTime, 3);
        Logger::info('PH_DealLoadService::bid succ cost:'.$cost);
        if($cost > 1){
            Monitor::add('PH_BID_TIME');
        }
        return $res;
    }


    /**
     * 判断定制标逻辑
     * @param array $user = array('id', 'create_time')
     */
    public function canUseDeal($deal, $user, $sourceType) {
        // 新手专享
        if($deal['deal_crowd']=='1'){
            $dealloadModel = new DealLoadModel();
            $isFirstBid = $dealloadModel->checkFirstBid($user['id']);
            if (empty($isFirstBid)) {
                return false;
            }
        }

        // 专享标
        if($deal['deal_crowd'] == '2'){
            $deal_group_service = new DealGroupService();
            $group_check = $deal_group_service->checkUserDealGroup($deal['id'], $user['id']);
            if(!$group_check){
                return false;
            }
        }

        // 手机专享
        // 手机新手专享
        $deal_service = new DealService();
        $allowdBid = $deal_service->allowedBidBySourceType($sourceType, $deal['deal_crowd'], $user);
        if($allowdBid['error'] == true) {
            return false;
        }

        // 指定用户可投
        if($deal['deal_crowd'] == '16' && $deal['deal_specify_uid'] != $user['id']) {
            return false;
        }

        // 老用户专享
        if ($deal['deal_crowd'] == DealEnum::DEAL_CROWD_OLD_USER) {
            $rule = app_conf('RULE_OLD_USER');
            if (!empty($rule)) {
                $arr = explode(';', $rule);
                if (2 == count($arr)) {
                    if (to_date($user['create_time'], 'Ymd') >= $arr[0]) {
                        return false;
                    }
                }
            }
        }


        // VIP用户专享
        if($deal['deal_crowd'] == DealEnum::DEAL_CROWD_VIP) {
            // 指定vip专享
            $vip = VipService::getVipInfoAndBidErrMsg($user['id'],$deal['deal_specify_uid']);
            if (empty($vip['vipInfo']) || $deal['deal_specify_uid'] > $vip['vipInfo']['service_grade']) {
                return false;
            }
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
    public function checkCanBid($deal, $user, $money, $source_type, $coupon_id, $site_id,$bonusInfo = array()) {

        if(!$user){
            throw new \Exception("用户不存在");
        }
        // 投资智多鑫的
        if(isset($deal['isDTB']) && $deal['isDTB'] === true){
            return true;
        }

        if(!$deal){
            throw new \Exception($GLOBALS['lang']['PLEASE_SPEC_DEAL']); // 未指定投标
        }

        if ($deal['report_status'] != DealEnum::DEAL_REPORT_STATUS_YES){
            throw new \Exception('该项目未到存管行报备');
        }
        $userCarryService = new UserCarryService();
        $isSupervision =  true;
        $user_money_limit = $userCarryService->canWithdrawAmount($user, $money, $isSupervision,true,$bonusInfo);
        if ($user_money_limit === false){
            throw new \Exception($GLOBALS['lang']['FORBID_BID']); // 账户无法投资
        }

        if($deal['user_id'] == $user['id']){
            throw new \Exception($GLOBALS['lang']['CANT_BID_BY_YOURSELF']);
        }
        if($deal['is_visible'] != 1){

            throw new \Exception($GLOBALS['lang']['DEAL_FAILD_OPEN']);
        }
        if(floatval($deal['progress_point']) >= 100){
            throw new \Exception($GLOBALS['lang']['DEAL_BID_FULL']);
        }

        if($deal['deal_status'] != 1 && ( ($deal['deal_status'] == 0 && $source_type != DealLoadModel::$SOURCE_TYPE['appointment']) || ($deal['deal_status'] == 6 && $source_type != DealLoadModel::$SOURCE_TYPE['reservation']) )){
            throw new \Exception($GLOBALS['lang']['DEAL_FAILD_OPEN']);
        }

        // 定时标
        if ($deal['start_loan_time'] && $deal['start_loan_time']>get_gmtime()) {
            throw new \Exception("该项目将于" . to_date($deal['start_loan_time'], "Y-m-d H点m分") . "开始，请稍后再试");
        }

        $deal_service = new DealService();
        // 检查最小投资年龄
        $age_check = $deal_service->allowedBidByCheckAge($user);
        if($age_check['error'] == true){
            throw new \Exception($age_check['msg']);
        }

        // 手机专享标
        $allowdBid = $deal_service->allowedBidBySourceType($source_type, $deal['deal_crowd'], $user);
        if($allowdBid['error'] == true) {
            throw new \Exception($allowdBid['msg']);
        }

        // 老用户专享逻辑
        if ($deal['deal_crowd'] == DealEnum::DEAL_CROWD_OLD_USER) {
            $rule = app_conf('RULE_OLD_USER');
            if (!empty($rule)) {
                $arr = explode(';', $rule);
                if (2 == count($arr)) {
                    if (to_date($user['create_time'], 'Ymd') >= $arr[0]) {
                        throw new \Exception($arr[1]);
                    }
                }
            }
        }


        // 检查是否新手
        if($deal['deal_crowd']=='1'){
            $dealloadModel = new DealLoadModel();
            $isFirstBid = $dealloadModel->checkFirstBid($user['id']);
            if (empty($isFirstBid)) {
                throw new \Exception("该项目为新手专享项目，只有初次出借的新用户可以出借");
            }
        }

        if($deal['deal_crowd'] == '16' && $deal['deal_specify_uid'] != $user['id']) {
            throw new \Exception( '该项目为专享标，只有特定用户才可出借');
        }


        //指定vip专享
        if($deal['deal_crowd'] == DealEnum::DEAL_CROWD_VIP) {
            // 指定vip专享
            $vip = VipService::getVipInfoAndBidErrMsg($user['id'],$deal['deal_specify_uid']);
            if (empty($vip['vipInfo']) || $deal['deal_specify_uid'] > $vip['vipInfo']['service_grade']) {
                throw new \Exception($vip['vipBidMsg']);
            }
        }


        // 个人/企业用户
        if ($deal['bid_restrict'] != 0) {
            if (UserService::isEnterprise($user['id'])) {
                if ($deal['bid_restrict'] == 1) {
                    throw new \Exception("本产品为个人会员专享");
                }
            } else {

                if ($deal['bid_restrict'] == 2) {
                    throw new \Exception("本产品为企业会员专享");
                }
            }
        }
        //特定用户组
        if($deal['deal_crowd'] == '2'){
            $deal_group_service = new DealGroupService();
            $group_check = $deal_group_service->checkUserDealGroup($deal['id'], $user['id'],$user['group_id']);
            if(!$group_check){
                throw new \Exception( "专享标为平台为特定用户推荐的优惠项目，只有特定用户才可以出借");
            }
        }

        // 验证优惠码有效性
        $coupon_id = ($coupon_id == CouponEnum::SHORT_ALIAS_DEFAULT) ? '' : $coupon_id;
        if($deal['must_coupon'] == 1 && empty($coupon_id)){
            throw new \Exception("该项目为专享标，请使用专享优惠码");
        }

        if ($coupon_id) {
            $coupon_id = str_replace(' ','',$coupon_id);
            $coupon = CouponService::queryCoupon($coupon_id,true);
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal['id'], $user['id'], $money, $coupon_id, $site_id, "coupon result", json_encode($coupon))));
            if (!empty($coupon)) {
                if (!$coupon['is_effect']) {
                    throw new \Exception("您使用的优惠码不适应此项目，请输入有效的优惠码，谢谢");
                }
            } else {
                throw new \Exception("优惠码有误，请重新输入");
            }
        }


        $isDT = $deal_service->isDealDT($deal['id']);

        if($isDT && ($source_type != DealLoadModel::$SOURCE_TYPE['dtb'])) {//多投宝标的只允许多投宝服务投资
            throw new \Exception("此项目为智多鑫专享，当前不可进行投资");
        }

        // 如果存在绑定优惠码，必须填绑定的优惠码，防止修改表单 20150303
        $coupon_latest = CouponService::getCouponLatest($user['id']);
        $is_fixed_coupon = !empty($coupon_latest) && $coupon_latest['is_fixed'];

        if (!$isDT && $is_fixed_coupon && $coupon_id != $coupon_latest['short_alias']) {
            throw new \Exception("您使用的优惠码不正确，请与客服联系，谢谢");
        }
        //优惠码结束

        // 金额相关开始
        if(bccomp($money, $deal['min_loan_money'], 2) == -1){
            throw new \Exception("最低投资金额为{$deal['min_loan_money']}元");
        }


        //最高投资限制 适用所用用户  只有最后一笔可以大于最大投资额度 其他情况都不能大于最大投资额度  $deal['need_money_decimal']-$money)>$deal['min_loan_money']（不是最后一笔投资）只有最后一笔可以大于 并且最后一笔必须是在最小加最大之间
        $deal_already_load_money = DealLoadModel::instance()->getUserLoadMoneyByDealid($user['id'], $deal['id']);
        if ($deal['max_loan_money']>0 && ($deal_already_load_money+$money)>$deal['max_loan_money'] && ($deal['need_money_decimal']-$money)>=$deal['min_loan_money'] || ($deal['max_loan_money']>0 && $money>=($deal['min_loan_money']+$deal['max_loan_money'])))
        {
            throw new \Exception("抱歉，当前标的最高累计投资{$deal['max_loan_money']}元");
        }

        //判断所投的钱是否超过了剩余投标额度
        $need = bcsub($deal['borrow_amount'], $deal['load_money'], 2);
        if(bccomp($money, $need, 2) == 1) {
            $message = "出借金额超过项目可出借金额。当前可出借额为：%s" ;
            throw new \Exception(sprintf($message,format_price($deal['borrow_amount'] - $deal['load_money'])));
        }

        $minLeft = bcsub($deal['need_money_decimal'], $money, 2);

        //当前标的最低投资额度
        $currentMinLoanMoney = $deal['min_loan_money'];

        if ($minLeft > 0 && bccomp($minLeft, $currentMinLoanMoney, 2) == -1) {
            $message = "项目即将满标，您需要一次性出借%s";
            throw new \Exception(sprintf($message, $deal['need_money']));
        }
        // 金额相关结束

        return true;
    }
    /**
     * 投标成功回调
     */
    public function bidSuccessCallback($param)
    {
        // TODO 远程调用接口完善后放到消息总线
        // TODO 打tag之外的首投复投事件逻辑
        $dealService = new DealService();
       // $dealService->dealEvent($param['user_id'], $param['money'], $param['coupon_id'], $param['load_id'], false, $param['site_id']);

        try {
            //智多鑫标的不再二次计算
            $isDT = $dealService->isDealDT($param['deal_id']);
            $param ['isDt'] = $isDT;

            $deal = $dealService->getDeal($param['deal_id'], true, false);
            $GLOBALS['db']->startTrans();
            // 满标操作
            if ($param['is_deal_full'] == true) {
                $state_manager = new \core\service\deal\state\StateManager($deal);
                $state_manager->work();
            }
            if ($isDT === false) {
                $msgboxservice = new MsgboxService();
                $content = '<p>您已向“' . $deal['name'] . '”项目投标，投资款' . $param['money'] . '元';
                $msgboxservice->create($param['user_id'], MsgBoxEnum::TYPE_DEAL_SUCCESS_TIPS, '投标完成提示', $content);
            }

            // 消息总线
            //增加投资券销券需要的参数
            $param['consumeType'] = 1;
            $param['annualizedAmount'] = DealService::getAnnualizedAmountByDealIdAndAmount($param['deal_id'], $param['money']);
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_BID_SUCESS,$param);
            $GLOBALS['db']->commit();
        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.$e->getMessage());
            return false;
        }
        return true;
    }
    public function errCatch($deal_id){
        $fatal = self::$fatal;
        if(!empty($deal_id) && !empty($fatal)){
            $deal_data = new DealData();
            $deal_data->leavePool($deal_id);
            $lastErr = error_get_last();
            Logger::error("bid err catch" ." lastErr: ". json_encode($lastErr) . " trace: ".json_encode(debug_backtrace()));
        }
    }

    /**
     * 已成功投资数目(流标、标被删除、无效均不算)
     *
     * @param $user_id int
     * @param $source_type array    来源 3:ios 4:android
     * @return integer
     */
    public function getCountByUserIdInSuccess($user_id, $source_type = array(),$source_type_allow=true) {
        return DealLoadModel::instance()->getCountByUserIdInSuccess($user_id, $source_type,$source_type_allow);
    }

    /**
     * 根据用户id获取投资列表
     *
     * @param $user_id
     * @param $offset
     * @param $page_size
     * @param int $status
     * @param bool $date_start
     * @param bool $date_end
     * @return mixed
     */
    public function getUserLoadList($user_id, $offset=0, $page_size=10, $status = 0, $date_start = false, $date_end = false, $type = '', $exclude_loantype = 0, $deal_type_id = 0) {

        $result = DealLoadModel::instance()->getUserLoadList($user_id, $offset, $page_size, $status, $date_start, $date_end, $type, $exclude_loantype, $deal_type_id);

        return $result;

        /*
         * 暂时不迁移
         *
         * if ($status == 5){
            $result = DealLoadModel::instance()->getUserLoadRepaidList($user_id, $offset, $page_size, $status, $date_start, $date_end, $type, $exclude_loantype, $deal_type_id);
        }else {
            $result = DealLoadModel::instance()->getUserLoadList($user_id, $offset, $page_size, $status, $date_start, $date_end, $type, $exclude_loantype, $deal_type_id);
        }*/
        return $result;
    }

    /**
     * 根据订单id获取投标列表
     *
     * @param $dealId
     * @return mixed
     */
    public function getDealLoanListByDealId($dealId) {
        return DealLoadModel::instance()->getDealLoanList($dealId);
    }

    /**
     *  投标生成合同
     */
    public function sendContract($param){

        $contract_service = new SendContractService();
        try {
            $ret = $contract_service->sendContract($param['deal_id'], $param['load_id'], $param['is_full'], $param['create_time']);
            if ($ret) {
                return true;
            }

        }catch (\Exception $e) {
            Logger::error(__CLASS__ . ' ' . __FUNCTION__ . ' SendContractFailed , param:' . json_encode($param) . ' ' . $e->getMessage());
            return false;
        }

        Logger::error(__CLASS__ . ' ' . __FUNCTION__ . ' SendContractFailed , param:' . json_encode($param) . ' false ');
        return false;

    }

    /**
     *  满标合同检测
     */
    public function fullCheck($param){

        $contract_service = new SendContractService();
        try {
            $ret = $contract_service->fullCheck($param['deal_id']);
            if ($ret) {
                return true;
            }
        }catch (\Exception $e) {
            Logger::error(__CLASS__ . ' ' . __FUNCTION__ . 'param:' . json_encode($param) . ' ' . $e->getMessage());
            return false;
        }

        Logger::error(__CLASS__ . ' ' . __FUNCTION__ . 'param:' . json_encode($param) . ' false ');

        return false;
    }

    // 取得今日投资人数
    public function getLoadUsersNumByTime(){
        $startTime = empty($startTime) ? strtotime(date('Y-m-d')) : $startTime;
        return DealLoadModel::instance()->getLoadUsersNumByTime($startTime);
    }

    /**
     * 获取用户投标的列表
     * @param $uid
     * @param $deal_id
     */
    public function getUserDealLoad($uid,$deal_id){
        $where = " deal_id = %d AND user_id = %d";
        $where = sprintf($where, $deal_id, $uid);
        return DealLoadModel::instance()->findByViaSlave($where);
    }

    /**
     * 根据user_id获取投资人某段时间内累计投资总额  | 不知道这段是否应该加到 user_statics用户统计中去？ @todo
     * @param int $user_id int
     * @param bool $date_start string|false
     * @param bool $date_end string|false
     * @return float
     * @author zhanglei5@ucfgroup.com
     */
    public function getTotalLoanMoneyByUserId($user_id,$date_start=false, $date_end=false,$deal_status=array()) {
        return DealLoadModel::instance()->getTotalLoanMoneyByUserId($user_id,$date_start, $date_end,$deal_status);
    }
     /*
     *根据id获取投标信息
     *
     * @param $id
     * @return \libs\db\Model
     */
    public function getDealLoadDetail($id, $show_more = true, $slave = false) {
        $deal_load = DealLoadModel::instance()->find($id, '*', $slave);
        if (empty($deal_load)) {
            return false;
        }

        if($show_more === false){
            return $deal_load;
        }

        $deal_service = new DealService();
        $deal = $deal_service->getDeal($deal_load['deal_id'], true);
        $deal_load['deal'] = $deal;
        if ($deal['is_crowdfunding'] == 1) {
            $deal_load['income'] = 0;
        } else {
            $deal_load['income'] = DealModel::instance()->floorfix(\libs\utils\Finance::getExpectEarningByDealLoan($deal_load));
        }
        $deal_load['real_income'] = 0;
        // 还款中和已还清  才有回款计划
        if ($deal['deal_status'] == DealEnum::DEAL_STATUS_REPAY || $deal['deal_status'] == DealEnum::DEAL_STATUS_REPAID) {
            $deal_load['real_income'] = \core\dao\repay\DealLoanRepayModel::instance()->getTotalIncomeMoney($id);
        }
        $deal_load['total_income'] = $deal_load['money'] + $deal_load['income'];

        $deal_loan_type = DealLoanTypeModel::instance()->findViaSlave($deal['type_id']);
        $deal_load['deal_loan_type'] = $deal_loan_type;
        $deal_load['is_lease'] = $deal_loan_type['type_tag'] == DealLoanTypeEnum::TYPE_ZCZR;

        $deal_load['isDealZX'] = false;

        return $deal_load;
    }
    /**
     * 获取用户是否首投(p2p+多投)
     * @param int $userId
     * @return bool
     */

    public function isFirstInvest($userId){
        $userId = intval($userId);

        $p2pUserInvest = DealLoadModel::instance()->countByUserId($userId);

        if($p2pUserInvest > 1){
            return false;
        }else{
            $param = array('userId' => $userId);
            $response = DuotouService::callByObject(array('NCFGroup\Duotou\Services\DealLoan', "getInvestNumByUserId", $param));
            if(!$response) {
                return;
            }
            if(($p2pUserInvest + $response['data']) > 1){
                return false;
            }
        }

        return true;



    }
    function getJumpDataAfterBid($user,$loadId,$dealId,$money,$otherParams=array()){
        \core\service\risk\RiskServiceFactory::instance(\libs\utils\Risk::BC_BID)->notify();
        $discountId = isset($otherParams['discountId']) ? $otherParams['discountId'] :'';
        $discountGoodsPrice = isset($otherParams['discountGoodsPrice']) ? $otherParams['discountGoodsPrice'] :'';
        $discountGoodsType = isset($otherParams['discountGoodsType']) ? $otherParams['discountGoodsType'] :'';
        $siteId = isset($otherParams['siteId']) ? $otherParams['siteId'] :'';

        $deal = DealModel::instance()->find($dealId);
        // 读取O2O列表
        $prizeList = array();
        // 通知贷标的投资,不生成O2O礼物
        if ($deal['deal_type'] != 1) {
            //$event = CouponGroupEnum::TRIGGER_REPEAT_DOBID;
            $loadId = $loadId;
           /*TODO 首投
           $digObject = new \core\service\DigService('makeLoan', array(
                'id' => $user['id'],
                'loadid' => $loadId,
            ));
            $prizeList = $digObject->getResult();*/
        }
        $showGiftInfo = 0;

        // 如果包含O2O礼券，则显示礼券领取图片
        if (!empty($prizeList)){
            $showGiftInfo = 1;
        }
        $this->template = "";
        //注册成功，消除SESSION中的token
        bid_check_token(true);
        $jumpData = ['id' => $loadId, 'gS' => $showGiftInfo, 'action' => $event];

        if ($discountId > 0) {
            $jumpData['dP'] = str_replace(',', '', $discountGoodsPrice);
            $jumpData['dT'] = intval($discountGoodsType);
        }
        return $jumpData;
    }

    public function countByUserId($userId) {
        return DealLoadModel::instance()->countByUserId($userId);
    }
}
