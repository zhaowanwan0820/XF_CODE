<?php
/**
 * 投资逻辑封装
 *
 *  1、库存扣减、标的状态更新、订单状态更新
 *  2、投资券通知
 *  3、投资成功 优惠码使用、投资券消费、首投逻辑处理、短信邮件
 *  4、合同服务JOBS
 *  5、即富投资完成逻辑
 *  6、满标合同逻辑
 *  7、满标更新项目信息
 *  8、满标触发自动上标逻辑
 *  9、给用户打Tag逻辑
 *  10、保存投资订单信息
 */

namespace core\service\deal;

use core\service\BaseService;
use core\enum\JobsEnum;
use core\service\deal\DealService;
use core\service\project\ProjectService;
use core\service\deal\P2pIdempotentService;
use core\service\deal\P2pDepositoryService;
use core\service\user\UserService;
use core\service\account\AccountService;
use core\service\deal\P2pDealBidService;
use core\service\dealload\DealLoadService;
use core\service\supervision\SupervisionAccountService;
use core\service\msgbus\MsgbusService;
use libs\utils\Logger;
use core\data\DealData;
use core\dao\deal\DealLoadModel;
use core\dao\deal\DealModel;
use core\dao\jobs\JobsModel;
use core\dao\project\DealProjectModel;


use core\dao\dealqueue\DealQueueModel;
use core\enum\VipEnum;
use core\enum\AccountEnum;
use core\enum\UserAccountEnum;
use core\enum\DealEnum;
use core\enum\P2pIdempotentEnum;
use core\enum\P2pDepositoryEnum;
use core\enum\MsgbusEnum;

use core\service\o2o\CouponService;
use NCFGroup\Common\Library\Idworker;
use core\service\deal\DealTagService;
use core\enum\CouponGroupEnum;
use core\service\bwlist\BwlistService;

class DealBidService extends BaseService {

    /**
     * 全局唯一订单号 通过Idworker 生成
     */
    private $orderId;

    /**
     * dealModel 对象
     */
    private $deal;

    /**
     * 标的ID
     */
    private $dealId;

    /**
     * userModel对象
     */
    private $user;

    /**
     * 用户ID
     */
    private $userId;

    /**
     * 还款金额
     */
    private $money;

    /**
     * 标的是否满标
     */
    private $isDealFull;


    /**
     * 投资ID
     */
    private $loadId;

    /**
     * 红包信息
     */
    private $bonusInfo;

    /**
     * 红包账户信息
     */
    private $bonusAccountInfo;


    /**
     * @param $orderId 订单ID
     * @param DealModel $deal 标的对象
     * @param UserModel $user 用户对象
     * @param $money 投资金额
     */

    public function __construct($orderId,$dealId,$userId,$money) {
        $this->orderId = $orderId;
        $this->dealId = $dealId;
        $this->userId = $userId;
        $this->money = $money;
        $this->_initialize();
    }

    public function _initialize() {
        $deal = \core\dao\deal\DealModel::instance()->find($this->dealId);
        if(!$deal){
            throw new \Exception("标的信息不存在");
        }
        $user = UserService::getUserById($this->userId);
        if(!$user){
            throw new \Exception("用户信息不存在");
        }
        $this->userService = new UserService($this->userId);
        $this->dealData = new DealData();
        $this->dealService = new DealService();

        $this->deal = $deal;
        $this->user = $user;
        $this->isP2pPath = true;
    }


    /**
     * 投资底层逻辑
     * @param $couponId
     * @param $sourceType 来源类型 0前台正常投标 1后台预约投标 3 ios 4 Android 5 前台预约投标 6-openAPI 8-WAP
     * @param $siteId 分站ID
     * @param $discountId 使用投资券ID
     * @param $discountType 投资券类型
     * @param $bidMore
     * @param $bonusInfo 红包信息
     * @param null $euid
     * @param null $trackId
     * @return bool
     */

    public function bid(Array $bidParams){
        $couponId   = isset($bidParams['couponId']) ? $bidParams['couponId'] : '';
        $sourceType = isset($bidParams['sourceType']) ? $bidParams['sourceType'] : '';
        $siteId     = isset($bidParams['siteId']) ? $bidParams['siteId'] : '';
        $discountId = isset($bidParams['discountId']) ? $bidParams['discountId'] : '';
        $discountType = isset($bidParams['discountType']) ? $bidParams['discountType'] : '';
        $bidMore    = isset($bidParams['bidMore']) ? $bidParams['bidMore'] : '';
        $bonusInfo  = isset($bidParams['bonusInfo']) ? $bidParams['bonusInfo'] : array();
        $euid       = isset($bidParams['euid']) ? $bidParams['euid'] : '';
        $trackId    = isset($bidParams['trackId']) ? $bidParams['trackId'] : '';
        $discountGoodsPrice = isset($bidParams['discountGoodsPrice']) ? $bidParams['discountGoodsPrice'] : '';
        $discountGoodsType =  isset($bidParams['discountGoodsType'])  ? $bidParams['discountGoodsType'] : '';
        $ip =  (isset($bidParams['ip']) && !empty($bidParams['ip']) )  ? $bidParams['ip'] : get_real_ip();

        try{
            $this->bonusInfo = $bonusInfo;
            $GLOBALS['db']->startTrans();

            $bonusAccountInfo = isset($bonusInfo['accountInfo']) ? $bonusInfo['accountInfo'] : array();

            // 处理红包转账 这个需要在投资前处理并和投资在一个事务中
            $bonusTrans = new SupervisionAccountService();
            $bonusTranRes = $bonusTrans->bonusTransfer($this->orderId);

            if(!$bonusTranRes){
                throw new \Exception('红包转账失败');
            }

            if($this->deal->isFull()) {
                throw new \Exception('标的已经满标');
            }

            $loadId = $this->deal->doBid($this->money, $this->userId, $this->user['user_name'], $ip, $sourceType, $siteId,$couponId);
            if ($loadId === false) {
                throw new \Exception('更新标的失败');
            }
            $this->loadId = $loadId;

            //更改资金记录
            $msg = "编号{$this->dealId} {$this->deal['name']}";

            $accountId = AccountService::getUserAccountId($this->userId,UserAccountEnum::ACCOUNT_INVESTMENT);
            $bizToken = array('dealId' => $this->dealId,'dealLoadId' => $loadId);
            $ret = AccountService::changeMoney($accountId, $this->money, "投标冻结", $msg, AccountEnum::MONEY_TYPE_LOCK, false,true,0,$bizToken);
            if ($ret === false) {
                throw new \Exception('投标冻结失败');
            }

            // 此处在此更新deal对象
            $this->deal = DealModel::instance()->find($this->dealId);
            $this->isDealFull = $this->deal['deal_status'] == 2 ? true : false; // 判断是否已经满标

            if ($this->isDealFull) {
                $this->fullDeal();
            }

            $this->bidSuccessCallBack($couponId, $siteId, $discountId, $euid, $trackId);

            $this->sendContract();


            $saveOrderRes = $this->saveBidOrder($bidParams);
            if($saveOrderRes === false){
                throw new \Exception('订单信息保存失败');
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            $this->dealData->leavePool($this->dealId);
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $this->dealId, $this->user['id'], $this->money, $couponId, $siteId, "fail ".$ex->getMessage())));
            return false;
        }

        try{
            $action = 4;//复投
            $siteId = 100;
            $annualizedAmount = DealService::getAnnualizedAmountByDealIdAndAmount($this->dealId, $this->money);
            $consumeType = 1;
            $triggerType = 1;
            $extra = array(
                'discountId' => $discountId,
                'bonusAmount' => isset($this->bonusInfo['money']) ? $this->bonusInfo['money'] : 0,
                'orderId' => $this->orderId,
                'sourceType' => $sourceType,
                'inviter' => $couponId,
                'dealTag' => (new DealTagService())->getTagByDealId($this->dealId),
                'dealBidDays' => $this->deal['repay_time'] * 30,
                'dealName' => $this->deal['name'],
                'loantype' => $this->deal['loantype'],
                'deal_type' => $this->deal['deal_type']
            );

            CouponService::triggerO2OOrder(
                $this->userId,
                $action,
                $this->loadId,
                $siteId,
                $this->money,
                $annualizedAmount,
                $consumeType,
                $triggerType,
                $extra
            );

            // 增加排行榜数据统计处理逻辑
            if(!$this->dealService->isDealDT($this->dealId)
                && $sourceType != DealLoadModel::$SOURCE_TYPE['reservation']
                && !BwlistService::inList('O2O_RANK_BLACK',$this->userId)){
                //智多新底层匹配资产会走到此逻辑，需要屏蔽智多新&随心约
                CouponService::updateRankScoreByTrigger($this->userId, $this->money, $annualizedAmount, $this->loadId, CouponGroupEnum::RANK_DEAL_TYPE_P2P, $extra);
            }
        }catch (\Exception $ex){
            //同步调用失败后放入msgbus通知
            $o2oParam = array(
                'userId' => $this->userId,
                'action' => $action,
                'dealLoadId' => $this->loadId,
                'siteId' => $siteId,
                'money' => $this->money,
                'annualizedAmount' => $annualizedAmount,
                'consumeType' => $consumeType,
                'triggerType' => $triggerType,
                'extra' => $extra,

            );
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_BID_TRIGGER_O2O,$o2oParam);
        }
        return true;
    }

    private function bidSuccessCallBack($couponId,$siteId,$discountId, $euid, $trackId) {

        //投标成功Jobs
        $jobsModel = new JobsModel();
        $function = '\core\service\dealload\DealLoadService::bidSuccessCallback';
        $param = array(
            'user_id' => $this->user['id'],
            'money' => $this->money,
            'deal_id' => $this->dealId,
            'deal_name' => $this->deal['name'],
            'load_id' => $this->loadId,
            'coupon_id' => $couponId,
            'contract_tpl_type' => $this->deal['contract_tpl_type'],
            'ip' => get_real_ip(),
            'time' => time(),
            'site_id' => $siteId,
            'discount_id' => $discountId,
            'bonus' => isset($this->bonusInfo['money']) ? $this->bonusInfo['money'] : 0,
            'is_deal_full' => $this->isDealFull,
            'phone' => $this->user['mobile'],
            'deal_type' => $this->deal['deal_type'],
            'euid' => $euid,
            'track_id' => $trackId,
            'order_id' => $this->orderId,
        );
        $jobsModel->priority = JobsEnum::PRIORITY_BID_SUCCESS_CALLBACK;
        $ret = $jobsModel->addJob($function, array('param' => $param)); //不重试
        if ($ret === false) {
            throw new \Exception('Jobs任务注册失败');
        }


        // 如果是智多鑫标的，需要同步给智多鑫loadId
        if($this->dealService->isDealDT($this->dealId)){
            $function = '\core\service\duotou\DtBidService::bidSuccessCallback';
            $jobsModel->priority = JobsEnum::PRIORITY_DT_BID_SUCCESS;
            $ret = $jobsModel->addJob($function, array('orderId'=>$this->orderId));
            if ($ret === false) {
                throw new \Exception('duotouJobs bidSuccessCallback fail');
            }
        } else {
            // 增加vip经验埋点
            $sourceType =  VipEnum::VIP_SOURCE_P2P;
            $dealLoadService = new DealLoadService();
            $isFirstInvest = $dealLoadService->isFirstInvest($this->user['id']);
            $vipParam = array(
                'userId' => $this->user['id'],
               'sourceAmount' => DealService::getAnnualizedAmountByDealIdAndAmount($this->dealId, $this->money),
                'sourceType' => $sourceType,
                'token' => $sourceType.'_'.$this->user['id'].'_'.$this->loadId,
                'info' => $this->deal['name'].",{$this->money}元",
                'sourceId' => $this->loadId,
                'isFirstInvest' => $isFirstInvest,
                'bidAmount' => $this->money,
            );
            $function = '\core\service\user\VipService::jobsUpdateVipPointCallback';
            $jobsModel->priority = JobsEnum::PRIORITY_BID_SUCCESS_CALLBACK;
            $ret = $jobsModel->addJob($function, array('param'=>$vipParam));
            if ($ret === false) {
                throw new \Exception('Jobs更新vip经验点失败');
            }
        }


        return true;
    }

    /**
     *
     * @return bool
     * @throws \Exception
     */
    private function sendContract() {
        $contractFunction = '\core\service\dealload\DealLoadService::sendContract';
        $contractParam = array(
            'deal_id' => $this->dealId,
            'load_id' => $this->loadId,
            'is_full' => false,
            'create_time'=> time(),
        );
        $jobsModel = new JobsModel();
        $jobsModel->priority = JobsEnum::BID_SEND_CONTRACT;
        $contractRet = $jobsModel->addJob($contractFunction, array('param' => $contractParam)); //不重试
        if ($contractRet === false) {
            throw new \Exception('load:'.$this->loadId.'合同任务插入注册失败');
        }

        if($this->deal['deal_status'] == 2 && !empty($this->deal['contract_tpl_type'])) {
            $contractFunction = '\core\service\dealload\DealLoadService::sendContract';
            $contractParam = array(
                'deal_id' => $this->dealId,
                'load_id' => 0,
                'is_full' => true,
                'create_time' => time(),
            );
            $jobsModel->priority = JobsEnum::BID_SEND_CONTRACT;
            $contract_ret = $jobsModel->addJob($contractFunction, array('param' => $contractParam)); //不重试
            if ($contract_ret === false) {
                throw new \Exception('满标合同任务插入注册失败');
            }

            $fullCkeckFunction = '\core\service\dealload\DealLoadService::fullCheck';
            $fullCkeckParam = array(
                'deal_id' => $this->dealId,
            );
            $jobsModel->priority = JobsEnum::BID_CHECK_FULL_CONTRACT;
            $fullCheckRet = $jobsModel->addJob($fullCkeckFunction, array('param' => $fullCkeckParam), get_gmtime() + 1800); //不重试
            if ($fullCheckRet === false) {
                throw new \Exception('检测标的合同任务注册失败');
            }
        }
        return true;
    }

    /**
     * 满标相关操作
     *  1、更新项目已投资金额
     *  2、满标触发自动上标逻辑
     * @throws \Exception
     */
    private function fullDeal() {
        $projectService = new ProjectService();
        $projectService->updateProLoaned($this->deal['project_id']);

        //自动上标逻辑  --- 自动上标有报备等网络请求、线上产生死锁  此处移动到投资完成的callback中处理
        //$QueueModel = DealQueueModel::instance()->getDealQueueByFirstDealId($this->dealId);
        //if (!empty($QueueModel) && $QueueModel->startDealAutoByQueue() === false) {
        //    throw new \Exception('满标触发自动上标失败');
        //}

        return true;
    }
    /**
     * 保存订单信息 提供后续幂等的判断条件
     */
    private function saveBidOrder($bidParams){

        $orderInfo = P2pIdempotentService::getInfoByOrderId($this->orderId);
        $oriBidParams = json_decode($orderInfo['params'],true);
        $bidParams = array_merge($oriBidParams,$bidParams);

        $data = array(
            'order_id' => $this->orderId,
            'loan_user_id' => $this->user['id'],
            'deal_id' => $this->dealId,
            'load_id' => $this->loadId,
            'money' => $this->money,
            'borrow_user_id' => $this->deal['user_id'],
            'params' => addslashes(json_encode($bidParams)),
            'type' => P2pDepositoryEnum::IDEMPOTENT_TYPE_BID,
            'result' => P2pIdempotentEnum::RESULT_SUCC,
            'status' => P2pIdempotentEnum::STATUS_CALLBACK,
        );

        // 对已经报备过的标的 订单信息是已经存在的，此处进行更新即可
        $affectedRows = P2pIdempotentService::updateOrderInfoByResult($this->orderId,$data,P2pIdempotentEnum::RESULT_WAIT);
        return $affectedRows > 0 ? true :false;

    }
}
