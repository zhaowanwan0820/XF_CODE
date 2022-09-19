<?php
/**
 * @author luzhengshuai@ucfgroup.com
 */


use libs\utils\Curl;
use core\service\O2OService;
use libs\db\Db;
use core\service\candy\CandyShopService;

class CandyShopAction extends CommonAction {

    // 兑换券类型
    private static $couponTypeMap = array(
        CandyShopService::COUPON_YIFANG_TYPE => '房租抵扣券',
        CandyShopService::COUPON_INVEST_TYPE => '投资券',
        CandyShopService::COUPON_UNITEDMONEY_TYPE => '货币兑换券',
        CandyShopService::COUPON_YOGA_TYPE => '瑜伽兑换券',
    );

    public function __construct() {
        parent::__construct();
    }

    /**
     * 增加券组
     */
    public function addCouponGroup() {
        $this->assign('couponTypeList', self::$couponTypeMap);
        $this->display();
    }

    /**
     * 编辑券组
     */
    public function editCouponGroup() {
        $productId = $_GET['productId'];
        $service = new \core\service\candy\CandyShopService();
        $product = $service->getProduct($productId);
        $this->assign('product', $product);
        $this->display();
    }

    public function doAddCouponGroup() {
        $couponGroupId = intval($_POST['couponGroupId']);
        $price = floatval($_POST['price']);
        $marketPrice = floatval($_POST['market_price']);
        $isSuggest = intval($_POST['isSuggest']);
        $dailyStock = !empty($_POST['daily_stock']) ? intval($_POST['daily_stock']) : 0;
        $stock = intval($_POST['stock']);
        $sort = intval($_POST['sort']);
        $isTop = intval($_POST['isTop']);
        $isLimited = intval($_POST['isLimited']);
        if ($isLimited == 0) {
            $dailyStock = 0;
            $stock = 0;
        }

        $type = intval($_POST['type']);
        $data = [
            'coupon_group_id' => $couponGroupId,
            'price' => $price,
            'market_price' => $marketPrice,
            'daily_stock' => $dailyStock,
            'stock' => $dailyStock == 0 ? $stock : $dailyStock,
            'total' => $stock,
            'product_info' => '',
            'status' => 0,
            'create_time' => time(),
            'is_suggest' => $isSuggest,
            'is_top' => $isTop,
            'is_limited' => $isLimited,
            'sort' => $sort,
            'version' => 0,
            'product_type' => $type,
        ];
        if (!Db::getInstance('candy')->insert('candy_shop_product', $data)) {
            return $this->ajaxReturn(-1, '添加失败');
        }
        return $this->ajaxReturn(0, '添加成功');
    }

    public function doEditCouponGroup() {
        $couponGroupId = intval($_POST['couponGroupId']);
        $price = floatval($_POST['price']);
        $marketPrice = floatval($_POST['market_price']);
        $isSuggest = !empty($_POST['isSuggest']) ? 1 : 0;
        $sort = intval($_POST['sort']);
        $productId = intval($_POST['productId']);
        $isTop = !empty($_POST['isTop']) ? 1 : 0;
        $isLimited = !empty($_POST['isLimited']) ? 1 : 0;
        $dailyStock = intval($_POST['daily_stock']);
        if ($isLimited == 0) {
            $dailyStock = 0;
        }
        $data = [
            'coupon_group_id' => $couponGroupId,
            'market_price' => $marketPrice,
            'price' => $price,
            'is_suggest' => $isSuggest,
            'is_top' => $isTop,
            'is_limited' => $isLimited,
            'sort' => $sort,
            'daily_stock' => $dailyStock,
        ];
        try {
            $shopService = new CandyShopService();
            $product = $shopService->getProduct($productId);
            if (empty($product)) {
                throw new \Exception('商品不存在');
            }
            $shopService->updateProduct($product, $data);
        } catch (\Exception $e) {
            return $this->ajaxReturn(-1, $e->getMessage());
        }
        return $this->ajaxReturn(0, '修改成功');
    }

    /**
     * 商品列表
     * @return array
     * @throws Exception
     */
    public function listProduct() {
        $o2oService = new O2OService();
        $productList = Db::getInstance('candy')->getAll("SELECT * FROM candy_shop_product WHERE product_type != " .CandyShopService::COUPON_GOODS_TYPE . "  ORDER BY SORT DESC");
        if (empty($productList)) {
            return [];
        }
        foreach ($productList as $key => $product) {
            $productList[$key]['product_info'] = json_decode($product['product_info'], true);
            if ($product['product_type'] == CandyShopService::COUPON_INVEST_TYPE) {
                $couponGroupInfo = $o2oService->getDiscountGroup($product['coupon_group_id']);
            }
            if ($product['product_type'] != CandyShopService::COUPON_GOODS_TYPE) {
                $couponGroupInfo = $o2oService->getCouponGroupInfoById($product['coupon_group_id']);
            }
            $productList[$key]['couponGroup'] = $couponGroupInfo;
        }

        $this->assign("productList", $productList);
        $this->display();
    }

    /**
     * 上传图片
     * @return bool|void
     */
    public function uploadImg() {
        $file = current($_FILES);
        if (empty($file) || $file['error'] != 0) {
            return $this->ajaxReturn(-4, "图片为空");
        }

        try {
            if (!empty($file)) {
                $uploadFileInfo = array(
                    'file' => $file,
                    'isImage' => 1,
                    'limitSizeInMB' => 10,
                    'savePath' => "fiveyear"
                );
                $result = uploadFile($uploadFileInfo);
            }
        } catch (\Exception $e) {
            return $this->ajaxReturn(-3, $e->getMessage());
        }
        if(empty($result['errors'])) {
            if (get_cfg_var("phalcon.env") == "dev" || get_cfg_var("phalcon.env") == "test") {
                $imgUrl = '//'. $GLOBALS['sys_config']['vfs_ftp']['ftp_host'] . '/' . $result['full_path'];
            } else {
                $imgUrl = "//static.firstp2p.com/". $result['full_path'];
            }
            return $this->ajaxReturn(0, "上传成功", ['imgUrl' => $imgUrl]);
        }

        if(!empty($result['errors'])) {
            return $this->ajaxReturn(-1, end($result['errors']));
        }

        return $this->ajaxReturn(-2, '图片上传失败');
    }

    /**
     * 更改商品库存
     * @return bool|void
     */
    public function modifyStock() {

        $productId = intval($_POST['productId']);
        $num = intval($_POST['num']);
        $shopService = new CandyShopService();
        $product = $shopService->getProduct($productId);
        if (empty($product)) {
            return $this->ajaxReturn(-1, '商品不存在');
        }

        try {
            $shopService->changeProductStock($product, $num);
        } catch (\Exception $e) {
            $this->ajaxReturn(-1, $e->getMessage());
        }

        return $this->ajaxReturn(0, '修改成功');
    }

    /**
     * 更改商品状态
     * @return bool|void
     */
    public function modifyStatus() {
        $productId = intval($_POST['productId']);
        $status= intval($_POST['status']);
        $shopService = new CandyShopService();
        $product = $shopService->getProduct($productId);
        if (empty($product)) {
            return $this->ajaxReturn(-1, '商品不存在');
        }

        $data = array(
            'status' => $status,
        );
        try {
            $shopService->updateProduct($product, $data);
        } catch (\Exception $e) {
            return $this->ajaxReturn(-1, $e->getMessage());
        }

        return $this->ajaxReturn(0, '修改成功');

    }

    /**
     *返回前端ajax文件
     */
    public function ajaxReturn($code, $msg, $data = []) {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ];
        echo json_encode($result);
        return true;
    }

    /**
     * 商品支付订单
     */
    public function payOrder()
    {
        $userId = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : '';
        $merchantId = !empty($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : '';
        $order = !empty($_REQUEST['out_order_id']) ? intval($_REQUEST['out_order_id']) : '';
        $status = !empty($_REQUEST['status']) ? intval($_REQUEST['status']) : '';

        $model = M('candy_pay_order', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('candy_pay_order');

        $condition = "1";
        if (!empty($userId)) {
            $condition .= " AND user_id = '{$userId}'";
        }
        if (!empty($merchantId)) {
            $condition .= " AND merchant_id = '{$merchantId}'";
        }
        if (!empty($order)) {
            $condition .=" AND out_order_id = '{$order}'";
        }
        if (!empty($status)) {
            $condition .=" AND status = '{$status}'";
        }

        $this->_list($model, $condition);
        $list = $this->get('list');

        foreach ($list as $key => $value) {
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
        }

        $this->assign('list', $list);
        $this->assign('userId', $userId);
        $this->assign('merchantId', $merchantId);
        $this->assign('order', $order);
        $this->assign('status', $status);

        $this->display();
    }

    /**
     * 投资券兑换记录
     */
    public function shopOrder()
    {
        $userId = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : '';
        $productId = !empty($_REQUEST['product_id']) ? intval(trim($_REQUEST['product_id'])) : '';
        $orderId = !empty($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : '';
        $status = !empty($_REQUEST['status']) ? intval($_REQUEST['status']) : '';

        $model = M('candy_shop_order', 'Model', false, 'candy', 'master');
        $model->setTrueTableName('candy_shop_order');

        $condition = "1";
        if (!empty($userId)) {
            $condition .= " AND user_id = '{$userId}'";
        }
        if (!empty($productId)) {
            $condition .= " AND product_id = '{$productId}'";
        }
        if (!empty($orderId)) {
            $condition .=" AND order_id = '{$orderId}'";
        }
        if (!empty($status)) {
            $condition .=" AND status = '{$status}'";
        }

        $this->_list($model, $condition);
        $list = $this->get('list');

        foreach ($list as $key => $value) {
            $addr = json_decode($value['address'], true);
            $list[$key]['address'] = $addr['address'];
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
        }

        $this->assign('list', $list);
        $this->assign('userId', $userId);
        $this->assign('productId', $productId);
        $this->assign('orderId', $orderId);
        $this->assign('status', $status);

        $this->display();
    }

    /**
     * 投资券退款
     */
    public function refundDiscount()
    {
        $this->display();
    }

    /**
     * 回退投资券，同时将信宝补回
     */
    public function doRefundDiscount()
    {
        $orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : '';
        $note = isset($_POST['note']) ? addslashes(trim($_POST['note'])) : '';
        if (empty($orderId)) {
            $this->error('订单号输入错误，请重新输入');
        }

        $db = Db::getInstance('candy');
        $orderInfo = $db->getRow("SELECT user_id, status, amount From candy_shop_order WHERE order_id='{$orderId}'");

        if (empty($orderInfo)) {
            $this->error('订单信息不存在');
        }

        $userId = $orderInfo['user_id'];
        $amount = $orderInfo['amount'];
        $status = $orderInfo['status'];

        if ($status == CandyShopService::ORDER_STATUS_REFUND) {
            $this->error('该订单已经退款');
        }

        //退投资券
        $token = CandyShopService::getDiscountToken($orderId);
        $refunDiscount = new O2OService();
        $resDiscount = $refunDiscount->refundDiscount($userId, 0, $token);

        if ($resDiscount == false) {
            $this->error('投资券退回失败');
        }

        $db->startTrans();
        try {
            //退信宝
            $changeAmount = new CandyAccountService();
            $changeAmount->changeAmount($userId, $amount, "投资券退款", "订单号:{$orderId}, {$note}");

            //更新订单状态
            $db->update('candy_shop_order', array('status' => CandyShopService::ORDER_STATUS_REFUND), "order_id='{$orderId}' AND status='{$status}'");
            if ($db->affected_rows() < 1) {
                $this->error("订单状态更新失败");
            }
            $db->commit();
            $this->success('修改成功', 0, "?m=CandyShop&a=shopOrder&user_id={$userId}");
        } catch (\Exception $e) {
            $db->rollback();
            $this->error($e->getMessage());
        }
    }
    
}
