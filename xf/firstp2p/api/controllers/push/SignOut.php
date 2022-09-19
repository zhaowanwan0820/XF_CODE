<?php
/**
 * 用户设备登出
 */
namespace api\controllers\push;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use NCFGroup\Protos\Ptp\RequestPushSignOut;

class SignOut extends AppBaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            //百度ChannelId
            'baiduChannelId' => array('filter' => 'required', 'message' => 'baiduChannelId不能为空'),
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

        $request = new RequestPushSignOut();
        $request->setAppId(1);
        $request->setAppUserId($userId);
        $request->setBaiduChannelId($data['baiduChannelId']);

        $response = $GLOBALS['rpc']->callByObject(array(
            'service' => 'NCFGroup\Ptp\services\PtpPush',
            'method' => 'signOut',
            'args' => $request
        ));

        if ($response->result === 0) {
            return $this->setErr('ERR_SYSTEM', '没有设备签出');
        }
        return $this->json_data = array();
    }

}
