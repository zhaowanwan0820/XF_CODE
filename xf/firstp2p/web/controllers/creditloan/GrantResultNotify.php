<?php
/**
 * 网信速贷提现申请回调接口
 * @author 王群强 <wangqunqiang@ucfgroup.com>
 */
namespace web\controllers\creditloan;

use web\controllers\BaseAction;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use NCFGroup\Common\Library\ApiJfpayLib;
use NCFGroup\Common\Library\AesLib;
use NCFGroup\Protos\Creditloan\RequestCommon;
use NCFGroup\Protos\Creditloan\Enum\CommonEnum as CreditEnum;

class GrantResultNotify extends BaseAction
{

    public function init()
    {
    }

    public function invoke()
    {
        //参数获取
        $data = file_get_contents("php://input");
        $sign = $_SERVER['HTTP_SIGN'];
        PaymentApi::log("GrantResultNotifyCallback Request. method:{$_SERVER['REQUEST_METHOD']}, params:".json_encode($data).' sign:'.$sign);
        $loanSrv = new \core\service\speedLoan\LoanService();
        $token = $loanSrv->getToken();
        $response = [];
        try {
            if (empty($data) || empty($sign)) {
                throw new \Exception('签名或者数据为空');
                return ;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            {
                PaymentApi::log("GrantResultNotifyCallback redirect.");
                return app_redirect('/');
            }

            //签名验证
            $verifyResult = ApiJfpayLib::verify($data, $sign, $token);
            if (!$verifyResult)
            {
                PaymentApi::log("Signature failed. get:$sign", Logger::ERR);
                \libs\utils\Alarm::push('payment', 'GrantResultNotify', "Signature failed. get:$sign, params:".json_encode($data));
                throw new \Exception('验签失败');
            }
            $requestData = json_decode(base64_decode($data), true);
            $data = $loanSrv->decodeData($requestData);
            PaymentApi::log("GrantResultNotifyCallback Request. params:".json_encode($data, JSON_UNESCAPED_UNICODE));
            if (empty($data['respData'])) {
                throw new \Exception('解密失败');
                return ;
            }
            $respData = $data['respData'];

            // 远程Rpc调用
            $loanSrv = new \core\service\speedLoan\LoanService();
            $result = $loanSrv->grantResultNotify($respData);
            if (!isset($result['code']) || $result['code'] == CreditEnum::RESPONSE_FAILURE) {
                throw new \Exception('处理提现结果通知失败');
            }
            $response = ['code' => '0000', 'msg' => '成功'];
        } catch (\Exception $e) {
            $response = ['code' => '0001', 'msg' => $e->getMessage()];
        }
        return $loanSrv->response($response);
    }

}
