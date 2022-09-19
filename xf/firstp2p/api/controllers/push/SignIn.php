<?php
/**
 * 用户设备注册
 */
namespace api\controllers\push;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\RequestPushSignIn;

class SignIn extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            //客户端版本(3.0)
            'appVersion' => array('filter' => 'required', 'message' => 'appVersion不能为空'),
            //百度UserId
            'baiduUserId' => array('filter' => 'required', 'message' => 'baiduUserId不能为空'),
            //百度ChannelId
            'baiduChannelId' => array('filter' => 'required', 'message' => 'baiduChannelId不能为空'),
            //系统类型(IOS/Android)
            'osType' => array('filter' => 'required', 'message' => 'osType不能为空'),
            //系统版本(8.1.3)
            'osVersion' => array('filter' => 'required', 'message' => 'osVersion不能为空'),
            //设备型号(iPad2/iPhone6/Xiaomi4)
            'model' => array('filter' => 'required', 'message' => 'model不能为空'),
            //Apple推送Token
            'apnsToken' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $user = $this->getUserByToken();
        if (empty($user)) {
            return $this->setErr('ERR_GET_USER_FAIL');
        }

        $userId = intval($user['id']);
        $osType = $data['osType'] === 'Android' ? 2 : 1;
        $apnsToken = empty($data['apnsToken']) ? '' : $data['apnsToken'];

        $request = new RequestPushSignIn();
        $request->setAppId(1);
        $request->setAppUserId($userId);
        $request->setAppVersion($data['appVersion']);
        $request->setBaiduUserId($data['baiduUserId']);
        $request->setBaiduChannelId($data['baiduChannelId']);
        $request->setOsType($osType);
        $request->setOsVersion($data['osVersion']);
        $request->setModel($data['model']);
        $request->setApnsToken($apnsToken);

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpPush',
            'method' => 'signIn',
            'args' => $request
        ));

        if (!$response->result) {
            return $this->setErr('ERR_SYSTEM');
        }
        return $this->json_data = array();
    }

}
