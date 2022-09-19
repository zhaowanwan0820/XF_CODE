<?php
/**
 * 存管动账接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\common\ErrCode;
use core\service\gateway\GatewayService;
use core\service\SupervisionBaseService AS Supervision;


class Gateway extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        $_POST = !empty($_POST['data']) ? json_decode($_POST['data'], true) : [];
        // 解密
        if (!isset($_POST['tm']) || !isset($_POST['data']) ) {
            throw new \Exception('参数不正确', Supervision::RESPONSE_CODE_FAILURE);
        }
        $supervision = PaymentApi::instance('supervision')->getGateway();
        //参数获取
        PaymentApi::log("Supervision Gateway Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision Gateway redirect.");
            return app_redirect('/');
        }

        // 解密Data数据
        $requestData = $supervision->getData($_POST);
        //签名验证
        $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
        $verifyResult = $supervision->verifyServiceSignature($requestData, $signature);
        if ($verifyResult === false)
        {
            echo $supervision->responseFailure('ERR_SIGNATURE');
            return;
        }

        //逻辑处理
        try {
            $result = (new GatewayService())->execute($requestData);
            if (empty($result)) {
                throw new \Exception(ErrCode::getMsg('ERR_SYSTEM'), ErrCode::getCode('ERR_SYSTEM'));
            }
            echo $supervision->response([
                'respCode' => Supervision::RESPONSE_CODE_SUCCESS,
                'status' => Supervision::RESPONSE_SUCCESS,
                'respMsg' => '',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            echo $supervision->response([
                'respCode' => Supervision::RESPONSE_CODE_FAILURE,
                'status' => Supervision::RESPONSE_FAILURE,
                'respMsg' => $e->getMessage().'('.$e->getCode().')',
            ]);
        }
        return;
    }
}