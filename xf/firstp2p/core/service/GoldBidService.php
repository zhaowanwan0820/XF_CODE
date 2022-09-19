<?php
/**
 * 黄金项目service
 * @data 2017.05.16
 * @author wangzhen wangzhen@ucfgroup.com
 */


namespace core\service;

use libs\utils\Logger;
use core\service\SendContractService;
use core\service\DiscountService;
use core\service\GoldBidBaseService;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\JobsModel;
use NCFGroup\Protos\Gold\RequestCommon;
use NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum;
use core\tmevent\gold\BidEvent;
use core\tmevent\gold\UserEvent;
use core\tmevent\bid\BonusGoldConsumeEvent;
use core\tmevent\discount\DiscountConsumeEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use core\service\MoneyOrderService;
use core\exception\MoneyOrderException;
use core\service\DealQueueService;
use core\service\O2OService;
use core\service\vip\VipService;
use NCFGroup\Protos\Ptp\Enum\VipEnum;
use app\models\service\GoldFinance;

class GoldBidService extends GoldBidBaseService {

    public function __construct($dealId = '', $userId = '', $buyAmount = '', $buyPrice = '', $coupon = '',
                                $orderId = '', $discountId = 0, $discountGroupId = 0, $discountSign = '',
                                $discountSuccessDesc = '') {
        parent::__construct();
        $this->dealId = $dealId;
        $this->userId = $userId;
        $this->buyAmount = $buyAmount;
        $this->buyPrice = $buyPrice;//购买价格
        $this->coupon = trim($coupon);//邀请码
        $this->orderId = $orderId;
        $this->discountId = $discountId;
        $this->discountGroupId = $discountGroupId;
        $this->discountSign = trim($discountSign);
        $this->discountSuccessDesc = $discountSuccessDesc;
    }

    public function doBid(){
        $response = array('errCode' => 0,'msg' =>'','data' => false);
        //验证信息
        try {
                $log_info = array(__CLASS__,__FUNCTION__,$this->userId,$this->dealId,$this->buyAmount,$this->buyPrice, $this->orderId,$this->coupon,$this->discountId);
                Logger::info(implode(" | ", array_merge($log_info, array(' check start '))));
                //加锁
                self::$fatal = 1;
                $this->lock();
                register_shutdown_function(array($this, "errCatch"),$this->dealId);
                $this->checkCanBid();
                $this->checkMoney();

                Logger::info(implode(" | ", array_merge($log_info, array(' check end '))));
            }

        catch (\Exception $e) {
            //释放锁
            self::$fatal = 0;
            $this->releaseLock();
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__, 'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice, 'discountId:'.$this->discountId, "error:".$e->getMessage())));
            $response['errCode'] = 1;
            $response['msg'] = $e->getMessage();
            return $response;
        }

        //投资相关
        try {
            \libs\utils\Monitor::add('GOLD_BID_START');

            //基于TM 的投资逻辑
            $gtm = new GlobalTransactionManager();
            $gtm->setName('goldBid');

            $params = array(
                'orderId' =>$this->orderId,
                'dealInfo' => $this->dealInfo,
                'userInfo' => $this->userInfo,
                'moneyInfo' =>$this->moneyInfo,
                'buyPrice' => $this->curent_price,
                'buyAmount' => $this->buyAmount,
                'money' => $this->money,
                'fee' => $this->fee,
                'coupon' => $this->coupon,
                'discountId' => $this->discountId,
                'discountGoldOrderId' => 0
            );
            Logger::info(implode(" | ", array_merge($log_info, array('start',json_encode($params)))));
            // 消费投资券
            if ($this->discountId > 0) {
                // 对于黄金券的使用，需要传递购买优金宝的订单ID，保证黄金券购金的幂等
                $params['discountGoldOrderId'] = \NCFGroup\Common\Library\Idworker::instance()->getId();
                $gtm->addEvent(new DiscountConsumeEvent($this->userInfo['id'], $this->discountId, $this->orderId,
                    CouponGroupEnum::DISCOUNT_TYPE_GOLD, time(), CouponGroupEnum::CONSUME_TYPE_GOLD_ORDER));
            }

            // 红包消费
            $bonusInfo = $this->moneyInfo['bonusInfo'];
            if(bccomp($bonusInfo['money'],'0.00',2) !== -1){
                $gtm->addEvent(new BonusGoldConsumeEvent($this->userInfo['id'],$bonusInfo,$this->orderId,$this->dealInfo['name']));
            }

            //用户冻结资金
            $gtm->addEvent(new UserEvent($params));
            //投资操作
            $gtm->addEvent(new BidEvent($params));

            $bidRes = $gtm->execute(); // 同步执行

            if($bidRes === false){
                Logger::error(implode('|',array(__CLASS__,__FUNCTION__,
                    'orderId:'.$this->orderId, 'dealId:'.$this->dealId, 'userId:'.$this->userId, 'buyPrice:'.$this->buyPrice,
                    'curentPrice:'.$this->curent_price, 'buyAmount:'.$this->buyAmount, 'discountId:'.$this->discountId,
                    "msg:GTM事务处理失败")));

                throw new \Exception('投资失败');
            }
        } catch (\Exception $e) {
            //释放锁
            self::$fatal = 0;
            $this->releaseLock();
            Logger::error(implode('|',array(__CLASS__,__FUNCTION__,
                'orderId:'.$this->orderId, 'dealId:'.$this->dealId, 'userId:'.$this->userId, 'buyPrice:'.$this->buyPrice,
                'curentPrice:'.$this->curent_price, 'buyAmount:'.$this->buyAmount, 'discountId:'.$this->discountId,
                "msg:".$e->getMessage())));

            $response['errCode'] = 1;
            $response['msg'] = $e->getMessage();
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            \libs\utils\Monitor::add('GOLD_BID_FAILED');
            return $response;
        }
        //释放锁
        self::$fatal = 0;
        $this->releaseLock();
        Logger::info(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,'curentPrice:'.$this->curent_price,'buyAmount:'.$this->buyAmount,"done ", 'discountId:'.$this->discountId)));

        //计算交易获得的vip经验
        $vipService = new VipService();
        $vipPoint = '';
        $isShowVip = 0;
        $sourceAmount = $this->getAnnualizedAmount($this->dealInfo['loantype'], $this->dealInfo['repayTime'], $this->money);
        if ($vipService->isShowVip($this->userId)) {
            $vipSourceType = VipEnum::VIP_SOURCE_GOLD;
            $vipPoint = $vipService->computeVipPoint($vipSourceType, $sourceAmount);
            $expectVipRase = $vipService->getExpectVipRebateForGold($this->userId, $sourceAmount);
            $expectVipRebate = $expectVipRase['rebateDesc'];
            $isShowVip = 1;
        }

        //赠金满额赠金逻辑(只考虑区间过滤和新手券屏蔽逻辑，不关注触发方式)
        // 购金锁定期 买金增金
        $o2oService = new O2OService();
        $dealBidDays = $this->dealInfo['repayTime'];
        if ($this->dealInfo['loantype'] != 5) {
            $dealBidDays *= 30;
        }
        $rebateGold = $o2oService->getRebateGold($this->userId, $this->buyAmount, $sourceAmount, $this->discountId, $dealBidDays, time());
        $data = array(
            'name'=>$this->dealInfo['name'],
            'buy_amount'=>number_format($this->buyAmount,3).'克',
            'buy_price'=>number_format($this->curent_price,2).'元/克',
            'fee'=>number_format($this->fee,2).'元',
            'money'=>number_format($this->money,2).'元',
            'goodPrice' => $this->discountSuccessDesc,
            //增加vip经验值字段
            'vipPoint' => $isShowVip ? $vipPoint : '',
            'expectVipRebate' => $isShowVip ? $expectVipRebate : '',
            'isShowVip' => $isShowVip,
            'orderId' => $this->orderId,
            'rebateGold' => $rebateGold ? $rebateGold.'克': 0,
        );

        $response['data'] = $data;
        \libs\utils\Monitor::add('GOLD_BID_SUCCESS');
        return $response;
    }



    public function checkCanBid(){

        $this->checkFirst();
        //检查浮动费率
        $this->checkvariablePriceRate();
        //检查用户信息
        $this->checkUser();
        //检查标状态
        $this->checkDeal();
        $this->checkShortAlias();
        //检查券是否可用
        $this->checkDiscount();
        //检查其其他信息
        $this->checkEnd();
    }

    /**
     * 验证标信息
     * @param array $dealInfo
     */
    protected function checkDeal(){

        $log_info = array(__CLASS__,__FUNCTION__,$this->dealId);

        Logger::info(implode(" | ", array_merge($log_info, array(' start '))));

        $response  = $this->getDealById($this->dealId,0);
        if($response['errCode']){
            throw new \Exception('标不存在');
        }

        //intro,note字段数据过大，导致GTM event写入数据库失败
        unset($response['data']['note']);
        unset($response['data']['intro']);
        $this->dealInfo = $response['data'];

        if ($this->dealInfo['dealStatus'] == 2) {
            throw new \Exception('购买克数超过项目可购克数,当前可购克数为0.000克 ');
        }

        if($this->dealInfo['dealStatus'] != 1 || $this->dealInfo['isEffect'] == 0 || $this->dealInfo['isDelete'] == 1){
            throw new \Exception('标状态不正确');
        }

        if($this->dealInfo['isVisible'] != 1){
            throw new \Exception($GLOBALS['lang']['DEAL_FAILD_OPEN']);
        }

        if(bccomp($this->dealInfo['minLoanMoney'],$this->buyAmount,4) === 1){
            throw new \Exception("低于起购克重");
        }
        // 累计购买克重
        $userTotalAmount = $this->getUserLoadGoldByDealid($this->userId,$this->dealId);
        $userTotalAmount = $userTotalAmount['data'];
        $need_money_decimal = $this->dealInfo['borrowAmount'] - $this->dealInfo['loadMoney'];
        $maxLoan = bccomp($this->dealInfo['maxLoanMoney'],0.000,3);
        if (
            $maxLoan == 1
            && bccomp(($userTotalAmount+$this->buyAmount),$this->dealInfo['maxLoanMoney'],3) == 1
            && bccomp(($need_money_decimal-$this->buyAmount),$this->dealInfo['minLoanMoney'],3) >= 0
            || ($maxLoan == 1 && bccomp($this->buyAmount,($this->dealInfo['minLoanMoney']+$this->dealInfo['maxLoanMoney']),3) >= 0 )
        ){
            throw new \Exception("当前产品最高可购".floorfix($this->dealInfo['maxLoanMoney'],3).'克,您剩余可购克数为'.bcsub($this->dealInfo['maxLoanMoney'],$userTotalAmount,3).'克');
        }
        //购买超额
        if(bccomp(bcsub($this->dealInfo['borrowAmount'],bcadd($this->dealInfo['loadMoney'],$this->buyAmount,3),3),0,3) != 0){
            if(bccomp(bcsub($this->dealInfo['borrowAmount'],bcadd($this->dealInfo['loadMoney'],$this->buyAmount,3),3),0,3) == -1){
                throw new \Exception('购买克重超过项目可购克重,当前可购克重为'.bcsub($this->dealInfo['borrowAmount'] , $this->dealInfo['loadMoney'],3).'克');
            }elseif(bccomp(bcsub($this->dealInfo['borrowAmount'],bcadd($this->dealInfo['loadMoney'],$this->buyAmount,3),3),$this->dealInfo['minLoanMoney'],3) <0){
                throw new \Exception('黄金产品即将售罄，您需要一次性购买'.bcsub($this->dealInfo['borrowAmount'] , $this->dealInfo['loadMoney'],3).'克');
            }
        }

        if(floatval($this->dealInfo['pointPercent']) >= 1){
            throw new \Exception($GLOBALS['lang']['DEAL_BID_FULL']);
        }

        Logger::info(implode(" | ", array_merge($log_info, array(' end  ',$this->dealInfo['borrowAmount'],$this->dealInfo['dealStatus'],$this->dealInfo['loadMoney'],$userTotalAmount,$need_money_decimal,$maxLoan))));
    }

    /**
     * 投资操作
     * @param array $params
     * @throws \Exception
     * @return unknown
     */
    public function bidEvent($params){
        try {
            $res = $this->bid($params);
            if(empty($res)){
                throw new \Exception('投资失败');
            }
            $params['dealLoadId'] = $res['dealLoadId'];
            $params['isFull'] = $res['isFull'];
            $params['isFirst'] = $res['isFirst'];
            $this->bidSuccess($params);
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,json_encode($params),"error:".$e->getMessage())));
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            return false;
        }
        return true;
    }

    protected function bidSuccess($params){
        $log_info = array(__CLASS__,__FUNCTION__,$params['dealLoadId'],$params['userInfo']['id'], $params['dealInfo']['id'],$params['dealInfo']['userId']);

        try {
            Logger::info(implode(" | ", array_merge($log_info, array(' start '))));
            $GLOBALS['db']->startTrans();
            $jobsModel = new JobsModel();

            // 投标成功Jobs
            $param = array();
            $param['dealLoadId']=$params['dealLoadId'];
            $param['shortAlias'] = $params['coupon'];
            $param['consumeUserId'] = $params['userInfo']['id'];
            $param['couponFields'] = array('money'=>$params['money'],'deal_id'=>$params['dealInfo']['id'],'loantype'=>$params['dealInfo']['loantype'],'repay_time'=>$params['dealInfo']['repayTime']);
            $param['discountId'] = $params['discountId'];
            $param['discountGoldOrderId'] = $params['discountGoldOrderId'];
            $param['dealName'] = $params['dealInfo']['name'];
            $param['buyPrice'] = $params['buyPrice'];
            $function = '\core\service\GoldBidService::goldBidSuccessCallback';
            $jobsModel->priority = JobsModel::PRIORITY_GOLD_BID_SUCCESS_CALLBACK;
            $ret = $jobsModel->addJob($function,array($param)); //不重试
            if ($ret === false) {
                throw new \Exception('投资Jobs任务注册失败');
            }

            //合同jobs
            $function = '\core\service\SendContractService::sendGoldConstract';
            $jobsModel->priority = JobsModel::PRIORITY_GOLD_CONTRACT;
            $param = array();
            $param['borrowId'] = $params['dealInfo']['userId'];
            $param['dealId'] = $params['dealInfo']['id'];
            $param['userId'] = $params['userInfo']['id'];
            $param['loadId'] = $params['dealLoadId'];
            // 生成用户投资的合同
            $param['isFull'] = false;
            if (false == $jobsModel->addJob($function,$param)) {
                throw new \Exception('投资Jobs任务注册失败');
            }
            // 满标单独生成一条jobs
            if ($params['isFull']) {
                $param['loadId'] = 0;
                $param['isFull'] = true;
                if (false == $jobsModel->addJob($function,$param)) {
                    throw new \Exception('投资Jobs任务注册失败');
                }
            }

            // 购金锁定期 买金增金
            // repatTime may be month or day
            $dealBidDays = $params['dealInfo']['repayTime'] * 30;
            if ($params['dealInfo']['loantype'] == 5) {
                $dealBidDays = $params['dealInfo']['repayTime'];
            }
            $extra = array(
                'goldMoney'=>$params['money'],
                'buyPrice'=>$params['buyPrice'],
                'dealTags'=>$params['dealInfo']['tags'],
                'dealName'=>$params['dealInfo']['name'],
                'dealRepayTime'=>$params['dealInfo']['repayTime'],
                'discountId' => $params['discountId'],
                'dealBidDays' => $dealBidDays,
            );

            // 处理o2o的触发,这里的action值必须正确
            $action = CouponGroupEnum::TRIGGER_GOLD_REPEAT_DOBID;
            if ($params['isFirst']) {
                $action = CouponGroupEnum::TRIGGER_GOLD_FIRST_DOBID;
            }

            // 计算年化 价格 * 天数 / 360
            $annualizedAmount = floorfix($params['money'] * $params['dealInfo']['repayTime'] / 360, 2);
            O2OService::triggerO2OOrder(
                $params['userInfo']['id'],
                $action,
                $params['dealLoadId'],
                0,
                $params['buyAmount'],
                $annualizedAmount,
                CouponGroupEnum::CONSUME_TYPE_GOLD,
                CouponGroupEnum::TRIGGER_TYPE_GOLD,
                $extra
            );

            // 增加vip经验埋点
            $sourceType = VipEnum::VIP_SOURCE_GOLD;
            $vipParam = array(
                'userId' => $params['userInfo']['id'],
                //TODO 黄金年化需要明确公式
                'sourceType' => $sourceType,
                'sourceAmount' => $this->getAnnualizedAmount($params['dealInfo']['loantype'], $params['dealInfo']['repayTime'], $params['money']),
                'token' => $sourceType.'_'.$params['userInfo']['id'].'_'.$params['dealLoadId'],
                'info' => $params['dealInfo']['name'].",{$params['money']}元",
                'sourceId' => $params['dealLoadId'],
            );
            $function = '\core\service\vip\VipService::updateVipPointCallback';
            $jobsModel->priority = JobsModel::PRIORITY_GOLD_BID_SUCCESS_CALLBACK;
            $ret = $jobsModel->addJob($function, array('param'=>$vipParam));
            if ($ret === false) {
                throw new \Exception('Jobs任务注册失败');
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'params:' . json_encode($param), $e->getMessage())));
            $GLOBALS['db']->rollback();
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'data:'.json_encode($param),"error:".$e->getMessage())));
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            return false;
        }

        if (isset($params['discountId']) && $params['discountId'] > 0) {
            \SiteApp::init()->cache->set(DiscountService::CACHE_CONSUME_PREFIX.$params['discountId'], 1, 3600);//投资劵消费缓存
        }
        Logger::info(implode(" | ", array_merge($log_info, array(' done '))));

        //满标触发自动上标，不必要做到事务里面，允许失败
        if ($params['isFull']) {
            $dealQueueService = new DealQueueService(0,DealQueueService::GOLD);
            $dealQueueService->process($param['dealId']);
        }
        return true;
    }

    /**
     * 投资对用户操作 资金冻结，红包充值
     * @param array $params
     * @return boolean
     */
    public function userEvent($params) {

        $log_info = array(__CLASS__,__FUNCTION__,$params['dealInfo']['id'],$params['money'],$params['fee'],$params['orderId'],$params['userInfo']['id']);
        Logger::info(implode(" | ", array_merge($log_info, array(' start '))));
        //更改资金记录
        $dealInfo = $params['dealInfo'];
        $userInfo = $params['userInfo'];
        $money = $params['money']-$params['fee'];//资金记录分两条
        $fee= $params['fee'];
        $msg = "编号{$dealInfo['id']} {$dealInfo['name']}";
        try {
            $GLOBALS['db']->startTrans();

            //红包充值
            $params['bonusAccountInfo']=  $params['moneyInfo']['bonusInfo']['accountInfo'];
            $bonus = $this->bidBonusTransfer($params);
            if(empty($bonus)){
                throw new \Exception('红包充值失败');
            }

            $moneyOrderService = new MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
            $moneyOrderService->changeMoneyAsyn = false;
            $moneyOrderService->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
            $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDLOCK, $money, "买金冻结", $msg, userModel::TYPE_LOCK_MONEY);

            if(bccomp($fee,0,2) == 1){
                $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDFEELOCK, $fee, "买金手续费冻结", $msg, userModel::TYPE_LOCK_MONEY);
            }

            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__, 'data:'.json_encode($params), "error:".$e->getMessage())));
            $GLOBALS['db']->rollback();
            //changeMoney捕获到订单已经存在的情况下，返回true,GTM 重试导致异常情况
            if ($e instanceof MoneyOrderException && $e->getCode() ==MoneyOrderException::CODE_ORDER_EXIST){
               return true;
            }
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            return false;
        }

        Logger::info(implode(" | ", array_merge($log_info, array(' done ',json_encode($params)))));
    }


   /**
    * 投资对用户操作回滚 资金冻结回滚
    * @param array $params
    * @throws \Exception
    * @return boolean
    */
    public function userEventRollback($params){

        $log_info = array(__CLASS__,__FUNCTION__,json_encode($params));
        Logger::info(implode(" | ", array_merge($log_info, array(' start '))));
        //更改资金记录
        $dealInfo = $params['dealInfo'];
        $userInfo = $params['userInfo'];
        $money = $params['money']-$params['fee'];
        $fee= $params['fee'];//资金记录分两条
        try {
            $GLOBALS['db']->startTrans();
            $msg = "编号{$dealInfo['id']} {$dealInfo['name']}";

            $moneyOrderService = new MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
            $moneyOrderService->changeMoneyAsyn = false;
            $moneyOrderService->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
            $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDRELEASELOCK, -$money, "买金失败解冻", $msg, userModel::TYPE_LOCK_MONEY);

            if(bccomp($fee,0,2) == 1){
                $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDFEERELEASELOCK, -$fee, "买金失败解冻", $msg, userModel::TYPE_LOCK_MONEY);
            }
            $GLOBALS['db']->commit();
            return true;
        }
        catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__, __FUNCTION__, 'data:'.json_encode($params), "error:".$e->getMessage())));
            $GLOBALS['db']->rollback();
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            if ($e instanceof MoneyOrderException && $e->getCode() ==MoneyOrderException::CODE_ORDER_EXIST){
                return true;
            }
            return false;
        }

        Logger::info(implode(" | ", array_merge($log_info, array(' done ',json_encode($params)))));
    }

    /**
     * 黄金投资
     * @param array $params
     * @throws \Exception
     * @return number dealloadId
     */
    protected function bid($params){

        $log_info = array(__CLASS__,__FUNCTION__,);


        $orderId = $params['orderId'];
        $dealInfo = $params['dealInfo'];
        $userInfo = $params['userInfo'];
        $buyPrice = $params['buyPrice'];
        $buyAmount = $params['buyAmount'];
        $coupon = $params['coupon'];
        $money = $params['money'];
        $fee = $params['fee'];
        $request = new RequestCommon();
        $data = array(
            'orderId'=>$orderId,
            'dealId'=>$dealInfo['id'],
            'userId'=>$userInfo['id'],
            'userName'=>$userInfo['user_name'],
            'userDealName' => get_deal_username($userInfo['id']),
            'shortAlias'=>$coupon,
            'siteId'=>'1',
            'buyPrice'=>$buyPrice,
            'buyAmount'=>$buyAmount,
            'discountId' => $params['discountId'],
            'money' => $money,
            'fee' => $fee
        );
        Logger::info(implode(" | ", array_merge($log_info, array(' start ',json_encode($data)))));
        $request->setVars($data);
        $response = $this->requestGold('NCFGroup\Gold\Services\Deal', 'doBid', $request);
        if(!empty($response['errCode'])){
            throw new \Exception($response['errMsg']);
        }
        Logger::info(implode(" | ", array_merge($log_info, array(' done ',json_encode($data)))));
        return $response['data'];
    }

    /**
     * getAnnualizedAmount计算黄金投资年化
     *
     * @author liguizhi <liguizhi@ucfgroup.com>
     * @date 2017-08-28
     * @param mixed $loanType
     * @param mixed $repayTime
     * @param mixed $amount
     * @access public
     * @return void
     */
    public function getAnnualizedAmount($loanType, $repayTime, $amount) {
        switch($loanType){
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']:
                $repayTime = $repayTime;
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']:
                $repayTime = $repayTime * 30;
                break;
        }

        //年化投资金额=投资金额*期限*/360
        return round(bcdiv(bcmul($amount , $repayTime, 2) ,GoldFinance::DAY_OF_YEAR, 2), 2);
    }
}
