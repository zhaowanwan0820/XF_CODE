<?php
/**
 * 买金赠金服务
 */

namespace core\service;

use libs\utils\Logger;
use core\service\GoldBidBaseService;
use core\service\TransferService;
use core\dao\UserModel;
use core\dao\DealModel;
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
use core\dao\OtoAllowanceLogModel;
use core\service\WXBonusService;
use core\dao\JobsModel;

/**
 * 买金赠金服务
 */
class GoldBidRebateService extends GoldBidBaseService {
    public function __construct($userId = '', $buyAmount = '', $buyPrice = '', $coupon = '', $orderId = '',
                                $rebateConf = array()) {
        parent::__construct();
        $this->dealId = CommonEnum::GOLD_CURRENT_DEALID;
        $this->userId = $userId;
        $this->buyAmount = $buyAmount;
        // 购买价格
        $this->buyPrice = $buyPrice;
        // 邀请码
        $this->coupon = trim($coupon);

        $this->orderId = $orderId;
        $this->rebateConf = $rebateConf;
    }

    public function doBid() {
        // 验证信息
        try {
            $this->checkCanBid();
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(
                __CLASS__,
                __FUNCTION__,
                'dealId:'.$this->dealId ,
                'userId:'.$this->userId,
                'buyPrice:'.$this->buyPrice,
                "error:".$e->getMessage()
            )));
            // 这里的购买异常需要特殊处理
            throw $e;
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
            'type'=>CommonEnum::GOLD_REBATE_TYPE_ID,
            'rebateConf' => $this->rebateConf
        );
        // 投资相关
        try {
            \libs\utils\Monitor::add('GOLD_BID_CURRENT_REBATE_START');
            // 基于GTM的投资逻辑
            $gtm = new GlobalTransactionManager();
            $gtm->setName('goldRebateBid');

            // 投资操作
            $gtm->addEvent(new BidCurrentEvent($params));
            // 用户冻结资金
            $gtm->addEvent(new UserCurrentEvent($params));

            // 同步执行
            $bidRes = $gtm->execute();
            if($bidRes === false) {
                throw new \Exception('GTM事务处理失败');
            }
        } catch (\Exception $e) {
            Logger::error(implode('|', array(
                __CLASS__,
                __FUNCTION__,
                'orderId:'.$this->orderId,
                'dealId:'.$this->dealId ,
                'userId:'.$this->userId,
                'buyPrice:'.$this->buyPrice,
                'curentPrice:'.$this->curent_price,
                'buyAmount:'.$this->buyAmount,
                "msg:".$e->getMessage()
            )));

            if (isset($params['userInfo'])) {
                $userInfo = $params['userInfo'];
                $params['userInfo'] = $userInfo->getRow();
            }

            \libs\utils\Alarm::push(CommonEnum::ALARM_PUSH_FATAL_ERROR_KEY, 'errMsg:'.$e->getMessage(), $params);
            \libs\utils\Monitor::add('GOLD_BID_CURRENT_REBATE_FAILED');

            throw $e;
        }

        Logger::info(implode('|', array(
            __CLASS__,
            __FUNCTION__,
            'orderId:'.$this->orderId,
            'dealId:'.$this->dealId ,
            'userId:'.$this->userId,
            'buyPrice:'.$this->buyPrice,
            'curentPrice:'.$this->curent_price,
            'buyAmount:'.$this->buyAmount,
            "msg:投资成功"
        )));

        \libs\utils\Monitor::add('GOLD_BID_CURRENT_REBATE_SUCCESS');

        $data = array(
            'name'=>$this->dealInfo['name'],
            'buy_amount'=>$this->buyAmount,
            'buy_price'=>$this->curent_price,
            'fee'=>$this->fee,
            'money'=>$this->money
        );
        return $data;
    }

    public function checkCanBid() {
        // 核销参数判断
        $rebateConf = $this->rebateConf;
        if (empty($rebateConf)) {
            throw new \Exception('满金配置为空');
        }

        if (empty($rebateConf['wxUserId'])) {
            throw new \Exception('满金配置出资方为空');
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
    protected function checkDeal() {
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
        $rebateConf = $params['rebateConf'];
        $money = $params['money'] - $params['fee'];//资金记录分两条
        $fee= $params['fee'];
        $orderId = $params['orderId'];
        $msg = "买入{$dealInfo['name']},单号{$orderId}";

        $GLOBALS['db']->startTrans();
        try {
            $currentTime = time();
            // 添加触发返利记录
            $data = array();
            $data['from_user_id'] = $rebateConf['wxUserId'];
            $data['to_user_id'] = $userInfo['id'];
            $data['acquire_log_id'] = $rebateConf['logId'];
            $data['gift_id'] = 0;
            $data['gift_group_id'] = 0;
            $data['deal_load_id'] = $rebateConf['dealLoadId'];
            $data['action_type'] = OtoAllowanceLogModel::ACTION_TYPE_TRIGGER;
            $data['create_time'] = $currentTime;
            $data['update_time'] = $currentTime;
            $data['allowance_type'] = CouponGroupEnum::ALLOWANCE_TYPE_GOLD;
            $data['allowance_money'] = $params['money'];
            $data['allowance_coupon'] = $params['buyAmount'];
            $data['allowance_id'] = $orderId;
            $data['token'] = $rebateConf['token'];
            $data['status'] = OtoAllowanceLogModel::STATUS_DONE;
            $data['site_id'] = $rebateConf['siteId'];
            $allowanceLogId = OtoAllowanceLogModel::instance()->addRecord($data);
            if (!$allowanceLogId) {
                throw new \Exception('添加触发返利记录失败');
            }

            $type = '赠金充值';
            $receiverType = '赠金充值';
            $note = "满额赠金,购买{$dealInfo['name']}{$orderId}";
            $receiverNote = "满额赠金{$params['buyAmount']}克{$dealInfo['name']}{$orderId}";

            $transferService = new TransferService();
            $transferService->transferById($rebateConf['wxUserId'], $userInfo['id'],
                $params['money'], $type, $note, $receiverType, $receiverNote);

            $moneyOrderService = new MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
            $moneyOrderService->changeMoneyAsyn = false;
            $moneyOrderService->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
            $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDDISCOUNT, -$money, "系统赠金", $msg, userModel::TYPE_MONEY);
            if(bccomp($fee,0,2) == 1){
                $moneyOrderService->changeUserMoney($params['orderId'], $userInfo['id'], GoldMoneyOrderEnum::BUYGOLDDISCOUNTFEE, -$fee, "系统赠金手续费", $msg, userModel::TYPE_MONEY);
            }
            $moneyOrderService->changeUserMoney($params['orderId'], $dealInfo['userId'], GoldMoneyOrderEnum::SELLGOLDDISCOUNT, $money, "系统赠金", $msg, userModel::TYPE_MONEY);
            if(bccomp($fee,0,2) == 1){
                $moneyOrderService->changeUserMoney($params['orderId'], $dealInfo['userId'], GoldMoneyOrderEnum::SELLGOLDDISCOUNTFEE, $fee, "系统赠金手续费", $msg, userModel::TYPE_MONEY);
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

            //同步红包记录
            $jobsModel = new JobsModel();
            $function = '\core\service\oto\O2OAllowanceService::rebateGoldBonusLogCallback';
            $jobsModel->priority = JobsModel::PRIORITY_GOLD_BID_SUCCESS_CALLBACK;
            $params['userId'] = $userInfo['id'];
            unset($params['userInfo']);
            $ret = $jobsModel->addJob($function, array('param'=>$params));
            if ($ret === false) {
                throw new \Exception('Jobs任务注册失败');
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            if (isset($params['userInfo'])) {
                $userInfo = $params['userInfo'];
                $params['userInfo'] = $userInfo->getRow();
            }

            Logger::error(implode(' | ', array(
                __CLASS__,
                __FUNCTION__,
                'data:'.json_encode($params),
                "error:".$e->getMessage()
            )));

            $GLOBALS['db']->rollback();

            \libs\utils\Alarm::push(CommonEnum::ALARM_PUSH_FATAL_ERROR_KEY, 'errMsg:'.$e->getMessage(), $params);
            // changeMoney捕获到订单已经存在的情况下，返回true,GTM 重试导致异常情况
            if ($e instanceof MoneyOrderException
                && $e->getCode() == MoneyOrderException::CODE_ORDER_EXIST) {
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
