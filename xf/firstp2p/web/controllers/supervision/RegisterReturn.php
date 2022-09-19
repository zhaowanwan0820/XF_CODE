<?php
/**
 * firstp2p网站-普通用户开户-同步回调接口
 * 
 */
namespace web\controllers\supervision;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;

class registerReturn extends BaseAction
{
    public function init()
    {
    }

    public function invoke()
    {
        // 校验来源参数
        $platform = isset($_GET['pf']) ? addslashes($_GET['pf']) : 'pc';
        $supervisionBaseObj = new \core\service\SupervisionBaseService();
        if (true !== $supervisionBaseObj->checkPlatform($platform)) {
            PaymentApi::log("Supervision registerReturnCallback redirect, platform:{$platform}");
            return app_redirect('/');
        }

        // PC的回调，关闭当前页
        if ($platform === 'pc') {
            echo '<script>window.close();</script>';
            exit;
        }

        //参数获取
        PaymentApi::log("Supervision registerReturnCallback Request. method:{$_SERVER['REQUEST_METHOD']}, platform:{$platform}, params:".json_encode($_POST));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Supervision registerReturnCallback redirect.");
            return app_redirect('/');
        }

        // 存管系统异常，同步回调没有返回开户状态等参数@TODO
        if (empty($_POST)) {
            echo '存管系统异常，直接跳转到商户相应页面';
            exit;
        }

        // 解密Data数据
        $supervision = PaymentApi::instance(PaymentApi::PAYMENT_SERVICE_SUPERVISION)->getGateway();
        $requestData = $supervision->getData($_POST);
        PaymentApi::log('Supervision registerReturnCallback Request params decode:' . json_encode($requestData));

        // 签名验证
        $signature = isset($requestData['signature']) ? trim($requestData['signature']) : 0;
        $verifyResult = $supervision->verifySignature($requestData, $signature);
        if ($verifyResult === false)
        {
            echo $supervision->responseFailure('ERR_SIGNATURE');
            return;
        }

        // 参数列表
        $params = array();
        $params['merchantId'] = isset($requestData['merchantId']) ? addslashes($requestData['merchantId']) : '';
        $params['userId'] = isset($requestData['userId']) ? intval($requestData['userId']) : '';
        $params['status'] = isset($requestData['status']) ? addslashes($requestData['status']) : '';
        $params['remark'] = isset($requestData['remark']) ? addslashes($requestData['remark']) : '';

        // 逻辑处理
        $result = $this->rpc->local('SupervisionAccountService\registerNotify', [$params]);
        echo !empty($result['respMsg']) ? $result['respMsg'] : '个人用户开户失败，请重试';
        exit;
    }
}