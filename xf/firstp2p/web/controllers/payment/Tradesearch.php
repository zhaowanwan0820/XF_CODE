<?php
/**
 * firstp2p网站对账查询接口
 * @author 全恒壮 <quanhengzhuang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class TradeSearch extends BaseAction
{

    //MD5 'xfjr'
    CONST PARTNER_ID = '6e199e0893798f90db7c016ad96462f7';

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("Tradesearch Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("Tradesearch redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['partnerId'] = isset($_POST['partnerId']) ? trim($_POST['partnerId']) : '';
        $params['stamp'] = isset($_POST['stamp']) ? intval($_POST['stamp']) : 0;
        $params['beginTime'] = isset($_POST['beginTime']) ? intval($_POST['beginTime']) : 0;
        $params['endTime'] = isset($_POST['endTime']) ? intval($_POST['endTime']) : 0;
        $params['tranType'] = isset($_POST['tranType']) ? intval($_POST['tranType']) : 0;
        
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
                \libs\utils\Alarm::push('payment', 'Tradesearch', "param $key is empty. params:".json_encode($params));
                return;
            }
        }

        //非必填参数
        isset($_POST['ptype']) && $params['ptype'] = intval($_POST['ptype']);
        isset($_POST['userId']) && $params['userId'] = intval($_POST['userId']);
        isset($_POST['pageNo']) && $params['pageNo'] = intval($_POST['pageNo']);
        isset($_POST['pageSize']) && $params['pageSize'] = intval($_POST['pageSize']);

        //PartnerId校验
        if ($params['partnerId'] !== '6e199e0893798f90db7c016ad96462f7')
        {
            echo PaymentApi::instance()->getGateway()->response(array(
                'respCode' => '01',
                'respMsg' => 'PartnerId is invalid',
            ));
            PaymentApi::log("PartnerId error. partnerId:{$params['partnerId']}", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'Tradesearch', "PartnerId error. partnerId:{$params['partnerId']}, params:".json_encode($params));
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
            \libs\utils\Alarm::push('payment', 'Tradesearch', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }

        //逻辑处理
        $result = $this->rpc->local('PaymentService\searchTrades', array($params, $params['pageNo']));

        //返回
        echo PaymentApi::instance()->getGateway()->response($result);
        return;
    }

}
