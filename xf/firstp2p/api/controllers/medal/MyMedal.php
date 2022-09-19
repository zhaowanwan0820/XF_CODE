<?php
/**
 * 我的勋章接口
 * @author zhaohui <zhaohui3@ucfgroup.com>
 */
namespace api\controllers\medal;
use api\controllers\AppBaseAction;
use libs\web\Form;
use api\conf\Error;
use NCFGroup\Protos\Medal\RequestMedalUser;

class MyMedal extends AppBaseAction {
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
        $request = new RequestMedalUser();
        $request->setUserId(intval($user['id']));
        $res = $this->rpc->local("MedalService\getUserMedalList",array($request));
        $this->json_data = $res;
    }
}
