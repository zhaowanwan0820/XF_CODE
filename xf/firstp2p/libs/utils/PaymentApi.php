<?php
/**
 * 先锋支付请求接口
 *
 * 依赖配置文件 conf/paymentapi.conf.php
 * @author 全恒壮 <quanhengzhuang@ucfgroup.com>
 */
namespace libs\utils;

use libs\utils\Curl;
use libs\utils\Aes;
use libs\utils\Logger;
use libs\utils\PaymentRemoteLog;

class PaymentApi
{
    /**
     * 支付方式支持的方法数组
     * @var array
     */
    private $supportMethods;

    /**
     * PaymentApi对象
     * @var array
     */
    static $pObjects = array();

    /**
     * 支付网关对象
     * @var object
     */
    private $gateway;

    /**
     * 默认的支付方式
     * @var string
     */
    private $_paymentMethod = 'ucfpay';

    /**
     * 接口参数配置
     * @var array
     */
    private $config = null;

    /**
     * 配置文件
     * @var string
     */
    const CONFIG_FILE = 'conf/paymentapi.conf.php';

    /**
     * 日志文件前缀
     * @var string
     */
    const LOG_FILE_PREFIX = 'log/logger/paymentapi';

    /**
     * 接口重试次数
     * @var int
     */
    const REQUEST_API_RETRY_COUNT = 3;

    /**
     * 支付方式-先锋支付
     * @var string
     */
    const PAYMENT_SERVICE_UCFPAY = 'ucfpay';

    /**
     * 支付方式-易宝支付
     * @var string
     */
    const PAYMENT_SERVICE_YEEPAY = 'yeepay';

    /**
     * 支付方式-海口联合农商银行
     * @var string
     */
    const PAYMENT_SERVICE_UNITEBANK = 'unitebank';

    /**
     * 支付方式-存管系统
     * @var string
     */
    const PAYMENT_SERVICE_SUPERVISION = 'supervision';

    private function __construct($method = 'ucfpay')
    {
        // 设置支付方式
        $this->_setPaymentMethod($method);
    }

    /**
     * 支付服务是否处于降级状态
     */
    public static function isServiceDown()
    {
        return app_conf('PAYMENT_SERVICE_LEVEL') == '1' ?  true : false;
    }

    /**
     * 读取配置为可用的支付方法
     * 配置格式：先锋支付:ucfpay:0|易宝支付:yeepay:1
     * @return array('ucfpay'=>'先锋支付', 'yeepay'=>'易宝支付');
     */
    public static function getPaymentChannel()
    {
        $enableChannels = array();
        // 默认启用ucfpay
        $paymentChannels = app_conf('PAYMENT_SERVICE_CHANNEL')?: '';
        if (!empty($paymentChannels))
        {
            $paymentChannels = explode('|', $paymentChannels);

            if (is_array($paymentChannels))
            {
                foreach ($paymentChannels as $paymentChannel)
                {
                    $paymentChannel = explode(':', $paymentChannel);
                    if (isset($paymentChannel[0]) && isset($paymentChannel[2]) && $paymentChannel[2] == 1)
                    {
                        $enableChannels[$paymentChannel[1]] = $paymentChannel[0];
                    }
                }
                return $enableChannels;
            }
        }
        return array();
    }

    /**
     * 支付服务降级提示信息
     */
    public static function maintainMessage()
    {
        return app_conf('PAYMENT_SERVICE_MAINTAINCE_MESSAGE')?:'支付系统维护中,请稍后再试';
    }

    /**
     * 初始化支付对象实例
     *
     *
     * @param  string $method 支付方式名称
     * @return Object $pObject 特定的支付对象
     */
    static function instance($method = 'ucfpay')
    {
        if (empty($method))
        {
            // 根据后台配置切换支付渠道
            $method = 'ucfpay';
        }
        if (empty(self::$pObjects[$method]))
        {
            $pObject = new PaymentApi($method);
            self::$pObjects[$method] = $pObject;
        }
        return self::$pObjects[$method];
    }

    /**
     * 获取支付网关句柄
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * 获取支付方式名称
     */
    public function getPaymentMethod()
    {
        return $this->_paymentMethod;
    }

    /**
     * 设置支付方式
     */
    private function _setPaymentMethod($method)
    {
        $method = strtolower($method);
        if ($this->config === null)
        {
            $this->config = include APP_ROOT_PATH . self::CONFIG_FILE;
        }

        if ($this->supportMethods === null)
        {
            $this->supportMethods = array_keys($this->config);
        }
        if (!in_array($method, $this->supportMethods))
        {
            throw new \Exception('Invalid payment method name:'.$method);
        }
        // init payment
        $paymentClass = 'libs\payment\\'.$method.'\\'.ucfirst($method);
        if (!class_exists($paymentClass))
        {
            throw new \Exception(sprintf('payment class [%s] is not found', $paymentClass));
        }
        $this->_paymentMethod = $method;
        $this->gateway = new $paymentClass();
        // override config array
        $this->config = $this->config[$method];
        // 透传配置参数
        $this->gateway->setGlobalConfig($this->config);
    }

    /**
     * 后台请求trade-p2p
     * @param string $key 接口关键字，参考paymentapi.conf.php
     * @param array $params 参数数组
     * @return array
     */
    public function request($key, $params)
    {
        return $this->gateway->request($key, $params);
    }

    /**
     * 请求mobile-p2p系统
     */
    public function requestMobile($key, $params)
    {
        return $this->gateway->requestMobile($key, $params);
    }

    /**
     * 日志记录
     */
    public static function log($body, $level = Logger::INFO)
    {
        $ucfpay = PaymentApi::instance();
        //远程日志
        $ip = $ucfpay->getGateway()->getConfig('common', 'REMOTE_LOG_IP');
        $port = $ucfpay->getGateway()->getConfig('common', 'REMOTE_LOG_PORT');

        PaymentRemoteLog::instance($ip, $port)->add($level, $body);
    }

}
