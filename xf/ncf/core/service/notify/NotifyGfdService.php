<?php

/**
 * NotifyGfdService 通知功夫贷服务
 * @date 2019-04-09
 * @auther wangchuanlu@ncfgroup.com
 */

namespace core\service\notify;

use core\dao\deal\DealModel;
use core\dao\deal\FeeAfterGrantModel;
use core\enum\JobsEnum;
use core\service\deal\FeeAfterGrantService;
use libs\utils\Logger;
use libs\utils\Curl;
use NCFGroup\Common\Library\encrypt\TripleDES;
use NCFGroup\Common\Library\OpenSSL;
use core\service\user\UserService;
use core\service\user\BankService;

class NotifyGfdService {

    const CODE_SUCCESS = '0000';
    /**
     * 请求接口超时时间（秒）
     */
    const REQ_TIMEOUT = 8;

    // 非加密参数
    public static $unencodeParams = [
        'code',
        'message',
        'merchant',
        'version',
    ];

    /**
     * 通知放款后收费
     * @param $orderId 唯一订单号
     */
    public function notifyFeeAfterGrant($orderId) {
        $record = FeeAfterGrantModel::instance()->getRecordByOrderId($orderId);
        if (empty($record))
        {
            throw new \Exception('代扣缴费通知订单'.$orderId.'不存在');
        }
        $deal = DealModel::instance()->getDealInfo($record['deal_id'],true);
        $dealInfo = $deal->getRow();

        $userInfo = UserService::getUserById(intval($record['deal_user_id']));
        if(empty($userInfo)) {
            throw new \Exception('获取用户信息失败');
        }

        // 银行卡信息
        $bankcard = BankService::getUserBankInfo(intval($record['deal_user_id']));
        if(empty($bankcard)) {
            throw new \Exception('获取用户银行卡信息失败');
        }
        // 获取银行名称数据
        $bankInfo = BankService::getBankInfoByBankId($bankcard['bank_id'], 'id,abbreviate_name');
        if (empty($bankInfo) || empty($bankInfo['abbreviate_name']))
        {
            throw new \Exception('获取银行信息失败');
        }
        $bankId = self::getGfdBankId($bankInfo['abbreviate_name']);
        if(empty($bankId)) {
            throw new \Exception('获取银行Id信息失败');
        }

        //产品说暂不考虑受托支付，此处银行卡信息只取借款人银行卡信息

        if($record['charge_result'] == FeeAfterGrantService::STATUS_FAILURE_OVERTIME){
            // 功夫贷特殊逻辑处理，功夫贷没有超时关单
            $record['charge_result'] = FeeAfterGrantService::STATUS_FAILURE;
        }
        $params = array();
        $params['merchant']     = $GLOBALS['sys_config']['NOTIFY_GDF_SERVICE']['merchant']; //商户编号
        $params['version']      = $GLOBALS['sys_config']['NOTIFY_GDF_SERVICE']['version']; //版本号
        $params['outOrderNo']   = strval($orderId); //外部订单号
        $params['orderStatus']  = intval($record['charge_result']); //扣款状态
        $params['paidAmount']   = strval(bcdiv($record['fee_amount'],100,2)); //扣款金额
        $params['cardNo']       = strval($bankcard['bankcard']); //银行卡号
        $params['userCardId']   = strval($userInfo['idno']); //身份证号
        $params['userName']     = strval($userInfo['real_name']); //用户名
        $params['mobile']       = strval($userInfo['mobile']); //手机号
        $params['bankId']       = intval($bankId); //扣款银行
        $params['startTime']    = date("Y-m-d H:i:s",$record['grant_time']); //扣款发起时间 格式为yyyy-MM-dd HH: mm:ss
        $params['occurTime']    = date("Y-m-d H:i:s",$record['callback_time']); //实际扣款时间 格式为yyyy-MM-dd HH: mm:ss

        $feeDetailList = json_decode($record['fee_detail_list'],true);
        $bizExtData = array(
            'approveNumber' => $dealInfo['approve_number'], //放款申请单号
            'platformFee'   => strval(bcdiv($feeDetailList['loan_fee']['amount'],100,2)), //平台费（单位元）
            'merchantFee'   => strval(bcdiv($feeDetailList['consult_fee']['amount'],100,2)), //咨询费（单位元）
        );
        $params['bizExtData']   = json_encode($bizExtData); //业务扩展数据
        $params['remark']       = $record['fail_reason'];
        $response = self::request($params);
        if(empty($response) || $response['code'] != self::CODE_SUCCESS) {//请求失败
            Logger::error("notifyFeeAfterGrant response:".json_encode($response));
            $errMsg = " fail code ".$response['code']." fail message ".$response['message'];
            throw new \Exception("通知功夫贷扣款结果失败{$errMsg}", JobsEnum::ERRORCODE_NEEDDELAY);
        }
        return true;
    }

    /**
     * 发起请求
     * @param $requestParams 请求参数
     * @return array|bool|\mix|mixed|null|\stdClass|string
     */
    public static function request($requestParams)
    {
        $timeStart = microtime(true);
        Logger::info('NotifyGfd Request Start');
        Logger::info('NotifyGfd OriginalParam:' .json_encode($requestParams));

        $url = $GLOBALS['sys_config']['NOTIFY_GDF_SERVICE']['url'];
        $requestParams['sign'] = self::signature($requestParams);
        $data = self::encode($requestParams);
        Logger::info('NotifyGfd EncryptedParam:' .json_encode($data));

        $getParams = array();
        foreach ($data as $k=>$v) {
            $getParams[] = sprintf('%s=%s',  $k, urlencode($v));
        }

        $url = $url.'?'.implode('&',$getParams);
        Logger::info('NotifyGfd RequestUrl---: '.$url);
        $response = Curl::post($url, [], [], self::REQ_TIMEOUT);
        if (empty($response)) {
            Logger::info('NotifyGfd Error: httpCode' . Curl::$httpCode . ', errno:' . Curl::$errno . ', error:' . Curl::$error);
            return false;
        }
        Logger::info('NotifyGfd EncryptedResponse:' . $response . ', cost:' . round((microtime(true) - $timeStart) * 1000, 3) );

        $response = json_decode($response, true);
        $response = self::decode($response);
        return $response;
    }

    /**
     * 签名
     */
    public static function signature($data)
    {
        $signStr = self::buildString($data);
        return base64_encode(OpenSSL::signature($signStr, $GLOBALS['sys_config']['NOTIFY_GDF_SERVICE']['rsa_private'], OPENSSL_ALGO_SHA256));
    }

    /**
     * 验签
     */
    public static function verifySignature($data, $signature)
    {
        $signature = base64_decode($signature);
        $signStr = self::buildString($data);
        return OpenSSL::verifySignature($signStr, $signature, $GLOBALS['sys_config']['NOTIFY_GDF_SERVICE']['rsa_public'], OPENSSL_ALGO_SHA256);
    }

    /**
     * 解密所需字段
     */
    public static function decode($data)
    {
        $des = new TripleDES(CRYPT_DES_MODE_ECB);
        $des->setKey($GLOBALS['sys_config']['NOTIFY_GDF_SERVICE']['des_3des']);
        foreach ($data as $key=> $value) {
            if(!in_array($key,self::$unencodeParams)) {
                $data[$key] = $des->decrypt(base64_decode($value));
            }
        }
        return $data;
    }

    /**
     * 加密所需字段
     */
    public static function encode($data)
    {
        $des = new TripleDES(CRYPT_DES_MODE_ECB);
        $des->setKey($GLOBALS['sys_config']['NOTIFY_GDF_SERVICE']['des_3des']);

        foreach ($data as $key=> $value) {
            if(!in_array($key,self::$unencodeParams)) {
                $data[$key] = base64_encode($des->encrypt($value));
            }
        }
        return $data;
    }

    /**
     * 构建签名所需字符串
     */
    private static function buildString($data)
    {
        ksort($data);
        $signStrs = '';
        foreach ($data as $key => $value) {
            if ($value !== null && $key !== 'signature' && $key !== 'sign') {
                $signStrs[] = sprintf('%s=%s',  $key, urlencode($value));
            }
        }
        return implode('&',$signStrs);
    }

    /**
     * 获取银行Id
     * @param $bankName
     */
    private static function getGfdBankId($bankName) {
        $bankInfos = $GLOBALS['dict']['GFD_BANKLIST'];
        foreach ($bankInfos as $bankInfo) {
            if($bankInfo['name'] == trim($bankName)) {
                return $bankInfo['id'];
            }
        }
        return 0;
    }

}
