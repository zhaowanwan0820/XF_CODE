<?php

namespace NCFGroup\Common\Library;

use NCFGroup\Common\Library\Api;
use NCFGroup\Common\Library\TraceSdk;

/**
 * services 基类
 **/
class ApiService {
    // 是否有错误
    protected static $error = false;
    // 错误信息
    protected static $errorMsg = '';
    // 错误码
    protected static $errorCode = 0;
    // 错误产生的时候，保存错误数据
    protected static $errorData = '';

    protected static function clearError() {
        self::$error = false;
        self::$errorData = '';
        self::$errorMsg = '';
        self::$errorCode = 0;
    }

    protected static function setError($msg, $code, $data = '') {
        self::$error = true;
        self::$errorMsg = $msg;
        self::$errorCode = $code;
        self::$errorData = $data;
    }

    public static function getErrorMsg() {
        return self::$errorMsg;
    }

    public static function getErrorCode() {
        return self::$errorCode;
    }

    public static function getErrorData() {
        return self::$errorData;
    }

    public static function hasError() {
        return self::$error;
    }

    /**
     * 请求数据
     * @param $servieType string 业务类型，如firstp2p, o2o, ncfwx等
     * @param $route string 业务路由
     * @param $params array 请求参数
     * @param $isThrow bool 是否抛出异常，默认为false，即不抛出异常
     * @param $timeout int 超时时间，默认是3s
     * @return mixed
     */
    public static function rpc($servieType, $route, $params, $isThrow = false, $timeout = 3) {
        self::clearError();

        try {
            $result = Api::instance('rpc')
                ->gateway($servieType, $route, true)
                ->request($servieType, $params, [], true, $timeout);

            if (!isset($result['errorCode'])) {
                throw new \Exception('Invalid response, no errCode');
            }

            if ($result['errorCode'] != 0) {
                throw new \Exception($result['errorMsg'], $result['errorCode']);
            }

            // 直接返回结果数据
            return $result['data'];
        } catch (\Exception $ex) {
            self::setError($ex->getMessage(), $ex->getCode(), empty($result['data']) ? '' : $result['data']);
            $logData = array(
                'serviceType' => $servieType,
                'route' => $route,
                'params' => $params
            );

            TraceSdk::record(TraceSdk::LOG_TYPE_ERROR, __FILE__, __LINE__, 'rpc', [
                'request'=>$logData,
                'response'=>isset($result) ? $result : '',
                'error'=>$ex->getMessage()
            ]);

            if ($isThrow) {
                throw $ex;
            }

            return false;
        }
    }
}