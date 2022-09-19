<?php
/**
 * 会员经验详情
* @author liguizhi <liguizhi@ucfgroup.com>
*/
namespace api\controllers\vip;
use api\controllers\AppBaseAction;
use libs\web\Form;

class VipPoint extends AppBaseAction {


    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
                "token" => array("filter" => "required", "message" => "token is required"),
        );

        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
        return true;
    }
    public function invoke() {
        $user = $this->getUserByToken();
        if (empty($user)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if (!$this->rpc->local("VipService\isShowVip", array($user['id']), "vip")) {
            return false;
        }

        $userPointInfo = $this->rpc->local("VipService\getVipPoint", array($user['id']), "vip");
        $this->json_data = $userPointInfo;
    }
}

