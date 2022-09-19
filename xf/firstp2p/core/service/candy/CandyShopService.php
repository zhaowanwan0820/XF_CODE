<?php

namespace core\service\candy;

use core\service\AddressService;
use core\service\O2OService;
use libs\db\Db;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Curl;

/**
 * 积分商店
 */
class CandyShopService
{

    // 订单状态成功
    const ORDER_STATUS_SUCCESS = 1;
    // 订单已退款
    const ORDER_STATUS_REFUND = 2;
    // 投资券类型
    const COUPON_INVEST_TYPE = 1;
    // 实物商品券类型
    const COUPON_GOODS_TYPE = 2;
    // 房租抵扣券类型
    const COUPON_YIFANG_TYPE = 3;
    // 联合货币兑换券
    const COUPON_UNITEDMONEY_TYPE = 4;
    // 瑜伽VIP会员兑换券
    const COUPON_YOGA_TYPE = 5;

    // 券类型对应券组
    private static $couponGroup = array(
        self::COUPON_INVEST_TYPE => 'investCoupon',
        self::COUPON_YIFANG_TYPE => 'yifangCoupon',
        self::COUPON_UNITEDMONEY_TYPE => 'unitedMoneyCoupon',
        self::COUPON_YOGA_TYPE => 'yogaCoupon'
    );

    /**
     * 获取券列表
     *
     * @param bool $getCouponGroupInfo
     * @return array productList
     */
    public function getCouponList()
    {
        $productList = Db::getInstance('candy')->getAll("SELECT * FROM candy_shop_product WHERE status = 1 AND product_type NOT IN (" . self::COUPON_GOODS_TYPE . ") ORDER BY sort DESC");

        $productList = $this->formatProduct($productList);
        return $this->classifyCoupon($productList);
    }

    /**
     * 分类券组
     */
    private function classifyCoupon($productList)
    {
        $couponList = array();
        foreach ($productList as $key => $product) {
            $couponList[self::$couponGroup[$product['product_type']]][$key] = $product;
        }

        return $couponList;
    }

    /**
     * 获取推荐列表
     * @return array
     */
    public function getSuggestProductList() {

        $productList = Db::getInstance('candy')->getAll("SELECT * FROM candy_shop_product WHERE status = 1 AND is_suggest = 1 AND product_type = " . self::COUPON_INVEST_TYPE . " ORDER BY sort DESC");
        return $this->formatProduct($productList);
    }


    /**
     * 获取首页推荐列表列表
     * @return array
     */
    public function getTopProductList() {

        $productList = Db::getInstance('candy')->getAll("SELECT * FROM candy_shop_product WHERE status = 1 AND is_top = 1 AND product_type = " . self::COUPON_INVEST_TYPE . " ORDER BY sort DESC");
        return $this->formatProduct($productList);
    }

    /**
     * 获取生活首页推荐
     * @return array
     */
    public function getTopLifeGoods() {

        $indexCount =  app_conf('LIFE_GOODS_TOP_NUM');
        $goodList = $this->requestLifeGoods(['size' => $indexCount]);
        return $this->transGoodsToProducts($goodList);
    }

    /**
     *  获取列表推荐列表
     */
    public function getSuggestLifeGoods() {

        $suggestCount =  app_conf('LIFE_GOODS_SUGGEST_NUM');
        $goodList = $this->requestLifeGoods(['size' => $suggestCount]);
        return $this->transGoodsToProducts($goodList);
    }

    /**
     * 获取商品信息
     *
     * @param ineger $productId
     * @return array product
     */
    public function getProduct($productId)
    {
        $product = Db::getInstance('candy')->getAll("SELECT * FROM candy_shop_product WHERE id = '$productId'");
        return $this->formatProduct($product)[0];
    }

    /**
     * 检查商品是否可以兑换
     */
    private function checkProduct($userId, $productId)
    {
        $db = Db::getInstance('candy');
        $product = Db::getInstance('candy')->getRow("SELECT * FROM candy_shop_product WHERE id = '{$productId}'");
        if (empty($product)) {
            throw new \Exception('商品不存在');
        }

        // 限制券验证库存
        if ($product['is_limited'] && $product['stock'] == 0) {
            throw new \Exception('该券可兑换库存量不足');
        }

        // 券兑换次数限制
        $dayStartTime = strtotime(date("Ymd"));
        // 瑜伽券每人只可兑换一次，其他券每日可兑换一次
        $time = $product['product_type'] == self::COUPON_YOGA_TYPE ? 0 : $dayStartTime;
        $extInfo = $product['product_type'] == self::COUPON_YOGA_TYPE ? '' : '一天';
        $order = $db->getRow("SELECT id FROM candy_shop_order WHERE user_id = '{$userId}' AND product_id = '{$product['id']}' AND create_time >= '{$time}'");
        if (!empty($order)) {
            throw new \Exception("本商品{$extInfo}只能兑换一次，看看其他商品吧");
        }

        $accountService = new CandyAccountService();
        $account = $accountService->getAccountInfo($userId);
        if ($account['amount'] < $product['price']) {
            throw new \Exception('可用信宝不足');
        }

        return $product;
    }

    public function exchangeCoupon($userId, $productId)
    {
        $product = $this->checkProduct($userId, $productId);
        $db = Db::getInstance('candy');
        $o2oService = new O2OService();
        $couponGroupInfo = $o2oService->getDiscountGroup($product['coupon_group_id']);
        $orderId = Idworker::instance()->getId();

        $db->startTrans();
        try {
            if ($product['is_limited']) {
                $this->changeProductStock($product, -1);
            }
            $logInfo = "兑换投资券-{$couponGroupInfo['name']}-{$product['coupon_group_id']}";
            $this->saveExchangeOrder($userId, $product, $orderId, '兑换投资券', $logInfo);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error('信宝兑换商品异常:' . $e->getMessage());
            throw new \Exception('系统繁忙，请稍后再试');
        }
        $token = $this->getDiscountToken($orderId);

        $acquireRes = $o2oService->acquireDiscount($userId, $product['coupon_group_id'], $token);
        if ($acquireRes === false) {
            Logger::error('信宝兑换投资券异常:' . O2OService::$error . '|' . O2OService::$errorCode  . '|' . O2OService::$errorMsg);
        }

        return true;
    }

    /**
     * 兑换一房租房兑换券
     */
    public function exchangeYifangCoupon($userId, $productId)
    {
        return $this->exchangeVirtualCoupon($userId, $productId, '房租抵扣券');
    }

    /**
     * 兑换货币兑换券
     */
    public function exchangeUnitedmoneyCoupon($userId, $productId)
    {
        return $this->exchangeVirtualCoupon($userId, $productId, '货币抵扣券');
    }

    /**
     * 兑换瑜伽VIP会员兑换券
     */
    public function exchangeYogaVIPCoupon($userId, $productId)
    {
        return $this->exchangeVirtualCoupon($userId, $productId, '瑜伽VIP会员兑换券');
    }

    /**
     * 更新兑换券
     */
    private function exchangeVirtualCoupon($userId, $productId, $couponType)
    {
        $product = $this->checkProduct($userId, $productId);
        $o2oService = new O2OService();
        $couponGroupInfo = $o2oService->getCouponGroupInfoById($product['coupon_group_id']);
        $orderId = Idworker::instance()->getId();

        $db = Db::getInstance('candy');
        $db->startTrans();
        try {
            if ($product['is_limited']) {
                $this->changeProductStock($product, -1);
            }
            $logInfo = "兑换{$couponType}-{$couponGroupInfo['name']}-{$product['coupon_group_id']}";
            $this->saveExchangeOrder($userId, $product, $orderId, '兑换'.$couponType, $logInfo);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error('exchange coupon failed. msg:'.$e->getMessage());
            throw new \Exception('系统繁忙，请稍后再试');
        }

        $token = $this->getDiscountToken($orderId);
        $result = $o2oService->acquireAllowanceCoupon($product['coupon_group_id'], $userId, $token);
        if ($result === false) {
            Logger::error("exchange coupon failed. o2oerr:".O2OService::$errorMsg.", userId:{$userId}, productId:{$productId}, type:{$couponType}");
        }

        Logger::info("exchange coupon success. userId:{$userId}, productId:{$productId}, type:{$couponType}");

        return true;
    }

    /**
     * 根据订单号生成token
     * @param $orderId
     * @return string
     */
    public function getDiscountToken($orderId)
    {
        return 'candy_'.$orderId;
    }

    public function saveExchangeOrder($userId, $product, $orderId, $type, $logInfo)
    {
        $accountService = new CandyAccountService();
        $db = Db::getInstance('candy');
        $db->startTrans();
        try{
            $accountService->changeAmount($userId, -$product['price'], $type, $logInfo);
            $exchangeOrder = [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'order_id' => $orderId,
                'amount' => $product['price'],
                'status' => 1,
                'create_time' => time(),
            ];
            $insertId = $db->insert('candy_shop_order', $exchangeOrder);
            if (empty($insertId)) {
                throw new \Exception('插入订单失败');
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            Logger::error('信宝兑换异常:' . $e->getMessage());
            throw new \Exception('系统繁忙，请稍后再试');
        }
        return true;
    }

    public function changeProductStock($product, $num) {
        $stockNew = $product['stock'] + $num;
        if ($stockNew < 0) {
            throw new \Exception('库存不足');
        }
        $data = array(
            'stock' => $stockNew,
        );

        if ($num > 0) {
            $data['total'] = $product['total'] + $num;
        }

        $this->updateProduct($product, $data);
        return true;
    }

    public function updateProduct($product, $data) {
        $db = Db::getInstance('candy');
        $data['update_time'] = time();
        $data['version'] = $product['version'] + 1;
        $where = "id='{$product['id']}' AND version='{$product['version']}'";
        if (!$db->update('candy_shop_product', $data, $where)) {
            throw new \Exception("更新失败");
        }
        if ($db->affected_rows() < 1) {
            throw new \Exception("更新失败，数据冲突");
        }
        return true;
    }

    private function formatProduct($productList) {
        if (empty($productList)) {
            return [];
        }

        $o2oService = new O2OService();
        foreach ($productList as $key => $product) {
            $productList[$key]['price'] = floatval($product['price']);
            $productList[$key]['market_price'] = floatval($product['market_price']);
            if ($product['product_type'] == self::COUPON_INVEST_TYPE) {
                $couponGroupInfo = $o2oService->getDiscountGroup($product['coupon_group_id']);
            } else {
                $couponGroupInfo = $o2oService->getCouponGroupInfoById($product['coupon_group_id']);
            }

            $productList[$key]['couponGroup'] = $couponGroupInfo;
        }
        return $productList;
    }

    // 请求商城商品列表
    private function requestLifeGoods($params) {

        $uri = $GLOBALS['sys_config']['LIFE_SHOP']['TOP_PRODUCT_LIST'];
        $url = $uri . '?' . http_build_query($params);
        Logger::info(__FUNCTION__ . ' requestUrl:' . $url);
        $start = microtime(true);
        $result = Curl::get($url, false, 1);
        $cost = round(microtime(true) - $start, 3);
        Logger::info(__FUNCTION__ . ' httpCode:' . Curl::$httpCode .', error:' . Curl::$error . ',cost:' . $cost . ', response:' . $result);
        if (Curl::$httpCode != '200') {
            \libs\utils\Alarm::push('CANDY', '请求生活商品列表失败', __FUNCTION__ . ' httpCode:' . Curl::$httpCode .', error:' . Curl::$error . ',cost:' . $cost . ', response:' . $result);
            return [];
        }
        $response = json_decode($result, true);
        if ($response['result'] != 1) {
            return [];
        }

        return $response['data']['goodsList'];
    }

    private function transGoodsToProducts($goodList) {
        if (empty($goodList)) {
            return [];
        }
        $productList = [];
        foreach($goodList as $good)  {
            $productList[] = [
                'price' => $good['xb_price'],
                'product_info' => [
                    'name' => $good['name'],
                    'market_price' => $good['price'],
                    'img_url' => $good['image_path'],
                ],
                'url' => $good['url'],
                'product_type' => self::COUPON_GOODS_TYPE
            ];
        }

        return $productList;
    }

}
