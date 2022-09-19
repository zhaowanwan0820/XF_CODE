<?php
namespace libs\passport;

use NCFGroup\Common\Library\OpenSSL;
use libs\utils\Curl;
use libs\utils\Logger;
use libs\utils\Aes;
use libs\utils\ABControl;

// 网信通行证
class Passport
{
    const SIGNATURE_ALGORITHM = 'sha256WithRSAEncryption';

    const VERSION = '1.0.0';

    public static $encodeParams = [
        'requestParam',
        'bizResponse',
    ];

    public static function request($requestParams, $service, $extProperties = [])
    {
        $timeStart = microtime(true);
        Logger::info('Passport Request Start');
        $url = app_conf('PASSPORT_SERVER');
        $data = [
            'requestParam' => self::makeJson($requestParams),
            'service' => $service,
            'version' => self::VERSION,
            'extProperties' => self::makeJson($extProperties)
        ];
        $logData = $data;
        if ($service == 'api.login.authenticate') {
            $requestParams['password'] = '*****';
            $logData['requestParam'] = self::makeJson($requestParams);
        }
        Logger::info('Passport OriginalParam:' .json_encode($logData));
        $data = self::encode($data);
        $data['signature'] = self::signature($data);
        $data = self::makeJson($data);
        Logger::info('Passport EncryptedParam:' .$data);
        $response = Curl::post_json($url, $data, app_conf('PASSPORT_TIMEOUT'));
        if (empty($response)) {
            Logger::info('Passport Error: httpCode' . Curl::$httpCode . ', errno:' . Curl::$errno . ', error:' . Curl::$error);
            return false;
        }
        Logger::info('Passport EncryptedResponse:' . $response . ', cost:' . round((microtime(true) - $timeStart) * 1000, 3) );
        $response = json_decode($response, true);
        if (!self::verifySignature($response, $response['signature'])) {
            Logger::info('Passport Error: Response 验签失败');
        }
        $response = self::decode($response);
        //Logger::info('Passport FinalResponse:' . json_encode($response) . ', cost:' . round((microtime(true) - $timeStart) * 1000, 3));
        return $response;
    }

    /**
     * 统一返回接口
     */
    public static function response($code, $data = [], $extProperties = [])
    {
        $response = [
            'bizResponse' => empty($data) ? null : self::makeJson($data),
            'code' => substr($code, 0, 3),
            'extProperties' => self::makeJson($extProperties),
            'subCode' => substr($code, 3, 3),
            'respMsg' => isset(CodeEnum::$msg[$code]) ? CodeEnum::$msg[$code] : '未知错误'
        ];
        //Logger::info('Passport OriginalResponse:' .var_export($response, true));
        $response = self::encode($response);
        $response['signature'] = self::signature($response);
        $response = self::makeJson($response);
        Logger::info('Passport EncryptedResponse:' .var_export($response, true));

        header('Content-type: application/json;charset=UTF-8');
        echo $response;
        exit();
    }

    /**
     * 签名
     */
    public static function signature($data)
    {
        $signStr = self::buildString($data);
        return base64_encode(OpenSSL::signature($signStr, app_conf('PASSPORT_WX_PRIVATE_KEY'), self::SIGNATURE_ALGORITHM));
    }

    /**
     * 验签
     */
    public static function verifySignature($data, $signature)
    {
        $signature = base64_decode($signature);
        $signStr = self::buildString($data);
        return OpenSSL::verifySignature($signStr, $signature, app_conf('PASSPORT_PUBLIC_KEY'), self::SIGNATURE_ALGORITHM);
    }

    /**
     * 解密所需字段
     */
    public static function decode($data)
    {
        foreach (self::$encodeParams as $field) {
            if (!empty($data[$field])) {
                $data[$field] = json_decode(Aes::decode($data[$field], app_conf('PASSPORT_AES_KEY')), true);
            }
        }
        return $data;
    }

    /**
     * 加密所需字段
     */
    public static function encode($data)
    {
        foreach (self::$encodeParams as $field) {
            if (!empty($data[$field])) {
                $data[$field] = Aes::encode($data[$field], app_conf('PASSPORT_AES_KEY'));
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
        $signStr = '';
        foreach ($data as $key => $value) {
            if ($value !== null && $key !== 'signature' && $key !== 'sign') {
                $signStr .= sprintf('"%s""%s"',  $key, $value);
            }
        }
        return $signStr;
    }

    /**
     * json标准
     */
    private static function makeJson($data)
    {
        return json_encode($data, JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE);
    }
}
