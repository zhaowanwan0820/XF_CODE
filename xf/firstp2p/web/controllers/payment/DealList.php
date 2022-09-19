<?php
/**
 * firstp2p获取标的列表接口
 * @author weiwei12 <weiwei12@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;

class DealList extends BaseAction
{

    //MD5 'xfjr'
    CONST PARTNER_ID = '6e199e0893798f90db7c016ad96462f7';

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("DealList Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("DealList redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['partnerId'] = isset($_POST['partnerId']) ? trim($_POST['partnerId']) : '';
        $params['page'] = isset($_POST['page']) ? trim($_POST['page']) : 1; //默认第一页
        $params['page_size'] = isset($_POST['page_size']) ? trim($_POST['page_size']) : 20; //默认每页20条记录

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
                \libs\utils\Alarm::push('payment', 'DealList', "param $key is empty. params:".json_encode($params));
                return;
            }
        }

        //非必填参数


        //PartnerId校验
        if ($params['partnerId'] !== self::PARTNER_ID)
        {
            echo PaymentApi::instance()->getGateway()->response(array(
                'respCode' => '01',
                'respMsg' => 'PartnerId is invalid',
            ));
            PaymentApi::log("PartnerId error. partnerId:{$params['partnerId']}", Logger::ERR);
            \libs\utils\Alarm::push('payment', 'DealList', "PartnerId error. partnerId:{$params['partnerId']}, params:".json_encode($params));
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
            \libs\utils\Alarm::push('payment', 'DealList', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }

        //逻辑处理
        //public function getList($cate, $type, $field, $page, $page_size=0, $is_all_site=false, $site_id=0, $show_crowd_specific = true, $dealTypes = '', $dealTagName = '',$needCount=true) {
        $result = $this->rpc->local('DealService\getList', array(0, 1, 0, $params['page'], $params['page_size'], true, 0, true, 0));

        $response = array(
            'respCode' => '00',
            'respMsg' => '成功',
            'count' => $result['count'],
            'list' => $result['list']['list'],
        );

        //返回
        echo PaymentApi::instance()->getGateway()->response($response);
        return;
    }

}
