<?php
/**
 * firstp2p 标的详情页查询接口
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
class GetDealDetails extends BaseAction
{

    //MD5 'xfjr'
    CONST PARTNER_ID = '6e199e0893798f90db7c016ad96462f7';

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("GetDealDetails Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("GetDealDetails redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['partnerId'] = isset($_POST['partnerId']) ? trim($_POST['partnerId']) : '';
        $params['dealId'] = isset($_POST['dealId']) ? trim($_POST['dealId']) : '';

        //必填参数验证
        foreach ($params as $key => $value)
        {
            if ($value === '' || $value === 0)
            {
                echo PaymentApi::instance()->getGateway()->response(array(
                    'respCode' => '01',
                    'respMsg' => "Param $key is invalid",
                ));
                PaymentApi::log("param $key is empty", Logger::ERR);
                \libs\utils\Alarm::push('payment', 'GetDealDetails', "param $key is empty. params:".json_encode($params));
                return;
            }
        }

        //PartnerId校验
        if ($params['partnerId'] !== '6e199e0893798f90db7c016ad96462f7')
        {
            echo PaymentApi::instance()->getGateway()->response(array(
                'respCode' => '01',
                'respMsg' => 'PartnerId is invalid',
            ));
            PaymentApi::log("PartnerId error. partnerId:{$params['partnerId']}", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'GetDealDetails', "PartnerId error. partnerId:{$params['partnerId']}, params:".json_encode($params));
            return;
        }

        //签名验证
        $signature = isset($_POST['sign']) ? trim($_POST['sign']) : 0;
        unset($_POST['sign']);
        $signatureLocal = PaymentApi::instance()->getGateway()->getSignature($_POST);
        if ($signature !== $signatureLocal)
        {
            echo PaymentApi::instance()->getGateway()->response(array(
                'respCode' => '01',
                'respMsg' => 'SIGNATURE_ERROR',
            ));
            PaymentApi::log("Signature failed. get:$signature, local:$signatureLocal", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'GetDealDetails', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }

        $requestParams = [
            $params['dealId'],
            true,
            true,
        ];
        //逻辑处理
        $result = $this->rpc->local('DealService\getDeal', $requestParams);
        $responseData = ['respCode' => '00', 'respMsg' => '', 'data' => []];
        if ($result === false)
        {
            $responseData['respCode'] = '01';
            $responseData['respMsg'] = '标不存在';

        }
        else
        {
            $responseData['data'] = $result->getRow();
            // 获取借款人真实姓名
            $userInfo = !empty($responseData['data']['user_id']) ? $this->rpc->local('UserService\getUserArray', array($responseData['data']['user_id'], 'real_name')) : array();
            $responseData['data']['user_real_name'] = !empty($userInfo['real_name']) ? $userInfo['real_name'] : $responseData['data']['user_deal_name'];
            $responseData['data']['cate_info'] = $result['cate_info']->getRow();
            $responseData['data']['type_info'] = $result['type_info']->getRow();
            $responseData['data']['agency_info'] = $result['agency_info']->getRow();
        }

        //返回
        echo PaymentApi::instance()->getGateway()->response($responseData);
        return;
    }

}
