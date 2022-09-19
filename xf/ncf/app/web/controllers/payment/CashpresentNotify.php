<?php
/**
 * 现金代发回调接口
 * @author 全恒壮 <quanhengzhuang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\PaymentCashApi;
use libs\utils\Logger;

class CashpresentNotify extends BaseAction
{

    public function invoke()
    {
        PaymentApi::log("CashpresentNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        //签名验证
        if (!PaymentCashApi::instance()->verify($_POST)) {
            PaymentApi::log("Signature failed.", Logger::ERR);
            \libs\utils\Alarm::push('paymentcashapi', 'CashpresentNotify', "Signature failed. params:".json_encode($params));
            echo PaymentCashApi::instance()->response('ERROR:签名错误');
            return;
        }

        //参数获取
        $params = array();
        $params['merchantId'] = isset($_POST['merchantId']) ? trim($_POST['merchantId']) : '';
        $params['merchantNo'] = isset($_POST['merchantNo']) ? trim($_POST['merchantNo']) : '';
        $params['amount'] = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
        $params['transCur'] = isset($_POST['transCur']) ? trim($_POST['transCur']) : '';
        $params['tradeNo'] = isset($_POST['tradeNo']) ? trim($_POST['tradeNo']) : '';
        $params['tradeTime'] = isset($_POST['tradeTime']) ? trim($_POST['tradeTime']) : '';
        $params['status'] = isset($_POST['status']) ? trim($_POST['status']) : '';
        $params['memo'] = isset($_POST['memo']) ? trim($_POST['memo']) : '';
        //$params['resCode'] = isset($_POST['resCode']) ? trim($_POST['resCode']) : '';
        //$params['resMessage'] = isset($_POST['resMessage']) ? trim($_POST['resMessage']) : '';

        //必填参数验证
        foreach ($params as $key => $value) {
            if ($value === '' || $value === 0) {
                PaymentApi::log("param $key is empty", Logger::ERR);
                \libs\utils\Alarm::push('paymentcashapi', 'CashpresentNotify', "param $key is empty. params:".json_encode($params));
                echo PaymentCashApi::instance()->response("ERROR:{$key}不能为空");
                return;
            }
        }

        //逻辑处理
        try {
            //接口反查
            $orderInfo = PaymentCashApi::instance()->request('query', array('merchantNo' => $params['merchantNo']));
            if (!$this->rpc->local('CashpresentService\processApiResult', array($orderInfo['merchantNo'], $orderInfo['status'], $orderInfo['memo']))) {
                echo PaymentCashApi::instance()->response('ERROR:逻辑调用失败');
                return;
            }

        } catch (\Exception $e) {
            echo PaymentCashApi::instance()->response('ERROR:'.$e->getMessage());
            return;
        }

        echo PaymentCashApi::instance()->response('SUCCESS');
        return;
    }

}
