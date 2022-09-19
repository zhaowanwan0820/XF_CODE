<?php

namespace core\service\candy;

use core\dao\DealModel;
use core\service\UserService;
use libs\db\Db;
use libs\utils\Monitor;
use libs\utils\Logger;
use core\service\candy\CandyAccountService;
use libs\utils\Rpc;
use core\service\candy\CandyUtilService;
use core\service\candy\CandyServiceException;

/**
 * 信宝支付相关
 */
class CandyPayService
{

    // 生活商城商户号
    const LIFE_MERCHANT_ID = 100001;

    // 支付成功
    const PAY_STAUTS_SUCCESS = 1;

    // 已退款
    const PAY_STAUTS_REFUNDED = 2;

    // 限制投资额
    const LIMIT_DEAL_AMOUNT = 1;

    // 限制年化
    const LIMIT_DEAL_AMOUNT_ANNUALIZED = 2;

    // 新用户专区配置
    const DISCOUNT_NEW_USER = '1.1';

    /**
     * 支付
     */
    public function pay($merchantId, $outOrderId, $userId, $amount, $note)
    {
        if ($amount <= 0) {
            throw new \Exception('amount参数错误');
        }

        // 是否受限用户
        $accountService = new CandyAccountService();
        if ($accountService->isLimited($userId)) {
            throw new \Exception('投资1次即可兑换精美商品！');
        }

        Db::getInstance('candy')->startTrans();
        try {
            // 创建订单
            $this->createOrder($merchantId, $outOrderId, $userId, $amount, $note);
            // 扣款
            $changeAmount = bcsub(0, $amount, CandyAccountService::AMOUNT_DECIMALS);
            $accountService->changeAmount($userId, $changeAmount, '兑换商品', $note);
            Db::getInstance('candy')->commit();
        } catch (\Exception $e) {
            Db::getInstance('candy')->rollback();
            throw new \Exception('支付失败:'.$e->getMessage());
        }
    }

    /**
     * 创建订单
     */
    private function createOrder($merchantId, $outOrderId, $userId, $amount, $note)
    {
        // 创建订单
        $data = array(
            'merchant_id' => $merchantId,
            'out_order_id' => $outOrderId,
            'user_id' => $userId,
            'amount' => $amount,
            'note' => $note,
            'status' => self::PAY_STAUTS_SUCCESS,
            'create_time' => time(),
        );

        return Db::getInstance('candy')->insert('candy_pay_order', $data);
    }

    /**
     * 获取订单信息
     */
    public function getOrdersInfo($merchantId, array $outOrderIds)
    {
        $ids = array();
        foreach ($outOrderIds as $value) {
            $ids[] = "'".addslashes($value)."'";
        }
        $idsString = implode(',', $ids);

        $sql = "SELECT * FROM candy_pay_order WHERE merchant_id='{$merchantId}' AND out_order_id IN ({$idsString}) LIMIT 500";
        $result = Db::getInstance('candy')->getAll($sql);
        return $result;
    }

    /**
     * 退款
     */
    public function refund($merchantId, $orderId, $userId)
    {
        $result = $this->getOrdersInfo($merchantId, array($orderId));
        if (empty($result[0])) {
            throw new \Exception('订单不存在');
        }

        $orderInfo = $result[0];
        if ($userId != $orderInfo['user_id']) {
            throw new \Exception('用户ID不匹配');
        }

        // 已退款情况
        if ($orderInfo['status'] == self::PAY_STAUTS_REFUNDED) {
            return true;
        }

        $db = Db::getInstance('candy');
        $db->startTrans();
        try {
            // 修改订单状态
            $status = self::PAY_STAUTS_SUCCESS;
            $where = "merchant_id='{$merchantId}' AND out_order_id='{$orderId}' AND status='{$status}'";
            $data = array(
                'status' => self::PAY_STAUTS_REFUNDED,
                'update_time' => time(),
            );
            $db->update('candy_pay_order', $data, $where);
            if ($db->affected_rows() < 1) {
                throw new \Exception('修改订单状态失败');
            }

            // 返还
            $accountService = new CandyAccountService();
            $accountService->changeAmount($userId, $orderInfo['amount'], '商品退款', "订单号:{$orderInfo['out_order_id']}");
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw new \Exception('退款失败:'.$e->getMessage());
        }
    }

    /**
     * 闪购用户资格验证
     * @param integer $userId 用户ID
     * @param string $discount 折扣
     * @return bool
     * @throws \Exception
     */
    public function saleUserCheck($userId, $discount)
    {
        Monitor::add("CANDY_SALE_CHECK");
        $saleLimitConfig = $this->getSaleUserLimitConfig();
        if (empty($saleLimitConfig['discountConfig'][$discount])) {
            throw new \Exception('折扣配置为空');
        }

        $amount = $saleLimitConfig['discountConfig'][$discount]['amount'];
        // 默认投资额, 兼容老逻辑
        $amountType = (int) $saleLimitConfig['discountConfig'][$discount]['amountType'];

        // 新手专区
        if ($discount === self::DISCOUNT_NEW_USER) {
            return $this->saleNewUserCheck($userId, $amountType, $amount);
        }

        // 当日投资验证, 30天及以上，2000
        if (CandyUtilService::getUserInvestAmountToday($userId, $amountType) < $amount) {
            $messageExt = (!empty($amountType) && $amountType == CandyPayService::LIMIT_DEAL_AMOUNT_ANNUALIZED) ? '年化' : '';
            throw new CandyServiceException("当日{$messageExt}投资额累计满{$amount}(不包含智多新，网贷产品仅限网贷-网信普惠)即可兑换");
        }

        return true;
    }

    private function saleNewUserCheck($userId, $amountType, $limitAmount)
    {
        $createTime = strtotime('2019-01-01') - date('Z');
        // 获取用户注册时间
        $registerTime = Db::getInstance("firstp2p")->getOne("SELECT create_time FROM firstp2p_user WHERE id = {$userId} AND create_time >= {$createTime}");
        if (empty($registerTime) || ceil((time() - $registerTime) / 86400) > 45) {
            throw new CandyServiceException("2019年注册且有过投资行为（不包含智多新，网贷产品仅限网贷-网信普惠）的用户注册之日起45天内可兑换1次本区域商品");
        }

        $amount = CandyUtilService::getUserInvestAmount($userId, $amountType, $registerTime);
        if ($amount <= $limitAmount) {
            throw new CandyServiceException("2019年注册且有过投资行为（不包含智多新，网贷产品仅限网贷-网信普惠）的用户注册之日起45天内可兑换1次本区域商品");
        }

        return true;
    }

    /**
     * 获取闪购用户限制配置
     * {
     *     'amountType' : 1,
           'numLimit' : 1,
           'discountConfig' : {
               '0.5' : {
                    'amount' : 100000
               },
               '0.3' : {
                    'amount' : 500000
               },
               '0.2' : {
                    'amount' : 1000000
               },
               '1' : {
                    'amount' : 500,
               }
           }
       }
     *
     * @return array
     * @throws \Exception
     */
    public function getSaleUserLimitConfig()
    {
        $config = app_conf('CANDY_SALE_LIMIT_CONFIG');
        if (empty($config)) {
            throw new \Exception('活动限制配置为空');
        }
        $config = json_decode($config, true);
        if (empty($config)) {
            throw new \Exception('活动限制配置错误');
        }
        return $config;
    }

    /**
     * 获取信宝限时购配置信息
     */
    public function getShopQualifyConfig()
    {
        $config = app_conf('CANDY_SHOP_QUALIFY_CONFIG');
        $config = json_decode($config, true);
        if (empty($config)) {
            throw new \Exception('信宝限时购活动限制配置错误');
        }
        return $config;
    }
    
    /**
     * 限时购资格验证
     */
    public function shopQualifyCheck($userId, $qualifyId)
    {
        Monitor::add("CANDY_SHOP_QUALIFY_CHECK");
        $shopQuailfyConfig = $this->getShopQualifyConfig();
        if (empty($shopQuailfyConfig[$qualifyId])) {
            throw new \Exception('折扣配置为空');
        }

        $amount = $shopQuailfyConfig[$qualifyId]['amount'];
        $amountType = (int) $shopQuailfyConfig[$qualifyId]['amountType'];

        if (CandyUtilService::getUserInvestAmountToday($userId, $amountType) < $amount) {
            $messageExt = (!empty($amountType) && $amountType == CandyPayService::LIMIT_DEAL_AMOUNT_ANNUALIZED) ? '年化' : '';
            throw new CandyServiceException("当日{$messageExt}投资额累计满{$amount}(不包含智多新，网贷产品仅限网贷-网信普惠)即可兑换");
        }

        return true;
    }
}
