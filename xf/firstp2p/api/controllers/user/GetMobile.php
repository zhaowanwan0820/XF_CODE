<?php
/**
 * 通过token获取用户手机号
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;

class GetMobile extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter'=>'required', 'message'=> 'ERR_AUTH_FAIL'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        $vipInfo = (new \core\service\vip\VipService())->getVipInfo(intval($loginUser['id']));
        $isWhite = (new \core\service\GoldService())->isWhite($loginUser['id']);
        $this->json_data = array(
            "userId" => $loginUser['id'],
            "mobile" => $loginUser['mobile'],
            "vipLevel" => intval($vipInfo['service_grade']),
            "isWhite" => intval($isWhite),
        );
        return true;
    }
}
