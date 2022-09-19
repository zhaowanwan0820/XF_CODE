<?php
/**
 *  存管接口对接
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\ApiBase;
use NCFGroup\Common\Library\AesLib;
use NCFGroup\Common\Library\RsaLib;
use NCFGroup\Common\Library\services\Supervision;
use NCFGroup\Common\Library\StandardApi;

class ApiSupervisionLib extends ApiBase
{
    // 网关通用配置信息
    protected $config = null;
    // 将接口定义挪到类中，降低配置文件大小
    protected $services = null;

    public $specialHeaders = [];

    public function __construct($config, $services)
    {
        $this->config = $config;
        $this->services = $services;
    }

    /**
     * 拼接参数
     * @param array $params 业务参数
     * @return array 拼接好的参数
     */
    public function generateParams(array $params, $key = null)
    {
        if (empty($key))
        {
            throw new \Exception('无效的业务名称', Supervision::RESPONSE_CODE_FAILURE);
        }
        $service = $this->services->get($key);
        // 构建请求存管的消息体
        $requestBody = [];
        // 补全默认值
        if (!empty($service['defaults'])) {
            foreach ($service['defaults'] as $keyName => $defaultValue)
            {
                if (isset($params[$keyName]))
                {
                    continue;
                }
                $params[$keyName] = $defaultValue;
            }
        }
        //拼接参数
        // noticeUrl  默认配置了通知地址 但是业务还没传递通知地址的时候,使用拼装的通知地址
        if (!empty($service['noticeUrl']) && empty($params['noticeUrl']))
        {
            //不使用默认通知地址，业务方必须提供，修改请联系 @王群强
            //$params['callbackUrl'] = $this->getNotifyDomain() . $service['noticeUrl'];
        } else if (!empty($params['noticeUrl'])) {
            $params['callbackUrl'] = $params['noticeUrl'];
            unset($params['noticeUrl']);
        }

        // returnUrl 默认配置了 跳转地址 但是业务还没传递 跳转地址的时候,使用拼装的 跳转地址
        if (isset($service['returnUrl']) && empty($params['returnUrl']))
        {
            $params['returnUrl'] = $this->getReturnDomain() . $service['returnUrl'];
        }

        // 必填签参数校验
        $this->_checkParams($service, $params);

        // 补全系统级参数
        $params['merchantId']  = $this->services->getConfig('merchantId');
        $params['method']      = $this->services->getConfig('method');
        $params['source']      = $this->services->getConfig('source');
        $params['version']     = isset($params['version']) ? $params['version'] : $this->services->getConfig('version');
        $params['reqSn']       = strtoupper(md5(microtime(true) . uniqid() . mt_rand(100000, 999999)));
        $params['requestTime'] = date('Y-m-d H:i:s');
        $params['service']     = $service['service'];
        // 移动端类型
        if (strpos($service['service'], 'h5') !== false) {
            // 请求来源(11：APP 的IOS，12：APP的Android，21：wap的IOS，22：wap的Android)
            $params['mobileType'] = isset($params['mobileType']) && in_array($params['mobileType'], [11, 12, 21, 22]) ? (int)$params['mobileType'] : 11;
            $params['source']     = Supervision::REQ_SOURCE_MOBILE; // 请求来源(1:PC|2:MOBILE)
        }
        // 生成签名
        $params['signature'] = $this->getSignature($params);
        // 打印脱敏请求数据
        $this->filterLog($params, $key);

        // data 加密
        $requestBody['merchantId'] = $params['merchantId'];
        $paramsString = $this->getParamsString($params);
        $md5Val = md5($paramsString);
        // 生成AESKEY
        $aesKey = md5($md5Val);
        $aesKeyBin = $this->_aesKeyConvert($aesKey);
        $requestBody['data'] = AesLib::encode(json_encode($params, JSON_UNESCAPED_UNICODE), $aesKeyBin);
        $requestBody['tm']   = RsaLib::PublicEncrypt($md5Val, $this->services->getConfig('platformPublicKey'));
        return $requestBody;
    }

    /**
     *  生成签名
     */
    public function getSignature($params, $getRawSignData = false)
    {
        $paramsString = $this->getParamsString($params);
        if ($getRawSignData)
        {
            return $paramsString;
        }
        $md5Val = md5($paramsString);
        $signature = RsaLib::PrivateEncrypt($md5Val, $this->services->getConfig('merchantPrivateKey'));
        return $signature;
    }

    /**
     * 验证签名
     */
    public function verify($response)
    {
        // TODO 异步通知需要实现
        if (!isset($response['signature']))
        {
            StandardApi::instance($this->getGateway())->log(sprintf('ApiSupervisionLib_verify, Response data format cannot recognized, responseData:%s', json_encode($response)), 'ERROR');
            return false;
        }

        // 解析签名
        $md5Val = RsaLib::PublicDecrypt($response['signature'], $this->services->getConfig('platformPublicKey'));

        // 计算签名
        $signatureData = $this->getSignature($response, true);
        $paramsMd5Value = md5($signatureData);
        $result = $paramsMd5Value === $md5Val;
        if (!$result) {
            StandardApi::instance($this->getGateway())->log(sprintf('ApiSupervisionLib_verify, Signature failed. responseData:%s, get md5val = %s, local md5Val = %s', json_encode($response), $md5Val, $paramsMd5Value), 'ERROR');
            return false;
        }
        return true;
    }

    /**
     * 加密数据
     * @param string dataString 加密的明文数据
     * @param string $withWhichUser 指定用户证书
     * @return string 加密后数据
     */
    public function encrypt($dataString, $withWhichKey = 'platformPublicKey')
    {
        return AesLib::encode($dataString, $this->services->getConfig($withWhichKey));
    }

    /**
     * 解密数据
     * @param string $response 加密数据
     * @param boolean $verify 是否校验签名
     * @return string 解密后的数据
     */
    public function decrypt($response, $verify = false)
    {
        $response = json_decode($response, true);
        if (!isset($response['tm']) || !isset($response['data']) ) {
            throw new \Exception('存管系统返回结果的格式不正确', Supervision::RESPONSE_CODE_FAILURE);
        }

        $md5Val = RsaLib::PrivateDecrypt($response['tm'], $this->services->getConfig('merchantPrivateKey'));
        $aesKey = md5($md5Val);
        $aesKeyBin = $this->_aesKeyConvert($aesKey);
        $decryptData = AesLib::decode($response['data'], $aesKeyBin);
        StandardApi::instance($this->getGateway())->log(sprintf("ApiSupervisionLib_decrypt, AesKey:%s, result:%s", $aesKey, $decryptData), 'INFO');

        // 把data转成数组
        $result = !empty($decryptData) ? json_decode($decryptData, true) : [];
        $resultData = is_null($result) ? [] : $result;
        if (!$verify || empty($resultData)) {
            // 远程返回的不是json格式或为空,返回包有问题
            return $resultData;
        }

        // 进行签名校验
        $signRet = $this->verify($resultData);
        return $signRet === true ? $resultData : [];
    }

    public function response($params)
    {
        $requestBody = array();
        $requestBody['merchantId'] = $this->services->getConfig('merchantId');
        $paramsString = $this->getParamsString($params);
        $md5Val = md5($paramsString);
        $aesKey = md5($md5Val);
        $aesKeyBin = $this->_aesKeyConvert($aesKey);
        $params['signature'] = $this->getSignature($params);
        $requestBody['data'] = AesLib::encode(json_encode($params, JSON_UNESCAPED_UNICODE), $aesKeyBin);
        $requestBody['tm']   = RsaLib::PublicEncrypt($md5Val, $this->services->getConfig('platformPublicKey'));
        $data = json_encode($requestBody, JSON_UNESCAPED_UNICODE);
        StandardApi::instance($this->getGateway())->log(sprintf('ApiSupervisionLib_response. merchantId:%s, params:%s, responseData:%s', $requestBody['merchantId'], json_encode($params, JSON_UNESCAPED_UNICODE), json_encode($data, JSON_UNESCAPED_UNICODE)), 'INFO');
        return $data;
    }

    /**
     * 获取待签名的原数据
     * @param array $params
     * @return string
     */
    public function getParamsString($params) {
        if (isset($params['signature']))
        {
            unset($params['signature']);
        }
        ksort($params);

        $paramsJoin = array();
        foreach ($params as $key => $value)
        {
            $paramsJoin[] = "$key=$value";
        }
        $paramsString = implode('&', $paramsJoin);
        return $paramsString;
    }

    private function _checkParams($config, $params) {
        // 必填签参数校验
        if (isset($config['required'])) {
            $required = $config['required'];
            $fieldNames = array_keys($required);
            foreach ($fieldNames as $fieldName) {
                if (!array_key_exists($fieldName, $params) || $params[$fieldName] === '') {
                    $fieldDesc = $required[$fieldName];
                    $fieldName == 'callbackUrl' && $fieldName = 'noticeUrl'; //提示错误修改
                    $errorMsg = sprintf('%s(%s) Is Required', $fieldDesc, $fieldName);
                    StandardApi::instance($this->getGateway())->log(sprintf('ApiSupervisionLib_checkParams, service:%s, params:%s, %s', $config['service'], json_encode($params, JSON_UNESCAPED_UNICODE), $errorMsg), 'ERROR');
                    throw new \Exception($errorMsg, Supervision::RESPONSE_CODE_FAILURE);
                }
            }
        }
    }

    /**
     * AES Key转换
     */
    private function _aesKeyConvert($key)
    {
        $result = '';
        $keyLen = strlen($key);
        for ($i = 0; $i < $keyLen; $i += 2)
        {
            $result .= chr('0x'.$key[$i].$key[$i + 1]);
        }
        return $result;
    }

}
