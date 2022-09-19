<?php
/**
 * 黄金充值
 * 黄金项目service
 * @data 2017.07.20
 * @author wangzhen wangzhen@ucfgroup.com
 */


namespace core\service;

use libs\utils\Logger;
use core\service\CouponService;
use core\service\GoldBidBaseService;
use core\service\TransferService;
use core\exception\O2OBuyDiscountGoldException;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\JobsModel;
use core\tmevent\gold\BidCurrentEvent;
use core\tmevent\gold\UserRechargeEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\dao\FinanceQueueModel;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use core\service\MoneyOrderService;
use core\exception\MoneyOrderException;

/**
 * 黄金充值
 */
class GoldBidRechargeService extends GoldBidBaseService {
    public function __construct($userId = '', $buyAmount = '', $buyPrice = '', $coupon = '', $orderId = '',$wxUserId = 0) {
        parent::__construct();
        $this->dealId = CommonEnum::GOLD_CURRENT_DEALID;
        $this->userId = $userId;
        $this->buyAmount = $buyAmount;
        // 购买价格
        $this->buyPrice = $buyPrice;
        // 邀请码
        $this->coupon = trim($coupon);
        // 浮动利率
        $this->orderId = $orderId;
        //黄金充值出资方id
        $this->wxUserId = $wxUserId;
    }

    public function doBid() {
        //验证信息
        try {
            $this->checkCanBid();
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,"error:".$e->getMessage())));
            return false;
        }

        // 手续费为0
        $this->fee = 0;
        // 计算购买金额
        $this->money= floorfix(bcmul($this->curent_price, $this->buyAmount, 4), 2);

        // 投资相关
        try {
            \libs\utils\Monitor::add('GOLD_BID_CURRENT_RECHARGE_START');
            // 基于GTM的投资逻辑
            $gtm = new GlobalTransactionManager();
            $gtm->setName('goldRechargeBid');

            $params = array(
                'orderId' =>$this->orderId,
                'dealInfo' => $this->dealInfo,
                'userInfo' => $this->userInfo,
                'buyPrice' => $this->curent_price,
                'buyAmount' => $this->buyAmount,
                'money' => $this->money,
                'fee' => $this->fee,
                'coupon' => $this->coupon,
                'type'=>CommonEnum::GOLD_CURRENT_TYPE_ID,
                'wxUserId' => $this->wxUserId,
            );

            // 投资操作
            $gtm->addEvent(new BidCurrentEvent($params));
            // 用户冻结资金
            $gtm->addEvent(new UserRechargeEvent($params));

            // 同步执行
            $bidRes = $gtm->execute();

            if($bidRes === false){
                Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,'curentPrice:'.$this->curent_price,'buyAmount:'.$this->buyAmount,"msg:GTM事务处理失败")));
                throw new \Exception('投资失败');
            }
        } catch (\Exception $e) {
            Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,'curentPrice:'.$this->curent_price,'buyAmount:'.$this->buyAmount,"msg:".$e->getMessage())));
            if (isset($params['userInfo'])) {
                $userInfo = $params['userInfo'];
                $params['userInfo'] = $userInfo->getRow();
            }

            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(), $params);
            \libs\utils\Monitor::add('GOLD_BID_CURRENT_RECHARGE_FAILED');

            throw $e;
        }

        Logger::info(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,'curentPrice:'.$this->curent_price,'buyAmount:'.$this->buyAmount,"msg:投资成功")));

        $data = array(
            'name'=>$this->dealInfo['name'],
            'buy_amount'=>$this->buyAmount,
            'buy_price'=>$this->curent_price,
            'fee'=>$this->fee,
            'money'=>$this->money
        );

        \libs\utils\Monitor::add('GOLD_BID_CURRENT_RECHARGE_SUCCESS');
        return $data;
    }

    public function checkCanBid() {
        $this->checkFirst();
        //检查浮动费率
        $this->checkvariablePriceRate();
        //检查用户信息
        $this->checkUser();
        //检查标状态
        $this->checkDeal();
        //$this->checkShortAlias();
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

        //购买超额
        if ($this->dealInfo['borrowAmount'] <= 0) {
            throw new \Exception('购买克重超过项目可购克重,当前可购克重为0克');
        }
        if (bccomp(bcsub($this->dealInfo['borrowAmount'], $this->buyAmount,3),0,3) == -1) {
            throw new \Exception('购买克重超过项目可购克重,当前可购克重为'.$this->dealInfo['borrowAmount'].'克');
        }
    }

    protected function bidSuccess($params) {
        return true;
    }

    /**
     * 投资对用户操作 资金冻结，红包充值
     * @param array $params
     * @return boolean
     */
    public function userEvent($params) {
        // 更改资金记录
        $dealInfo = $params['dealInfo'];
        $userInfo = $params['userInfo'];
        $money = $params['money']-$params['fee'];//资金记录分两条
        $fee= $params['fee'];
        $orderId = $params['orderId'];
        $msg = "买入{$dealInfo['name']},单号{$orderId}";
        try {
            $GLOBALS['db']->startTrans();

            $type = '充值';
            $receiverType = '充值';
            $note = "2017年7月赠金活动,用户".$userInfo['id'];
            $receiverNote = "2017年7月赠金活动";

            $transferService = new TransferService();
            $transferService->transferById($params['wxUserId'], $userInfo['id'],
                $params['money'], $type, $note, $receiverType, $receiverNote);

            $moneyOrderService = new MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
            $moneyOrderService->changeMoneyAsyn = false;
            $moneyOrderService->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
            $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDDISCOUNT, -$money, "买金", $msg, userModel::TYPE_MONEY);
            if(bccomp($fee,0,2) == 1){
                $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDDISCOUNTFEE, -$fee, "买金手续费", $msg, userModel::TYPE_MONEY);
            }
            $moneyOrderService->changeUserMoney($params['orderId'], $dealInfo['userId'], GoldMoneyOrderEnum::SELLGOLDDISCOUNT, $money, "买金", $msg, userModel::TYPE_MONEY);
            if(bccomp($fee,0,2) == 1){
                $moneyOrderService->changeUserMoney($params['orderId'], $dealInfo['userId'], GoldMoneyOrderEnum::SELLGOLDDISCOUNTFEE, $fee, "买金手续费", $msg, userModel::TYPE_MONEY);
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
            if (isset($params['userInfo'])) {
                $userInfo = $params['userInfo'];
                $params['userInfo'] = $userInfo->getRow();
            }

            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'data:'.json_encode($params),"error:".$e->getMessage())));
            $GLOBALS['db']->rollback();

            \libs\utils\Alarm::push('gold_exception','errMsg:'.$e->getMessage(),$params);
            //changeMoney捕获到订单已经存在的情况下，返回true,GTM 重试导致异常情况
            if ($e instanceof MoneyOrderException && $e->getCode() ==MoneyOrderException::CODE_ORDER_EXIST){
                return true;
            }
            return false;
        }
        return true;
    }
}
