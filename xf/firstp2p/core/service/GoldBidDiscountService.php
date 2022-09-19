<?php
/**
 * 黄金项目service
 * @data 2017.05.16
 * @author wangzhen wangzhen@ucfgroup.com
 */


namespace core\service;

use libs\utils\Logger;
use core\service\GoldBidBaseService;
use core\service\TransferService;
use core\exception\O2OBuyDiscountGoldException;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\DiscountModel;
use core\dao\DiscountRateModel;
use core\tmevent\gold\BidCurrentEvent;
use core\tmevent\discount\UserCurrentEvent;
use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use core\dao\FinanceQueueModel;
use NCFGroup\Protos\Gold\Enum\CommonEnum;
use NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\service\MoneyOrderService;
use core\exception\MoneyOrderException;

/**
 * 使用黄金券购买活期黄金商品
 */
class GoldBidDiscountService extends GoldBidBaseService {
    public function __construct($userId = '', $buyAmount = '', $buyPrice = '', $coupon = '', $orderId = '',
                                $discount = array()) {
        parent::__construct();
        $this->dealId = CommonEnum::GOLD_CURRENT_DEALID;
        $this->userId = $userId;
        $this->buyAmount = $buyAmount;
        // 购买价格
        $this->buyPrice = $buyPrice;
        // 邀请码
        $this->coupon = trim($coupon);

        $this->orderId = $orderId;
        $this->discount = $discount;
    }

    public function doBid() {
        // 验证信息
        try {
            $this->checkCanBid();
        } catch (\Exception $e) {
            Logger::error(implode(' | ',array(__CLASS__,__FUNCTION__,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,"error:".$e->getMessage())));

            // 这里的购买异常需要特殊处理
            throw new O2OBuyDiscountGoldException($e->getMessage(), $e->getCode(), $e);
        }

        // 手续费为0
        $this->fee = 0;
        // 计算购买金额
        $this->money= floorfix(bcmul($this->curent_price, $this->buyAmount, 4), 2);

        $params = array(
            'orderId' =>$this->orderId,
            'dealInfo' => $this->dealInfo,
            'userInfo' => $this->userInfo,
            'buyPrice' => $this->curent_price,
            'buyAmount' => $this->buyAmount,
            'money' => $this->money,
            'fee' => $this->fee,
            'coupon' => $this->coupon,
            'type'=>CommonEnum::GOLD_DISCOUNT_TYPE_ID,
            'discount' => $this->discount
        );
        // 投资相关
        try {
            \libs\utils\Monitor::add('GOLD_BID_CURRENT_DISCOUNT_START');
            // 基于GTM的投资逻辑
            $gtm = new GlobalTransactionManager();
            $gtm->setName('goldDiscountBid');

            // 投资操作
            $gtm->addEvent(new BidCurrentEvent($params));
            // 用户冻结资金
            $gtm->addEvent(new UserCurrentEvent($params));

            // 同步执行
            $bidRes = $gtm->execute();

            if($bidRes === false){
                Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,'curentPrice:'.$this->curent_price,'buyAmount:'.$this->buyAmount,"msg:GTM事务处理失败")));
                throw new O2OBuyDiscountGoldException('投资失败');
            }
        } catch (\Exception $e) {
            Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'orderId:'.$this->orderId,'dealId:'.$this->dealId ,'userId:'.$this->userId,'buyPrice:'.$this->buyPrice,'curentPrice:'.$this->curent_price,'buyAmount:'.$this->buyAmount,"msg:".$e->getMessage())));
            if (isset($params['userInfo'])) {
                $userInfo = $params['userInfo'];
                $params['userInfo'] = $userInfo->getRow();
            }

            \libs\utils\Alarm::push(CommonEnum::ALARM_PUSH_FATAL_ERROR_KEY,'errMsg:'.$e->getMessage(), $params);
            \libs\utils\Monitor::add('GOLD_BID_CURRENT_DISCOUNT_FAILED');

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

        \libs\utils\Monitor::add('GOLD_BID_CURRENT_DISCOUNT_SUCCESS');
        return $data;
    }

    public function checkCanBid() {
        // 核销参数判断
        $discount = $this->discount;
        if (empty($discount)) {
            throw new \Exception('黄金券不能为空');
        }

        if (empty($discount['wxUserId'])) {
            throw new \Exception('黄金券出资方不能为空');
        }

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
     * 价格变动率
     */
    protected function checkvariablePriceRate() {
        $response= $this->getGoldPrice(true);
        if ($response['errCode'] != '0' || empty($response['data']['gold_price'])) {
            throw new \Exception('当前非交易时段');
        }

        $this->curent_price = $response['data']['gold_price'];
        // 这里不管黄金价格的波动
        if (bccomp(bcadd($this->curent_price,$this->price_rate,2),$this->buyPrice,2) <0 || bccomp(bcsub($this->curent_price,$this->price_rate,2),$this->buyPrice,2) >0 ){
            // 如果黄金价格波动，则取当前的黄金金价
            $this->buyPrice = $this->curent_price;
        }
    }

    /**
     * 验证标信息
     * @param array $dealInfo
     */
    protected function checkDeal(){
        $this->dealInfo = $this->getDealCurrent();
        if (empty($this->dealInfo)){
            throw new \Exception('标不存在');
        }

        // 获取黄金运营账号黄金库存
        $this->dealInfo['borrowAmount'] = $this->getGoldByUserId($this->dealInfo['userId']);

        // 购买超额
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
        $discount = $params['discount'];
        $money = $params['money'] - $params['fee'];//资金记录分两条
        $fee= $params['fee'];
        $orderId = $params['orderId'];
        $msg = "买入{$dealInfo['name']},单号{$orderId}";

        try {
            $GLOBALS['db']->startTrans();

            // 券使用凭证
            $updateCond = "discount_id = {$discount['id']} AND status = 0";
            $updateData = array(
                'consume_type' =>  $discount['consumeType'],
                'consume_id' => $discount['dealLoadId'],
                'status' => 1
            );

            DiscountModel::instance()->updateAll($updateData, $updateCond, true);

            // 添加返利记录
            $data = array();
            $data['user_id'] = $userInfo['id'];
            $data['discount_id'] = $discount['id'];
            $data['discount_type'] = CouponGroupEnum::DISCOUNT_TYPE_GOLD;
            $data['consume_type'] = $discount['consumeType'];
            $data['consume_id'] = $discount['dealLoadId'];
            $data['allowance_type'] = $discount['goodsType'];
            $data['allowance_money'] = $params['money'];
            $data['allowance_id'] = $orderId;
            $data['token'] = $discount['id'];
            $data['create_time'] = date('Y-m-d H:i:s');
            $recordId = DiscountRateModel::instance()->addRecord($data);
            if (!$recordId) {
                throw new \Exception('添加返利记录失败');
            }

            $type = '黄金券充值';
            $receiverType = '黄金券充值';
            $note = "使用{$discount['productName']},购买{$discount['dealName']}";
            $receiverNote = "使用黄金券充值购买{$params['buyAmount']}克{$dealInfo['name']}";

            $transferService = new TransferService();
            $transferService->transferById($discount['wxUserId'], $userInfo['id'],
                $params['money'], $type, $note, $receiverType, $receiverNote);

            $moneyOrderService = new MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
            $moneyOrderService->changeMoneyAsyn = false;
            $moneyOrderService->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
            $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDDISCOUNT, -$money, "买金", $msg, userModel::TYPE_MONEY);
            if(bccomp($fee,0,2) == 1){
                $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDDISCOUNTFEE, -$fee, "买金手续费", $msg, userModel::TYPE_MONEY);
            }
            $moneyOrderService->changeMoneyAsyn = true;
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
            if (!empty($syncRemoteData)) {
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

            \libs\utils\Alarm::push(CommonEnum::ALARM_PUSH_FATAL_ERROR_KEY,'errMsg:'.$e->getMessage(),$params);
            //changeMoney捕获到订单已经存在的情况下，返回true,GTM 重试导致异常情况
            if ($e instanceof MoneyOrderException && $e->getCode() ==MoneyOrderException::CODE_ORDER_EXIST){
                return true;
            }

            // 幂等处理
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return true;
            }

            return false;
        }
        return true;
    }
}
