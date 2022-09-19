<?php
/**
 * 第三方投资接口通知服务
 */
namespace core\service;

use libs\utils\Curl;
use libs\utils\PaymentApi;

class InvestNotifyService extends BaseService
{

    //接口状态
    const API_SUCCESS = 'S';
    const API_FAILED = 'F';
    const API_INHAND = 'I';
    // 第三方接口返回文本
    const API_CLIENT_RESPONSE_TEXT = 'success';


    public function notify($notifyData) {
        try
        {
            $url = app_conf('OPENAPI_RESPONSE_GATEWAY');
            if (empty($url)) {
                throw new \Exception('无效的配置OPENAPI_RESPONSE_GATEWAY');
            }
            // 去掉支付传过来的多余的参数
            if (isset($notifyData['signature'])) {unset($notifyData['signature']);}
            if (isset($notifyData['event_id'])) {unset($notifyData['event_id']);}

            // 构造回调参数列表
            $params = $notifyData;

            $paramsJson = json_encode($params, JSON_UNESCAPED_UNICODE);
            //请求openapi代理接口发送回调
            $requestStart = microtime(true);
            PaymentApi::log('Request investnotify , url:'.$url.', params:'.$paramsJson);
            // 读取第三方返回数据内容
            $response = Curl::post($url, $params);
            $requestCost = round(microtime(true) - $requestStart, 3);
            $httpCode = Curl::$httpCode;
            $error = Curl::$error;
            // 只能记录跟openapi之间的交互网络状态
            PaymentApi::log('Response investnotify . code:'.$httpCode.', cost:'.$requestCost.'s, err:'.$error.', ret:'.$response);
            $responseData = json_decode($response, true);

            if (is_array($responseData)) {
                // 响应成功
                if (isset($responseData['respCode']) && $responseData['respCode'] == '00')
                {
                    return true;
                }
                // 响应失败，重试
                else
                {
                    \libs\utils\Alarm::push('investNotify', 'Request_Failed', "url:$url, code:$code, error:$error, params:$paramsJson, response:$response");
                    return false;
                }
            }
            else
            {
                return false;
            }

        }
        catch(\Exception $e)
        {
            PaymentApi::log($e->getMessage());
            return false;
        }

        return true;
    }



}
