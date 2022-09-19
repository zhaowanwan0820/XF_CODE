<?php

/**
 * 即富系统对接
 */

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\SignatureLib;
use NCFGroup\Common\Library\AesLib;
use NCFGroup\Common\Library\Idworker;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;
use NCFGroup\Common\Library\Logger;


class ApiJfpayLib
{

    static $GZIPMap = [
        'apply',
    ];

    private $config = null;

    /**
     * 特别的头信息
     */
    public $specialHeaders = [
    ];

    public $specialHeadersTmpl = [
        'Content-Type: application/z-herion',
    ];

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 拼接参数
     * @param array $params 请求业务参数
     * @param string $key 请求业务接口名称，如果有默认参数，需要传递
     * @return array
     */
    public function generateParams(array $params, $key = '')
    {
        // 初始化特殊header 修复token失效时刷新token导致的多个sign问题
        $this->specialHeaders = $this->specialHeadersTmpl;
        // token 处理
        $token = isset($params['token']) ? trim($params['token']) : null;
        if ($key !== 'refreshToken' && empty($token)) {
            throw new \Exception('GenerateParams needs a token.');
        }
        // 移除token
        unset($params['token']);
        // 封装CommReq内容
        $requestParams = [
            'reqNo' => (string)Idworker::instance()->getId(),
            'reqTime' => date('YmdHis'),
            'chCode' => $this->config['channelId'],
            'reqData' => !empty($params) ? $params : '',
        ];
        if ($key === 'refreshToken') {
            $requestParams['reqData'] = ['chCode' => $requestParams['chCode']];
        }
        // reqData 加密
        Logger::info("Jfpay generateParams. data :".json_encode($requestParams, JSON_UNESCAPED_UNICODE));
        $requestParams['reqData'] = json_encode($requestParams['reqData'], JSON_UNESCAPED_UNICODE);
        // 数据gzip压缩
        if (in_array($key, self::$GZIPMap)) {
            //$requestParams['reqData'] = base64_encode(gzencode($requestParams['reqData']));
        }
        $config = getDI()->getConfig()->api->jfpay;
        Logger::error(json_encode($config));
        $requestParams['reqData']= AesLib::CreditLoanCrypt($requestParams['reqData'], $config['cryptKey'], $config['cryptIv']);
        // 封装CommReq
        $requestParams = base64_encode(json_encode($requestParams, JSON_UNESCAPED_UNICODE));
        $sign = strtoupper(bin2hex(hash_hmac('sha256', $requestParams, $token, true)));
        $this->specialHeaders[] = "sign: $sign";
        return $requestParams;
    }

    public function getBytes($input)
    {
        $bytes = array();
        for($i = 0; $i < strlen($input); $i++){
             $bytes[] = ord($input[$i]);
        }
        return join('', $bytes);
    }

    /**
     * 验证签名
     */
    public static function verify($data, $signGet = '', $token = '')
    {
        $sign = strtoupper(bin2hex(hash_hmac('sha256', $data, $token, true)));
        return $sign === $signGet;
    }

    /**
     * 加密数据
     */
    public function encode($data)
    {
        return $data;
    }

    /**
     * 解密数据
     */
    public function decode($data, $key = '', $withGzip = false)
    {
        // 尝试解码，如果json解码成功，则返回了非加密消息, 通常是失败的业务
        $input = json_decode($data, 1);
        if (empty($input)) {
            // 如果json解码失败，则返回了base64加密消息
            $input = json_decode(base64_decode($data), 1);
        }
        if (empty($input) || empty($input['code'])  || (isset($input['code']) && $input['code'] != CreditEnum::CODE_SUCCESS)) {
            Logger::error('Encode failed, data:'.json_encode($input, JSON_UNESCAPED_UNICODE));
            return $input;
        }
        // 通知类的消息有加解密策略时启用respData解密
        if ($withGzip) {
            $input['respData'] = gzdecode($input['respData']);
        }
        if (!isset($input['respData']))
        {
            return $input;
        }
        $config = getDI()->getConfig()->api->jfpay;
        $data = AesLib::CreditLoanDecrypt($input, $config['cryptKey'], $config['cryptIv']);
        Logger::info("Jfpay decode. data :".json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

}
