<?php

/**
 * RongpayClass.php
 * @author changqi
 * @version 1.0
 * @desc 融宝支付接口类
 */

class RongpayClass {
    
    public $name = '融宝在线支付'; // 名称
    public $logo = 'ReaPal';
    public $version = '1.0';
    public $description = "融宝在线支付";
    public $charset = 'UTF-8';
    // notify 通知成功返回数据
    public $noticeSuccessCode = 'success';
    // notify 通知失败返回数据
    public $noticeFailCode = 'fail';
    
    public $debug = false;
    
    public static $orderby = 3;
    // 融宝的后台登陆账号
    public static $seller_email;
    
    protected static $_includePath = '';
    
    protected $_noticeData = array();
    protected $_returnData = array();
    
    function __construct() {
        $config = require_once dirname(dirname(dirname(dirname (__FILE__))))."/config/config.php";
        self::$seller_email = $config["rongpay_email"];
        
        self::$_includePath = dirname(__FILE__).'/../include';
        
    }
    
    /**
     * 构造支付请求表单
     * @param array $formData
     *   $formData 中元素含义解释
     *       tradeNo: 必填 商户提供的唯一订单号
     *       orderAmount: 必填 订单明细金额, 整型数字，以元为整数单位
     *       productName: 必填 商品名称
     *         bankCode: 可空, 银行机构代码
     *         returnUrl: 商户回调地址
     *         notifyUrl: 商户通知地址
     *         memberID: 商户ID
     *         privateKey: 私钥
     *         clientIp: 客户端ip
     *         productDesc: 可空, 商品描述
     * 
     * 
     * @return string $html
     */
    public function buildForm($formData) {
        include_once self::$_includePath .'/rongpay/RongpayServiceClass.php';
        
        /* 商户号 */
        $merchant_ID = $formData['memberID'];
        /* 密钥 */
        $key = $formData ['privateKey'];
        /* 返回处理地址 */
        $return_url = $formData['returnUrl'];
        $notify_url = $formData['notifyUrl'];
        
        /* 商家订单号,长度若超过32位，取前32位。财付通只记录商家订单号，不保证唯一。 */
        $out_trade_no = $formData ['tradeNo'];
        
        /* 商品价格（包含运费），以分为单位 */
        $total_fee = round($formData["orderAmount"], 2);
        
        /* 银行类型 */
        $defaultbank = !empty( $formData ['bankCode'] ) ? $formData ['bankCode'] : '';
        if ($defaultbank) {
            $paymethod = 'directPay'; // bankPay，网银支付，默认值；directPay，银行直连
        } else {
            $paymethod = 'bankPay';
        }

        if(isset($formData['seller_email'])) {
            $seller_email = $formData['seller_email'];
        } else {
            $seller_email = self::$seller_email;
        }
        
        $parameter = array (
                "service" => "online_pay", // 接口名称，不需要修改
                "payment_type" => "1", // 交易类型，不需要修改
                
                "merchant_ID" => $merchant_ID,
                "seller_email" => $seller_email,
                "return_url" => $return_url,
                "notify_url" => $notify_url,
                "charset" => $this->charset,
                
                "order_no" => $out_trade_no,
                "title" => $formData['productName'],// 'ITZ理财产品'
                "body" => $formData['productDesc'],  //'ITZ理财产品'
                "total_fee" => $total_fee,
                
                // 扩展功能参数——银行直连
                "paymethod" => $paymethod,
                "defaultbank" => $defaultbank 
        );
        
        // 组织表单数据
        $rongpay = new RongpayServiceClass( $merchant_ID, $key, 'MD5', 'UTF-8' );
        $sHtmlText = $rongpay->BuildForm($parameter);
        $html = $sHtmlText;
        return $html;
    }
    
    /**
     * 获取支付通知结果
     * @param array $data
     *         memberID: 商户ID
     *         privateKey: 私钥
     * @return boolean
     */
    public function noticeResult($data) {
        include_once self::$_includePath .'/rongpay/RongpayNotifyClass.php';
        $merchant_ID = $data['memberID'];
        $pkey = $data['privateKey'];
        $signType = 'MD5';
        $RongpayNotify = new RongpayNotifyClass($merchant_ID, $pkey, $signType, $this->charset, 'http');
        
        // 验证通知
        $verifyResult = $RongpayNotify->notifyVerify();
        
        $noticeData = $this->getNoticeData();
        $trade_status = $noticeData['trade_status'];    //交易状态
        
        if($verifyResult) {
            if($trade_status == "TRADE_FINISHED") {
                $payResult = true;
            } elseif($trade_status == "WAIT_BUYER_PAY") {
                $payResult = false;
                $this->_errorInfo = '等待支付';
            } elseif($trade_status == "TRADE_FAILURE") {
                $payResult = false;
                $this->_errorInfo = '支付失败';
            } else {
                $payResult = false;
                $this->_errorInfo = '未知错误';
            }
        } else {
            $payResult = false;
            $this->_errorInfo = 'Verify signature is not consistent.';
        }
        
        return $payResult;
        
    }
    
    /**
     * 获取支付回调结果
     * @param array $data
     *         memberID: 商户ID
     *         privateKey: 私钥
     * @return boolean
     */
    public function returnResult($data) {
        include_once self::$_includePath .'/rongpay/RongpayNotifyClass.php';
        
        $merchant_ID = $data['memberID'];
        $pkey = $data['privateKey'];
        $signType = 'MD5';
        $RongpayNotify = new RongpayNotifyClass($merchant_ID, $pkey, $signType, $this->charset, 'http');
        
        // 验证通知
        $verifyResult = $RongpayNotify->returnVerify();
        
        $noticeData = $this->getNoticeData();
        $trade_status = $noticeData['trade_status'];    //交易状态
        
        if($verifyResult) {
            if($trade_status == "TRADE_FINISHED") {
                $payResult = true;
            } elseif($trade_status == "WAIT_BUYER_PAY") {
                $payResult = false;
                $this->_errorInfo = '等待支付';
            } elseif($trade_status == "TRADE_FAILURE") {
                $payResult = false;
                $this->_errorInfo = '支付失败';
            } else {
                $payResult = false;
                $this->_errorInfo = '未知错误';
            }
        } else {
            $payResult = false;
            $this->_errorInfo = 'Verify signature is not consistent.';
        }
        
        return $payResult;
    }
    
    /**
     * 获取支付通知参数
     * @return boolean
     */
    public function &getNoticeData() {
        if(!empty($this->_noticeData)) {
            return $this->_noticeData;
        }
        $this->_noticeData = $_POST;
        return $this->_noticeData;
    }
    
    /**
     * 获取支付回调参数
     * @return boolean
     */
    public function &getReturnData() {
        if(!empty($this->_returnData)) {
            return $this->_returnData;
        }
        $this->_returnData = $_POST;
        return $this->_returnData;
    }
    
    public function getErrorInfo() {
        return $this->_errorInfo;
    }
    
}
