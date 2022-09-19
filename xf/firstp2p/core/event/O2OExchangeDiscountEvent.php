<?php

namespace core\event;

use core\event\BaseEvent;
use core\service\TransferService;
use core\service\oto\O2ODiscountService;
use libs\utils\Finance;
use core\dao\DiscountModel;
use core\dao\BonusModel;
use core\dao\OtoBonusAccountModel;
use libs\utils\Monitor;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;
use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\DiscountRateModel;
use core\exception\O2OException;
use NCFGroup\Common\Library\Logger;

class O2OExchangeDiscountEvent extends BaseEvent {
    // 投资券兑换返利
    const DISCOUNT_TRANSFER = 'DISCOUNT_TRANSFER';

    private $couponId;
    private $userId;
    private $dealLoadId;
    private $dealName;
    private $couponCode;
    private $buyPrice;
    private $discountGoldCurrentOrderId;
    private $consumeType;
    private $annualizedAmount;
    private $isDelayRebate = false;

    public function __construct($userId, $couponId, $dealLoadId, $dealName, $couponCode  = '', $buyPrice = 0,
                                $discountGoldCurrentOrderId = 0, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P,
                                $annualizedAmount = 0, $isDelayRebate = false) {
        $this->couponId = $couponId;
        $this->userId = $userId;
        $this->dealLoadId = $dealLoadId;
        $this->dealName = $dealName;
        $this->couponCode = $couponCode;
        $this->buyPrice = $buyPrice;
        $this->discountGoldCurrentOrderId = $discountGoldCurrentOrderId;
        $this->consumeType = $consumeType;
        $this->annualizedAmount = $annualizedAmount;
        $this->isDelayRebate = $isDelayRebate;
    }

    public function execute() {
        // 记录返利信息
        $logParams = array(
            'msg'=>'O2OExchangeDiscountEvent executing',
            'discountId'=>$this->couponId,
            'userId'=>$this->userId,
            'dealLoadId'=>$this->dealLoadId,
            'consumeType'=>$this->consumeType,
            'couponCode'=>$this->couponCode,
            'buyPrice'=>$this->buyPrice,
            'discountGoldCurrentOrderId'=>$this->discountGoldCurrentOrderId,
            'dealName'=>$this->dealName,
            'annualizedAmount'=>$this->annualizedAmount,
            'isDelayRebate'=>$this->isDelayRebate
        );
        Logger::info($logParams);

        // 幂等判断
        $condition = "discount_id = '{$this->couponId}' AND user_id='{$this->userId}'";
        $logInfo = DiscountModel::instance()->findBy($condition, 'id, create_time, update_time, status, extra_info');
        if (empty($logInfo)) {
            Logger::warn('投资券使用记录不存在');
            return true;
        }

        // 幂等判断，1表示已核销
        if ($logInfo['status'] == 1) {
            Logger::info("O2OExchangeDiscountEvent:discount已核销");
            return true;
        }

        // 优化代码，远程请求不要放到事务里面，这里先远程请求兑换
        $o2oDiscountService = new O2ODiscountService();
        // 取p2p记录的触发时间
        $triggerTime = strtotime($logInfo['create_time']);
        $coupon = $o2oDiscountService->exchangeDiscount(
            $this->userId,
            $this->couponId,
            $this->dealLoadId,
            $triggerTime
        );

        // 对于timeout需要重试
        if ($coupon === false) {
            $errCode = $o2oDiscountService->getErrorCode();
            // 如果是投资券过期，不需要处理
            if ($errCode == O2OException::CODE_RPC_TIMEOUT) {
                throw new \Exception($o2oDiscountService->getErrorMsg(), $errCode);
            }
        }

        $extraInfo = !empty($logInfo['extra_info']) ? json_decode($logInfo['extra_info'], true) : array();
        if (empty($extraInfo['annualizedAmount']) || $this->annualizedAmount > 0) {
            $extraInfo['annualizedAmount'] = $this->annualizedAmount;
        }

        // 黄金券特殊处理，远程请求不要放在事务里面
        if ($coupon && $coupon['type'] == CouponGroupEnum::DISCOUNT_TYPE_GOLD) {
            $this->handleGoldRebate($coupon, $logInfo['id']);
        } else {
            // 2表示延迟返利
            if ($logInfo['status'] == 2) {
                // 延迟返利
                if ($this->isDelayRebate) {
                    $this->handleDelayRebate($coupon, $logInfo['id'], $extraInfo);
                }
            } else {
                // 投资返利
                $this->handleInvestRebate($coupon, $logInfo['id'], $extraInfo);
            }
        }

        return true;
    }

    /**
     * 处理延迟返利
     */
    private function handleDelayRebate(array $coupon, $logId, $extraInfo) {
        $GLOBALS['db']->startTrans();
        try {
            // 更新消费券记录状态，从延迟返利到已核销
            $updateCond = 'id = '.intval($logId).' AND status = 2';
            // 1表示已核销
            DiscountModel::instance()->updateAll(array('status'=>1), $updateCond, true);

            // 处理返利
            if ($coupon) {
                if ($coupon['type'] == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK) {
                    // 返现券
                    $this->handleCashBack($coupon, $logId, $this->consumeType);
                } else if ($coupon['type'] == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES) {
                    // 加息券
                    $this->handleRaiseRates($coupon, $logId, $this->consumeType, $extraInfo);
                } else {
                    // 这里只记录错误，不抛异常
                    Logger::error([
                        'msg'=>'O2OExchangeDiscountEvent handleDelayRebate非法的投资券类型',
                        'discount'=>$coupon
                    ]);
                }
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error("O2OExchangeDiscountEvent handleDelayRebate: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * 处理投资返利
     */
    private function handleInvestRebate($coupon, $logId, $extraInfo) {
        $GLOBALS['db']->startTrans();
        try {
            // 更新消费券记录状态
            $updateCond = 'id = '.intval($logId).' AND status = 0';
            // 1表示已核销，2表示延迟返利
            $status = ($this->consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) ? 2 : 1;
            $updateData = array(
                'consume_type' => $this->consumeType,
                'consume_id' => $this->dealLoadId,
                'status' => $status,
                'extra_info' => json_encode($extraInfo, JSON_UNESCAPED_UNICODE),
            );
            DiscountModel::instance()->updateAll($updateData, $updateCond, true);

            // 兑换成功，对于非智多新，进行返利处理
            if ($coupon && ($this->consumeType != CouponGroupEnum::CONSUME_TYPE_DUOTOU)) {
                if ($coupon['type'] == CouponGroupEnum::DISCOUNT_TYPE_CASHBACK) {
                    // 返现券
                    $this->handleCashBack($coupon, $logId, $this->consumeType);
                } else if ($coupon['type'] == CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES) {
                    // 加息券
                    $this->handleRaiseRates($coupon, $logId, $this->consumeType, $extraInfo);
                } else {
                    // 这里只记录错误，不抛异常
                    Logger::error([
                        'msg'=>'O2OExchangeDiscountEvent handleInvestRebate非法的投资券类型',
                        'discount'=>$coupon
                    ]);
                }
            }

            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error("O2OExchangeDiscountEvent handleInvestRebate: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * 黄金券返利
     */
    private function handleGoldRebate(array $coupon, $logId) {
        $coupon['consumeType'] = $this->consumeType;
        $coupon['dealLoadId'] = $this->dealLoadId;
        $coupon['dealName'] = $this->dealName;
        // 记录返利日志
        Logger::info([
            'msg'=>'O2OExchangeDiscountEvent.handleGoldRebate.allowance',
            'discount'=>$coupon,
            'logId'=>$logId
        ]);

        // 尝试给用户购买优金宝
        if ($coupon['goodsPrice'] > 0 && $coupon['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_GOLD) {
            // 给用户购买活期黄金(优金宝)，注意这个不要放到事务里面
            $goldBidDiscountService = new \core\service\GoldBidDiscountService(
                $this->userId,
                $coupon['goodsPrice'],
                $this->buyPrice,
                $this->couponCode,
                $this->discountGoldCurrentOrderId,
                $coupon
            );
            $goldBidDiscountService->doBid();

            Monitor::add(self::DISCOUNT_TRANSFER);
        } else {
            // 直接更新凭证，这里必须更新状态
            $updateCond = 'id = '.intval($logId).' AND status=0';
            $updateData = array(
                'consume_type' => $this->consumeType,
                'consume_id' => $this->dealLoadId,
                'status' => 1
            );

            DiscountModel::instance()->updateAll($updateData, $updateCond, true);
        }
    }

    /**
     * 返现券返利
     */
    private function handleCashBack(array $coupon, $logId, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P) {
        // 记入相关日志备查
        // 记录返利日志
        Logger::info([
            'msg'=>'O2OExchangeDiscountEvent.handleCashBack.allowance',
            'discount'=>$coupon,
            'logId'=>$logId,
            'consumeType'=>$consumeType
        ]);

        // 对应返利对象的主键id
        $allowanceId = 0;
        // 价格大于0才返利
        if ($coupon['goodsPrice'] > 0) {
            // 转账逻辑
            if ($coupon['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_MONEY) {
                $type = '返现券支出';
                $receiverType = '返现券返利';
                $note = "使用{$coupon['productName']},投资{$this->dealName}";
                $receiverNote = "使用{$coupon['productName']},投资{$this->dealName}";

                $transferService = new TransferService();
                $transferService->transferById(
                    $coupon['wxUserId'],
                    $coupon['ownerUserId'],
                    $coupon['goodsPrice'],
                    $type,
                    $note,
                    $receiverType,
                    $receiverNote
                );

                $allowanceId = $coupon['wxUserId'];
                Monitor::add(self::DISCOUNT_TRANSFER);
            } else if ($coupon['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS) {
                $allowanceService = new \core\service\oto\O2OAllowanceService();
                $allowanceId = $allowanceService->rebateBonus(
                    $coupon['ownerUserId'],
                    $coupon['wxUserId'],
                    $coupon['goodsPrice'],
                    $coupon['goodsLimit'],
                    BonusModel::BONUS_DISCOUNT_REBATE,
                    OtoBonusAccountModel::MODE_DISCOUNT,
                    $logId,
                    $coupon['discountGroupId']
                );

                Monitor::add(self::DISCOUNT_TRANSFER);
            } else {
                Logger::error([
                    'msg'=>'O2OExchangeDiscountEvent handleCashBack非法的投资券类型',
                    'discount'=>$coupon
                ]);
            }
        } else {
            Logger::warn("O2OExchangeDiscountEvent.handleCashBack goodsPrice为零");
        }

        // 添加返利记录
        $data = array();
        $data['user_id'] = $coupon['ownerUserId'];
        $data['discount_id'] = $coupon['id'];
        $data['discount_type'] = CouponGroupEnum::DISCOUNT_TYPE_CASHBACK;
        $data['consume_type'] = $consumeType;
        $data['consume_id'] = $this->dealLoadId;
        $data['allowance_type'] = $coupon['goodsType'];
        $data['allowance_money'] = $coupon['goodsPrice'];
        $data['allowance_id'] = $allowanceId;
        $data['token'] = $coupon['id'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $recordId = DiscountRateModel::instance()->addRecord($data);
        if (!$recordId) {
            throw new \Exception('添加返利记录失败');
        }
   }

    /**
     * 加息券返利
     */
    private function handleRaiseRates(array $coupon, $logId, $consumeType = CouponGroupEnum::CONSUME_TYPE_P2P, $extraInfo = array()) {
        // 一次性返利
        if ($coupon['goodsGiveType'] == CouponGroupEnum::DISCOUNT_GIVE_ONE_TIME) {
            if ($consumeType == CouponGroupEnum::CONSUME_TYPE_DUOTOU) {
                $money = isset($extraInfo['money']) ? intval($extraInfo['money']) : 0;
                $lockDay = isset($extraInfo['lockDay']) ? intval($extraInfo['lockDay']) : 0;
                $moneyYear = $money * $lockDay / DealModel::DAY_OF_YEAR;
                $goodPrice = bcmul($moneyYear, $coupon['goodsPrice'] * 0.01, 5);
            } else if ($consumeType == CouponGroupEnum::CONSUME_TYPE_RESERVE) {
                $goodPrice = bcmul($this->annualizedAmount, $coupon['goodsPrice'] * 0.01, 5);
            } else {
                // @todo 年华值应该存储在annualizedAmount，直接又上层传过来，这里计算不合理，需要优化
                // 投资记录信息
                if (isset($extraInfo['isP2p'])) {
                    //普惠的加息券返利不需要查dealLoad,直接使用extraInfo的annualizedAmount
                    $goodPrice = bcmul($extraInfo['annualizedAmount'], $coupon['goodsPrice'] * 0.01, 5);
                } else {
                    $dealLoan = DealLoadModel::instance()->find($this->dealLoadId);
                    if (empty($dealLoan)) {
                        throw new \Exception('交易'.$this->dealLoadId.'不存在');
                    }
                    $dealModel = DealModel::instance()->find($dealLoan->deal_id);
                    $finance = new Finance();
                    // 计算年化额
                    $moneyYear = $finance->getMoneyYearPeriod($dealLoan->money, $dealModel->loantype, $dealModel->repay_time);
                    $rebateRate = $dealModel->getRebateRate($dealModel->loantype);
                    $goodPrice = bcmul($moneyYear, $coupon['goodsPrice'] * 0.01 * $rebateRate, 5);
                }
            }

            if ($coupon['goodsMaxPrice'] > 0 && bccomp($goodPrice, $coupon['goodsMaxPrice'], 5) == 1) {
                $goodPrice = $coupon['goodsMaxPrice'];
            }

            // 金额分位取整
            $goodPrice = DealModel::instance()->floorfix($goodPrice);
            // 记入相关日志备查
            Logger::info('O2OExchangeDiscountEvent.handleRaiseRates.allowance'
                . ", 投资券{$coupon['id']}, payout:" . $coupon['wxUserId']
                . ' payin:'. $coupon['ownerUserId']
                . ', type：' . CouponGroupEnum::$DISCOUNT_ALLOWANCE_TYPE[$coupon['goodsType']]
                . ', money:' . $goodPrice . ', limit: '.$coupon['goodsLimit'].',annualizedAmount:'.$this->annualizedAmount
            );

            $allowanceId = 0;
            if ($goodPrice > 0) {
                // 转账逻辑
                if ($coupon['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_MONEY) {
                    $type = '加息券支出';
                    $receiverType = '加息券返利';
                    $note = "使用{$coupon['productName']},投资{$this->dealName}";
                    $receiverNote = "使用{$coupon['productName']},投资{$this->dealName}";

                    $transferService = new TransferService();
                    $transferService->transferById(
                        $coupon['wxUserId'],
                        $coupon['ownerUserId'],
                        $goodPrice,
                        $type,
                        $note,
                        $receiverType,
                        $receiverNote
                    );

                    $allowanceId = $coupon['wxUserId'];
                    Monitor::add(self::DISCOUNT_TRANSFER);
                } else if ($coupon['goodsType'] == CouponGroupEnum::ALLOWANCE_TYPE_BONUS) {
                    $allowanceService = new \core\service\oto\O2OAllowanceService();
                    $allowanceId = $allowanceService->rebateBonus(
                        $coupon['ownerUserId'],
                        $coupon['wxUserId'],
                        $goodPrice,
                        $coupon['goodsLimit'],
                        BonusModel::BONUS_DISCOUNT_RAISE_RATE,
                        OtoBonusAccountModel::MODE_DISCOUNT_RAISE_RATE,
                        $logId,
                        $coupon['discountGroupId']
                    );

                    Monitor::add(self::DISCOUNT_TRANSFER);
                } else {
                    Logger::error([
                        'msg'=>'O2OExchangeDiscountEvent.handleRaiseRates非法的返利类型'.$coupon['goodsType'],
                        'discount'=>$coupon,
                    ]);
                }
            } else {
                Logger::warn("O2OExchangeDiscountEvent.handleRaiseRates goodsPrice为零");
            }

            // 添加返利记录
            $data = array();
            $data['user_id'] = $coupon['ownerUserId'];
            $data['discount_id'] = $coupon['id'];
            $data['discount_type'] = CouponGroupEnum::DISCOUNT_TYPE_RAISE_RATES;
            $data['consume_type'] = $consumeType;
            $data['consume_id'] = $this->dealLoadId;
            $data['allowance_type'] = $coupon['goodsType'];
            $data['allowance_money'] = $goodPrice;
            $data['allowance_id'] = $allowanceId;
            $data['token'] = $coupon['id'];
            $data['create_time'] = date('Y-m-d H:i:s');
            $recordId = DiscountRateModel::instance()->addRecord($data);
            if (!$recordId) {
                throw new \Exception('添加返利记录失败');
            }
        } else if ($coupon['goodsGiveType'] == CouponGroupEnum::DISCOUNT_GIVE_WITH_INTEREST) {
            // 随息发放
            Monitor::add(self::DISCOUNT_TRANSFER);
            Logger::info([
                'msg'=>'O2OExchangeDiscountEvent.handleRaiseRates随息发放',
                'discount'=>$coupon
            ]);
        } else {
            Logger::error([
                'msg'=>'O2OExchangeDiscountEvent.handleRaiseRates非法的加息券发放方式'.$coupon['goodsGiveType'],
                'discount'=>$coupon
            ]);
        }
    }

    public function alertMails() {
        return array('yanbingrong@ucfgroup.com', 'liguizhi@ucfgroup.com', 'luzhengshuai@ucfgroup.com');
    }
}
