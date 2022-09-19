<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 16/8/26
 * Time: 10:40
 */

namespace itzlib\sdk;

class SdkClient
{
    /**
     * @var string APP Key
     */
    public $key = '';
    /**
     * @var string APP Secret
     */
    public $secret = '';
    /**
     * @var string iAuth 服务位于 zookeeper 树的名称
     */
    public $service = '';
    /**
     * @var string
     */
    public $info;
    /**
     * @var int
     */
    public $code;
    /**
     * @var string
     */
    const VERSION = 'v1';

    public function curl($verb, $path, $params)
    {
        $url = ServiceUtil::getAddress($this->service);
        $token = "x-itz-apptoken: " . str_pad("", 32, "1");
        $httpHeader = [$token];
        $curl = new CurlRestClient($url, $httpHeader);
        $curl->setOptions([CURLOPT_TIMEOUT => 3]);
        try {
            $rawResult = $curl->$verb($path, $params);
        } catch (\Exception $e) {
            \Yii::log($e, \CLogger::LEVEL_ERROR);
            $this->logRequest($url, $path, $curl->getHttpInfo(CURLINFO_TOTAL_TIME), false);
            return false;
        }

        $res = json_decode($rawResult, true);
        if ($res && is_array($res)) {
            $this->code = $res['code'];
            $this->info = $res['info'];
            $data = $res['data'];
            $this->logRequest($url, $path, $curl->getHttpInfo(CURLINFO_TOTAL_TIME), true, $this->code, $this->info);
        } else {
            $data = $rawResult;
            $this->logRequest($url, $path, $curl->getHttpInfo(CURLINFO_TOTAL_TIME), true, -1);
        }

        return $data;
    }

    /**
     * @param $url
     * @param $path
     * @param $consumedTime
     * @param $result
     * @param $code
     * @param $info
     */
    public function logRequest($url, $path, $consumedTime, $result, $code = 'NaN', $info = '')
    {
        $result = $result ? 'success' : 'fail';
        $msg = "url: {$url}, path: {$path}, consumed time: {$consumedTime}, result: {$result}, code: {$code}, info: {$info}";
        \Yii::log($msg, \CLogger::LEVEL_INFO, __CLASS__);
    }
}
