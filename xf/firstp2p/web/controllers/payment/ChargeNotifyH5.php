<?php
/**
 * firstp2p网站充值回调接口
 * @author 全恒壮 <quanhengzhuang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\PaymentGatewayApi;
use libs\utils\Logger;

class ChargeNotifyH5 extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("ChargeNotifyH5Callback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        //签名验证
        if (!PaymentGatewayApi::instance()->verify($_POST)) {
            PaymentApi::log('SignatureFailed. params:'.json_encode($params), Logger::ERR);
            \libs\utils\Alarm::push('h5', '充值回调签名错误', 'params:'.json_encode($params));
            echo PaymentGatewayApi::response(array(
                'errno' => '2',
                'error' => 'signature failed',
            ));
            return;
        }

        $params = array();
        $params['merchantId'] = isset($_POST['merchantId']) ? trim($_POST['merchantId']) : '';
        $params['curType'] = isset($_POST['curType']) ? trim($_POST['curType']) : '';
        $params['userId'] = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $params['amount'] = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
        $params['outOrderId'] = isset($_POST['outOrderId']) ? trim($_POST['outOrderId']) : '';
        $params['tranTime'] = isset($_POST['tranTime']) ? trim($_POST['tranTime']) : '';
        $params['orderStatus'] = isset($_POST['orderStatus']) ? trim($_POST['orderStatus']) : '';

        //必填参数验证
        foreach ($params as $key => $value) {
            if ($value === '' || $value === 0) {
                echo PaymentGatewayApi::response(array(
                    'errno' => '1',
                    'error' => "param $key is empty",
                ));
                PaymentApi::log("param $key is empty", Logger::ERR);
                \libs\utils\Alarm::push('h5', '充值回调参数错误', "param $key is empty. params:".json_encode($params));
                return;
            }
        }

        //逻辑处理
        $result = $this->rpc->local('PaymentService\chargeResultCallback', array($params['outOrderId'], $params['orderStatus'], $params['amount']));

        echo PaymentGatewayApi::response(array(
            'errno' => intval($result['respCode']),
            'error' => $result['respMsg'],
        ));
        return;
    }
}