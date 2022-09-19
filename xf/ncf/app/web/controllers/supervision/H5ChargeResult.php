<?php
/**
 * 存管充值成功页面跳转落地页
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use core\enum\SupervisionEnum;
use core\service\supervision\SupervisionService;
use core\service\supervision\SupervisionFinanceService;

class H5ChargeResult extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        PaymentApi::log("Supervision Gateway Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision Gateway redirect.");
            return app_redirect('/');
        }
        try {
            //参数获取
            $supervisionObj = new SupervisionService();
            $requestData = $supervisionObj->getApi()->decode(json_encode($_POST));
            //签名验证
            $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
            $verifyResult = $supervisionObj->getApi()->verify($requestData);
            if ($verifyResult === false)
            {
                throw new \Exception('签名验证失败');
            }
            $supervisionFinanceService = new SupervisionFinanceService();
            $result = $supervisionFinanceService->chargeNotify($requestData, '充值到存管账户');
            if ($result['respCode'] == SupervisionEnum::RESPONSE_CODE_SUCCESS && $result['status'] == SupervisionEnum::RESPONSE_SUCCESS) {
                echo '<h1>充值成功</h1>';
            } else {
                throw new \Exception('订单处理失败', '20001');
            }
        } catch (\Exception $e) {
            echo '<h1>充值失败:'.$e->getCode().' '.$e->getMessage().'</h1>';
        }
    }
}