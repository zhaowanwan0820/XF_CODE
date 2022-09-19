<?php
/**
 * 投资逻辑封装
 *
 *  1、即付投资逻辑
 *  2、库存扣减、标的状态更新、订单状态更新
 *  3、投资券通知
 *  4、投资成功 优惠码使用、投资券消费、首投逻辑处理、短信邮件
 *  5、合同服务JOBS
 *  6、即富投资完成逻辑
 *  7、满标合同逻辑
 *  8、满标更新项目信息
 *  9、满标触发自动上标逻辑
 *  10、给用户打Tag逻辑
 *  11、保存投资订单信息
 */

namespace core\service;

use core\service\DealService;
use core\service\DiscountService;
use core\service\DealProjectService;
use core\service\P2pIdempotentService;
use core\service\P2pDepositoryService;
use core\service\risk\RiskService;
use core\service\UserService;

use core\service\P2pDealBidService;
use core\service\DealLoadService;
use core\service\TransferService;
use libs\utils\Logger;
use core\data\DealData;
use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\JobsModel;
use core\dao\DealProjectModel;


use core\dao\DiscountModel;
use core\dao\DealQueueModel;
use core\dao\ThirdpartyOrderModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\oto\O2OUtils;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use core\service\O2OService;

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
     * 是否即富
     */
    private $isJf;

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
        $deal = \core\dao\DealModel::instance()->find($this->dealId);
        if(!$deal){
            throw new \Exception("标的信息不存在");
        }
        $user = \core\dao\UserModel::instance()->find($this->userId);
        if(!$user){
            throw new \Exception("用户信息不存在");
        }
        $this->userService = new UserService($this->userId);
        $this->dealData = new DealData();
        $this->dealService = new DealService();

        $this->deal = $deal;
        $this->user = $user;
        $this->isP2pPath = $this->dealService->isP2pPath($this->deal);
    }


    /**
     * 投资底层逻辑
     * @param $couponId
     * @param $sourceType 来源类型 0前台正常投标 1后台预约投标 3 ios 4 Android 5 前台预约投标 6-openAPI 8-WAP
     * @param $siteId 分站ID
     * @param $jfOrderId 即富订单ID
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
        $jfOrderId  = isset($bidParams['jfOrderId']) ? $bidParams['jfOrderId'] : '';
        $discountId = isset($bidParams['discountId']) ? $bidParams['discountId'] : '';
        $discountType = isset($bidParams['discountType']) ? $bidParams['discountType'] : '';
        $bidMore    = isset($bidParams['bidMore']) ? $bidParams['bidMore'] : '';
        $bonusInfo  = isset($bidParams['bonusInfo']) ? $bidParams['bonusInfo'] : array();
        $euid       = isset($bidParams['euid']) ? $bidParams['euid'] : '';
        $trackId    = isset($bidParams['trackId']) ? $bidParams['trackId'] : '';
        $discountGoodsPrice = isset($bidParams['discountGoodsPrice']) ? $bidParams['discountGoodsPrice'] : '';
        $discountGoodsType =  isset($bidParams['discountGoodsType'])  ? $bidParams['discountGoodsType'] : '';

        try{
            $this->isJf = $this->dealService->isDealJF($siteId);
            $this->bonusInfo = $bonusInfo;
            $GLOBALS['db']->startTrans();

            $bonusAccountInfo = isset($bonusInfo['accountInfo']) ? $bonusInfo['accountInfo'] : array();

            //处理红包转账 这个需要在投资前处理并和投资在一个事务中
            if($this->isP2pPath){
                $bonusTrans = new \core\service\UserThirdBalanceService();
                $bonusTranRes = $bonusTrans->supervisionBonusTransfer($this->orderId);
            }else{
                $bonusTranRes = $this->bidBonusTransfer($bonusAccountInfo);
            }
            if(!$bonusTranRes){
                throw new \Exception('红包转账失败');
            }

            // 即付逻辑，投资时保证幂等且需要代理充值
            if ($this->isJf === true) {
                $bidTransferId = $this->dealService->transferBidJF($this->user, $this->money, $this->dealId, $jfOrderId);
                if ( $bidTransferId === false) {
                    throw new \Exception($this->deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL ? "出借失败，请稍后再试" : "投资失败，请稍后再试");
                }
            }

            if($this->deal->isFull()) {
                throw new \Exception('标的已经满标');
            }

            $ip = get_real_ip();
            $loadId = $this->deal->doBid($this->money, $this->userId, $this->user['user_name'], $ip, $sourceType, $siteId,$couponId);
            if ($loadId === false) {
                throw new \Exception('更新标的失败');
            }
            $this->loadId = $loadId;

            //更改资金记录
            $msg = "编号{$this->dealId} {$this->deal['name']}";

            $this->user->changeMoneyDealType = $this->dealService->getDealType($this->deal);

            $bizToken = [
                'dealId' => $this->dealId,
                'dealLoadId' => $this->loadId,
            ];

            if ($this->user->changeMoney($this->money, "投标冻结", $msg, 0, 0, 1, 0, $bizToken) === false) {
                throw new \Exception('投标冻结失败');
            }

            if ($discountId > 0) {
                $this->addDiscountRecord($discountId,$discountType);
            }

            // 此处在此更新deal对象
            $this->deal = DealModel::instance()->find($this->dealId);
            $this->isDealFull = $this->deal['deal_status'] == 2 ? true : false; // 判断是否已经满标

            if ($this->isDealFull) {
                $this->fullDeal();
            }

            $this->bidSuccessCallBack($couponId, $siteId, $discountId, $euid, $trackId);

            $this->sendContract();
            if($this->isJf === true){
                $this->saveJfOrder($bidTransferId,$jfOrderId,$siteId);
            }

            // 用户投资次数相关，打tag，（重要，必须实时，否则返利计算错误）, 必须在consume之前
            $this->userService->makeUserBidTag($this->userId, $this->money, $couponId, $this->loadId, false, $bidMore, false, [
                'discountId'=>$discountId,
                'bonusAmount'=>isset($this->bonusInfo['money']) ? $this->bonusInfo['money'] : 0,
                'orderId'=>$this->orderId,
                'sourceType'=>$sourceType
            ]);

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

        //增加网信抽奖码触发
        try{
            O2OService::triggerUniqueCode($this->userId, CouponGroupEnum::CONSUME_TYPE_ZHUANXIANG, $this->loadId, $this->money, CouponGroupEnum::TRIGGER_REPEAT_DOBID);
        }catch(\Exception $ex) {
            \libs\utils\Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $this->dealId, $this->user['id'], $this->money, $couponId, $siteId, "triggerUniqueCode fail ".$ex->getMessage())));
        }
        return true;
    }

    /**
     * 投资券消费
     * @param $discountId
     * @param $discountType
     * @throws \Exception
     */
    public function addDiscountRecord($discountId,$discountType){
        $discountParams = array('user_id' => $this->user['id'], 'discount_id' => $discountId, 'consume_id' => $this->loadId, 'discount_type' => intval($discountType), 'create_time' => date('Y-m-d H:i:s'));
        $discountParams['update_time'] = $discountParams['create_time'];
        if (!array_key_exists($discountType, CouponGroupEnum::$DISCOUNT_TYPES)) {
            \libs\utils\Monitor::add(DiscountService::DEAL_FAILD_TYPE);
            // throw new \Exception('劵类型错误');
        }
        if (!DiscountModel::instance()->addRecord($discountParams)) {
            \libs\utils\Monitor::add(DiscountService::DEAL_FAILD);
            throw new \Exception('使用投资劵失败');
        }
        \libs\utils\Monitor::add(DiscountService::DEAL_SUCCESS);
    }

    private function bidSuccessCallBack($couponId,$siteId,$discountId, $euid, $trackId) {

        //投标成功Jobs
        $jobsModel = new JobsModel();
        $function = '\core\service\DealLoadService::bidSuccessCallback';
        $annualizedAmount = O2OUtils::getAnnualizedAmountByDealIdAndAmount($this->dealId, $this->money);
        $param = array(
            'user_id' => $this->user['id'],
            'money' => $this->money,
            'deal_id' => $this->dealId,
            'deal_name' => $this->deal['name'],
            'load_id' => $this->loadId,
            'coupon_id' => $couponId,
            'contract_tpl_type' => $this->deal['contract_tpl_type'],
            'ip' => get_client_ip(),
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
            'annualized_amount' => $annualizedAmount,
        );
        $jobsModel->priority = JobsModel::PRIORITY_BID_SUCCESS_CALLBACK;
        $ret = $jobsModel->addJob($function, array('param' => $param)); //不重试
        if ($ret === false) {
            throw new \Exception('Jobs任务注册失败');
        }


        // 如果是智多鑫标的，需要同步给智多鑫loadId
        if($this->dealService->isDealDT($this->dealId)){
            $function = '\core\service\DtBidService::bidSuccessCallback';
            $jobsModel->priority = JobsModel::PRIORITY_DT_BID_SUCCESS;
            $ret = $jobsModel->addJob($function, array('orderId'=>$this->orderId));
            if ($ret === false) {
                throw new \Exception('Jobs任务注册失败');
            }
        } else {
            // 增加vip经验埋点
            $sourceType = ($this->deal['deal_type'] == DealModel::DEAL_TYPE_GENERAL) ? VipEnum::VIP_SOURCE_P2P : VipEnum::VIP_SOURCE_ZHUANXIANG;
            $vipParam = array(
                'userId' => $this->user['id'],
                'sourceAmount' => $annualizedAmount,
                'sourceType' => $sourceType,
                'token' => $sourceType.'_'.$this->user['id'].'_'.$this->loadId,
                'info' => $this->deal['name'].",{$this->money}元",
                'sourceId' => $this->loadId,
            );
            $function = '\core\service\vip\VipService::updateVipPointCallback';
            $jobsModel->priority = JobsModel::PRIORITY_BID_SUCCESS_CALLBACK;
            $ret = $jobsModel->addJob($function, array('param'=>$vipParam));
            if ($ret === false) {
                throw new \Exception('Jobs任务注册失败');
            }
        }

        $bidSuccParam = array(
            'user_id' => $param['user_id'],
            'user_name' => '',
            'mobile' => $param['phone'],
            'bid_type' => $param['deal_type'],
            'amount' => bcmul($param['money'],100),
            'business_time' => $param['time'],
            'order_id' => $param['load_id'],
        );
        RiskService::report('BID',RiskService::STATUS_SUCCESS,$bidSuccParam);

        return true;
    }

    /**
     * 投资成功后生成合同
     * @return bool
     * @throws \Exception
     */
    private function sendContract() {
        $contractFunction = '\core\service\DealLoadService::sendContract';
        $contractParam = array(
            'deal_id' => $this->dealId,
            'load_id' => $this->loadId,
            'is_full' => false,
            'create_time'=> time(),
        );
        $jobsModel = new JobsModel();
        $jobsModel->priority = 123;
        $contractRet = $jobsModel->addJob($contractFunction, array('param' => $contractParam)); //不重试
        if ($contractRet === false) {
            throw new \Exception('load:'.$this->loadId.'合同任务插入注册失败');
        }

        if($this->deal['deal_status'] == 2 && !empty($this->deal['contract_tpl_type'])) {
            $contractFunction = '\core\service\DealLoadService::sendContract';
            $contractParam = array(
                'deal_id' => $this->dealId,
                'load_id' => 0,
                'is_full' => true,
                'create_time' => time(),
            );
            $jobsModel->priority = 123;
            $contract_ret = $jobsModel->addJob($contractFunction, array('param' => $contractParam)); //不重试
            if ($contract_ret === false) {
                throw new \Exception('满标合同任务插入注册失败');
            }

            $fullCkeckFunction = '\core\service\DealLoadService::fullCheck';
            $fullCkeckParam = array(
                'deal_id' => $this->dealId,
            );
            $jobsModel->priority = 122;
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
        $projectService = new DealProjectService();
        $projectService->updateProLoaned($this->deal['project_id']);

        //自动上标逻辑  --- 自动上标有报备等网络请求、线上产生死锁  此处移动到投资完成的callback中处理
        //$QueueModel = DealQueueModel::instance()->getDealQueueByFirstDealId($this->dealId);
        //if (!empty($QueueModel) && $QueueModel->startDealAutoByQueue() === false) {
        //    throw new \Exception('满标触发自动上标失败');
        //}

        // 检查专享项目是否满标
        if ($projectService->isProjectEntrustZX($this->deal['project_id']) && $projectService->isProjectFull($this->deal['project_id'])) {
            // 更新项目业务状态 - 满标待审核
            if (!DealProjectModel::instance()->changeProjectStatus($this->deal['project_id'], DealProjectModel::$PROJECT_BUSINESS_STATUS['full_audit'])) {
                throw new \Exception('项目业务状态变更失败');
            }
        }

        return true;
    }

    /**
     * 即富类型的订单保存订单信息
     * @param $bidTransferId
     * @param $jfOrderId
     * @param $siteId
     * @return bool
     * @throws \Exception
     */
    private function saveJfOrder($bidTransferId,$jfOrderId,$siteId){
        if (empty($jfOrderId)) {
            throw new \Exception("投资订单缺失，请稍后再试");
        } else {
            $tpo_model = new ThirdpartyOrderModel();
            $res_tpo = $tpo_model->createOrderRecord($siteId, $jfOrderId, $this->user['id'], $this->user['mobile'],
                $this->dealId, $this->money, $this->loadId, $bidTransferId);
            if ($res_tpo == ThirdpartyOrderModel::ORDER_ALREADY_EXISTED) {
                throw new \Exception("投资订单已经存在，请稍后重试");
            } elseif ($res_tpo == ThirdpartyOrderModel::ORDER_CREATED_FAILED) {
                throw new \Exception("投资订单创建失败，请稍后重试");
            } elseif ($res_tpo != ThirdpartyOrderModel::ORDER_CREATED_SUCCESS) {
                throw new \Exception("投资订单创建异常，请稍后重试");
            }
        }
        return true;
    }

    /**
     * 保存订单信息 提供后续幂等的判断条件
     */
    private function saveBidOrder($bidParams){
        if($this->isP2pPath){
            $orderInfo = P2pIdempotentService::getInfoByOrderId($this->orderId);
            $oriBidParams = json_decode($orderInfo['params'],true);
            $bidParams = array_merge($oriBidParams,$bidParams);
        }
        $data = array(
            'order_id' => $this->orderId,
            'loan_user_id' => $this->user['id'],
            'deal_id' => $this->dealId,
            'load_id' => $this->loadId,
            'money' => $this->money,
            'borrow_user_id' => $this->deal['user_id'],
            'params' => addslashes(json_encode($bidParams)),
            'type' => P2pDepositoryService::IDEMPOTENT_TYPE_BID,
            'result' => P2pIdempotentService::RESULT_SUCC,
            'status' => P2pIdempotentService::STATUS_CALLBACK,
        );

        // 对已经报备过的标的 订单信息是已经存在的，此处进行更新即可
        if($this->deal['report_status'] == \core\dao\DealModel::DEAL_REPORT_STATUS_YES){
            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($this->orderId,$data,P2pIdempotentService::RESULT_WAIT);
            return $affectedRows > 0 ? true :false;
        }else{
            //$data['status'] = P2pIdempotentService::STATUS_CALLBACK;
            return P2pIdempotentService::addOrderInfo($this->orderId,$data);
        }
    }

    public function bidBonusTransfer($bonusAccountInfo)
    {
        //从机构账户扣款
        $transferService = new TransferService();
        $transferService->payerChangeMoneyAsyn = true;

        // 分批转账
        foreach ($bonusAccountInfo as $item) {
            $payerId = $item['rpUserId'];
            $money = $item['rpAmount'];
            $payObj = UserModel::instance()->find($payerId);
            if(empty($payObj)) {
                return false;
            }
            //$payRes = $payObj->changeMoney(-$money, '红包充值', "{$bid_user_id}使用红包充值投资{$this->name}", 0, 0, 0);
            $payType = app_conf('NEW_BONUS_TITLE') . '充值';
            $payNote = "{$this->user['id']}使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$this->deal['name']}";
            $payBizToken = ['dealId' => $this->dealId, 'orderId' => $this->orderId];

            $changeDate = strtotime('2017-06-01 00:00:00');
            if (time() >= $changeDate) {
                $receiverType = '使用' . app_conf('NEW_BONUS_TITLE') . '充值';
            } else {
                $receiverType = '充值';
            }
            $receiverNote = "使用" . app_conf('NEW_BONUS_TITLE') . "充值用于{$this->deal['name']}";
            $receiverBizToken = ['dealId' => $this->dealId, 'orderId' => $this->orderId];

            $transRes = $transferService->transferById($payerId, $this->user['id'], $money, $payType,
                $payNote, $receiverType, $receiverNote, $outOrderId = '', $payBizToken, $receiverBizToken);
            if ($transRes === false) {
                return false;
            }
        }
        return true;
    }
}
