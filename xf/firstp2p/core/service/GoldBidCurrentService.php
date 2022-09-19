<?php
/**
 * 黄金活期项目service
 * @data 2017.05.16
 * @author wangzhen wangzhen@ucfgroup.com
 */


namespace core\service;

use libs\utils\Logger;
use core\service\GoldBidBaseService;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\JobsModel;
use NCFGroup\Protos\Gold\RequestCommon;
use core\tmevent\gold\BidCurrentEvent;
use core\tmevent\gold\UserCurrentEvent;
use core\tmevent\bid\BonusGoldCurrentConsumeEvent;
use core\tmevent\discount\DiscountConsumeEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\dao\FinanceQueueModel;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use core\service\MoneyOrderService;
use core\exception\MoneyOrderException;
use core\service\vip\VipService;

class GoldBidCurrentService extends GoldBidBaseService {

    public function __construct($userId = '', $buyAmount = '', $buyPrice = '', $coupon = '',$orderId = '',
                                $discountId = 0, $discountGroupId = 0, $discountSign = '', $discountSuccessDesc = '') {
        parent::__construct();
        $this->dealId = CommonEnum::GOLD_CURRENT_DEALID;
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
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,"error:".$e->getMessage())));
            $response['errCode'] = 1;
            $response['msg'] = $e->getMessage();
            return $response;
        }

        //投资相关
        try {
            \libs\utils\Monitor::add('GOLD_BID_CURRENT_START');
            //基于TM 的投资逻辑
            $gtm = new GlobalTransactionManager();
            $gtm->setName('goldCurrentBid');

            $params = array(
                'orderId' =>$this->orderId,
                'dealInfo' => $this->dealInfo,
                'userInfo' => $this->userInfo,
                'moneyInfo' =>$this->moneyInfo,
                'buyPrice' => $this->curent_price,
                'buyAmount' => $this->buyAmount,
                'money' => $this->money,
                'fee' => $this->fee,
                'type' => CommonEnum::GOLD_CURRENT_TYPE_ID,
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
                $gtm->addEvent(new BonusGoldCurrentConsumeEvent($this->userInfo['id'],$bonusInfo,$this->orderId,$this->dealInfo['name']));
            }

            //投资操作
            $gtm->addEvent(new BidCurrentEvent($params));
            //用户转账
            $gtm->addEvent(new UserCurrentEvent($params));

            $bidRes = $gtm->execute(); // 同步执行

            if($bidRes === false){
                Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,'curentPrice:'.$this->curent_price,'buyAmount:'.$this->buyAmount, 'discountId:'.$this->discountId,"msg:GTM事务处理失败")));
                throw new \Exception('投资失败');
            }

        } catch (\Exception $e) {
            //释放锁
            self::$fatal = 0;
            $this->releaseLock();
            Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,'curentPrice:'.$this->curent_price,'buyAmount:'.$this->buyAmount, 'discountId:'.$this->discountId,"msg:".$e->getMessage())));
            $response['errCode'] = 1;
            $response['msg'] = $e->getMessage();
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            \libs\utils\Monitor::add('GOLD_BID_CURRENT_FAILED');
            return $response;
        }
        //释放锁
        self::$fatal = 0;
        $this->releaseLock();
        Logger::info(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,'curentPrice:'.$this->curent_price,'buyAmount:'.$this->buyAmount,"msg:投资成功", 'discountId:'.$this->discountId)));
        //是否显示vip加息
        $vipService = new VipService();
        $raiseInterest = 0;
        $vipInfo = $vipService->getVipInfo($this->userId);
        if ($vipInfo) {
            $raiseInterest = $vipService->getVipInterest($vipInfo['service_grade']);
        }
        $data = array(
            'name'=>$this->dealInfo['name'],
            'buy_amount'=>number_format($this->buyAmount,3).'克',
            'buy_price'=>number_format($this->curent_price,2).'元/克',
            'fee'=>number_format($this->fee,2).'元',
            'money'=>number_format($this->money,2).'元',
            'repay_start_time'=>date('Y-m-d',time()+86400),
            'goodPrice' => $this->discountSuccessDesc,
            'vipPoint' => '购买成功后每日计算并发放',//显示“购买成功后每日计算并发放”,提前增加字段供app定版用
            'vipInfo' => ($raiseInterest > 0) ? '购买成功后每日计算并发放' : '',//购买成功后每日计算并发放”,提前增加字段供app定版用
        );
        $response['data'] = $data;
        \libs\utils\Monitor::add('GOLD_BID_CURRENT_SUCCESS');
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
        $this->dealInfo = $this->getDealCurrent();
        if(empty($this->dealInfo)){
            throw new \Exception('标不存在');
        }

        //获取黄金运营账号黄金库存
        $this->dealInfo['borrowAmount'] = $this->getGoldByUserId($this->dealInfo['userId']);

        if(bccomp($this->dealInfo['minBuyAmount'],$this->buyAmount,4) === 1){
            throw new \Exception("低于起购克重");
        }

        //购买是否超过当日购买最大克数
        $totalAmount = $this->getTotalAmountByDate($this->userId,date("Y-m-d"));
        if(bccomp($this->dealInfo['maxBuyAmount'],0,3) && bccomp(bcadd($totalAmount,$this->buyAmount,3),$this->dealInfo['maxBuyAmount'],3)>0){
            throw new \Exception("优金宝单日最高可购".floorfix($this->dealInfo['maxBuyAmount'],3)."克,您今日剩余可购克数为".(bccomp($this->dealInfo['maxBuyAmount'],$totalAmount,3)>0?bcsub($this->dealInfo['maxBuyAmount'],$totalAmount,3):floorfix(0,3))."克");
        }

        //购买超额
        if ($this->dealInfo['borrowAmount'] <= 0) {
            throw new \Exception('购买克重超过项目可购克重,当前可购克重为0克');
        }
        if(bccomp(bcsub($this->dealInfo['borrowAmount'],$this->buyAmount,3),0,3) != 0){
            if(bccomp(bcsub($this->dealInfo['borrowAmount'],$this->buyAmount,3),0,3) == -1){
                throw new \Exception('购买克重超过项目可购克重,当前可购克重为'.$this->dealInfo['borrowAmount'].'克');
            }elseif(bccomp(bcsub($this->dealInfo['borrowAmount'],$this->buyAmount,3),$this->dealInfo['minBuyAmount'],3) <0){
                throw new \Exception('黄金产品即将售罄，您需要一次性购买'.$this->dealInfo['borrowAmount'].'克');
            }
        }

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
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,json_encode($params),"error:".$e->getMessage())));
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            return false;
        }
        return true;
    }

    /**
     * 投资操作
     * @param array $params
     * @throws \Exception
     * @return unknown
     */
    public function bidRollbackEvent($params){
        try {
            $res = $this->bidBack($params);
            if(empty($res)){
                throw new \Exception('投资回滚失败');
            }
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,json_encode($params),"error:".$e->getMessage())));
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            return false;
        }
        return true;
    }

    protected function bidSuccess($params){
        try {
            $GLOBALS['db']->startTrans();
            $jobsModel = new JobsModel();

            //投标成功Jobs
            $param = array();
            $dealLoadInfo = $this->getDealLoadCurrentByOrderId($params['orderId']);
            $param['dealLoadId'] = $dealLoadInfo['id'];
            $param['shortAlias'] = $params['coupon'];
            $param['consumeUserId'] = $params['userInfo']['id'];
            $param['couponFields'] = array('money'=>$params['money'],'deal_id'=>$params['dealInfo']['id'],'loantype'=>$params['dealInfo']['loantype'],'repay_time'=>$params['dealInfo']['repayTime']);
            $param['discountId'] = $params['discountId'];
            $param['discountGoldOrderId'] = $params['discountGoldOrderId'];
            $param['dealName'] = $params['dealInfo']['name'];
            $param['buyPrice'] = $params['buyPrice'];

            $function = '\core\service\GoldBidCurrentService::goldBidSuccessCallback';
            $jobsModel->priority = JobsModel::PRIORITY_GOLD_BID_SUCCESS_CALLBACK;
            $ret = $jobsModel->addJob($function,array($param)); //不重试
            if ($ret === false) {
                throw new \Exception('投资Jobs任务注册失败');
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, 'params:' . json_encode($param), $e->getMessage())));
            $GLOBALS['db']->rollback();
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'data:'.json_encode($param),"error:".$e->getMessage())));
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            return false;
        }
        return true;
    }

    /**
     * 投资对用户操作 资金冻结，红包充值
     * @param array $params
     * @return boolean
     */
    public function userEvent($params){
        //更改资金记录
        $dealInfo = $params['dealInfo'];
        $userInfo = $params['userInfo'];
        $money = $params['money']-$params['fee'];//资金记录分两条
        $fee= $params['fee'];
        $orderId = $params['orderId'];
        $msg = "买入{$dealInfo['name']},单号{$orderId}";
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
            $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDCURRENT, -$money, "买金", $msg, userModel::TYPE_MONEY);
            if(bccomp($fee,0,2) == 1){
                $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDCURRENTFEE, -$fee, "买金手续费", $msg, userModel::TYPE_MONEY);
            }
            $moneyOrderService->changeMoneyAsyn = true;
            $moneyOrderService->changeUserMoney($params['orderId'], $dealInfo['userId'], GoldMoneyOrderEnum::SELLGOLDCURRENT, $money, "买金", $msg, userModel::TYPE_MONEY);
            if(bccomp($fee,0,2) == 1){
                $moneyOrderService->changeUserMoney($params['orderId'], $dealInfo['userId'], GoldMoneyOrderEnum::SELLGOLDCURRENTFEE, $fee, "买金手续费", $msg, userModel::TYPE_MONEY);
            }

            $syncRemoteData[] = array(
                    'outOrderId' => 'GOLD_CURRENT_BID|' . $orderId,
                    'payerId' => $userInfo['id'],
                    'receiverId' => $dealInfo['userId'],
                    'repaymentAmount' => bcmul(bcadd($money,$fee,2), 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $orderId,
            );

            //同步支付
            if ( !empty($syncRemoteData)) {
                FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
            }

            $result = $this->bidSuccess($params);
            if(empty($result)){
                throw new \Exception('投资成功回调失败');
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'data:'.json_encode($params),"error:".$e->getMessage())));
            $GLOBALS['db']->rollback();
            //changeMoney捕获到订单已经存在的情况下，返回true,GTM 重试导致异常情况
            if ($e instanceof MoneyOrderException && $e->getCode() ==MoneyOrderException::CODE_ORDER_EXIST){
                return true;
            }
            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            return false;
        }
        return true;
    }

    /**
     * 黄金投资
     * @param array $params
     * @throws \Exception
     * @return number dealloadId
     */
    protected function bid($params){
        $orderId = $params['orderId'];
        $dealInfo = $params['dealInfo'];
        $userInfo = $params['userInfo'];
        $buyPrice = $params['buyPrice'];
        $buyAmount = $params['buyAmount'];
        $coupon = $params['coupon'];
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
                'fee'=>$params['fee'],
                'type'=>$params['type']
        );
        $request->setVars($data);
        $response = $this->requestGold('NCFGroup\Gold\Services\DealCurrent', 'doBid', $request);
        if(!empty($response['errCode'])){
            throw new \Exception($response['errMsg']);
        }
        return $response['data'];
    }

    /**
     * 黄金投资
     * @param array $params
     * @throws \Exception
     * @return number dealloadId
     */
    protected function bidBack($params){
        $orderId = $params['orderId'];
        $userInfo = $params['userInfo'];
        $buyAmount = $params['buyAmount'];
        $type = $params['type'];
        $request = new RequestCommon();
        $data = array(
                'orderId'=>$orderId,
                'userId'=>$userInfo['id'],
                'buyAmount'=>$buyAmount,
                'type' => $type

        );
        $request->setVars($data);
        $response = $this->requestGold('NCFGroup\Gold\Services\DealCurrent', 'doBackBid', $request);
        if(!empty($response['errCode'])){
            throw new \Exception($response['errMsg']);
        }
        return $response['data'];
    }

}
