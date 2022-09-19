<?php
/**
 * firstp2p用户基本信息查询接口 
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
class GetUserInfo extends BaseAction
{

    //MD5 'xfjr'
    CONST PARTNER_ID = '6e199e0893798f90db7c016ad96462f7';

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        PaymentApi::log("GetUserInfo Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($_POST));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        {
            PaymentApi::log("GetUserInfo redirect.");
            return app_redirect('/');
        }

        $params = array();
        $params['partnerId'] = isset($_POST['partnerId']) ? trim($_POST['partnerId']) : '';

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
                \libs\utils\Alarm::push('payment', 'GetUserInfo', "param $key is empty. params:".json_encode($params));
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
            \libs\utils\Alarm::push('payment', 'GetUserInfo', "PartnerId error. partnerId:{$params['partnerId']}, params:".json_encode($params));
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
            \libs\utils\Alarm::push('payment', 'GetUserInfo', "Signature failed. get:$signature, local:$signatureLocal, params:".json_encode($params));
            return;
        }
        // 业务参数
        $requestParams = [];
        $userId = isset($_POST['userId']) ? intval($_POST['userId']) : '';
        $mobile = isset($_POST['mobile']) ? addslashes(trim($_POST['mobile'])) : '';
        if (!empty($userId))
        {
            $condition = !empty($userId)?" id = '{$userId}'":'';
        }
        else if (!empty($mobile))
        {
            $condition = !empty($mobile) ? " mobile = '{$mobile}'":'';
        }
        else {
            echo PaymentApi::instance()->getGateway()->response(array(
                'respCode' => '01',
                'respMsg' => '查询条件不能同时为空',
            ));

        }
        $requestParams['condition'] = $condition;
        $requestParams['fileds'] = 'id,user_name,real_name,mobile,money,lock_money,idno';


        //逻辑处理
        $result = $this->rpc->local('UserService\getUserByCondition', $requestParams);
        // 查询银行卡数据
        if (!empty($result['id']))
        {
            $bankInfo = $this->rpc->local('UserBankcardService\getBankcard', ['user_id' => $result['id']]);
            if (!empty($bankInfo))
            {
                $result['bankcard'] = $bankInfo['bankcard'];
            }
        }
        $responseData = ['respCode' => '00', 'respMsg' => '', 'data' => []];
        if (empty($result))
        {
            $responseData['respCode'] = '01';
            $responseData['respMsg'] = '用户不存在';

        }
        else
        {

            $responseData['data'] = [
                'userId' => $result['id'],
                'money' => $result['money'],
                'lockMoney' => $result['lock_money'],
                'realName' => $result['real_name']?:'-',
                'idNo' => $result['idno']?:'-',
                'bankcardNo' => $result['bankcard']?:'-',
                'mobile' => $result['mobile']?:'-',
            ];
        }

        //返回
        echo PaymentApi::instance()->getGateway()->response($responseData);
        return;
    }

}
